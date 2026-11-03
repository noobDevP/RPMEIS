<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CredentialController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DoctorPortalController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\RecruiterController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\SigningDoctorController;
use App\Http\Controllers\ApprovingAuthorityController;
use App\Http\Controllers\StationPageController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\TwoFactorController;

// Database Connection Diagnostic Endpoint
Route::get('/db-diagnostic', function () {
    $result = [
        'database_configured' => config('database.connections.mysql.database'),
        'username_configured' => config('database.connections.mysql.username'),
        'host_configured' => config('database.connections.mysql.host'),
        'connection_status' => 'unknown',
        'error' => null,
        'tables_found' => [],
        'user_table_count' => null,
        'admin_credential_count' => null,
    ];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $result['connection_status'] = 'CONNECTED_SUCCESSFULLY';

        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        foreach ($tables as $t) {
            $arr = (array) $t;
            $result['tables_found'][] = array_values($arr)[0];
        }

        if (in_array('user_table', $result['tables_found'], true)) {
            $result['user_table_count'] = \Illuminate\Support\Facades\DB::table('user_table')->count();
        }
        if (in_array('admin_credential', $result['tables_found'], true)) {
            $result['admin_credential_count'] = \Illuminate\Support\Facades\DB::table('admin_credential')->count();
        }
    } catch (\Throwable $e) {
        $result['connection_status'] = 'CONNECTION_FAILED';
        $result['error'] = $e->getMessage();
    }

    return response()->json($result, $result['connection_status'] === 'CONNECTED_SUCCESSFULLY' ? 200 : 500);
});

// 2FA Routes
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify')->middleware('throttle:login');
Route::get('/2fa/recovery', [TwoFactorController::class, 'recoveryChallenge'])->name('2fa.recovery');
Route::post('/2fa/recovery', [TwoFactorController::class, 'verifyRecovery'])->name('2fa.recovery.verify')->middleware('throttle:login');

