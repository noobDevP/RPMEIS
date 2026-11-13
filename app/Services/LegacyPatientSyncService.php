<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Branch;
use App\Models\ClearanceAttachment;
use App\Models\ExamCenter;
use App\Models\MedicalExamination;
use App\Models\MedicalFormAssessment;
use App\Models\Patient;
use App\Models\PatientProfile;
use App\Models\Race;
use App\Models\Rank;
use App\Models\ServiceRecord;
use App\Models\Station;
use App\Models\StationClearance;
use App\Models\Unit;
use App\Support\LegacySignatureVerifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class LegacyPatientSyncService
{
    private const ALLOWED_APPROVALS = ['GO', 'NO GO', 'COMPLIANCE', 'PENDING'];

    private const ALLOWED_OVERALL = ['NOT DONE', 'DONE', 'COMPLETED'];

    /** @var array<string, int>|null */
    private ?array $stationsMap = null;

    /** @var array<string, array<string, int>> */
    private array $lookupCache = [];

    public function __construct(
        private LegacySignatureVerifier $verifier,
        private LegacySqlDumpParser $sqlParser,
    ) {}

    /**
     * Pull paginated legacy patient rows from philarmy-lab.com and sync them idempotently.
     *
     * @return array<string, mixed>
     */
    public function syncFromApi(array $options = []): array
    {
        $baseUrl = rtrim((string) config('services.philarmylab_legacy.url'), '/');
        $endpoint = '/' . ltrim((string) config('services.philarmylab_legacy.endpoint', '/api_sync_export.php'), '/');
        $secret = (string) config('services.philarmylab_legacy.secret');

        if ($baseUrl === '' || $secret === '') {
            throw new RuntimeException('PHILARMYLAB_LEGACY_URL and PHILARMYLAB_LEGACY_SECRET must be configured.');
        }

        $dryRun = (bool) ($options['dry_run'] ?? false);
        $fetchFiles = ! ($options['skip_files'] ?? false);
        $assetsDir = $options['assets_dir'] ?? null;
        $limit = max(1, min(500, (int) ($options['limit'] ?? 100)));
        $afterPatientNo = max(0, (int) ($options['after_patient_no'] ?? 0));
        $patientNo = isset($options['patient_no']) && $options['patient_no'] !== null
            ? (int) $options['patient_no']
            : null;
        $since = isset($options['since']) && $options['since'] !== '' ? (string) $options['since'] : null;

        $summary = $this->emptySummary();
        $maxTimestampSeen = $since;

        do {
            $query = array_filter([
                'resource' => 'patients',
                'limit' => $limit,
                'after_patient_no' => $afterPatientNo,
                'patient_no' => $patientNo,
                'since' => $since,
            ], static fn ($v) => $v !== null && $v !== '');

            $payload = $this->signedGetJson($baseUrl . $endpoint, $query, $secret);
            $rows = $payload['patients'] ?? [];
            if (! is_array($rows) || $rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $this->processSingleRow($row, $dryRun, $fetchFiles, $assetsDir, $summary);
                $rowNo = (int) ($row['patient_no'] ?? 0);
                if ($rowNo > $afterPatientNo) {
                    $afterPatientNo = $rowNo;
                }
                $rowTs = $this->extractLatestTimestamp($row);
                if ($rowTs !== null && ($maxTimestampSeen === null || $rowTs > $maxTimestampSeen)) {
                    $maxTimestampSeen = $rowTs;
                }
            }

            $hasMore = (bool) ($payload['has_more'] ?? false) && $patientNo === null;
        } while ($hasMore);

        $summary['last_cursor_patient_no'] = $afterPatientNo;
        $summary['max_timestamp_seen'] = $maxTimestampSeen;

        return $summary;
    }

    /**
     * Backfill or dry-run directly from a legacy MySQL dump and optional local uploads folder.
     *
     * @return array<string, mixed>
     */
    public function syncFromSqlFile(string $sqlFilePath, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $assetsDir = $options['assets_dir'] ?? null;
        $fallbackApi = (bool) ($options['fallback_api'] ?? false);
        $fetchFiles = $fallbackApi || ($assetsDir !== null && $assetsDir !== '');
        $onlyPatientNo = isset($options['patient_no']) && $options['patient_no'] !== null
            ? (int) $options['patient_no']
            : null;
        $limit = isset($options['limit']) && (int) $options['limit'] > 0 ? (int) $options['limit'] : null;

        $summary = $this->emptySummary();
        $seenApplicantNos = [];

        foreach ($this->sqlParser->parseTableRows($sqlFilePath, 'patient_data') as $row) {
            $patientNo = (int) ($row['patient_no'] ?? 0);
            if ($onlyPatientNo !== null && $patientNo !== $onlyPatientNo) {
                continue;
            }

            if ($dryRun) {
                $rawApp = trim((string) ($row['applicant_no'] ?? ''));
                if ($rawApp !== '' && isset($seenApplicantNos[$rawApp])) {
                    $summary['warnings'][] = "[dry-run] Duplicate applicant_no '{$rawApp}' on patient_no={$patientNo} -> will store as '{$rawApp}-P{$patientNo}'";
                    $summary['deduplicated_applicant_nos']++;
                } elseif ($rawApp !== '') {
                    $seenApplicantNos[$rawApp] = $patientNo;
                }
            }

            $this->processSingleRow($row, $dryRun, $fetchFiles && ! $dryRun, $assetsDir, $summary, $fallbackApi);

            if ($limit !== null && $summary['processed'] >= $limit) {
                break;
            }
        }

        return $summary;
    }

    /**
     * Upsert one legacy patient_data associative array into RPMIS normalized tables.
     *
     * @return array{patient_id: int, created: bool, warnings: list<string>, attachments_synced: int, photo_synced: bool}
     */
    public function syncPatientRow(
        array $row,
        bool $dryRun = false,
        bool $fetchRemoteFiles = true,
        ?string $assetsDir = null,
    ): array {
        $patientNo = (int) ($row['patient_no'] ?? 0);
        if ($patientNo <= 0) {
            throw new RuntimeException('Legacy row missing valid positive patient_no.');
        }

        $warnings = [];
        $birthdate = $this->parseLegacyDate($row['birthdate'] ?? null, 'birthdate', $patientNo, $warnings);
        $examDate = $this->parseLegacyDate($row['dateOfExamination'] ?? null, 'dateOfExamination', $patientNo, $warnings);
        $sex = $this->normalizeSex($row['sex'] ?? null);
        $overallStat = $this->normalizeOverallStat($row['overall_stat'] ?? null);
        $chiefApproval = $this->deriveChiefDoctorApproval($overallStat, $row);
        $appStatus = strtolower(trim((string) ($row['status'] ?? 'active'))) === 'inactive' ? 'inactive' : 'active';

        if ($dryRun) {
            $existingPatient = Patient::query()->find($patientNo);

            return [
                'patient_id' => $patientNo,
                'created' => $existingPatient === null,
                'warnings' => $warnings,
                'attachments_synced' => 0,
                'photo_synced' => false,
            ];
        }

        $attachmentsSynced = 0;
        $photoSynced = false;

        $created = DB::transaction(function () use (
            $row,
            $patientNo,
            $birthdate,
            $examDate,
            $sex,
            $overallStat,
            $chiefApproval,
            $appStatus,
            $fetchRemoteFiles,
            $assetsDir,
            &$warnings,
            &$attachmentsSynced,
            &$photoSynced,
        ): bool {
            $stationsMap = $this->ensureStationsMap();

            $raceId = $this->resolveLookupId('race', Race::class, 'race_id', ['race_name' => $this->cleanLookupValue($row['raceSelect'] ?? null)]);
            $rankId = $this->resolveLookupId('rank', Rank::class, 'rank_id', ['rank_name' => $this->cleanLookupValue($row['rank'] ?? null)]);
            $branchId = $this->resolveLookupId('branch', Branch::class, 'branch_id', ['branch_name' => $this->cleanLookupValue($row['branch'] ?? null)]);
            $unitName = $this->cleanLookupValue($row['unitorg'] ?? null);
            $unitId = $unitName !== null
                ? $this->resolveLookupId('unit', Unit::class, 'unit_id', ['unit_name' => $unitName, 'branch_id' => $branchId])
                : null;
            $centerId = $this->resolveLookupId('center', ExamCenter::class, 'center_id', ['center_name' => $this->cleanLookupValue($row['placeOfExamination'] ?? null)]);

            $patient = Patient::query()->find($patientNo);
            $isNew = $patient === null;
            $patientAttrs = [
                'first_name' => trim((string) ($row['firstName'] ?? '')),
                'middle_name' => $this->cleanNullableText($row['middleName'] ?? null),
                'last_name' => trim((string) ($row['lastName'] ?? '')),
                'birthdate' => $birthdate,
                'sex' => $sex,
                'race_id' => $raceId,
                'place_of_birth' => $this->cleanNullableText($row['placeOfBirth'] ?? null),
                'is_activated' => 1,
            ];

            if ($isNew) {
                $patient = Patient::query()->create(['patient_id' => $patientNo] + $patientAttrs);
            } else {
                $patient->fill($patientAttrs)->save();
            }

            // Photo & profile
            $rawPhotoPath = $this->cleanNullableText($row['photo_path'] ?? null);
            $storedPhotoPath = null;
            if ($rawPhotoPath !== null) {
                $storedPhotoPath = $this->resolveOrTransferAsset(
                    $rawPhotoPath,
                    $assetsDir,
                    $fetchRemoteFiles,
                    $warnings,
                    'photo',
                    $patientNo
                );
                if ($storedPhotoPath !== null) {
                    $photoSynced = true;
                }
            }

            $existingProfile = PatientProfile::query()->where('patient_id', $patientNo)->first();
            PatientProfile::query()->updateOrCreate(
                ['patient_id' => $patientNo],
                [
                    'current_address' => $this->cleanNullableText($row['currentAddress'] ?? null),
                    'photo_path' => $storedPhotoPath ?? $existingProfile?->photo_path ?? $rawPhotoPath,
                ]
            );

            // Service record (enforce serial_no NULL when empty/NA or colliding across patients)
            $rawSerial = $this->cleanLookupValue($row['serialNo'] ?? null);
            $serialToSave = $rawSerial;
            if ($serialToSave !== null) {
                $serialConflict = ServiceRecord::query()
                    ->where('serial_no', $serialToSave)
                    ->where('patient_id', '!=', $patientNo)
                    ->exists();
                if ($serialConflict) {
                    $warnings[] = "Duplicate serialNo '{$serialToSave}' on patient_no={$patientNo}; stored as NULL.";
                    $serialToSave = null;
                }
            }

            ServiceRecord::query()->updateOrCreate(
                ['patient_id' => $patientNo],
                [
                    'serial_no' => $serialToSave,
                    'rank_id' => $rankId,
                    'unit_id' => $unitId,
                ]
            );

            // Application (preserve applicant_no if unique, else suffix -P{patientNo})
            $application = Application::query()
                ->where('patient_id', $patientNo)
                ->orderBy('application_id')
                ->first();

            $rawApplicantNo = trim((string) ($row['applicant_no'] ?? ''));
            $resolvedApplicantNo = $rawApplicantNo !== '' ? $rawApplicantNo : "LEGACY-{$patientNo}";
            $applicantConflict = Application::query()
                ->where('applicant_no', $resolvedApplicantNo)
                ->when($application, fn ($q) => $q->where('application_id', '!=', $application->application_id))
                ->exists();

            if ($applicantConflict) {
                $dedup = ($rawApplicantNo !== '' ? $rawApplicantNo : 'LEGACY') . '-P' . $patientNo;
                $warnings[] = "Duplicate applicant_no '{$rawApplicantNo}' on patient_no={$patientNo}; stored as '{$dedup}'.";
                $resolvedApplicantNo = $dedup;
            }

            $applicationAttrs = [
                'patient_id' => $patientNo,
                'applicant_no' => $resolvedApplicantNo,
                'app_code' => $this->cleanNullableText($row['appcode'] ?? null),
                'batch' => $this->cleanNullableText($row['batch'] ?? null),
                'purpose' => $this->cleanNullableText($row['purpose'] ?? null),
                'status' => $appStatus,
            ];

            if ($application) {
                $application->fill($applicationAttrs)->save();
            } else {
                $applicationAttrs['applied_at'] = $examDate ? Carbon::parse($examDate) : now();
                $application = Application::query()->create($applicationAttrs);
            }

            // Medical Examination (merge form_data safely)
            $exam = $application->examinations()->orderByDesc('examination_id')->first();
            $formData = is_array($exam?->form_data) ? $exam->form_data : [];

            $legacyDoctorsDiagnosis = $this->cleanNullableText($row['doctors_diagnosis'] ?? null);
            $legacyDoctorsRemarks = $this->cleanNullableText($row['doctors_remarks'] ?? null);
            if ($legacyDoctorsDiagnosis !== null) {
                $formData['doctors_diagnosis'] = $legacyDoctorsDiagnosis;
            }
            if ($legacyDoctorsRemarks !== null) {
                $formData['doctors_remarks'] = $legacyDoctorsRemarks;
            }

            $formData['legacy_patient_no'] = $patientNo;
            if ($rawApplicantNo !== '') {
                $formData['legacy_applicant_no'] = $rawApplicantNo;
            }
            if ($rawSerial !== null && $serialToSave === null) {
                $formData['legacy_serial_no'] = $rawSerial;
            }
            $otherData = $this->cleanNullableText($row['otherData'] ?? null);
            if ($otherData !== null) {
                $formData['legacy_other_data'] = $otherData;
            }
            $drugtestAssessment = $this->cleanNullableText($row['drugtest_assessment'] ?? null);
            if ($drugtestAssessment !== null) {
                $formData['legacy_drugtest_assessment'] = $drugtestAssessment;
            }
            if ($rawPhotoPath !== null) {
                $formData['legacy_photo_path'] = $rawPhotoPath;
            }
            $formData['legacy_synced_at'] = now()->toIso8601String();

            $examAttrs = [
                'application_id' => $application->application_id,
                'center_id' => $centerId,
                'exam_date' => $examDate,
                'overall_stat' => $overallStat,
                'chief_doctor_approval' => $chiefApproval,
                'status' => in_array($overallStat, ['DONE', 'COMPLETED'], true)
                    ? MedicalExamination::STATUS_COMPLETED
                    : ($exam?->status ?? MedicalExamination::STATUS_DRAFT),
                'step' => $exam?->step ?? 1,
                'form_data' => $formData,
            ];

            if ($exam) {
                $exam->fill($examAttrs)->save();
            } else {
                $exam = MedicalExamination::query()->create($examAttrs);
            }

            // 6 Station Clearances & Attachments
            foreach (config('stations.stations', []) as $stationKey => $cfg) {
                $stationId = $stationsMap[$stationKey] ?? null;
                if ($stationId === null) {
                    continue;
                }

                $approval = $this->normalizeApproval($row[$cfg['approval']] ?? null);
                $diagnosis = $this->cleanNullableText($row[$cfg['diagnosis']] ?? null);
                $remarks = $this->cleanNullableText($row[$cfg['remarks']] ?? null);
                $stationUpdatedAt = $this->parseLegacyTimestamp($row[$cfg['timestamp']] ?? null);
                $attachmentSource = $this->cleanNullableText($row[$cfg['attachment']] ?? null);

                $clearance = StationClearance::query()->updateOrCreate(
                    [
                        'examination_id' => $exam->examination_id,
                        'station_id' => $stationId,
                    ],
                    [
                        'approval_status' => $approval,
                        'diagnosis' => $diagnosis,
                        'remarks' => $remarks,
                    ]
                );

                if ($stationUpdatedAt !== null) {
                    $clearance->updated_at = $stationUpdatedAt;
                    $clearance->save(['timestamps' => false]);
                }

                if ($attachmentSource !== null) {
                    $storedAttachment = $this->resolveOrTransferAsset(
                        $attachmentSource,
                        $assetsDir,
                        $fetchRemoteFiles,
                        $warnings,
                        'attachment',
                        $patientNo,
                        $stationKey
                    );

                    if ($storedAttachment !== null) {
                        $publicFilePath = '/storage/' . ltrim($storedAttachment, '/');
                        $baseName = basename($attachmentSource);
                        $ext = strtolower(pathinfo($attachmentSource, PATHINFO_EXTENSION));

                        $alreadyExists = ClearanceAttachment::query()
                            ->where('clearance_id', $clearance->clearance_id)
                            ->where(function ($q) use ($publicFilePath, $baseName) {
                                $q->where('file_path', $publicFilePath)
                                    ->orWhere('file_name', $baseName);
                            })
                            ->exists();

                        if (! $alreadyExists) {
                            ClearanceAttachment::query()->create([
                                'clearance_id' => $clearance->clearance_id,
                                'file_name' => $baseName,
                                'file_path' => $publicFilePath,
                                'file_type' => $ext !== '' ? $ext : null,
                            ]);
                            $attachmentsSynced++;
                        }
                    }
                }
            }

            return $isNew;
        });

        return [
            'patient_id' => $patientNo,
            'created' => $created,
            'warnings' => $warnings,
            'attachments_synced' => $attachmentsSynced,
            'photo_synced' => $photoSynced,
        ];
    }

    /**
     * Delete a synced legacy patient cleanly when deleted on philarmy-lab.com.
     */
    public function deleteLegacyPatient(int $patientNo): bool
    {
        if ($patientNo <= 0) {
            return false;
        }

        return DB::transaction(function () use ($patientNo): bool {
            $patient = Patient::query()->find($patientNo);
            if (! $patient) {
                return false;
            }

            $appIds = Application::query()->where('patient_id', $patientNo)->pluck('application_id');
            if ($appIds->isNotEmpty()) {
                $examIds = MedicalExamination::query()->whereIn('application_id', $appIds)->pluck('examination_id');
                if ($examIds->isNotEmpty()) {
                    $clearanceIds = StationClearance::query()->whereIn('examination_id', $examIds)->pluck('clearance_id');
                    if ($clearanceIds->isNotEmpty()) {
                        ClearanceAttachment::query()->whereIn('clearance_id', $clearanceIds)->delete();
                        StationClearance::query()->whereIn('clearance_id', $clearanceIds)->delete();
                    }
                    MedicalFormAssessment::query()->whereIn('examination_id', $examIds)->delete();
                    MedicalExamination::query()->whereIn('examination_id', $examIds)->delete();
                }
                Application::query()->whereIn('application_id', $appIds)->delete();
            }

            ServiceRecord::query()->where('patient_id', $patientNo)->delete();
            PatientProfile::query()->where('patient_id', $patientNo)->delete();
            $patient->delete();

            return true;
        });
    }

    private function processSingleRow(
        array $row,
        bool $dryRun,
        bool $fetchRemoteFiles,
        ?string $assetsDir,
        array &$summary,
        bool $allowRemoteFallback = true,
    ): void {
        try {
            $result = $this->syncPatientRow(
                $row,
                $dryRun,
                $fetchRemoteFiles && $allowRemoteFallback,
                $assetsDir
            );
            $summary['processed']++;
            if ($result['created']) {
                $summary['created']++;
            } else {
                $summary['updated']++;
            }
            if ($result['photo_synced']) {
                $summary['photos_synced']++;
            }
            $summary['attachments_synced'] += $result['attachments_synced'];
            foreach ($result['warnings'] as $warning) {
                $summary['warnings'][] = $warning;
            }
        } catch (Throwable $e) {
            $summary['failed']++;
            $pNo = $row['patient_no'] ?? '?';
            $summary['errors'][] = "patient_no={$pNo}: {$e->getMessage()}";
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(): array
    {
        return [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'photos_synced' => 0,
            'attachments_synced' => 0,
            'deduplicated_applicant_nos' => 0,
            'warnings' => [],
            'errors' => [],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function ensureStationsMap(): array
    {
        if ($this->stationsMap !== null) {
            return $this->stationsMap;
        }

        $order = 1;
        foreach (config('stations.stations', []) as $key => $cfg) {
            Station::query()->firstOrCreate(
                ['station_key' => $key],
                [
                    'station_name' => $cfg['label'] ?? strtoupper($key),
                    'sort_order' => $order++,
                ]
            );
        }

        $this->stationsMap = Station::query()->pluck('station_id', 'station_key')->map(fn ($id) => (int) $id)->all();

        return $this->stationsMap;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<string, mixed>  $attributes
     */
    private function resolveLookupId(string $bucket, string $modelClass, string $pk, array $attributes): ?int
    {
        $filtered = array_filter($attributes, static fn ($v) => $v !== null && $v !== '');
        if ($filtered === [] || (count($attributes) > 0 && reset($attributes) === null)) {
            return null;
        }

        $cacheKey = json_encode($attributes);
        if (isset($this->lookupCache[$bucket][$cacheKey])) {
            return $this->lookupCache[$bucket][$cacheKey];
        }

        $record = $modelClass::query()->firstOrCreate($attributes);
        $id = (int) $record->{$pk};
        $this->lookupCache[$bucket][$cacheKey] = $id;

        return $id;
    }

    private function cleanLookupValue(mixed $value): ?string
    {
        $text = $this->cleanNullableText($value);
        if ($text === null) {
            return null;
        }

        if (in_array(strtoupper($text), ['NA', 'N/A', 'NONE', 'NULL', '-'], true)) {
            return null;
        }

        return $text;
    }

    private function cleanNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeSex(mixed $raw): ?string
    {
        $val = strtolower(trim((string) ($raw ?? '')));
        if ($val === '') {
            return null;
        }
        if (in_array($val, ['male', 'm'], true)) {
            return 'Male';
        }
        if (in_array($val, ['female', 'f'], true)) {
            return 'Female';
        }

        return 'Other';
    }

    private function normalizeOverallStat(mixed $raw): string
    {
        $val = strtoupper(trim((string) ($raw ?? '')));

        return in_array($val, self::ALLOWED_OVERALL, true) ? $val : 'NOT DONE';
    }

    private function normalizeApproval(mixed $raw): ?string
    {
        $val = strtoupper(trim((string) ($raw ?? '')));

        return in_array($val, self::ALLOWED_APPROVALS, true) ? $val : null;
    }

    private function deriveChiefDoctorApproval(string $overallStat, array $row): ?string
    {
        if (! in_array($overallStat, ['DONE', 'COMPLETED'], true)) {
            return null;
        }

        $approvals = [];
        foreach (config('stations.stations', []) as $cfg) {
            $approvals[] = $this->normalizeApproval($row[$cfg['approval']] ?? null);
        }

        if (in_array('NO GO', $approvals, true)) {
            return 'FAILED';
        }

        if ($approvals !== [] && count(array_filter($approvals, static fn ($v) => $v === 'GO')) === count($approvals)) {
            return 'PASSED';
        }

        return null;
    }

    /**
     * Parse Y-m-d dates strictly and auto-repair known 5-digit-year typos like "82024-08-28".
     */
    private function parseLegacyDate(mixed $raw, string $field, int $patientNo, array &$warnings): ?string
    {
        $val = $this->cleanNullableText($raw);
        if ($val === null || $val === '0000-00-00') {
            return null;
        }

        if (preg_match('/^\d((?:19|20)\d{2}-\d{2}-\d{2})$/', $val, $m) === 1) {
            $warnings[] = "Repaired malformed {$field} '{$val}' -> '{$m[1]}' on patient_no={$patientNo}.";
            $val = $m[1];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m) === 1) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            if ($y >= 1900 && $y <= 2100 && checkdate($mo, $d, $y)) {
                return $val;
            }
        }

        try {
            $parsed = Carbon::parse($val);
            if ($parsed->year >= 1900 && $parsed->year <= 2100) {
                return $parsed->format('Y-m-d');
            }
        } catch (Throwable) {
        }

        $warnings[] = "Invalid {$field} '{$val}' on patient_no={$patientNo}; stored as NULL.";

        return null;
    }

    private function parseLegacyTimestamp(mixed $raw): ?Carbon
    {
        $val = $this->cleanNullableText($raw);
        if ($val === null || str_starts_with($val, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($val);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve a legacy photo or station attachment into Storage::disk('public'), copying from local assets or fetching over API.
     */
    private function resolveOrTransferAsset(
        string $legacyRelativePath,
        ?string $assetsDir,
        bool $fetchRemoteFiles,
        array &$warnings,
        string $kind,
        int $patientNo,
        string $stationKey = '',
    ): ?string {
        $cleaned = str_replace('\\', '/', ltrim(trim($legacyRelativePath), '/'));
        if ($cleaned === '' || str_contains($cleaned, '..')) {
            return null;
        }

        $baseName = basename($cleaned);
        $paddedPatientNo = str_pad((string) $patientNo, 3, '0', STR_PAD_LEFT);
        $targetRelativePath = $kind === 'photo'
            ? $cleaned
            : "attachments/{$paddedPatientNo}/{$stationKey}_{$baseName}";

        $disk = Storage::disk('public');
        if ($disk->exists($targetRelativePath)) {
            return $targetRelativePath;
        }

        if ($kind === 'photo' && is_file(public_path($cleaned))) {
            return $cleaned;
        }

        if ($assetsDir !== null && $assetsDir !== '') {
            $localCandidate = rtrim($assetsDir, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleaned);
            if (is_file($localCandidate) && is_readable($localCandidate)) {
                $contents = @file_get_contents($localCandidate);
                if ($contents !== false && $contents !== '') {
                    $disk->put($targetRelativePath, $contents);

                    return $targetRelativePath;
                }
            }
        }

        if ($fetchRemoteFiles) {
            $bytes = $this->fetchRemoteAssetBytes($cleaned, $warnings, $patientNo);
            if ($bytes !== null && $bytes !== '') {
                $disk->put($targetRelativePath, $bytes);

                return $targetRelativePath;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function signedGetJson(string $url, array $query, string $secret): array
    {
        $timestamp = time();
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $signature = $this->verifier->sign($canonicalQuery, $timestamp, $secret);
        $timeout = (int) config('services.philarmylab_legacy.timeout', 20);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Legacy-Timestamp' => (string) $timestamp,
                'X-Legacy-Signature' => $signature,
            ])
            ->get($url . '?' . $canonicalQuery);

        if (! $response->successful()) {
            throw new RuntimeException("Legacy API request failed ({$response->status()}): " . $response->body());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Legacy API returned invalid JSON.');
        }

        return $json;
    }

    private function fetchRemoteAssetBytes(string $relativePath, array &$warnings, int $patientNo): ?string
    {
        $baseUrl = rtrim((string) config('services.philarmylab_legacy.url'), '/');
        $endpoint = '/' . ltrim((string) config('services.philarmylab_legacy.endpoint', '/api_sync_export.php'), '/');
        $secret = (string) config('services.philarmylab_legacy.secret');

        if ($baseUrl === '' || $secret === '') {
            return null;
        }

        $query = [
            'resource' => 'file',
            'path' => $relativePath,
        ];
        $timestamp = time();
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $signature = $this->verifier->sign($canonicalQuery, $timestamp, $secret);
        $timeout = (int) config('services.philarmylab_legacy.timeout', 20);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'X-Legacy-Timestamp' => (string) $timestamp,
                    'X-Legacy-Signature' => $signature,
                ])
                ->get($baseUrl . $endpoint . '?' . $canonicalQuery);

            if ($response->successful()) {
                return $response->body();
            }

            $warnings[] = "Remote file missing ({$response->status()}) for patient_no={$patientNo}: {$relativePath}";
        } catch (Throwable $e) {
            $warnings[] = "Failed downloading '{$relativePath}' for patient_no={$patientNo}: {$e->getMessage()}";
        }

        return null;
    }

    private function extractLatestTimestamp(array $row): ?string
    {
        $max = null;
        foreach (config('stations.stations', []) as $cfg) {
            $ts = $this->cleanNullableText($row[$cfg['timestamp']] ?? null);
            if ($ts !== null && ! str_starts_with($ts, '0000-00-00') && ($max === null || $ts > $max)) {
                $max = $ts;
            }
        }

        return $max;
    }
}
