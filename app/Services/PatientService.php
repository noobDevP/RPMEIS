<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientProfile;
use App\Models\ServiceRecord;
use App\Models\Application;
use App\Models\MedicalExamination;
use App\Models\Rank;
use App\Models\Branch;
use App\Models\Unit;
use App\Models\Race;
use App\Models\ExamCenter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientService
{
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            // Lookup inserts
            $rankId = !empty($data['rank']) ? Rank::firstOrCreate(['rank_name' => $data['rank']])->rank_id : null;
            $branchId = !empty($data['branch']) ? Branch::firstOrCreate(['branch_name' => $data['branch']])->branch_id : null;
            $unitId = !empty($data['unitorg']) ? Unit::firstOrCreate(['unit_name' => $data['unitorg'], 'branch_id' => $branchId])->unit_id : null;
            $raceId = !empty($data['raceSelect']) ? Race::firstOrCreate(['race_name' => $data['raceSelect']])->race_id : null;
            $centerId = !empty($data['placeOfExamination']) ? ExamCenter::firstOrCreate(['center_name' => $data['placeOfExamination']])->center_id : null;

            $sex = null;
            if (!empty($data['sex'])) {
                if (in_array(strtolower($data['sex']), ['male', 'm'])) $sex = 'Male';
                elseif (in_array(strtolower($data['sex']), ['female', 'f'])) $sex = 'Female';
            }

            $birthdate = null;
            if (!empty($data['birthdate'])) {
                try {
                    $birthdate = Carbon::parse($data['birthdate'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            // 1. Patient
            $patient = Patient::create([
                'first_name' => $data['firstName'],
                'middle_name' => $data['middleName'] ?? null,
                'last_name' => $data['lastName'],
                'birthdate' => $birthdate,
                'sex' => $sex,
                'race_id' => $raceId,
                'place_of_birth' => $data['placeOfBirth'] ?? null,
                'is_activated' => 0,
            ]);

            // 2. Patient Profile
            PatientProfile::create([
                'patient_id' => $patient->patient_id,
                'current_address' => $data['currentAddress'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
            ]);

            // 3. Service Record
            ServiceRecord::create([
                'patient_id' => $patient->patient_id,
                'serial_no' => $data['serialNo'] ?? null,
                'rank_id' => $rankId,
                'unit_id' => $unitId,
                'is_active_duty' => 1,
            ]);

            // 4. Application
            $application = Application::create([
                'patient_id' => $patient->patient_id,
                'applicant_no' => $data['applicant_no'] ?? null,
                'app_code' => $data['appcode'] ?? null,
                'batch' => $data['batch'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'status' => 'active',
                'applied_at' => now(),
            ]);

            // 5. Medical Examination
            $examDate = null;
            if (!empty($data['dateOfExamination'])) {
                try {
                    $examDate = Carbon::parse($data['dateOfExamination'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            MedicalExamination::create([
                'application_id' => $application->application_id,
                'center_id' => $centerId,
                'exam_date' => $examDate,
                'overall_stat' => 'NOT DONE',
                'form_data' => !empty($data['otherData']) ? json_decode($data['otherData'], true) : null,
            ]);

            return $patient->refresh();
        });
    }

    public function findByPatientNo(int $patientNo): ?Patient
    {
        return Patient::with(['applications.examinations', 'profile', 'serviceRecord'])->find($patientNo);
    }

    public function findByAppCode(?string $appcode): ?Patient
    {
        if (!$appcode) return null;
        $app = Application::where('app_code', $appcode)->first();
        return $app ? $app->patient : null;
    }

    public function listPending(): Collection
    {
        return Patient::whereHas('applications.examinations', function ($q) {
            $q->where('overall_stat', 'NOT DONE');
        })->get();
    }

    public function generateApplicantNo(): string
    {
        $year = date('Y');
        $lastApp = Application::where('applicant_no', 'LIKE', "{$year}-%")->latest('applicant_no')->first();
        if (! $lastApp) return "{$year}-000001";
        
        $parts = explode('-', $lastApp->applicant_no);
        $number = (int) end($parts);
        
        return sprintf("%s-%06d", $year, $number + 1);
    }

    public function reactivate(int $patientNo): bool
    {
        return (bool) Application::where('patient_id', $patientNo)->update(['status' => 'active']);
    }

    public function markDone(int $patientNo): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        return (bool) MedicalExamination::where('application_id', $app->application_id)->update(['overall_stat' => 'COMPLETED']);
    }

    public function markCleared(int $patientNo): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        return (bool) MedicalExamination::where('application_id', $app->application_id)->update([
            'chief_doctor_approval' => 'PASSED',
            'overall_stat' => 'DONE',
        ]);
    }

    public function approvePatient(int $patientNo): bool
    {
        return $this->markCleared($patientNo);
    }

    public function disqualifyPatient(int $patientNo): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        return (bool) MedicalExamination::where('application_id', $app->application_id)->update([
            'chief_doctor_approval' => 'FAILED',
            'overall_stat' => 'DONE',
        ]);
    }

    public function returnPatient(int $patientNo): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        return (bool) MedicalExamination::where('application_id', $app->application_id)->update(['overall_stat' => 'NOT DONE', 'chief_doctor_approval' => null]);
    }

    public function registrarReturnPatient(int $patientNo): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        return (bool) MedicalExamination::where('application_id', $app->application_id)->update(['overall_stat' => 'DONE']);
    }

    public function delete(int $patientNo): bool
    {
        return (bool) Application::where('patient_id', $patientNo)->update(['status' => 'inactive']);
    }

    public function listActiveRecords(): Collection
    {
        return Patient::whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))->get();
    }
    public function listProcessed(): Collection
    {
        return Patient::whereHas('applications.examinations', fn($q) => $q->whereIn('overall_stat', ['DONE', 'COMPLETED']))->get();
    }
    public function listCleared(): Collection
    {
        return Patient::whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'PASSED'))->get();
    }
    public function listDone(): Collection
    {
        return Patient::whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'COMPLETED'))->get();
    }
    public function listOngoing(): Collection
    {
        return Patient::whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'NOT DONE'))->get();
    }
    public function listDisqualified(): Collection
    {
        return Patient::whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED'))->get();
    }

    public function nextApplicantNo(): string
    {
        return $this->generateApplicantNo();
    }
    public function findByIdentity(string $firstName, ?string $middleName, string $lastName, ?string $birthdate = null): ?Patient
    {
        $q = Patient::where('first_name', $firstName)->where('last_name', $lastName);
        if ($middleName) $q->where('middle_name', $middleName);
        if ($birthdate) $q->where('birthdate', Carbon::parse($birthdate)->format('Y-m-d'));
        return $q->first();
    }
    public function readings(int $patientNo): ?array
    {
        $patient = $this->findByPatientNo($patientNo);
        $exam = $patient?->applications->first()?->latestExamination();
        if (!$exam || !is_array($exam->form_data)) return null;
        return [
            'height' => $exam->form_data['height'] ?? '',
            'weight' => $exam->form_data['weight'] ?? '',
            'bp' => $exam->form_data['blood_pressure'] ?? '',
            'hr' => $exam->form_data['heart_rate'] ?? '',
        ];
    }
    public function updateDoctorNotes(int $patientNo, string $diagnosis, string $remarks = '', ?string $approvingAuthority = null): bool
    {
        $app = Application::where('patient_id', $patientNo)->latest('application_id')->first();
        if (!$app) return false;
        $exam = MedicalExamination::where('application_id', $app->application_id)->first();
        if (!$exam) return false;
        $data = is_array($exam->form_data) ? $exam->form_data : [];
        $data['doctors_diagnosis'] = $diagnosis;
        $data['doctors_remarks'] = $remarks;
        if ($approvingAuthority) {
            $data['approving_authority_name'] = $approvingAuthority;
        }
        return (bool) $exam->update(['form_data' => $data]);
    }
}
