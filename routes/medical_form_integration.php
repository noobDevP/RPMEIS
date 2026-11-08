<?php

use App\Http\Controllers\MedicalFormController;
use App\Http\Controllers\MedicalFormAssessmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff', 'role:DOCTOR_GPE,DOCTOR'])->group(function () {
    Route::get('/medical-form/start/{patient_no}', [MedicalFormController::class, 'start'])->name('medical-form.start');
    Route::get('/medical-form/edit/{medical_form_id}', [MedicalFormController::class, 'startEdit'])->name('medical-form.edit');
    Route::get('/medical-form/step/{medical_form_id}/{step}', [MedicalFormController::class, 'showStep'])->name('medical-form.step');
    Route::post('/medical-form/step/{medical_form_id}/{step}', [MedicalFormController::class, 'saveStep'])->name('medical-form.step.save');
    Route::post('/medical-form/submit/{medical_form_id}', [MedicalFormController::class, 'submitForm'])->name('medical-form.submit');
});

Route::middleware(['staff'])->group(function () {
    Route::get('/medical-form/assessment/{type}/{patient_no}', [MedicalFormAssessmentController::class, 'create'])->name('medical-form.assessment.create');
    Route::post('/medical-form/assessment/{type}/{patient_no}', [MedicalFormAssessmentController::class, 'store'])->name('medical-form.assessment.store');
    Route::get('/medical-form/assessment/{type}/{patient_no}/edit', [MedicalFormAssessmentController::class, 'edit'])->name('medical-form.assessment.edit');
    Route::put('/medical-form/assessment/{type}/{patient_no}', [MedicalFormAssessmentController::class, 'update'])->name('medical-form.assessment.update');
});

Route::middleware(['staff', 'role:DOCTOR_ANY,DOCTOR,EXECUTIVE,RECRUITER,REGISTRAR,ADMIN'])->group(function () {
    Route::get('/medical-form/pdf/{medical_form_id}', [MedicalFormController::class, 'downloadPdf'])->name('medical-form.pdf');
});
