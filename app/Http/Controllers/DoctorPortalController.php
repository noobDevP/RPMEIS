<?php

namespace App\Http\Controllers;

use App\Models\DoctorLog;
use App\Models\SigningDoctor;
use App\Services\PatientTablePayloadService;
use App\Support\StationRegistry;
use Illuminate\View\View;

class DoctorPortalController extends Controller
{
    public function __construct(
        private PatientTablePayloadService $patientTables,
    ) {}

    public function chiefScanner(): View
    {
        return view('doctor.chief_scanner');
    }

    public function index(): View
    {
        return $this->renderChiefQueue('not_completed');
    }

    public function scanner(string $station): View
    {
        $config = StationRegistry::get($station);

        return view('doctor.scanner', [
            'station' => $station,
            'stationConfig' => $config,
        ]);
    }

    public function show(string $station): View
    {
        $config = StationRegistry::get($station);

        $directory = $this->patientTables->fetchDirectoryData([
            'mode' => 'doctor_station',
            'station' => $station,
            'activatedOnly' => true,
            'inactiveApplications' => false,
            'examConstraint' => 'not_completed',
        ]);

        $signingDoctors = SigningDoctor::orderBy('name')->get();

        return view('doctor.show', [
            'station' => $station,
            'stationConfig' => $config,
            'patients' => collect(),
            'patientsPayload' => $directory['patientsPayload'],
            'batchOptions' => $directory['batchOptions'],
            'assessmentType' => $directory['assessmentType'],
            'signingDoctors' => $signingDoctors,
        ]);
    }

    public function logs(): View
    {
        try {
            $patients = DoctorLog::query()
                ->select('patient_no_logs', 'firstName_logs', 'lastName_logs')
                ->distinct()
                ->orderByDesc('patient_no_logs')
                ->get();
        } catch (\Throwable) {
            $patients = collect();
        }

        return view('doctor.logs', compact('patients'));
    }

    public function cleared(): View
    {
        return $this->renderChiefQueue('done_not_completed');
    }

    public function disqualified(): View
    {
        return $this->renderChiefQueue('failed_not_completed');
    }

    public function completedForms(): View
    {
        $patients = $this->patientTables
            ->patientDirectoryQuery(activatedOnly: true, inactiveApplications: false)
            ->whereHas('applications.examinations', fn ($q) => $q->where('overall_stat', 'COMPLETED'))
            ->get();

        return view('doctor.completed-forms', compact('patients'));
    }

    private function renderChiefQueue(string $examConstraint): View
    {
        $directory = $this->patientTables->fetchDirectoryData([
            'mode' => 'doctor_chief',
            'activatedOnly' => true,
            'inactiveApplications' => false,
            'examConstraint' => $examConstraint,
        ]);

        $stations = config('stations.stations', []);
        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
        $chiefSurgeons = \App\Models\ChiefSurgeon::orderBy('name')->get();

        return view('doctor.index', [
            'patients' => collect(),
            'patientsPayload' => $directory['patientsPayload'],
            'batchOptions' => $directory['batchOptions'],
            'stations' => $stations,
            'signingDoctors' => $signingDoctors,
            'approvingAuthorities' => $approvingAuthorities,
            'chiefSurgeons' => $chiefSurgeons,
        ]);
    }
}



