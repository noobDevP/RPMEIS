<?php

namespace App\Http\Controllers;

use App\Services\PatientTablePayloadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruiterController extends Controller
{
    public function __construct(
        private PatientTablePayloadService $patientTables,
    ) {}

    public function index(): View
    {
        return view('recruiter.index');
    }

    public function processed(Request $request): View
    {
        try {
            $filter = strtolower(trim($request->query('filter', '')));

            $directory = $this->patientTables->fetchDirectoryData([
                'mode' => 'recruiter',
                'activatedOnly' => false,
                'inactiveApplications' => false,
                'filter' => $filter,
                'batch' => $request->filled('batch') ? (string) $request->get('batch') : null,
                'order' => 'asc',
                'recruiterDisqualifiedMode' => true,
            ]);
            $patients = collect();
            $patientsPayload = $directory['patientsPayload'];
            $batchOptions = $directory['batchOptions'];

            $batches = \App\Models\Application::query()
                ->whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->orderBy('batch')
                ->pluck('batch');
        } catch (\Throwable) {
            $patients = collect();
            $patientsPayload = collect();
            $batchOptions = collect();
            $batches = collect();
            $filter = strtolower(trim($request->query('filter', '')));
        }

        return view('recruiter.processed', compact('patients', 'patientsPayload', 'batchOptions', 'batches', 'filter'));
    }

    public function scanner(): View
    {
        return view('recruiter.scanner');
    }
}





