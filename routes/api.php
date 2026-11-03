<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\StationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Staff JSON APIs (/api/*)
|--------------------------------------------------------------------------
|
| Mounted under /api with 'web' + 'staff' middleware so session-based
| staff authentication, CSRF protection, and role-based access control apply.
|
*/

Route::middleware(['web', 'staff'])->group(function () {
    // Shared read-only / lookup endpoints for authenticated staff
    Route::get('/patients/pending', [PatientController::class, 'pending']);
    Route::get('/patients/next-applicant-no', [PatientController::class, 'nextApplicantNo']);
    Route::get('/patients/active', [PatientController::class, 'activeRecords']);
    Route::get('/patients/processed', [PatientController::class, 'processed']);
    Route::get('/patients/cleared', [PatientController::class, 'cleared']);
    Route::get('/patients/done', [PatientController::class, 'done']);
    Route::get('/patients/ongoing', [PatientController::class, 'ongoing']);
    Route::get('/patients/disqualified', [PatientController::class, 'disqualified']);
    Route::post('/patients/record', [PatientController::class, 'record']);
    Route::post('/patients/validate', [PatientController::class, 'validateApplicant']);
    Route::post('/patients/validate-record', [PatientController::class, 'validateRecord']);
    Route::get('/patients/{patientNo}/readings', [PatientController::class, 'readings'])->whereNumber('patientNo');
    Route::get('/patients/{patientNo}/pdf', [PdfController::class, 'show'])->whereNumber('patientNo');
    Route::post('/patients/{patientNo}/med-cert', [PdfController::class, 'medCert'])->whereNumber('patientNo');
    Route::get('/patients/{patientNo}', [PatientController::class, 'show'])->whereNumber('patientNo');

    Route::get('/places', [PlaceController::class, 'index']);
    Route::get('/analytics/chart', [AnalyticsController::class, 'chartData']);
    Route::get('/analytics/batches', [AnalyticsController::class, 'batches']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);

    // Recruiter / Registrar intake & photo endpoints
    Route::middleware(['role:RECRUITER,REGISTRAR'])->group(function () {
        Route::post('/patients', [PatientController::class, 'store']);
        Route::delete('/patients/{patientNo}', [PatientController::class, 'destroy'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/reactivate', [PatientController::class, 'reactivate'])->whereNumber('patientNo');
        Route::post('/patients/status', [PatientController::class, 'updateStatus']);
        Route::post('/photos', [PhotoController::class, 'storeBase64']);
    });

    // Registrar-only workflow transitions
    Route::middleware(['role:REGISTRAR'])->group(function () {
        Route::post('/patients/{patientNo}/activate', [PatientController::class, 'activateAccount'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/done', [PatientController::class, 'markDone'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/registrar-return', [PatientController::class, 'registrarReturnPatient'])->whereNumber('patientNo');
    });

    // Chief Doctor / Executive adjudication endpoints
    Route::middleware(['role:DOCTOR,EXECUTIVE'])->group(function () {
        Route::post('/patients/{patientNo}/cleared', [PatientController::class, 'markCleared'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/approve', [PatientController::class, 'approvePatient'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/disqualify', [PatientController::class, 'disqualifyPatient'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/return', [PatientController::class, 'returnPatient'])->whereNumber('patientNo');
        Route::post('/patients/{patientNo}/approving-authority', [PatientController::class, 'updateApprovingAuthority'])->whereNumber('patientNo');
        Route::post('/patients/doctor-notes', [PatientController::class, 'updateDoctorNotes']);
    });

    // Station Clerk & Specialist Doctor examination updates
    Route::middleware(['role:CLERK_ANY,DOCTOR_ANY,REGISTRAR'])->group(function () {
        Route::post('/stations/{station}/clerk', [StationController::class, 'clerkUpdate']);
        Route::post('/stations/{station}/attachment', [StationController::class, 'uploadAttachment']);
    });

    Route::middleware(['role:DOCTOR_ANY,EXECUTIVE'])->group(function () {
        Route::post('/stations/{station}/doctor', [StationController::class, 'doctorUpdate']);
    });

    // Import / Export & Logs & Announcements management
    Route::middleware(['role:RECRUITER,REGISTRAR,DOCTOR,EXECUTIVE'])->group(function () {
        Route::post('/patients/import', [ImportExportController::class, 'import']);
        Route::get('/patients/export', [ImportExportController::class, 'export']);
        Route::get('/logs/registrar', [LogController::class, 'registrar']);
        Route::get('/logs/doctors', [LogController::class, 'doctors']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->whereNumber('id');
    });
});
