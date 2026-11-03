<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClerkStationRequest;
use App\Http\Requests\UpdateDoctorFieldRequest;
use App\Models\Patient;
use App\Models\ExaminationApproval;
use App\Models\Station;
use App\Models\StationClearance;
use App\Services\StationService;
use App\Services\PmeIntegrationService;
use App\Support\StationRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StationController extends Controller
{
    public function __construct(
        private StationService $stations,
        private PmeIntegrationService $integration,
    ) {}

    public function clerkUpdate(UpdateClerkStationRequest $request): JsonResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_no'));

        try {
            $this->stations->updateClerkStation(
                $patient,
                $request->input('station'),
                $request->input('diagnosis', ''),
                $request->input('remarks', ''),
                $request->input('action', ''),
                $request->file('fileAttachment'),
                $request->input('user_username', session('staff.display_name', 'Guest')),
            );

            // Sync with external PMC
            $this->integration->pushStationResult($patient, $request->input('station'), [
                'action' => $request->input('action', '')
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Data updated successfully']);
    }

    public function doctorFieldUpdate(UpdateDoctorFieldRequest $request): JsonResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_no'));

        try {
            $this->stations->updateDoctorField(
                $patient,
                $request->input('station'),
                $request->input('field'),
                $request->input('value'),
                $request->input('user_username', session('staff.display_name', 'Guest')),
            );

            if ($request->input('field') === 'approval_status' || $request->input('field') === 'action') {
                $this->integration->pushStationResult($patient, $request->input('station'), [
                    'action' => $request->input('value')
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Data updated successfully']);
    }

    public function clearDoctorStatus(Request $request, string $stationKey): JsonResponse
    {
        $stationConfig = StationRegistry::get($stationKey);
        $patient = Patient::query()->findOrFail($request->integer('patient_no'));

        try {
            $exam = $patient->applications()->latest()->first()?->latestExamination();
            if (!$exam) {
                throw new \InvalidArgumentException('No examination record found.');
            }

            if ($exam->status === 'PASSED' || $exam->status === 'FAILED') {
                throw new \InvalidArgumentException('Cannot retract status after Chief Doctor has marked the applicant as PASSED or FAILED.');
            }

            $station = Station::where('station_key', $stationKey)->first();
            if (!$station) {
                throw new \InvalidArgumentException("Invalid station: {$stationKey}");
            }

            StationClearance::updateOrCreate(
                [
                    'examination_id' => $exam->examination_id,
                    'station_id' => $station->station_id
                ],
                [
                    'approval_status' => null,
                    'diagnosis' => null,
                    'remarks' => null,
                ]
            );

            // Log the retraction
            $patientProfile = $patient->patientProfile;
            $stationLabel = $stationConfig['label'] ?? $stationConfig['title'] ?? $stationKey;

            $logData = [
                'patient_no_logs' => (string) $patient->patient_id,
                'firstName_logs' => $patientProfile?->first_name ?? '',
                'lastName_logs' => $patientProfile?->last_name ?? '',
                'user_username' => session('staff.display_name', 'Guest'),
            ];
            if (!empty($stationConfig['log_approval'])) {
                $logData[$stationConfig['log_approval']] = null;
            }
            if (!empty($stationConfig['log_timestamp'])) {
                $logData[$stationConfig['log_timestamp']] = now();
            }

            \App\Models\RegistrarLog::query()->create($logData);

            \App\Services\ActivityLogger::log("Cleared status for station: {$stationLabel}", (string) $patient->patient_id);

            $this->integration->pushStationResult($patient, $stationKey, ['action' => 'retracted']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Station status cleared successfully']);
    }

    public function clerkUpdateLegacy(Request $request, string $station): JsonResponse
    {
        $request->merge(['station' => $station]);
        return $this->clerkUpdate(UpdateClerkStationRequest::createFrom($request));
    }

    public function doctorUpdateLegacy(Request $request, string $station): JsonResponse
    {
        $request->merge(['station' => $station]);
        return $this->doctorFieldUpdate(UpdateDoctorFieldRequest::createFrom($request));
    }

    public function clerkUpdateClean(Request $request, string $station): JsonResponse
    {
        $request->merge(['station' => $station]);
        return $this->clerkUpdate(UpdateClerkStationRequest::createFrom($request));
    }

    public function doctorUpdateClean(Request $request, string $station): JsonResponse
    {
        $request->merge(['station' => $station]);
        return $this->doctorFieldUpdate(UpdateDoctorFieldRequest::createFrom($request));
    }

    public function uploadAttachment(Request $request, string $station): JsonResponse
    {
        return $this->uploadAttachmentClean($request, $station);
    }

    public function uploadAttachmentClean(Request $request, string $stationKey): JsonResponse
    {
        $stationConfig = StationRegistry::get($stationKey);
        $uploadField = $stationConfig['upload_input'] ?? ($stationKey.'_attachment');
        
        $request->validate([
            'patient_no' => 'required|integer',
            $uploadField => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $patient = Patient::query()->findOrFail($request->integer('patient_no'));
        $file = $request->file($uploadField);
        $ext = strtolower($file->getClientOriginalExtension());
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (! in_array($ext, $allowedExts, true)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid file extension.'], 400);
        }

        $patientNoStr = str_pad((string)$patient->patient_id, 3, '0', STR_PAD_LEFT);
        $fileName = "{$stationKey}_{$patientNoStr}_" . time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
        
        $path = $file->storeAs("attachments/{$patientNoStr}", $fileName, 'public');

        $exam = $patient->applications()->latest()->first()?->latestExamination();
        $station = Station::where('station_key', $stationKey)->first();
        if ($exam && $station) {
            $clearance = StationClearance::firstOrCreate([
                'examination_id' => $exam->examination_id, 
                'station_id' => $station->station_id
            ]);
            
            \App\Models\ClearanceAttachment::create([
                'clearance_id' => $clearance->clearance_id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => '/storage/' . $path,
                'file_type' => $ext
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'File uploaded successfully']);
    }
}
