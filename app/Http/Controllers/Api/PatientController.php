<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
use App\Services\PmeIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patients,
        private PmeIntegrationService $integration,
    ) {}

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patients->create($request->validated());
        \App\Services\ActivityLogger::log('Created Applicant Account', $patient->patient_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data saved successfully!',
            'patient_no' => $patient->patient_id,
        ]);
    }

    public function show(int $patientNo): JsonResponse
    {
        $patient = $this->patients->findByPatientNo($patientNo);

        if (! $patient) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $patient->load(['applications.examinations.assessments', 'profile', 'serviceRecord.rank', 'serviceRecord.unit.branch', 'race']);

        return response()->json($patient);
    }

    public function pending(): JsonResponse
    {
        return response()->json($this->patients->listPending());
    }

    public function nextApplicantNo(): JsonResponse
    {
        return response()->json([
            'nextApplicantNo' => $this->patients->nextApplicantNo(),
        ]);
    }

    public function destroy(int $patientNo): JsonResponse|\Illuminate\Http\Response
    {
        if (!in_array(session('staff.role'), ['RECRUITER'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $this->patients->delete($patientNo)) {
            return response('error', 500);
        }

        return response('success');
    }

        public function activateAccount(int $patientNo): JsonResponse
    {
        if (!in_array(session('staff.role'), ['REGISTRAR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::query()->find($patientNo);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $patient->is_activated = 1;
        $patient->save();

        return response()->json(['success' => true]);
    }

    public function reactivate(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['RECRUITER', 'REGISTRAR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->reactivate($finalPatientNo);

        return response()->json(['success' => $success]);
    }

    public function markDone(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['REGISTRAR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        \App\Services\ActivityLogger::log('Marked Applicant as Done', $finalPatientNo);
        $success = $this->patients->markDone($finalPatientNo);

        if ($success) {
            $patient = $this->patients->findByPatientNo($finalPatientNo);
            if ($patient) {
                $this->integration->pushFinalResult($patient);
            }
        }

        return response()->json(['success' => $success]);
    }

    public function markCleared(Request $request, ?int $patientNo = null): JsonResponse
    {
        // Only DOCTOR (chief doctor) can mark cleared
        if (!in_array(session('staff.role'), ['DOCTOR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->markCleared($finalPatientNo);
        if ($success) \App\Services\ActivityLogger::log('Marked Applicant Cleared', $finalPatientNo);

        return response()->json(['success' => $success]);
    }

    public function readings(int $patientNo): JsonResponse
    {
        $readings = $this->patients->readings($patientNo);

        if ($readings === null) {
            return response()->json(['success' => false, 'message' => 'No data found']);
        }

        return response()->json(['success' => true] + $readings);
    }

    public function record(Request $request): JsonResponse
    {
        $request->validate([
            'firstName' => ['required', 'string'],
            'middleName' => ['nullable', 'string'],
            'lastName' => ['required', 'string'],
            'birthdate' => ['required', 'string'],
        ]);

        $patient = $this->patients->findByIdentity(
            $request->input('firstName'),
            $request->input('middleName', ''),
            $request->input('lastName'),
            $request->input('birthdate'),
        );

        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Applicant not found']);
        }
        
        // Block Station Portals from seeing patients that are cleared or completed
        if (in_array($patient->overall_stat, ['DONE', 'COMPLETED'])) {
            return response()->json(['success' => false, 'message' => 'Applicant already cleared or completed.']);
        }

        $patient->load(['applications.examinations.assessments', 'profile', 'serviceRecord.rank', 'serviceRecord.unit.branch', 'race']);

        return response()->json(['success' => true, 'data' => $patient]);
    }

    public function activeRecords(): JsonResponse
    {
        return response()->json($this->patients->listActiveRecords());
    }

    public function processed(): JsonResponse
    {
        return response()->json($this->patients->listProcessed());
    }

    public function updateApprovingAuthority(Request $request, $patientNo): JsonResponse
    {
        $request->validate([
            'approving_authority' => 'nullable|string|max:255',
            'reviewing_officer' => 'nullable|string|max:255',
        ]);
        
        $patient = Patient::where('patient_id', $patientNo)->firstOrFail();
        $form = $patient->applications->first()?->latestExamination();
        
        if ($form) {
            $data = $form->form_data ?? [];
            if ($request->has('approving_authority')) {
                $data['approving_authority_name'] = $request->approving_authority;
            }
            if ($request->has('reviewing_officer')) {
                $data['reviewing_officer_name'] = $request->reviewing_officer;
                $data['reviewing_officer_enabled'] = $request->reviewing_officer ? '1' : '0';
            }
            $form->update(['form_data' => $data]);
        }
        
        return response()->json(['message' => 'Approving authority assigned successfully']);
    }

    public function updateDoctorNotes(Request $request): JsonResponse
    {
        $request->validate([
            'patient_no' => ['required', 'integer'],
            'diagnosis' => ['required', 'string'],
            'remarks' => ['required', 'string'],
            'approving_authority' => ['nullable', 'string'],
        ]);

        $this->patients->updateDoctorNotes(
            (int) $request->input('patient_no'),
            $request->input('diagnosis'),
            $request->input('remarks'),
            $request->input('approving_authority')
        );

        return response()->json('Record updated successfully.', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function validateApplicant(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        if (! is_array($data) || $data === []) {
            return response()->json(['error' => 'No data provided or invalid JSON'], 400);
        }

        $query = Patient::query()
            ->whereRaw('TRIM(rank) = ?', [trim($data['rank'] ?? '')])
            ->whereRaw('TRIM(serialNo) = ?', [trim($data['serialNo'] ?? '')])
            ->whereRaw('TRIM(branch) = ?', [trim($data['branch'] ?? '')])
            ->whereRaw("COALESCE(TRIM(first_name), '') = ?", [trim($data['firstName'] ?? '')])
            ->whereRaw("COALESCE(TRIM(middle_name), '') = ?", [trim($data['middleName'] ?? '')])
            ->whereRaw("COALESCE(TRIM(last_name), '') = ?", [trim($data['lastName'] ?? '')])
            ->whereRaw("COALESCE(TRIM(birthdate), '') = ?", [trim($data['birthdate'] ?? '')])
            ->whereRaw('TRIM(age) = ?', [trim((string) ($data['age'] ?? ''))])
            ->whereRaw('TRIM(sex) = ?', [trim($data['sex'] ?? '')])
            ->whereRaw('TRIM(raceSelect) = ?', [trim($data['raceSelect'] ?? $data['race'] ?? '')])
            ->whereRaw('TRIM(placeOfBirth) = ?', [trim($data['placeOfBirth'] ?? $data['placeOfBirth'] ?? '')])
            ->whereRaw('TRIM(placeOfExamination) = ?', [trim($data['placeOfExamination'] ?? $data['examPlace'] ?? '')])
            ->whereRaw('TRIM(dateOfExamination) = ?', [trim($data['dateOfExamination'] ?? $data['examDate'] ?? '')])
            ->whereRaw('TRIM(purpose) = ?', [trim($data['purpose'] ?? '')])
            ->whereRaw('TRIM(batch) = ?', [trim($data['batch'] ?? '')])
            ->whereRaw('TRIM(otherData) = ?', [trim($data['otherData'] ?? '')]);

        $patient = $query->with(['applications.examinations.assessments'])->first();

        if ($patient) {
            return response()->json(['valid' => true, 'applicant' => $patient]);
        }

        return response()->json(['valid' => false]);
    }

    public function validateRecord(Request $request): JsonResponse
    {
        $patient = null;
        if ($request->filled("patient_no")) {
            $patient = \App\Models\Patient::where("patient_id", $request->input("patient_no"))->first();
        }

        if (! $patient) {
            $patient = \App\Models\Patient::query()
                ->whereRaw("COALESCE(TRIM(first_name), '') = ?", [trim($request->input("firstName", ""))])
                ->whereRaw("COALESCE(TRIM(middle_name), '') = ?", [trim($request->input("middleName", ""))])
                ->whereRaw("COALESCE(TRIM(last_name), '') = ?", [trim($request->input("lastName", ""))])
                ->whereRaw("COALESCE(TRIM(birthdate), '') = ?", [trim($request->input("birthdate", ""))])
                ->first();
        }

        return response()->json(["exists" => $patient !== null, "patient_no" => $patient ? $patient->patient_id : null]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $patient = null;
        if ($request->filled('patient_no')) {
            $patient = Patient::where('patient_id', $request->input('patient_no'))->first();
        }

        if (! $patient) {
            $patient = Patient::query()
                ->whereRaw("COALESCE(TRIM(first_name), '') = ?", [trim($request->input('firstName', ''))])
                ->whereRaw("COALESCE(TRIM(middle_name), '') = ?", [trim($request->input('middleName', ''))])
                ->whereRaw("COALESCE(TRIM(last_name), '') = ?", [trim($request->input('lastName', ''))])
                ->whereRaw("COALESCE(TRIM(birthdate), '') = ?", [trim($request->input('birthdate', ''))])
                ->first();
        }

        if (! $patient) {
            return response()->json(['success' => false]);
        }

        $patient->status = 'active';
        $patient->save();

        return response()->json(['success' => true]);
    }

    public function approvePatient(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['DOCTOR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->approvePatient($finalPatientNo);
        if ($success) \App\Services\ActivityLogger::log('Marked Applicant Cleared', $finalPatientNo);
        return response()->json(['success' => $success]);
    }

    public function disqualifyPatient(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['DOCTOR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->disqualifyPatient($finalPatientNo);
        if ($success) \App\Services\ActivityLogger::log('Marked Applicant Disqualified', $finalPatientNo);
        return response()->json(['success' => $success]);
    }

    public function returnPatient(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['DOCTOR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->returnPatient($finalPatientNo);
        if ($success) \App\Services\ActivityLogger::log('Returned Applicant', $finalPatientNo);
        return response()->json(['success' => $success]);
    }

    public function registrarReturnPatient(Request $request, ?int $patientNo = null): JsonResponse
    {
        if (!in_array(session('staff.role'), ['REGISTRAR'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $finalPatientNo = $patientNo ?? (int) $request->input('patient_no');
        $success = $this->patients->registrarReturnPatient($finalPatientNo);
        if ($success) \App\Services\ActivityLogger::log('Returned Applicant from Done', $finalPatientNo);
        return response()->json(['success' => $success]);
    }

    public function cleared(): JsonResponse
    {
        return response()->json($this->patients->listCleared());
    }

    public function done(): JsonResponse
    {
        return response()->json($this->patients->listDone());
    }

    public function ongoing(): JsonResponse
    {
        return response()->json($this->patients->listOngoing());
    }

    public function disqualified(): JsonResponse
    {
        return response()->json($this->patients->listDisqualified());
    }
}