Route::get('/2fa/settings', [TwoFactorController::class, 'settings'])->name('2fa.settings');
Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable')->middleware('throttle:login');
Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable')->middleware('throttle:login');
Route::get('/2fa/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('2fa.recovery-codes');
Route::post('/2fa/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery-codes.regenerate')->middleware('throttle:login');



// --- Clean named routes for JavaScript APIs (mirrors legacy-api.php endpoints) ---
Route::middleware(['staff'])->name('api.')->group(function () {
        Route::get('/analytics/chart', [\App\Http\Controllers\Api\AnalyticsController::class, 'chartData'])->name('analytics.chart');
        Route::get('/analytics/batches', [\App\Http\Controllers\Api\AnalyticsController::class, 'batches'])->name('analytics.batches');
        Route::post('/places', [\App\Http\Controllers\Api\PlaceController::class, 'index'])->name('places.index');
        Route::post('/upload-photo', [\App\Http\Controllers\Api\PhotoController::class, 'storeBase64'])->name('photo.upload')->middleware('role:RECRUITER,REGISTRAR');
        Route::get('/next-applicant-no', [\App\Http\Controllers\Api\PatientController::class, 'nextApplicantNo'])->name('patient.next-applicant-no');
        Route::post('/save-patient', [\App\Http\Controllers\Api\PatientController::class, 'store'])->name('patient.save')->middleware('role:RECRUITER,REGISTRAR');
        Route::post('/patients/import', [\App\Http\Controllers\Api\ImportExportController::class, 'import'])->name('patients.import')->middleware('role:RECRUITER,REGISTRAR,DOCTOR,EXECUTIVE');
        Route::get('/patients/export', [\App\Http\Controllers\Api\ImportExportController::class, 'export'])->name('patients.export')->middleware('role:RECRUITER,REGISTRAR,DOCTOR,EXECUTIVE');
        Route::get('/patients/pending', [\App\Http\Controllers\Api\PatientController::class, 'pending'])->name('patient.pending');
        Route::post('/patient/validate-record', [\App\Http\Controllers\Api\PatientController::class, 'validateRecord'])->name('patient.validate-record');
        Route::post('/patient/fetch-record', [\App\Http\Controllers\Api\PatientController::class, 'record'])->name('patient.fetch-record');
        Route::post('/station/{station}/update', [\App\Http\Controllers\Api\StationController::class, 'clerkUpdateClean'])->name('station.update')->middleware('role:CLERK_ANY,DOCTOR_ANY,REGISTRAR');
        Route::post('/station/{station}/upload', [\App\Http\Controllers\Api\StationController::class, 'uploadAttachmentClean'])->name('station.upload')->middleware('role:CLERK_ANY,DOCTOR_ANY,REGISTRAR');
        Route::post('/doctor/{station}/update', [\App\Http\Controllers\Api\StationController::class, 'doctorUpdateClean'])->name('doctor.update-field')->middleware('role:DOCTOR_ANY,EXECUTIVE');
        Route::post('/doctor/{station}/clear', [\App\Http\Controllers\Api\StationController::class, 'clearDoctorStatus'])->name('doctor.clear-status')->middleware('role:DOCTOR_ANY,EXECUTIVE');
        Route::post('/doctor/update-patient', [\App\Http\Controllers\Api\PatientController::class, 'updateDoctorNotes'])->name('doctor.update-patient')->middleware('role:DOCTOR_ANY,EXECUTIVE');
        Route::post('/patients/mark-done', [\App\Http\Controllers\Api\PatientController::class, 'markDone'])->name('patients.mark-done')->middleware('role:REGISTRAR');
        Route::post('/patients/return', [\App\Http\Controllers\Api\PatientController::class, 'returnPatient'])->name('patients.return')->middleware('role:DOCTOR,EXECUTIVE');
        Route::get('/logs/registrar', [\App\Http\Controllers\Api\LogController::class, 'registrar'])->name('logs.registrar')->middleware('role:REGISTRAR,DOCTOR,EXECUTIVE');
        Route::get('/logs/doctor', [\App\Http\Controllers\Api\LogController::class, 'doctors'])->name('logs.doctor')->middleware('role:REGISTRAR,DOCTOR_ANY,EXECUTIVE');
        Route::post('/password/change', [\App\Http\Controllers\Auth\PasswordController::class, 'changePassword'])->name('password.change');
        Route::post('/password/change-chief', [\App\Http\Controllers\Auth\PasswordController::class, 'changePasswordAdmin'])->name('password.change-chief');
        Route::get('/patients/{patientNo}/pdf', [\App\Http\Controllers\Api\PdfController::class, 'show'])->name('patients.pdf');
        Route::post('/patients/med-cert', [\App\Http\Controllers\Api\PdfController::class, 'medCert'])->name('patients.med-cert');
    });

// --- Auth ---
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store')->middleware('throttle:login');
Route::post('/auth/lookup-display-name', [LoginController::class, 'lookupDisplayName'])->name('auth.lookup-display-name')->middleware('throttle:lookup');
Route::match(['get', 'post'], '/logout', [LogoutController::class, 'destroy'])->name('logout');
Route::middleware(['staff'])->post('/password/change-profile', [\App\Http\Controllers\Auth\PasswordController::class, 'changePassword'])->name('password.change');


Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store')->middleware('throttle:login');

    Route::middleware(['admin'])->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::post('/places', [PlaceController::class, 'store'])->name('places.store');
        Route::delete('/places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');

        Route::post('/signing-doctors', [SigningDoctorController::class, 'store'])->name('signing-doctors.store');
        Route::put('/signing-doctors/{signingDoctor}', [SigningDoctorController::class, 'update'])->name('signing-doctors.update');
        Route::delete('/signing-doctors/{signingDoctor}', [SigningDoctorController::class, 'destroy'])->name('signing-doctors.destroy');

        Route::post('/approving-authorities', [ApprovingAuthorityController::class, 'store'])->name('approving-authorities.store');
        Route::put('/approving-authorities/{approvingAuthority}', [ApprovingAuthorityController::class, 'update'])->name('approving-authorities.update');
        Route::delete('/approving-authorities/{approvingAuthority}', [ApprovingAuthorityController::class, 'destroy'])->name('approving-authorities.destroy');

        Route::post('/chief-surgeons', [\App\Http\Controllers\ChiefSurgeonController::class, 'store'])->name('chief-surgeons.store');
        Route::put('/chief-surgeons/{id}', [\App\Http\Controllers\ChiefSurgeonController::class, 'update'])->name('chief-surgeons.update');
        Route::put('/chief-surgeons/{id}/set-default', [\App\Http\Controllers\ChiefSurgeonController::class, 'setAsDefault'])->name('chief-surgeons.set-default');
        Route::delete('/chief-surgeons/{id}', [\App\Http\Controllers\ChiefSurgeonController::class, 'destroy'])->name('chief-surgeons.destroy');

        Route::post('/verify-password', [CredentialController::class, 'verifyPassword'])->name('verify-password');
        Route::put('/credentials', [CredentialController::class, 'update'])->name('credentials.update');
    });
});


