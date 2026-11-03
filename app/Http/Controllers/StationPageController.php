<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\StationRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StationPageController extends Controller
{
    public function show(string $station): View
    {
        $config = StationRegistry::get($station);
        if (session('staff.role') !== $config['clerk_role']) {
            abort(403, 'You do not have permission to access this station.');
        }
        $legacyUpdate = match ($station) {
            'dental' => '/update_patient_data.php',
            'gpe' => '/update_patient_data_gpe.php',
            'np' => '/update_patient_data_np.php',
            'ecg_xray' => '/update_patient_data_ecg_xray.php',
            'optha_eent' => '/update_patient_data_optha_eent.php',
            'lab_drugtest' => '/update_patient_lab_drugtest.php',
            default => '/update_patient_data.php',
        };

        $legacyUpload = match ($station) {
            'dental' => '/upload_dental_attachment.php',
            'gpe' => '/upload_gpe_attachment.php',
            'np' => '/upload_neuro_attachment.php',
            'ecg_xray' => '/upload_ecg_xray_attachment.php',
            'optha_eent' => '/upload_optha_eent_attachment.php',
            'lab_drugtest' => '/upload_lab_drugtest_attachment.php',
            default => '/upload_attachment.php',
        };

        // Clean named route URLs (these call the same controller logic, just cleaner)
        $cleanUpdateUrl = route('api.station.update', ['station' => $station]);
        $cleanUploadUrl = route('api.station.upload', ['station' => $station]);

        return view('stations.show', [
            'station' => $station,
            'stationConfig' => $config,
            'legacyUpdate' => $legacyUpdate,
            'legacyUpload' => $legacyUpload,
            'cleanUpdateUrl' => $cleanUpdateUrl,
            'cleanUploadUrl' => $cleanUploadUrl,
        ]);
    }

    public function processed(Request $request, string $station): View
    {
        $config = StationRegistry::get($station);
        if (session('staff.role') !== $config['clerk_role']) {
            abort(403, 'You do not have permission to access this station.');
        }

        $legacyUpload = match ($station) {
            'dental' => '/upload_dental_attachment.php',
            'gpe' => '/upload_gpe_attachment.php',
            'np' => '/upload_neuro_attachment.php',
            'ecg_xray' => '/upload_ecg_xray_attachment.php',
            'optha_eent' => '/upload_optha_eent_attachment.php',
            'lab_drugtest' => '/upload_lab_drugtest_attachment.php',
            default => '/upload_attachment.php',
        };

        $cleanUpdateUrl = route('api.station.update', ['station' => $station]);
        $cleanUploadUrl = route('api.station.upload', ['station' => $station]);

        $filter = strtolower(trim($request->query('filter', '')));

        $patients = Patient::query()
            ->whereHas('applications', fn($q) => $q->whereRaw("LOWER(status) = 'active'"))
            ->where('is_activated', 1)
            ->when($filter === 'ongoing', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'NOT DONE')))
            ->when($filter === 'cleared', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'DONE'))->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'PASSED')))
            ->when($filter === 'done', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('overall_stat', 'COMPLETED')))
            ->when($filter === 'disqualified', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->where('chief_doctor_approval', 'FAILED')))
            ->when($filter === '', fn ($q) => $q->whereHas('applications.examinations', fn($q) => $q->whereNotIn('overall_stat', ['DONE', 'COMPLETED'])))
            ->orderByDesc('patient_id')
            ->get();

        return view('stations.processed', [
            'station' => $station,
            'stationConfig' => $config,
            'legacyUpload' => $legacyUpload,
            'cleanUpdateUrl' => $cleanUpdateUrl,
            'cleanUploadUrl' => $cleanUploadUrl,
            'filter' => $filter,
            'patients' => $patients,
        ]);
    }
}



