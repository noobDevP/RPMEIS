<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFinalResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        public string $appcode,
        public string $chiefDoctorApproval,
        public array $stationApprovals
    ) {}

    public function handle(): void
    {
        $baseUrl = config('services.pme.url', 'http://philarmy-recruitment.com');
        $secret = config('services.pme.secret', 'philarmy-pme-secure-token-2026');

        $url = rtrim($baseUrl, '/') . '/api/applicants/' . urlencode($this->appcode) . '/results';

        $pmeStatus = match (strtoupper(trim($this->chiefDoctorApproval))) {
            'PASSED' => 'passed',
            default => 'failed',
        };

        try {
            $response = Http::withToken($secret)
                ->timeout(10)
                ->withoutVerifying()
                ->post($url, [
                    'pme_status' => $pmeStatus,
                    'pme_details' => $this->stationApprovals,
                ]);

            if (! $response->successful()) {
                Log::warning("PME Final Sync HTTP returned error: {$response->status()}", [
                    'appcode' => $this->appcode,
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("PME Final Sync exception during queue job: {$e->getMessage()}", [
                'appcode' => $this->appcode,
            ]);
        }
    }
}
