<?php

namespace App\Services;

use App\Models\Application;
use App\Models\MedicalExamination;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientTablePayloadService
{
    /** @var array<string, array<string, true>> */
    protected array $directoryCache = [];

    /** @var array<string, string> */
    protected array $photoUrlCache = [];

    /** @var array<int, string>|null */
    protected ?array $stationKeyById = null;

    protected ?string $assetBaseUrl = null;

    /**
     * Distinct application status values treated as active/inactive so DB indexes remain usable.
     *
     * @return list<string>
     */
    public static function applicationStatuses(bool $inactive = false): array
    {
        return $inactive
            ? ['inactive', 'INACTIVE', 'Inactive']
            : ['active', 'ACTIVE', 'Active'];
    }

    public static function assessmentTypeForStation(?string $station): ?string
    {
        return match ($station) {
            'dental' => 'dental',
            'ecg_xray' => 'ecg',
            'optha_eent' => 'ocular',
            'np' => 'psych',
            'lab_drugtest' => 'lab',
            'gpe' => 'gpe',
            default => null,
        };
    }

    /**
     * Build the base Patient Eloquent query (retained for callers needing Patient models).
     */
    public function patientDirectoryQuery(
        bool $activatedOnly = true,
        ?bool $inactiveApplications = null,
        bool $includeAssessments = false,
        string $order = 'desc'
    ): Builder {
        $relations = [
            'serviceRecord.unit',
            'applications' => fn ($q) => $q->orderByDesc('application_id'),
            'applications.examinations' => fn ($q) => $q->orderByDesc('examination_id'),
            'applications.examinations.clearances.attachments',
        ];

        if ($includeAssessments) {
            $relations[] = 'applications.examinations.assessments';
        }

        $query = Patient::query()->with($relations);

        if ($activatedOnly) {
            $query->where('is_activated', true);
        }

        if ($inactiveApplications !== null) {
            $statuses = self::applicationStatuses($inactiveApplications);
            $query->whereHas('applications', fn ($q) => $q->whereIn('status', $statuses));
        }

        return strtolower($order) === 'asc'
            ? $query->orderBy('patient_id')
            : $query->orderByDesc('patient_id');
    }

    /**
     * Query and assemble JSON-ready patient table payloads directly via lightweight DB queries
     * (skips Eloquent model hydration, Carbon parsing, and per-row asset()/file_exists() overhead).
     *
     * @param  array{
     *   mode: string,
     *   station?: string|null,
     *   activatedOnly?: bool,
     *   inactiveApplications?: bool|null,
     *   filter?: string,
     *   archive?: bool,
     *   batch?: string|null,
     *   order?: string,
     *   examConstraint?: string|null,
     *   excludeCompletedWhenEmpty?: bool,
     *   excludeDoneAndCompletedWhenEmpty?: bool,
     *   recruiterDisqualifiedMode?: bool
     * }  $spec
     * @return array{
     *   patientsPayload: Collection<int, array<string, mixed>>,
     *   batchOptions: Collection<int, string>,
     *   assessmentType: string|null
     * }
     */
    public function fetchDirectoryData(array $spec): array
    {
        $mode = $spec['mode'] ?? 'station';
        $stationFilterKey = $spec['station'] ?? null;
        $assessmentType = self::assessmentTypeForStation($stationFilterKey);
        $activatedOnly = $spec['activatedOnly'] ?? true;
        $inactiveApplications = $spec['inactiveApplications'] ?? false;
        $filter = strtolower(trim((string) ($spec['filter'] ?? '')));
        $archive = (bool) ($spec['archive'] ?? false);
        $batch = trim((string) ($spec['batch'] ?? ''));
        $order = strtolower((string) ($spec['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $examConstraint = $spec['examConstraint'] ?? null;
        $excludeCompletedWhenEmpty = (bool) ($spec['excludeCompletedWhenEmpty'] ?? false);
        $excludeDoneAndCompletedWhenEmpty = (bool) ($spec['excludeDoneAndCompletedWhenEmpty'] ?? false);
        $recruiterDq = (bool) ($spec['recruiterDisqualifiedMode'] ?? false);

        $patientQuery = DB::table('patients as p')
            ->leftJoin('patient_profiles as pp', 'pp.patient_id', '=', 'p.patient_id')
            ->select([
                'p.patient_id',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.birthdate',
                'p.sex',
                'pp.photo_path',
            ]);

        if ($activatedOnly) {
            $patientQuery->where('p.is_activated', 1);
        }

        if ($inactiveApplications !== null) {
            $statuses = self::applicationStatuses($inactiveApplications);
            $patientQuery->whereExists(function ($sub) use ($statuses, $batch) {
                $sub->selectRaw('1')
                    ->from('applications as a')
                    ->whereColumn('a.patient_id', 'p.patient_id')
                    ->whereIn('a.status', $statuses);

                if ($batch !== '' && strtoupper($batch) !== 'ALL') {
                    $sub->where('a.batch', $batch);
                }
            });
        } elseif ($batch !== '' && strtoupper($batch) !== 'ALL') {
            $patientQuery->whereExists(function ($sub) use ($batch) {
                $sub->selectRaw('1')
                    ->from('applications as a')
                    ->whereColumn('a.patient_id', 'p.patient_id')
                    ->where('a.batch', $batch);
            });
        }

        $this->applyExamFilters(
            $patientQuery,
            $filter,
            $archive,
            $examConstraint,
            $excludeCompletedWhenEmpty,
            $excludeDoneAndCompletedWhenEmpty,
            $recruiterDq
        );

        $patientQuery->orderBy('p.patient_id', $order);
        $patientRows = $patientQuery->get();

        if ($patientRows->isEmpty()) {
            return [
                'patientsPayload' => collect(),
                'batchOptions' => collect(),
                'assessmentType' => $assessmentType,
            ];
        }

        $patientIds = $patientRows->pluck('patient_id')->all();
        $chunkSize = DB::connection()->getDriverName() === 'sqlite' ? 400 : 2000;

        // Latest service unit per patient
        $unitByPatient = [];
        foreach (array_chunk($patientIds, $chunkSize) as $chunk) {
            $rows = DB::table('service_records as sr')
                ->join('units as u', 'u.unit_id', '=', 'sr.unit_id')
                ->select(['sr.patient_id', 'u.unit_name'])
                ->whereIn('sr.patient_id', $chunk)
                ->get();
            foreach ($rows as $r) {
                $unitByPatient[$r->patient_id] = $r->unit_name;
            }
        }

        // Latest application per patient (highest application_id, matching $patient->applications->first())
        $latestAppByPatient = [];
        $appIds = [];
        foreach (array_chunk($patientIds, $chunkSize) as $chunk) {
            $rows = DB::table('applications')
                ->select(['application_id', 'patient_id', 'app_code', 'batch', 'purpose', 'status'])
                ->whereIn('patient_id', $chunk)
                ->orderByDesc('application_id')
                ->get();
            foreach ($rows as $r) {
                if (!isset($latestAppByPatient[$r->patient_id])) {
                    $latestAppByPatient[$r->patient_id] = $r;
                    $appIds[] = $r->application_id;
                }
            }
        }

        // Latest examination per application
        $latestExamByApp = [];
        $examIds = [];
        $needsFormData = ($mode === 'registrar');
        if ($appIds !== []) {
            $examCols = [
                'examination_id',
                'application_id',
                'overall_stat',
                'chief_doctor_approval',
                'step',
                'status',
            ];
            if ($needsFormData) {
                $examCols[] = 'form_data';
            }

            foreach (array_chunk($appIds, $chunkSize) as $chunk) {
                $rows = DB::table('medical_examinations')
                    ->select($examCols)
                    ->whereIn('application_id', $chunk)
                    ->orderByDesc('examination_id')
                    ->get();
                foreach ($rows as $r) {
                    if (!isset($latestExamByApp[$r->application_id])) {
                        $latestExamByApp[$r->application_id] = $r;
                        $examIds[] = $r->examination_id;
                    }
                }
            }
        }

        // Station clearances & latest attachment path indexed by [examination_id][station_key]
        $stationKeyById = $this->stationKeyMap();
        $clearancesByExam = [];
        $assessmentTypesByExam = [];

        if ($examIds !== []) {
            foreach (array_chunk($examIds, $chunkSize) as $chunk) {
                $scRows = DB::table('station_clearances')
                    ->select(['examination_id', 'station_id', 'approval_status', 'diagnosis', 'remarks'])
                    ->whereIn('examination_id', $chunk)
                    ->get();
                foreach ($scRows as $sc) {
                    $key = $stationKeyById[$sc->station_id] ?? null;
                    if ($key && !isset($clearancesByExam[$sc->examination_id][$key])) {
                        $clearancesByExam[$sc->examination_id][$key] = [
                            'approval_status' => $sc->approval_status ?? '',
                            'diagnosis' => $sc->diagnosis ?? '',
                            'remarks' => $sc->remarks ?? '',
                            'attachment' => '',
                        ];
                    }
                }

                if ($mode !== 'doctor_station' || $stationFilterKey) {
                    $attRows = DB::table('clearance_attachments as ca')
                        ->join('station_clearances as sc', 'sc.clearance_id', '=', 'ca.clearance_id')
                        ->select(['sc.examination_id', 'sc.station_id', 'ca.file_path'])
                        ->whereIn('sc.examination_id', $chunk)
                        ->orderBy('ca.attachment_id')
                        ->get();
                    foreach ($attRows as $att) {
                        $key = $stationKeyById[$att->station_id] ?? null;
                        if ($key && isset($clearancesByExam[$att->examination_id][$key]) && $att->file_path) {
                            $clearancesByExam[$att->examination_id][$key]['attachment'] = $att->file_path;
                        }
                    }
                }

                if ($mode === 'doctor_station') {
                    $assRows = DB::table('medical_form_assessments')
                        ->select(['examination_id', 'type'])
                        ->whereIn('examination_id', $chunk)
                        ->get();
                    foreach ($assRows as $ass) {
                        $assessmentTypesByExam[$ass->examination_id][$ass->type] = true;
                    }
                }
            }
        }

        $stationsConfig = config('stations.stations', []);
        $assetBase = $this->assetBase();
        $todayY = (int) date('Y');
        $todayMd = (int) date('md');

        $payload = [];
        $batchesSeen = [];

        foreach ($patientRows as $p) {
            $app = $latestAppByPatient[$p->patient_id] ?? null;
            $exam = $app ? ($latestExamByApp[$app->application_id] ?? null) : null;
            $clearances = $exam ? ($clearancesByExam[$exam->examination_id] ?? []) : [];

            $chiefApproval = strtoupper(trim((string) ($exam?->chief_doctor_approval ?? '')));
            $overallStat = strtoupper(trim((string) ($exam?->overall_stat ?? '')));
            $appStatus = strtolower(trim((string) ($app?->status ?? '')));
            $batchVal = $app?->batch ?? '';
            if ($batchVal !== '') {
                $batchesSeen[$batchVal] = true;
            }

            [$displayOverall, $badgeClass, $badgeIcon] = $this->resolveOverallMeta(
                $mode,
                $appStatus,
                $chiefApproval,
                $overallStat
            );

            $fullName = trim(implode(' ', array_filter([
                $p->first_name ?? '',
                $p->middle_name ?? '',
                $p->last_name ?? '',
            ]))) ?: 'N/A';

            $allStationsGo = true;
            $allStationsAssessed = true;
            $stationData = [];

            foreach ($stationsConfig as $key => $config) {
                $eval = $clearances[$key] ?? null;
                $approvalVal = $eval ? (string) $eval['approval_status'] : '';
                $approvalUpper = trim(strtoupper($approvalVal));

                if ($approvalUpper !== 'GO') {
                    $allStationsGo = false;
                }
                if (trim($approvalVal) === '') {
                    $allStationsAssessed = false;
                }

                if ($mode !== 'doctor_station') {
                    $attPath = $eval ? (string) $eval['attachment'] : '';
                    $stationData[$key] = [
                        'label' => $config['label'],
                        'approval' => $approvalVal,
                        'diagnosis' => $eval ? $eval['diagnosis'] : '',
                        'remarks' => $eval ? $eval['remarks'] : '',
                        'attachment' => $attPath !== '' ? $this->fastAsset($assetBase, $attPath) : '',
                        'status' => $this->categoryStatus($approvalVal, $mode),
                    ];
                }
            }

            $base = [
                'patient_no' => (string) $p->patient_id,
                'firstName' => $p->first_name ?? '',
                'middleName' => $p->middle_name ?? '',
                'lastName' => $p->last_name ?? '',
                'full_name' => $fullName,
                'birthdate' => $this->formatBirthdateForPayload($p->birthdate),
                'sex' => $p->sex ?? '',
                'batch' => $batchVal,
                'unitorg' => $unitByPatient[$p->patient_id] ?? '',
                'appcode' => $app?->app_code ?? '',
                'photo_url' => $this->resolvePhotoUrl($p->photo_path ?? null),
                'overall_stat' => $exam?->overall_stat ?? '',
                'chief_doctor_approval' => $exam?->chief_doctor_approval ?? '',
                'display_status' => $displayOverall,
                'status_badge_label' => $displayOverall,
                'status_badge_icon' => $badgeIcon,
            ];

            if ($mode === 'doctor_chief') {
                $payload[] = $base + [
                    'age' => $this->computeAge($p->birthdate, $todayY, $todayMd) ?? '',
                    'doctors_diagnosis' => null,
                    'doctors_remarks' => null,
                    'approving_authority_name' => null,
                    'stations' => $stationData,
                    'can_approve' => $allStationsGo,
                    'can_disqualify' => $allStationsAssessed,
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => true,
                ];
            } elseif ($mode === 'doctor_station') {
                $stationEval = $stationFilterKey ? ($clearances[$stationFilterKey] ?? null) : null;
                $attPath = $stationEval ? (string) $stationEval['attachment'] : '';
                $types = $exam ? ($assessmentTypesByExam[$exam->examination_id] ?? []) : [];

                $payload[] = $base + [
                    'age' => $this->computeAge($p->birthdate, $todayY, $todayMd) ?? '',
                    'status_badge_class' => $badgeClass,
                    'diagnosis' => $stationEval ? $stationEval['diagnosis'] : '',
                    'remarks' => $stationEval ? $stationEval['remarks'] : '',
                    'status' => $this->categoryStatus($stationEval ? (string) $stationEval['approval_status'] : '', 'doctor_station'),
                    'attachment' => $attPath !== '' ? $this->fastAsset($assetBase, $attPath) : '',
                    'can_approve' => $allStationsGo,
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_step' => $exam?->step,
                    'medical_form_status' => $exam?->status,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => true,
                    'current_assessment_exists' => $assessmentType ? isset($types[$assessmentType]) : false,
                    'hearing_assessment_exists' => ($stationFilterKey === 'optha_eent') && isset($types['hearing']),
                    'xray_assessment_exists' => ($stationFilterKey === 'ecg_xray') && isset($types['xray']),
                ];
            } elseif ($mode === 'executive') {
                $payload[] = $base + [
                    'purpose' => $app?->purpose ?? '',
                    'status_badge_class' => $badgeClass,
                    'stations' => $stationData,
                    'chief_doctor' => [
                        'status' => match ($chiefApproval) {
                            'PASSED' => ['text' => 'PASSED', 'class' => 'status-approved'],
                            'FAILED' => ['text' => 'FAILED', 'class' => 'status-disqualified'],
                            default => ['text' => 'PENDING', 'class' => 'status-pending'],
                        },
                        'diagnosis' => '',
                        'remarks' => '',
                    ],
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => true,
                ];
            } elseif ($mode === 'registrar') {
                $formData = [];
                if ($exam && !empty($exam->form_data)) {
                    $decoded = is_array($exam->form_data)
                        ? $exam->form_data
                        : json_decode((string) $exam->form_data, true);
                    if (is_array($decoded)) {
                        $formData = $decoded;
                    }
                }

                $payload[] = $base + [
                    'status_badge_class' => $badgeClass,
                    'stations' => $stationData,
                    'chief_doctor' => [
                        'status' => match ($chiefApproval) {
                            'PASSED' => ['text' => 'CLEARED', 'class' => 'status-approved'],
                            'FAILED' => ['text' => 'DISQUALIFIED', 'class' => 'status-disqualified'],
                            default => ['text' => 'PENDING', 'class' => 'status-pending'],
                        },
                        'diagnosis' => $formData['doctors_diagnosis'] ?? '',
                        'remarks' => $formData['doctors_remarks'] ?? '',
                    ],
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => true,
                ];
            } else {
                $payload[] = $base + [
                    'status_badge_class' => $badgeClass,
                    'stations' => $stationData,
                ];
            }
        }

        $batchList = array_keys($batchesSeen);
        sort($batchList, SORT_STRING);

        return [
            'patientsPayload' => collect($payload),
            'batchOptions' => collect($batchList),
            'assessmentType' => $assessmentType,
        ];
    }

    /**
     * Build JSON-ready patient payloads + sorted batch filter options from an existing Eloquent collection.
     *
     * @param  iterable<Patient>  $patients
     * @param  array{mode?: string, station?: string|null}  $options
     * @return array{patientsPayload: Collection<int, array<string, mixed>>, batchOptions: Collection<int, string>}
     */
    public function buildDirectoryData(iterable $patients, array $options = []): array
    {
        $patientsCollection = $patients instanceof Collection ? $patients : collect($patients);
        $mode = $options['mode'] ?? 'station';
        $stationFilterKey = $options['station'] ?? null;
        $stationsConfig = config('stations.stations', []);
        $stationKeyById = $this->stationKeyMap();
        $assessmentType = self::assessmentTypeForStation($stationFilterKey);
        $assetBase = $this->assetBase();

        $patientsPayload = $patientsCollection->map(function (Patient $patient) use (
            $mode,
            $stationFilterKey,
            $stationsConfig,
            $stationKeyById,
            $assessmentType,
            $assetBase
        ) {
            $latestApp = $this->resolveLatestApplication($patient);
            $exam = $latestApp?->latestExamination();
            $clearancesByKey = $this->indexClearancesByStationKey($exam, $stationKeyById);

            $chiefApproval = strtoupper(trim((string) ($exam?->chief_doctor_approval ?? '')));
            $overallStat = strtoupper(trim((string) ($exam?->overall_stat ?? '')));
            $appStatus = strtolower(trim((string) ($latestApp?->status ?? '')));

            [$displayOverall, $badgeClass, $badgeIcon] = $this->resolveOverallMeta(
                $mode,
                $appStatus,
                $chiefApproval,
                $overallStat
            );

            $fullName = trim(implode(' ', array_filter([
                $patient->first_name ?? '',
                $patient->middle_name ?? '',
                $patient->last_name ?? '',
            ]))) ?: 'N/A';

            $photoUrl = $this->resolvePhotoUrl($patient->photo_path ?? null);

            $allStationsGo = true;
            $allStationsAssessed = true;
            $stationData = [];

            foreach ($stationsConfig as $key => $config) {
                $eval = $clearancesByKey[$key] ?? null;
                $approvalVal = $eval ? (string) $eval->approval_status : '';
                $approvalUpper = trim(strtoupper($approvalVal));

                if ($approvalUpper !== 'GO') {
                    $allStationsGo = false;
                }
                if (trim($approvalVal) === '') {
                    $allStationsAssessed = false;
                }

                if ($mode !== 'doctor_station') {
                    $attachmentPath = $eval ? $this->resolveAttachmentPath($eval) : null;
                    $stationData[$key] = [
                        'label' => $config['label'],
                        'approval' => $approvalVal,
                        'diagnosis' => $eval ? $eval->diagnosis : '',
                        'remarks' => $eval ? $eval->remarks : '',
                        'attachment' => $attachmentPath ? $this->fastAsset($assetBase, $attachmentPath) : '',
                        'status' => $this->categoryStatus($approvalVal, $mode),
                    ];
                }
            }

            $base = [
                'patient_no' => (string) $patient->patient_id,
                'firstName' => $patient->first_name ?? '',
                'middleName' => $patient->middle_name ?? '',
                'lastName' => $patient->last_name ?? '',
                'full_name' => $fullName,
                'birthdate' => $patient->birthdate ?? '',
                'sex' => $patient->sex ?? '',
                'batch' => $latestApp?->batch ?? '',
                'unitorg' => $patient->serviceRecord?->unit?->unit_name ?? '',
                'appcode' => $latestApp?->app_code ?? '',
                'photo_url' => $photoUrl,
                'overall_stat' => $exam?->overall_stat ?? '',
                'chief_doctor_approval' => $exam?->chief_doctor_approval ?? '',
                'display_status' => $displayOverall,
                'status_badge_label' => $displayOverall,
                'status_badge_icon' => $badgeIcon,
            ];

            return match ($mode) {
                'doctor_chief' => $base + [
                    'age' => $patient->age ?? '',
                    'doctors_diagnosis' => $patient->doctors_diagnosis,
                    'doctors_remarks' => $patient->doctors_remarks,
                    'approving_authority_name' => $patient->approving_authority_name,
                    'stations' => $stationData,
                    'can_approve' => $allStationsGo,
                    'can_disqualify' => $allStationsAssessed,
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => $exam?->allAssessmentsCompleted() ?? false,
                ],
                'doctor_station' => $this->mergeDoctorStationFields(
                    $base,
                    $patient,
                    $exam,
                    $clearancesByKey[$stationFilterKey] ?? null,
                    $allStationsGo,
                    $badgeClass,
                    $stationFilterKey,
                    $assessmentType,
                    $assetBase
                ),
                'executive' => $base + [
                    'purpose' => $latestApp?->purpose ?? '',
                    'status_badge_class' => $badgeClass,
                    'stations' => $stationData,
                    'chief_doctor' => [
                        'status' => match ($chiefApproval) {
                            'PASSED' => ['text' => 'PASSED', 'class' => 'status-approved'],
                            'FAILED' => ['text' => 'FAILED', 'class' => 'status-disqualified'],
                            default => ['text' => 'PENDING', 'class' => 'status-pending'],
                        },
                        'diagnosis' => $patient->doctors_diagnosis ?? '',
                        'remarks' => $patient->doctors_remarks ?? '',
                    ],
                    'medical_form_id' => $exam?->examination_id,
                    'medical_form_exists' => $exam !== null,
                    'all_assessments_completed' => $exam?->allAssessmentsCompleted() ?? false,
                ],
                'registrar' => $this->mergeRegistrarFields(
                    $base,
                    $exam,
                    $stationData,
                    $badgeClass,
                    $chiefApproval
                ),
                default => $base + [
                    'status_badge_class' => $badgeClass,
                    'stations' => $stationData,
                ],
            };
        })->values();

        $batchOptions = $patientsPayload->pluck('batch')->filter()->unique()->sort()->values();

        return [
            'patientsPayload' => $patientsPayload,
            'batchOptions' => $batchOptions,
        ];
    }

    /**
     * Resolve relative or external photo paths quickly without repeated disk stat syscalls.
     */
    public function resolvePhotoUrl(?string $rawPath): string
    {
        $path = trim((string) $rawPath);
        if ($path === '') {
            return '/images/blank.jpg';
        }

        if (isset($this->photoUrlCache[$path])) {
            return $this->photoUrlCache[$path];
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '//')
        ) {
            return $this->photoUrlCache[$path] = $path;
        }

        $cleaned = ltrim($path, '/');
        if ($cleaned === '') {
            return $this->photoUrlCache[$path] = '/images/blank.jpg';
        }

        if (str_starts_with($cleaned, 'storage/')) {
            $stripped = substr($cleaned, 8);
            if ($this->cachedFileExists(storage_path('app/public'), $stripped)) {
                return $this->photoUrlCache[$path] = '/' . $cleaned;
            }
        }

        if ($this->cachedFileExists(public_path(), $cleaned)) {
            return $this->photoUrlCache[$path] = '/' . $cleaned;
        }

        if ($this->cachedFileExists(storage_path('app/public'), $cleaned)) {
            return $this->photoUrlCache[$path] = '/storage/' . $cleaned;
        }

        return $this->photoUrlCache[$path] = '/images/blank.jpg';
    }

    protected function applyExamFilters(
        $patientQuery,
        string $filter,
        bool $archive,
        ?string $examConstraint,
        bool $excludeCompletedWhenEmpty,
        bool $excludeDoneAndCompletedWhenEmpty,
        bool $recruiterDq
    ): void {
        if ($examConstraint !== null) {
            $patientQuery->whereExists(function ($sub) use ($examConstraint) {
                $sub->selectRaw('1')
                    ->from('medical_examinations as me')
                    ->join('applications as a2', 'a2.application_id', '=', 'me.application_id')
                    ->whereColumn('a2.patient_id', 'p.patient_id');

                if ($examConstraint === 'not_completed') {
                    $sub->where('me.overall_stat', '!=', 'COMPLETED');
                } elseif ($examConstraint === 'done_not_completed') {
                    $sub->where('me.overall_stat', 'DONE')->where('me.overall_stat', '!=', 'COMPLETED');
                } elseif ($examConstraint === 'failed_not_completed') {
                    $sub->where('me.overall_stat', '!=', 'COMPLETED')->where('me.chief_doctor_approval', 'FAILED');
                }
            });

            return;
        }

        if ($filter === 'ongoing') {
            $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.overall_stat', 'NOT DONE'));
        } elseif ($filter === 'cleared') {
            if ($recruiterDq) {
                $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.overall_stat', 'DONE'));
            } else {
                $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.overall_stat', 'DONE')->where('me.chief_doctor_approval', 'PASSED'));
            }
        } elseif ($filter === 'done') {
            $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.overall_stat', 'COMPLETED'));
        } elseif ($filter === 'unactivated') {
            $patientQuery->where('p.is_activated', 0);
        } elseif ($filter === 'disqualified') {
            if ($recruiterDq) {
                $patientQuery->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('medical_examinations as me')
                        ->join('applications as a2', 'a2.application_id', '=', 'me.application_id')
                        ->whereColumn('a2.patient_id', 'p.patient_id')
                        ->where(function ($w) {
                            $w->where('me.chief_doctor_approval', 'FAILED')
                                ->orWhereExists(function ($sc) {
                                    $sc->selectRaw('1')
                                        ->from('station_clearances as sc')
                                        ->whereColumn('sc.examination_id', 'me.examination_id')
                                        ->where('sc.approval_status', 'NO GO');
                                });
                        });
                });
            } else {
                $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.chief_doctor_approval', 'FAILED'));
            }
        } elseif ($filter === '') {
            if ($excludeDoneAndCompletedWhenEmpty) {
                $this->whereExamExists($patientQuery, fn ($sub) => $sub->whereNotIn('me.overall_stat', ['DONE', 'COMPLETED']));
            } elseif ($excludeCompletedWhenEmpty && !$archive) {
                $this->whereExamExists($patientQuery, fn ($sub) => $sub->where('me.overall_stat', '!=', 'COMPLETED'));
            }
        }
    }

    protected function whereExamExists($patientQuery, callable $callback): void
    {
        $patientQuery->whereExists(function ($sub) use ($callback) {
            $sub->selectRaw('1')
                ->from('medical_examinations as me')
                ->join('applications as a2', 'a2.application_id', '=', 'me.application_id')
                ->whereColumn('a2.patient_id', 'p.patient_id');

            $callback($sub);
        });
    }

    protected function assetBase(): string
    {
        if ($this->assetBaseUrl === null) {
            $this->assetBaseUrl = rtrim(asset(''), '/');
        }

        return $this->assetBaseUrl;
    }

    protected function fastAsset(string $assetBase, string $path): string
    {
        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '//')
        ) {
            return $path;
        }

        return $assetBase . '/' . ltrim($path, '/');
    }

    protected function computeAge(?string $birthdate, int $todayY, int $todayMd): ?int
    {
        if ($birthdate === null || $birthdate === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $birthdate, $m)) {
            $year = (int) $m[1];
            $md = ((int) $m[2]) * 100 + (int) $m[3];
            $age = $todayY - $year;
            if ($todayMd < $md) {
                $age--;
            }

            return $age;
        }

        return null;
    }

    protected function formatBirthdateForPayload(?string $birthdate): string
    {
        if ($birthdate === null || $birthdate === '') {
            return '';
        }

        return $birthdate;
    }

    protected function resolveLatestApplication(Patient $patient): ?Application
    {
        if ($patient->relationLoaded('applications')) {
            return $patient->applications->first();
        }

        return $patient->applications()->latest('application_id')->first();
    }

    /**
     * @return array<int, string>
     */
    protected function stationKeyMap(): array
    {
        if ($this->stationKeyById === null) {
            $this->stationKeyById = DB::table('stations')
                ->pluck('station_key', 'station_id')
                ->all();
        }

        return $this->stationKeyById;
    }

    /**
     * @param  array<int, string>  $stationKeyById
     * @return array<string, mixed>
     */
    protected function indexClearancesByStationKey(?MedicalExamination $exam, array $stationKeyById): array
    {
        if (!$exam) {
            return [];
        }

        $indexed = [];
        foreach ($exam->clearances as $clearance) {
            $key = $stationKeyById[$clearance->station_id] ?? $clearance->station?->station_key;
            if ($key && !isset($indexed[$key])) {
                $indexed[$key] = $clearance;
            }
        }

        return $indexed;
    }

    protected function resolveAttachmentPath($eval): ?string
    {
        if ($eval->relationLoaded('attachments')) {
            return $eval->attachments->last()?->file_path;
        }

        return $eval->attachment;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    protected function resolveOverallMeta(
        string $mode,
        string $appStatus,
        string $chiefApproval,
        string $overallStat
    ): array {
        if ($mode === 'registrar' && $appStatus === 'inactive') {
            $label = 'Archived';
            $class = 'status-pending';
        } elseif ($chiefApproval === 'FAILED') {
            $label = 'Disqualified';
            $class = 'status-disqualified';
        } elseif ($overallStat === 'COMPLETED') {
            $label = 'Done';
            $class = 'status-approved';
        } elseif ($overallStat === 'DONE') {
            $label = 'Cleared';
            $class = 'status-approved';
        } elseif ($overallStat === 'NOT DONE') {
            $label = 'Ongoing';
            $class = 'status-pending';
        } else {
            $label = 'Pending';
            $class = 'status-disqualified';
        }

        if ($chiefApproval === 'FAILED') {
            $icon = '<i class="fas fa-times-circle" style="color: #dc2626;"></i>';
        } elseif ($overallStat === 'COMPLETED' || $overallStat === 'DONE') {
            $icon = '<i class="fas fa-circle-check" style="color: #16a34a;"></i>';
        } else {
            $icon = '<i class="fas fa-hourglass-half" style="color: #ca8a04;"></i>';
        }

        return [$label, $class, $icon];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryStatus(string $value, string $mode): array
    {
        $val = strtoupper(trim($value));

        if ($mode === 'doctor_station') {
            return match ($val) {
                'GO' => ['text' => 'GO', 'class' => 'status-approved', 'icon' => '<i class="fas fa-check-circle" style="color: #16a34a;"></i>'],
                'NO GO', 'DQ' => ['text' => 'NO GO', 'class' => 'status-disqualified', 'icon' => '<i class="fas fa-times-circle" style="color: #dc2626;"></i>'],
                'COMPLIANCE' => ['text' => 'COMPLIANCE', 'class' => 'status-compliance', 'icon' => '<i class="fas fa-clipboard-list" style="color: #ca8a04;"></i>'],
                'WAITING FOR RESULT' => ['text' => 'WAITING FOR RESULT', 'class' => 'status-compliance', 'icon' => '<i class="fas fa-clock" style="color: #ca8a04;"></i>'],
                default => ['text' => 'ONGOING', 'class' => 'status-pending', 'icon' => '<i class="fas fa-hourglass-half" style="color: #64748b;"></i>'],
            };
        }

        $defaultLabel = $mode === 'doctor_chief' ? 'ONGOING' : 'NOT STARTED';

        return match ($val) {
            'GO' => ['text' => 'GO', 'class' => 'status-approved'],
            'NO GO', 'DQ' => ['text' => 'NO GO', 'class' => 'status-disqualified'],
            'COMPLIANCE' => ['text' => 'COMPLIANCE', 'class' => 'status-compliance'],
            'WAITING FOR RESULT' => ['text' => 'WAITING FOR RESULT', 'class' => 'status-compliance'],
            default => ['text' => $defaultLabel, 'class' => 'status-pending'],
        };
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function mergeDoctorStationFields(
        array $base,
        Patient $patient,
        ?MedicalExamination $exam,
        $stationEval,
        bool $allStationsGo,
        string $badgeClass,
        ?string $stationFilterKey,
        ?string $assessmentType,
        string $assetBase
    ): array {
        $attachmentPath = $stationEval ? $this->resolveAttachmentPath($stationEval) : null;
        $assessments = $exam?->assessments;

        return $base + [
            'age' => $patient->age ?? '',
            'status_badge_class' => $badgeClass,
            'diagnosis' => $stationEval ? $stationEval->diagnosis : '',
            'remarks' => $stationEval ? $stationEval->remarks : '',
            'status' => $this->categoryStatus($stationEval ? (string) $stationEval->approval_status : '', 'doctor_station'),
            'attachment' => $attachmentPath ? $this->fastAsset($assetBase, $attachmentPath) : '',
            'can_approve' => $allStationsGo,
            'medical_form_id' => $exam?->examination_id,
            'medical_form_step' => $exam?->step,
            'medical_form_status' => $exam?->status,
            'medical_form_exists' => $exam !== null,
            'all_assessments_completed' => $exam?->allAssessmentsCompleted() ?? false,
            'current_assessment_exists' => $assessmentType && $assessments
                ? $assessments->contains('type', $assessmentType)
                : false,
            'hearing_assessment_exists' => ($stationFilterKey === 'optha_eent') && $assessments
                ? $assessments->contains('type', 'hearing')
                : false,
            'xray_assessment_exists' => ($stationFilterKey === 'ecg_xray') && $assessments
                ? $assessments->contains('type', 'xray')
                : false,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $stationData
     * @return array<string, mixed>
     */
    protected function mergeRegistrarFields(
        array $base,
        ?MedicalExamination $exam,
        array $stationData,
        string $badgeClass,
        string $chiefApproval
    ): array {
        $formData = ($exam && is_array($exam->form_data)) ? $exam->form_data : [];

        return $base + [
            'status_badge_class' => $badgeClass,
            'stations' => $stationData,
            'chief_doctor' => [
                'status' => match ($chiefApproval) {
                    'PASSED' => ['text' => 'CLEARED', 'class' => 'status-approved'],
                    'FAILED' => ['text' => 'DISQUALIFIED', 'class' => 'status-disqualified'],
                    default => ['text' => 'PENDING', 'class' => 'status-pending'],
                },
                'diagnosis' => $formData['doctors_diagnosis'] ?? '',
                'remarks' => $formData['doctors_remarks'] ?? '',
            ],
            'medical_form_id' => $exam?->examination_id,
            'medical_form_exists' => $exam !== null,
            'all_assessments_completed' => $exam?->allAssessmentsCompleted() ?? false,
        ];
    }

    /**
     * Index directory entries once per request so repeated file existence checks stay O(1).
     */
    protected function cachedFileExists(string $rootDir, string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);
        $lastSlash = strrpos($normalized, '/');
        $subDir = $lastSlash === false ? '' : substr($normalized, 0, $lastSlash);
        $fileName = $lastSlash === false ? $normalized : substr($normalized, $lastSlash + 1);

        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return false;
        }

        $targetDir = $subDir === ''
            ? rtrim($rootDir, '\\/')
            : rtrim($rootDir, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDir);

        if (!isset($this->directoryCache[$targetDir])) {
            $entries = [];
            if (is_dir($targetDir)) {
                $scan = @scandir($targetDir);
                if (is_array($scan)) {
                    foreach ($scan as $entry) {
                        if ($entry !== '.' && $entry !== '..') {
                            $entries[$entry] = true;
                        }
                    }
                }
            }
            $this->directoryCache[$targetDir] = $entries;
        }

        return isset($this->directoryCache[$targetDir][$fileName]);
    }
}