Route::get('/', fn () => view('portal-home'))->name('home');


// --- 4B: Recruiter ---
Route::middleware(['staff', 'role:RECRUITER'])->group(function () {
    Route::get('/recruiter', [RecruiterController::class, 'index'])->name('recruiter.dashboard');
    Route::get('/recruiter/get-places', [\App\Http\Controllers\Api\PlaceController::class, 'index'])->name('recruiter.get-places');
});



// --- 4C: Registrar ---
Route::middleware(['staff', 'role:REGISTRAR'])->group(function () {
    Route::get('/registrar', [RegistrarController::class, 'index'])->name('registrar.index');
    
    Route::get('/registrar/activate', [RegistrarController::class, 'activate'])->name('registrar.activate');
    Route::get('/registrar/records', [RegistrarController::class, 'records'])->name('registrar.records');
});

// --- 4D: Station clerk (consolidated) ---
Route::middleware(['staff'])->group(function () {
    Route::get('/stations/{station}', [StationPageController::class, 'show'])
        ->name('station.show')
        ->whereIn('station', array_keys(config('stations.stations', [])));
    Route::get('/stations/{station}/processed', [StationPageController::class, 'processed'])
        ->name('station.processed')
        ->whereIn('station', array_keys(config('stations.stations', [])));
});



// --- 4E: Doctor portal (consolidated) ---
Route::middleware(['staff', 'role:DOCTOR'])->group(function () {
    Route::get('/doctor', [DoctorPortalController::class, 'index'])->name('doctor.index');
    Route::get('/doctor/scanner', [DoctorPortalController::class, 'chiefScanner'])->name('doctor.chief-scanner');
    Route::get('/doctor/completed-forms', [DoctorPortalController::class, 'completedForms'])->name('doctor.completed-forms');
    Route::get('/doctor/cleared', [DoctorPortalController::class, 'cleared'])->name('doctor.cleared');
    Route::get('/doctor/dq', [DoctorPortalController::class, 'disqualified'])->name('doctor.dq');

});

Route::middleware(['staff'])->group(function () {
    Route::get('/doctor/{station}/scanner', [DoctorPortalController::class, 'scanner'])
        ->name('doctor.scanner')
        ->whereIn('station', array_keys(config('stations.stations', [])));
        
    Route::get('/doctor/{station}', [DoctorPortalController::class, 'show'])
        ->name('doctor.show')
        ->whereIn('station', array_keys(config('stations.stations', [])));
});



// --- 4F: Executive (unified dashboard + summarized status) ---
Route::middleware(['staff', 'role:EXECUTIVE'])->group(function () {
    Route::redirect('/executive', '/executive/processed');
    Route::get('/executive/processed', [ExecutiveController::class, 'processed'])->name('executive.processed');
    Route::get('/executive/logs', [ExecutiveController::class, 'logs'])->name('executive.logs');

    Route::post('/executive/change-password', [PasswordController::class, 'changePasswordExecutive'])->name('password.executive');
});

