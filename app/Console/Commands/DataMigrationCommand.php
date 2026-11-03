<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataMigrationCommand extends Command
{
    protected $signature = 'db:migrate-legacy-data';
    protected $description = 'Migrate data from legacy monolithic tables to the new normalized schema.';

    public function handle()
    {
        $this->info('Starting legacy data migration...');

        DB::transaction(function () {
            // Seed Stations table
            $stationsList = [
                ['station_key' => 'gpe', 'station_name' => 'General Physical Examination', 'sort_order' => 1],
                ['station_key' => 'dental', 'station_name' => 'Dental', 'sort_order' => 2],
                ['station_key' => 'optha_eent', 'station_name' => 'Optha / EENT', 'sort_order' => 3],
                ['station_key' => 'ecg_xray', 'station_name' => 'ECG / X-Ray', 'sort_order' => 4],
                ['station_key' => 'lab_drugtest', 'station_name' => 'Laboratory / Drug Test', 'sort_order' => 5],
                ['station_key' => 'np', 'station_name' => 'Neuro-Psychiatric', 'sort_order' => 6],
            ];

            foreach ($stationsList as $s) {
                DB::table('stations')->insertOrIgnore(array_merge($s, ['created_at' => now(), 'updated_at' => now()]));
            }

            $stationsMap = DB::table('stations')->pluck('station_id', 'station_key');

            // Fetch all legacy patients
            $legacyPatients = DB::table('legacy_patients')->get();
            $this->info("Found {$legacyPatients->count()} legacy patients. Processing...");

            foreach ($legacyPatients as $lp) {
                // Determine lookups first
                $rankId = !empty($lp->rank) ? $this->getOrCreateLookup('ranks', ['rank_name' => $lp->rank], 'rank_id') : null;
                $branchId = !empty($lp->branch) ? $this->getOrCreateLookup('branches', ['branch_name' => $lp->branch], 'branch_id') : null;
                $unitId = !empty($lp->unitorg) ? $this->getOrCreateLookup('units', ['unit_name' => $lp->unitorg, 'branch_id' => $branchId], 'unit_id') : null;
                $raceId = !empty($lp->raceSelect) ? $this->getOrCreateLookup('races', ['race_name' => $lp->raceSelect], 'race_id') : null;
                $centerId = !empty($lp->placeOfExamination) ? $this->getOrCreateLookup('exam_centers', ['center_name' => $lp->placeOfExamination], 'center_id') : null;

                // Core Demographics
                $sex = null;
                if (!empty($lp->sex)) {
                    if (in_array(strtolower($lp->sex), ['male', 'm'])) $sex = 'Male';
                    elseif (in_array(strtolower($lp->sex), ['female', 'f'])) $sex = 'Female';
                }

                $birthdate = null;
                if (!empty($lp->birthdate)) {
                    try { $birthdate = Carbon::parse($lp->birthdate)->format('Y-m-d'); } catch (\Exception $e) {}
                }

                DB::table('patients')->insert([
                    'patient_id' => $lp->patient_no,
                    'first_name' => $lp->firstName,
                    'middle_name' => $lp->middleName,
                    'last_name' => $lp->lastName,
                    'birthdate' => $birthdate,
                    'sex' => $sex,
                    'race_id' => $raceId,
                    'place_of_birth' => $lp->placeOfBirth,
                    'is_activated' => property_exists($lp, 'is_activated') ? $lp->is_activated : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Profiles & Service
                DB::table('patient_profiles')->insert([
                    'patient_id' => $lp->patient_no,
                    'current_address' => $lp->currentAddress,
                    'photo_path' => $lp->photo_path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('service_records')->insert([
                    'patient_id' => $lp->patient_no,
                    'serial_no' => $lp->serialNo,
                    'rank_id' => $rankId,
                    'unit_id' => $unitId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Applications & Examinations
                $appId = DB::table('applications')->insertGetId([
                    'patient_id' => $lp->patient_no,
                    'applicant_no' => $lp->applicant_no,
                    'app_code' => $lp->appcode,
                    'batch' => $lp->batch,
                    'purpose' => $lp->purpose,
                    'status' => $lp->status ?? 'pending',
                    'applied_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $examDate = null;
                if (!empty($lp->dateOfExamination)) {
                    try { $examDate = Carbon::parse($lp->dateOfExamination)->format('Y-m-d'); } catch (\Exception $e) {}
                }

                $legacyForm = DB::table('legacy_medical_forms')->where('patient_no', $lp->patient_no)->first();
                
                $examId = DB::table('medical_examinations')->insertGetId([
                    'application_id' => $appId,
                    'center_id' => $centerId,
                    'exam_date' => $examDate,
                    'overall_stat' => $lp->overall_stat,
                    'form_data' => $legacyForm ? $legacyForm->form_data : clone $lp->otherData,
                    'step' => $legacyForm ? $legacyForm->step : 1,
                    'status' => $legacyForm ? $legacyForm->status : 'draft',
                    'started_by' => $legacyForm ? $legacyForm->started_by : null,
                    'created_at' => $legacyForm ? $legacyForm->created_at : now(),
                    'updated_at' => $legacyForm ? $legacyForm->updated_at : now(),
                ]);

                // Station Clearances & Attachments
                $evals = DB::table('legacy_evaluations')->where('patient_no', $lp->patient_no)->get();
                foreach ($evals as $ev) {
                    $stationId = $stationsMap[$ev->station_key] ?? null;
                    if (!$stationId) continue;

                    // Ensure status is valid ENUM or null
                    $status = strtoupper(trim((string)$ev->approval_status));
                    if (!in_array($status, ['GO', 'NO GO', 'COMPLIANCE', 'PENDING'])) {
                        $status = null;
                    }

                    $clearanceId = DB::table('station_clearances')->insertGetId([
                        'examination_id' => $examId,
                        'station_id' => $stationId,
                        'approval_status' => $status,
                        'diagnosis' => $ev->diagnosis,
                        'remarks' => $ev->remarks,
                        'evaluated_by' => null,
                        'created_at' => $ev->created_at ?? now(),
                        'updated_at' => $ev->updated_at ?? now(),
                    ]);

                    if (!empty($ev->attachment)) {
                        DB::table('clearance_attachments')->insert([
                            'clearance_id' => $clearanceId,
                            'file_path' => $ev->attachment,
                            'created_at' => $ev->updated_at ?? now(),
                            'updated_at' => $ev->updated_at ?? now(),
                        ]);
                    }
                }

                // Sub-assessments
                if ($legacyForm) {
                    $assessments = DB::table('legacy_medical_form_assessments')->where('medical_form_id', $legacyForm->id)->get();
                    foreach ($assessments as $ass) {
                        DB::table('medical_form_assessments')->insert([
                            'examination_id' => $examId,
                            'type' => $ass->type,
                            'assessed_by' => $ass->assessed_by,
                            'assessment_data' => $ass->assessment_data,
                            'status' => $ass->status,
                            'created_at' => $ass->created_at,
                            'updated_at' => $ass->updated_at,
                        ]);
                    }
                }
            }
        });

        $this->info('Migration complete!');
    }
    private function getOrCreateLookup($table, $attributes, $idField) {
        $record = DB::table($table)->where($attributes)->first();
        if ($record) return $record->$idField;
        return DB::table($table)->insertGetId(array_merge($attributes, ['created_at' => now(), 'updated_at' => now()]));
    }
}



