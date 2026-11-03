<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncStationResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 15;

    public function __construct(
        public string $appcode,
        public string $station,
        public array $data
    ) {}

    public function handle(): void
    {
        $baseUrl = config('services.pme.url', 'http://philarmy-recruitment.com');
        $secret = config('services.pme.secret', 'philarmy-pme-secure-token-2026');

        $url = rtrim($baseUrl, '/') . '/api/applicants/' . urlencode($this->appcode) . '/results';

        $pmeStatus = 'pending';
        if ($this->station === 'chief_doctor') {
            $pmeStatus = ($this->data['action'] ?? '') === 'PASSED' ? 'passed' : 'failed';
        }

        try {
            $response = Http::withToken($secret)
                ->timeout(10)
                ->withoutVerifying()
                ->post($url, [
                    'pme_status' => $pmeStatus,
                    'pme_details' => [
                        $this->station => $this->data['action'] ?? '',
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning("PME Sync HTTP returned error: {$response->status()}", [
                    'appcode' => $this->appcode,
                    'station' => $this->station,
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("PME Sync exception during queue job: {$e->getMessage()}", [
                'appcode' => $this->appcode,
                'station' => $this->station,
            ]);
        }
    }
}