// Analytics API endpoint - accessible by EXECUTIVE and REGISTRAR
Route::middleware(['staff', 'role:EXECUTIVE,REGISTRAR'])->group(function () {
    Route::get('/api/analytics/category-counts', [\App\Http\Controllers\ExecutiveController::class, 'categoryCounts'])
        ->name('analytics.category-counts');
});




// --- Phase 4 audit: ported gaps ---
Route::middleware(['staff', 'role:RECRUITER'])->group(function () {
    Route::get('/recruiter/processed', [\App\Http\Controllers\RecruiterController::class, 'processed'])->name('recruiter.processed');
    Route::get('/recruiter/scanner', [\App\Http\Controllers\RecruiterController::class, 'scanner'])->name('recruiter.scanner');
});

Route::middleware(['staff', 'role:REGISTRAR'])->group(function () {
    Route::get('/registrar/logs', [\App\Http\Controllers\RegistrarController::class, 'logs'])->name('registrar.logs');

    
    // Batch Archives
    Route::get('/registrar/archives', [\App\Http\Controllers\RegistrarController::class, 'archives'])->name('registrar.archives');
    Route::post('/registrar/archives/mark-active', [\App\Http\Controllers\RegistrarController::class, 'markBatchActive'])->name('registrar.archives.mark-active');
    Route::post('/registrar/archives/mark-patient-active', [\App\Http\Controllers\RegistrarController::class, 'markPatientActive'])->name('registrar.archives.mark-patient-active');
    Route::get('/registrar/archives/{batch}', [\App\Http\Controllers\RegistrarController::class, 'viewArchiveBatch'])->name('registrar.archives.view');
    Route::delete('/registrar/archives/delete', [\App\Http\Controllers\RegistrarController::class, 'deleteBatch'])->name('registrar.archives.delete');
});

Route::middleware(['staff', 'role:DOCTOR'])->group(function () {
    Route::get('/doctor/logs', [\App\Http\Controllers\DoctorPortalController::class, 'logs'])->name('doctor.logs');

});

// --- Signing Doctors ---
Route::middleware(['staff', 'role:DOCTOR'])->prefix('signing-doctors')->name('signing-doctors.')->group(function () {
    Route::post('/', [SigningDoctorController::class, 'store'])->name('store');
    Route::put('/{signingDoctor}', [SigningDoctorController::class, 'update'])->name('update');
    Route::delete('/{signingDoctor}', [SigningDoctorController::class, 'destroy'])->name('destroy');
});

Route::middleware(['staff', 'role:DOCTOR'])->prefix('approving-authorities')->name('approving-authorities.')->group(function () {
    Route::post('/', [ApprovingAuthorityController::class, 'store'])->name('store');
    Route::put('/{approvingAuthority}', [ApprovingAuthorityController::class, 'update'])->name('update');
    Route::put('/{approvingAuthority}/set-default', [ApprovingAuthorityController::class, 'setAsDefault'])->name('set-default');
    Route::delete('/{approvingAuthority}', [ApprovingAuthorityController::class, 'destroy'])->name('destroy');
});

Route::middleware(['staff', 'role:DOCTOR'])->prefix('chief-surgeons')->name('chief-surgeons.')->group(function () {
    Route::post('/', [\App\Http\Controllers\ChiefSurgeonController::class, 'store'])->name('store');
    Route::put('/{id}', [\App\Http\Controllers\ChiefSurgeonController::class, 'update'])->name('update');
    Route::put('/{id}/set-default', [\App\Http\Controllers\ChiefSurgeonController::class, 'setAsDefault'])->name('set-default');
    Route::delete('/{id}', [\App\Http\Controllers\ChiefSurgeonController::class, 'destroy'])->name('destroy');
});

// --- Get signing doctors API
Route::middleware(['staff'])->get('/api/signing-doctors', [SigningDoctorController::class, 'index'])->name('api.signing-doctors');

require __DIR__.'/medical_form_integration.php';
