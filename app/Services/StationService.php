<?php

namespace App\Services;

use App\Models\DoctorLog;
use App\Models\Patient;
use App\Models\RegistrarLog;
use App\Models\StationClearance;
use App\Models\ClearanceAttachment;
use App\Models\Station;
use App\Services\ActivityLogger;
use App\Support\AssetPath;
use App\Support\StationRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StationService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public function updateClerkStation(
        Patient $patient,
        string $stationKey,
        ?string $diagnosis,
        ?string $remarks,
        ?string $approval,
        ?UploadedFile $attachment,
        string $userUsername,
    ): Patient {
        return DB::transaction(function () use ($patient, $stationKey, $diagnosis, $remarks, $approval, $attachment, $userUsername) {
            $stationConfig = StationRegistry::get($stationKey);

            $exam = $patient->applications()->latest()->first()?->latestExamination();
            if (!$exam) {
                throw new \Exception("No examination record found for patient.");
            }

            $station = Station::where('station_key', $stationKey)->first();
            if (!$station) {
                $station = Station::create([
                    'station_key' => $stationKey,
                    'station_name' => $stationConfig['label'] ?? ucfirst($stationKey),
                    'sort_order' => 0,
                    'is_active' => 1,
                ]);
            }

            $eval = StationClearance::firstOrNew([
                'examination_id' => $exam->examination_id,
                'station_id' => $station->station_id,
            ]);

            $eval->diagnosis = $diagnosis;
            $eval->remarks = $remarks;
            $eval->approval_status = $approval;
            $eval->save();

            if ($attachment) {
                $path = $this->storeAttachment($patient, $stationKey, $attachment);
                ClearanceAttachment::create([
                    'clearance_id' => $eval->clearance_id,
                    'file_name' => $attachment->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $attachment->getClientOriginalExtension(),
                ]);
            }

            $this->writeRegistrarLog($patient, $stationConfig, $userUsername, $approval);

            return $patient->refresh();
        });
    }

    public function updateDoctorField(
        Patient $patient,
        string $stationKey,
        string $field,
        ?string $value,
        string $userUsername,
    ): Patient {
        return DB::transaction(function () use ($patient, $stationKey, $field, $value, $userUsername) {
            $stationConfig = StationRegistry::get($stationKey);

            $exam = $patient->applications()->latest()->first()?->latestExamination();
            if (!$exam) {
                throw new \Exception("No examination record found for patient.");
            }

            $station = Station::where('station_key', $stationKey)->first();
            if (!$station) {
                $station = Station::create([
                    'station_key' => $stationKey,
                    'station_name' => $stationConfig['label'] ?? ucfirst($stationKey),
                    'sort_order' => 0,
                    'is_active' => 1,
                ]);
            }

            $eval = StationClearance::firstOrNew([
                'examination_id' => $exam->examination_id,
                'station_id' => $station->station_id,
            ]);

            // 'action' is the frontend alias for 'approval_status' (GO/NO GO/COMPLIANCE)
            if ($field === 'approval_status' || $field === 'action') {
                $eval->approval_status = $value;
            } elseif ($field === 'diagnosis') {
                $eval->diagnosis = $value;
            } elseif ($field === 'remarks') {
                $eval->remarks = $value;
            }
            
            $eval->save();

            if ($field === 'approval_status' || $field === 'action') {
                $firstName = $patient->first_name ?? '';
                $lastName = $patient->last_name ?? '';

                $logData = [
                    'patient_no_logs' => $patient->patient_id,
                    'firstName_logs' => $firstName,
                    'lastName_logs' => $lastName,
                    'user_username' => $userUsername,
                ];

                if (!empty($stationConfig['log_approval'])) {
                    $logData[$stationConfig['log_approval']] = $value;
                }
                if (!empty($stationConfig['log_timestamp'])) {
                    $logData[$stationConfig['log_timestamp']] = now();
                }

                DoctorLog::create($logData);

                $label = $stationConfig['label'] ?? $stationKey;
                ActivityLogger::log("Doctor updated {$label} status to: {$value}", (string) $patient->patient_id, $userUsername, 'DOCTOR');
            }

            return $patient->refresh();
        });
    }

    private function storeAttachment(Patient $patient, string $stationKey, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            throw new InvalidArgumentException("Invalid file type: {$ext}. Allowed types: " . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        $patientNoStr = str_pad((string)$patient->patient_id, 3, '0', STR_PAD_LEFT);
        $fileName = "{$stationKey}_{$patientNoStr}_" . time() . "." . $ext;
        
        $path = $file->storeAs("attachments/{$patientNoStr}", $fileName, 'public');
        return '/storage/' . $path;
    }

    private function writeRegistrarLog(Patient $patient, array $station, string $user, ?string $approval): void
    {
        $firstName = $patient->first_name ?? '';
        $lastName = $patient->last_name ?? '';

        $logData = [
            'patient_no_logs' => (string) $patient->patient_id,
            'firstName_logs' => $firstName,
            'lastName_logs' => $lastName,
            'user_username' => $user,
        ];

        if (!empty($station['log_approval']) && $approval) {
            $logData[$station['log_approval']] = $approval;
        }
        if (!empty($station['log_timestamp'])) {
            $logData[$station['log_timestamp']] = now();
        }

        RegistrarLog::create($logData);

        $label = $station['label'] ?? 'Station';
        ActivityLogger::log("Registrar updated {$label}" . ($approval ? " to {$approval}" : ''), (string) $patient->patient_id, $user, 'REGISTRAR');
    }
}
