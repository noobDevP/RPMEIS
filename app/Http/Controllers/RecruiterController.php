<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruiterController extends Controller
{
    public function index(): View
    {
        return view('recruiter.index');
    }

    public function processed(Request $request): View
    {
        try {
            $filter = strtolower(trim($request->query('filter', '')));

            $query = Patient::query()
                ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
                ->when($filter === 'ongoing', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'NOT DONE')))
                ->when($filter === 'cleared', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'DONE')))
                ->when($filter === 'done', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'COMPLETED')))
                ->when($filter === 'unactivated', fn ($q) => $q->where('is_activated', 0))
                ->when($filter === 'disqualified', fn ($q) => $q->where(function ($sq) {
                    $sq->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED'))
                        ->orWhereHas('applications.examinations.clearances', function ($eq) {
                            $eq->where('approval_status', 'NO GO');
                        });
                }));

            if ($request->filled('batch') && $request->get('batch') !== 'ALL') {
                $query->whereHas('applications', fn($q) => $q->where('batch', $request->get('batch')));
            }

            $patients = $query->orderBy('patient_id')->get();

            $batches = \App\Models\Application::query()
                ->whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->orderBy('batch')
                ->pluck('batch');
        } catch (\Throwable) {
            $patients = collect();
            $batches = collect();
        }

        return view('recruiter.processed', compact('patients', 'batches', 'filter'));
    }

    public function scanner(): View
    {
        return view('recruiter.scanner');
    }
}





