<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Patient;
use App\Models\Station;
use App\Models\StationClearance;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Return all distinct batch values from the applications table.
     */
    public function distinctBatches(): array
    {
        return Application::query()
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch')
            ->all();
    }

    /**
     * Count GO / NO GO / COMPLIANCE / WAITING FOR RESULT per station category.
     * Reads from the normalized station_clearances table.
     */
    public function categoryCounts(?string $batch, string $category, bool $archive = false): array
    {
        $stationKey = $category !== 'All' ? config("stations.analytics_categories.{$category}") : null;

        if ($category !== 'All' && !$stationKey) {
            return ['goCount' => 0, 'noGoCount' => 0, 'complyingCount' => 0, 'waitingCount' => 0];
        }

        // Build base query through the normalized chain:
        // patients → applications → medical_examinations → station_clearances → stations
        $query = DB::table('patients')
            ->join('applications', 'patients.patient_id', '=', 'applications.patient_id')
            ->join('medical_examinations', 'applications.application_id', '=', 'medical_examinations.application_id')
            ->join('station_clearances', 'medical_examinations.examination_id', '=', 'station_clearances.examination_id')
            ->join('stations', 'station_clearances.station_id', '=', 'stations.station_id')
            ->where('patients.is_activated', 1)
            ->whereRaw($archive ? "LOWER(applications.status) = 'inactive'" : "LOWER(applications.status) = 'active'")
            ->selectRaw("SUM(CASE WHEN TRIM(UPPER(station_clearances.approval_status)) = 'GO' THEN 1 ELSE 0 END) AS goCount")
            ->selectRaw("SUM(CASE WHEN TRIM(UPPER(station_clearances.approval_status)) = 'NO GO' THEN 1 ELSE 0 END) AS noGoCount")
            ->selectRaw("SUM(CASE WHEN TRIM(UPPER(station_clearances.approval_status)) = 'COMPLIANCE' THEN 1 ELSE 0 END) AS complyingCount")
            ->selectRaw("SUM(CASE WHEN TRIM(UPPER(station_clearances.approval_status)) = 'WAITING FOR RESULT' THEN 1 ELSE 0 END) AS waitingCount");

        if ($stationKey) {
            $query->where('stations.station_key', $stationKey);
        }

        if (!empty($batch) && $batch !== 'All') {
            $query->where('applications.batch', $batch);
        }

        $row = $query->first();

        return [
            'goCount'       => (int) ($row->goCount ?? 0),
            'noGoCount'     => (int) ($row->noGoCount ?? 0),
            'complyingCount'=> (int) ($row->complyingCount ?? 0),
            'waitingCount'  => (int) ($row->waitingCount ?? 0),
        ];
    }

    /**
     * Summarize patients (PASSED / DQ / ON GOING) grouped by batch.
     * Reads clearance statuses from station_clearances.
     */
    public function summarizeByBatch(?string $batch = null, ?string $sex = null): array
    {
        $stationKeys = array_keys(config('stations.stations', []));

        $query = Patient::query()
            ->with([
                'applications' => fn ($q) => $q->whereRaw("LOWER(status) = 'active'")
                    ->with(['examinations.clearances.station'])
            ])
            ->where('is_activated', 1)
            ->whereHas('applications', fn ($q) => $q->whereRaw("LOWER(status) = 'active'"));

        if (!empty($batch) && $batch !== 'All') {
            $query->whereHas('applications', fn ($q) => $q->where('batch', $batch));
        }

        if (!empty($sex)) {
            $query->where('sex', $sex);
        }

        $summary = [];

        foreach ($query->get() as $patient) {
            $app = $patient->applications->first();
            $batchKey = $app?->batch ?: 'Unknown';
            $exam = $app?->latestExamination();

            if (!isset($summary[$batchKey])) {
                $summary[$batchKey] = ['PASSED' => 0, 'DQ' => 0, 'ON GOING' => 0];
            }

            if (!$exam) {
                $summary[$batchKey]['ON GOING']++;
                continue;
            }

            // Collect approval statuses for all stations
            $statuses = collect($stationKeys)->map(function (string $key) use ($exam) {
                $clearance = $exam->clearances->first(fn ($c) => $c->station?->station_key === $key);
                return strtoupper(trim((string) ($clearance?->approval_status ?? '')));
            })->all();

            if (in_array('NO GO', $statuses, true)) {
                $summary[$batchKey]['DQ']++;
            } elseif (
                in_array('COMPLIANCE', $statuses, true)
                || in_array('WAITING FOR RESULT', $statuses, true)
                || in_array('', $statuses, true)
            ) {
                $summary[$batchKey]['ON GOING']++;
            } elseif (count(array_unique($statuses)) === 1 && reset($statuses) === 'GO') {
                $summary[$batchKey]['PASSED']++;
            } else {
                $summary[$batchKey]['ON GOING']++;
            }
        }

        return $summary;
    }

    /**
     * Overall counts: CLEARED / DISQUALIFIED / DONE / ON GOING.
     * Reads from medical_examinations for chief/overall status.
     */
    public function overallCounts(?string $batch = null): array
    {
        $query = Patient::query()
            ->with([
                'applications' => fn ($q) => $q->whereRaw("LOWER(status) = 'active'")
                    ->with(['examinations'])
            ])
            ->where('is_activated', 1)
            ->whereHas('applications', fn ($q) => $q->whereRaw("LOWER(status) = 'active'"));

        if (!empty($batch) && $batch !== 'All') {
            $query->whereHas('applications', fn ($q) => $q->where('batch', $batch));
        }

        $cleared = 0;
        $disqualified = 0;
        $done = 0;
        $ongoing = 0;

        foreach ($query->get() as $patient) {
            $exam = $patient->applications->first()?->latestExamination();
            $chief   = strtoupper(trim((string) ($exam?->chief_doctor_approval ?? '')));
            $overall = strtoupper(trim((string) ($exam?->overall_stat ?? '')));

            if ($overall === 'COMPLETED') {
                $done++;
            } elseif ($chief === 'FAILED') {
                $disqualified++;
            } elseif ($chief === 'PASSED') {
                $cleared++;
            } else {
                $ongoing++;
            }
        }

        return [
            'CLEARED'       => $cleared,
            'DISQUALIFIED'  => $disqualified,
            'DONE'          => $done,
            'ON GOING'      => $ongoing,
        ];
    }
}
