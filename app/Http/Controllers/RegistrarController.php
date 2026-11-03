<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\RegistrarLog;
use App\Models\SigningDoctor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarController extends Controller
{
            

    public function activate(): View
    {
        return view('registrar.activate');
    }

    public function index(): View
    {
        try {
            $batches = \App\Models\Application::query()
                ->whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->orderBy('batch')
                ->pluck('batch');
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::error($e->getMessage());
            $batches = collect();
        }

        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $categoryMap = config('stations.analytics_categories', []);

        return view('registrar.index', compact('batches', 'categoryMap', 'signingDoctors'));
    }

    public function records(Request $request): View
    {
        try {
            $batches = \App\Models\Application::query()
                ->whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->orderBy('batch')
                ->pluck('batch');

            $filter = strtolower(trim($request->query('filter', '')));

            $patients = Patient::query()
                ->with(['applications.examinations.assessments'])
                ->where('is_activated', 1)
                ->when($filter === 'archived', 
                    fn ($q) => $q->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'inactive'")),
                    fn ($q) => $q->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
                )
                ->when($filter === 'ongoing', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'NOT DONE')))
                ->when($filter === 'cleared', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'DONE'))->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'PASSED')))
                ->when($filter === 'done', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'COMPLETED')))
                ->when($filter === 'disqualified', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED')))
                ->orderByDesc('patient_id')
                ->get();
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::error($e->getMessage());
            $batches = collect();
            $patients = collect();
            $filter = strtolower(trim($request->query('filter', '')));
        }

        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $categoryMap = config('stations.analytics_categories', []);

        return view('registrar.records', compact('patients', 'batches', 'filter', 'categoryMap', 'signingDoctors'));
    }

    public function logs(): View
    {
        try {
            $patients = RegistrarLog::query()
                ->select('patient_no_logs', 'firstName_logs', 'lastName_logs')
                ->distinct()
                ->orderByDesc('patient_no_logs')
                ->get();
        } catch (\Throwable) {
            $patients = collect();
        }

        return view('registrar.logs', compact('patients'));
    }

    public function archives(): View
    {
        // Fetch batches where all patients are 'inactive'
        $batches = \App\Models\Application::select('batch')
            ->selectRaw('COUNT(*) as total_patients')
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'active' THEN 1 ELSE 0 END) as active_count")
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->groupBy('batch')
            ->having('active_count', '=', 0)
            ->get();

        return view('registrar.archives', compact('batches'));
    }

    public function viewArchiveBatch(string $batch): View
    {
        $categoryMap = config('stations.analytics_categories', []);

        $patients = Patient::query()
            ->with(['evaluations'])
            ->where('batch', $batch)
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'inactive'"))
            ->get();

        return view('registrar.view_batch', compact('batch', 'patients', 'categoryMap'));
    }

    public function markBatchActive(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'batch' => 'required|string'
        ]);

        $batch = $request->input('batch');
        $updated = \App\Models\Application::where('batch', $batch)->update(['status' => 'active']);

        return response()->json(['success' => true, 'message' => "Batch $batch marked as active. $updated records restored."]);
    }

    public function markPatientActive(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'patient_no' => 'required|string'
        ]);

        $patientNo = $request->input('patient_no');
        $updated = \App\Models\Application::where('patient_id', $patientNo)->update(['status' => 'active']);
        if ($updated) \App\Services\ActivityLogger::log('Returned Applicant to Active from Archive', $patientNo);

        if ($updated) {
            return response()->json(['success' => true, 'message' => "Applicant $patientNo has been marked as active."]);
        }

        return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
    }

    public function deleteBatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'batch' => 'required|string'
        ]);

        $batch = $request->input('batch');
        $deleted = \App\Models\Application::where('batch', $batch)->delete();

        return response()->json(['success' => true, 'message' => "Batch $batch and its $deleted records were permanently deleted."]);
    }
}











