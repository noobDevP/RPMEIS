<?php

namespace App\Console\Commands;

use App\Services\LegacyPatientSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncPhilarmyLabCommand extends Command
{
    private const STATE_PATH = 'legacy_sync_state.json';

    protected $signature = 'philarmylab:sync
        {--source=api : Source mode: api or sql}
        {--sql-file= : Path to legacy SQL dump when --source=sql}
        {--assets-dir= : Optional local public_html directory for photos/attachments}
        {--fallback-api : Fetch missing files over API when --source=sql}
        {--patient-no= : Sync a single patient_no}
        {--after-patient-no=0 : Start API pagination after this patient_no}
        {--since= : Sync API records modified at or after this timestamp (Y-m-d H:i:s)}
        {--since-last : Incremental catch-up using last saved cursor & timestamp}
        {--limit=100 : API batch size (1-500)}
        {--skip-files : Skip downloading photos and station attachments}
        {--dry-run : Validate and report without writing to database}';

    protected $description = 'Sync patient records and attachments from philarmy-lab.com (API or SQL dump) into RPMIS.';

    public function handle(LegacyPatientSyncService $syncService): int
    {
        $source = strtolower((string) $this->option('source'));
        $dryRun = (bool) $this->option('dry-run');
        $assetsDir = $this->option('assets-dir') ? (string) $this->option('assets-dir') : null;
        $patientNo = $this->option('patient-no') !== null ? (int) $this->option('patient-no') : null;
        $limit = (int) $this->option('limit');

        if ($source === 'sql') {
            $sqlFile = (string) $this->option('sql-file');
            if ($sqlFile === '') {
                $this->error('--sql-file is required when --source=sql.');

                return self::FAILURE;
            }

            $summary = $syncService->syncFromSqlFile($sqlFile, [
                'dry_run' => $dryRun,
                'assets_dir' => $assetsDir,
                'fallback_api' => (bool) $this->option('fallback-api'),
                'patient_no' => $patientNo,
                'limit' => $this->option('limit') !== null && $this->option('limit') !== '100' ? $limit : 0,
            ]);

            $this->renderSummary($summary, $dryRun);

            return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        if ($source !== 'api') {
            $this->error("Unsupported --source '{$source}'. Use 'api' or 'sql'.");

            return self::FAILURE;
        }

        if ((bool) $this->option('since-last')) {
            $state = $this->readState();
            $lastMaxPatientNo = (int) ($state['max_patient_no'] ?? 0);
            $lastMaxTs = isset($state['max_timestamp']) && is_string($state['max_timestamp'])
                ? Carbon::parse($state['max_timestamp'])->subSeconds(5)->format('Y-m-d H:i:s')
                : null;

            $combined = [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
                'photos_synced' => 0,
                'attachments_synced' => 0,
                'deduplicated_applicant_nos' => 0,
                'warnings' => [],
                'errors' => [],
                'last_cursor_patient_no' => $lastMaxPatientNo,
                'max_timestamp_seen' => $state['max_timestamp'] ?? null,
            ];

            // 1. Pull newly inserted patients beyond last seen max patient_no
            $newPatients = $syncService->syncFromApi([
                'dry_run' => $dryRun,
                'assets_dir' => $assetsDir,
                'after_patient_no' => $lastMaxPatientNo,
                'limit' => $limit,
                'skip_files' => (bool) $this->option('skip-files'),
            ]);
            $this->mergeSummaries($combined, $newPatients);

            // 2. Pull existing patients updated at or after last timestamp
            if ($lastMaxTs !== null) {
                $updatedPatients = $syncService->syncFromApi([
                    'dry_run' => $dryRun,
                    'assets_dir' => $assetsDir,
                    'since' => $lastMaxTs,
                    'limit' => $limit,
                    'skip_files' => (bool) $this->option('skip-files'),
                ]);
                $this->mergeSummaries($combined, $updatedPatients);
            }

            if (! $dryRun && $combined['failed'] === 0) {
                $this->writeState(
                    (int) ($combined['last_cursor_patient_no'] ?? $lastMaxPatientNo),
                    $combined['max_timestamp_seen'] ?? ($state['max_timestamp'] ?? null)
                );
            }

            $this->renderSummary($combined, $dryRun);

            return $combined['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $summary = $syncService->syncFromApi([
            'dry_run' => $dryRun,
            'assets_dir' => $assetsDir,
            'patient_no' => $patientNo,
            'after_patient_no' => (int) $this->option('after-patient-no'),
            'since' => $this->option('since') ? (string) $this->option('since') : null,
            'limit' => $limit,
            'skip_files' => (bool) $this->option('skip-files'),
        ]);

        if (! $dryRun && $patientNo === null && $summary['failed'] === 0) {
            $this->writeState(
                (int) ($summary['last_cursor_patient_no'] ?? 0),
                $summary['max_timestamp_seen'] ?? null
            );
        }

        $this->renderSummary($summary, $dryRun);

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mergeSummaries(array &$target, array $part): void
    {
        foreach (['processed', 'created', 'updated', 'failed', 'photos_synced', 'attachments_synced', 'deduplicated_applicant_nos'] as $key) {
            $target[$key] += (int) ($part[$key] ?? 0);
        }
        $target['warnings'] = array_merge($target['warnings'], $part['warnings'] ?? []);
        $target['errors'] = array_merge($target['errors'], $part['errors'] ?? []);
        $target['last_cursor_patient_no'] = max((int) $target['last_cursor_patient_no'], (int) ($part['last_cursor_patient_no'] ?? 0));

        $partTs = $part['max_timestamp_seen'] ?? null;
        if ($partTs !== null && ($target['max_timestamp_seen'] === null || $partTs > $target['max_timestamp_seen'])) {
            $target['max_timestamp_seen'] = $partTs;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(): array
    {
        if (! Storage::disk('local')->exists(self::STATE_PATH)) {
            return [];
        }

        $decoded = json_decode((string) Storage::disk('local')->get(self::STATE_PATH), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeState(int $maxPatientNo, ?string $maxTimestamp): void
    {
        $existing = $this->readState();
        $nextMaxNo = max($maxPatientNo, (int) ($existing['max_patient_no'] ?? 0));
        $nextTs = $maxTimestamp ?? ($existing['max_timestamp'] ?? null);
        if (isset($existing['max_timestamp']) && $maxTimestamp !== null && $existing['max_timestamp'] > $maxTimestamp) {
            $nextTs = $existing['max_timestamp'];
        }

        Storage::disk('local')->put(self::STATE_PATH, json_encode([
            'max_patient_no' => $nextMaxNo,
            'max_timestamp' => $nextTs,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    private function renderSummary(array $summary, bool $dryRun): void
    {
        $modeLabel = $dryRun ? 'DRY RUN' : 'SYNC';
        $this->info("[{$modeLabel}] Processed: {$summary['processed']}, Created: {$summary['created']}, Updated: {$summary['updated']}, Failed: {$summary['failed']}, Photos: {$summary['photos_synced']}, Attachments: {$summary['attachments_synced']}");

        foreach (array_slice($summary['warnings'], 0, 25) as $warning) {
            $this->warn($warning);
        }
        if (count($summary['warnings']) > 25) {
            $this->warn('... and ' . (count($summary['warnings']) - 25) . ' more warnings.');
        }

        foreach (array_slice($summary['errors'], 0, 25) as $error) {
            $this->error($error);
        }
    }
}
