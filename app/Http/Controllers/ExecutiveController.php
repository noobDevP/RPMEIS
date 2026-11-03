<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\SigningDoctor;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
    ) {}

    
    public function logs(Request $request): View
    {
        $query = \App\Models\ActivityLog::query();

        $filter = $request->query('filter');

        if ($filter === 'logins') {
            $query->whereIn('action', ['Logged In', 'Logged Out']);
        } elseif ($filter === 'medical') {
            $query->whereIn('action', ['Marked Applicant Cleared', 'Marked Applicant GO', 'Marked Applicant Disqualified', 'Marked Applicant as Done', 'Returned Applicant']);
        } elseif ($filter === 'downloads') {
            $query->whereIn('action', ['Downloaded Redform', 'Downloaded Medical Certificate']);
        }

        $activityLogs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('executive.logs', compact('activityLogs', 'filter'));
    }

    public function processed(Request $request): View
    {
        $filter = strtolower(trim($request->query('filter', '')));
        $archive = $request->query('archive') === '1';

        $patients = Patient::query()
            ->with(['applications.examinations.assessments'])
            ->whereHas('applications', fn($q) => $q->whereRaw($archive ? "LOWER(status) = 'inactive'" : "LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->when($filter === 'ongoing', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'NOT DONE')))
            ->when($filter === 'cleared', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'DONE'))->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'PASSED')))
            ->when($filter === 'done', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'COMPLETED')))
            ->when($filter === 'disqualified', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED')))
            ->when($filter === '', function ($q) use ($archive) {
                if (!$archive) {
                    $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', '!=', 'COMPLETED'));
                }
            })
            ->orderByDesc('patient_id')
            ->get();

        $batches = $this->analytics->distinctBatches();
        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
        $categoryMap = config('stations.analytics_categories', []);

        return view('executive.processed', [
            'patients' => $patients,
            'filter' => $filter,
            'archive' => $archive,
            'batches' => $batches,
            'categoryMap' => $categoryMap,
            'signingDoctors' => $signingDoctors,
                    ]);
    }

    /**
     * Determine overall patient status from all 6 station approvals.
     *
     * @param array<int, string|null> $statuses
     */
    public static function patientStatus(array $statuses): array
    {
        $statuses = array_map(
            fn ($s) => strtoupper(trim((string) $s)),
            $statuses,
        );

        if (in_array('NO GO', $statuses, true)) {
            return ['text' => 'DQ', 'class' => 'status-no-go'];
        }

        if (
            in_array('COMPLIANCE', $statuses, true)
            || in_array('WAITING FOR RESULT', $statuses, true)
            || in_array('', $statuses, true)
        ) {
            return ['text' => 'ON GOING', 'class' => 'status-ongoing'];
        }

        $uniqueStatuses = array_unique($statuses);
        if (count($uniqueStatuses) === 1 && reset($uniqueStatuses) === 'GO') {
            return ['text' => 'PASSED', 'class' => 'status-go'];
        }

        return ['text' => 'ON GOING', 'class' => 'status-ongoing'];
    }

    /**
     * Determine display status for a single category/approval column value.
     */
    public static function categoryStatus(?string $value): array
    {
        $val = strtoupper(trim((string) $value));

        return match ($val) {
            'GO' => ['text' => 'GO', 'class' => 'status-approved'],
            'NO GO', 'DQ' => ['text' => 'NO GO', 'class' => 'status-disqualified'],
            'COMPLIANCE' => ['text' => 'COMPLIANCE', 'class' => 'status-compliance'],
            'WAITING FOR RESULT' => ['text' => 'WAITING FOR RESULT', 'class' => 'status-compliance'],
            default => ['text' => 'NOT STARTED', 'class' => 'status-pending'],
        };
    }

    /**
     * Get category counts for analytics modal.
     */
    public function categoryCounts(Request $request): array
    {
        $batch = (string) $request->get('batch');
        $category = (string) $request->get('category');
        $archive = $request->get('archive') === '1';

        if (session('staff.role') === 'EXECUTIVE') {
            \App\Models\ExecutiveLog::query()->create([
                'action' => 'Viewed Analytics (' . $category . ' - Batch: ' . ($batch ?: 'All') . ')',
                'user_username' => session('staff.display_name')
            ]);
        }

        return app(AnalyticsService::class)->categoryCounts($batch, $category, $archive);
    }
}




