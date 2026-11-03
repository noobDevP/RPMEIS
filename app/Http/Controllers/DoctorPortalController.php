<?php

namespace App\Http\Controllers;

use App\Models\DoctorLog;
use App\Models\Patient;
use App\Models\SigningDoctor;
use App\Support\StationRegistry;
use Illuminate\View\View;

class DoctorPortalController extends Controller
{
        public function chiefScanner(): View
    {
        return view('doctor.chief_scanner');
    }

    public function index(): View
    {
        $patients = Patient::query()
            ->with(['applications.examinations.assessments'])
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', '!=', 'COMPLETED'))
            ->orderByDesc('patient_id')
            ->get();

        $stations = config('stations.stations', []);
        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
        $chiefSurgeons = \App\Models\ChiefSurgeon::orderBy('name')->get();

        return view('doctor.index', [
            'patients' => $patients,
            'stations' => $stations,
            'signingDoctors' => $signingDoctors,
            'approvingAuthorities' => $approvingAuthorities,
            'chiefSurgeons' => $chiefSurgeons,
        ]);
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

        $patients = Patient::query()
            ->with(['applications.examinations.assessments'])
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', '!=', 'COMPLETED'))
            ->orderByDesc('patient_id')
            ->get();

        $signingDoctors = SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();

        return view('doctor.show', [
            'station' => $station,
            'stationConfig' => $config,
            'patients' => $patients,
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
        $patients = Patient::query()
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'DONE'))
            ->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', '!=', 'COMPLETED'))
            ->orderByDesc('patient_id')
            ->get();

        $stations = config('stations.stations', []);
        $signingDoctors = \App\Models\SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
        $chiefSurgeons = \App\Models\ChiefSurgeon::orderBy('name')->get();

        return view('doctor.index', [
            'patients' => $patients,
            'stations' => $stations,
            'signingDoctors' => $signingDoctors,
            'approvingAuthorities' => $approvingAuthorities,
            'chiefSurgeons' => $chiefSurgeons,
        ]);
    }

    public function disqualified(): View
    {
        $patients = Patient::query()
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', '!=', 'COMPLETED'))
            ->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED'))
            ->orderByDesc('patient_id')
            ->get();

        $stations = config('stations.stations', []);
        $signingDoctors = \App\Models\SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
        $chiefSurgeons = \App\Models\ChiefSurgeon::orderBy('name')->get();

        return view('doctor.index', [
            'patients' => $patients,
            'stations' => $stations,
            'signingDoctors' => $signingDoctors,
            'approvingAuthorities' => $approvingAuthorities,
            'chiefSurgeons' => $chiefSurgeons,
        ]);
    }

    public function completedForms(): View
    {
        $patients = Patient::query()
            ->with(['applications.examinations.assessments'])
            ->whereHas('applications', fn ($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->whereHas('applications.examinations', fn ($q) => $q->where('overall_stat', 'COMPLETED'))
            ->orderByDesc('patient_id')
            ->get();

        return view('doctor.completed-forms', compact('patients'));
    }
}



