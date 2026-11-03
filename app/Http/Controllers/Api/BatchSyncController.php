<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PmcApiService;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BatchSyncController extends Controller
{
    public function __construct(
        private PmcApiService $pmcApi,
        private PatientService $patients,
    ) {}

    public function scheduled(): JsonResponse
    {
        $batches = $this->pmcApi->getScheduledBatches();
        return response()->json(['success' => true, 'data' => $batches]);
    }

    public function startBatch(int $batchId): JsonResponse
    {
        $response = $this->pmcApi->startBatch($batchId);
        
        if (!$response || empty($response['applicants'])) {
            return response()->json(['success' => false, 'message' => 'Failed to start batch or no applicants returned from PMC.'], 400);
        }

        $applicants = $response['applicants'];
        $count = 0;

        foreach ($applicants as $app) {
            // Check if patient already exists to avoid duplicates
            $existing = $this->patients->findByAppCode($app['applicant_code'] ?? null);
            if ($existing) continue;

            try {
                // Map the legacy JSON keys from PMC into the normalized fields for v2
                $this->patients->create([
                    // Handle missing applicant_no safely
                    'applicant_no' => $app['applicant_no'] ?? $this->patients->nextApplicantNo(),
                    'rank' => $app['rank'] ?? null,
                    'serialNo' => $app['serial_no'] ?? null,
                    'branch' => $app['branch'] ?? null,
                    'firstName' => $app['first_name'] ?? 'Unknown',
                    'middleName' => $app['middle_name'] ?? null,
                    'lastName' => $app['last_name'] ?? 'Unknown',
                    'birthdate' => $app['birth_date'] ?? null,
                    'age' => $app['age'] ?? null,
                    'sex' => $app['gender'] ?? null,
                    'raceSelect' => $app['ethnicity'] ?? null,
                    'placeOfBirth' => $app['pob'] ?? null,
                    'currentAddress' => $app['present_address'] ?? null,
                    'placeOfExamination' => $app['examPlace'] ?? null,
                    'dateOfExamination' => $app['examDate'] ?? null,
                    'purpose' => $app['purpose'] ?? null,
                    'batch' => $batchId, // Store the batch ID as the batch string
                    'unitorg' => $app['unitorg'] ?? null,
                    'appcode' => $app['applicant_code'] ?? null,
                    'otherData' => $app['other_data'] ?? null,
                    'photo_path' => $app['photo_url'] ?? null,
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to import applicant " . ($app['applicant_code'] ?? 'unknown') . ": " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Batch started successfully. Imported {$count} applicants.",
        ]);
    }
}
