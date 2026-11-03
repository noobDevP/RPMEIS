<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PmcApiService
{
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.pmc.url', 'http://127.0.0.1:8002/api');
        $this->apiToken = (string) config('services.pmc.token', 'default-secret-token');
    }

    protected function client()
    {
        return Http::withToken($this->apiToken)
            ->timeout(5)
            ->acceptJson();
    }

    public function getScheduledBatches()
    {
        try {
            // Note: If production PMC doesn't have this endpoint, it will return 404.
            // You may need to build a batch sync endpoint in Production later.
            $response = $this->client()->get("{$this->baseUrl}/batches/scheduled");
            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            Log::error("PMC API Connection error: " . $e->getMessage());
        }
        return [];
    }

    public function startBatch(int $batchId)
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/batches/{$batchId}/start");
            if ($response->successful()) {
                return $response->json();
            }
            Log::error("Failed to start batch {$batchId} in PMC API: " . $response->body());
        } catch (\Exception $e) {
            Log::error("PMC API Connection error: " . $e->getMessage());
        }
        return null;
    }

    public function syncResults(string $applicantId, string $status, array $details, ?string $remarks, ?string $diagnosis = null)
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/applicants/{$applicantId}/results", [
                'pme_status' => $status,
                'pme_details' => $details,
                'remarks' => $remarks,
                'diagnosis' => $diagnosis,
            ]);
            
            if (!$response->successful()) {
                 Log::error("Failed to sync results for {$applicantId}: " . $response->body());
                 return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error("PMC API Connection error: " . $e->getMessage());
            return false;
        }
    }
}
