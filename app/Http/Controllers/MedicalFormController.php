<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalFormStepRequest;
use App\Models\MedicalExamination;
use App\Models\MedicalFormAssessment;
use App\Models\Patient;
use App\Services\MedicalFormPdfService;
use App\Support\SpecialtyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class MedicalFormController extends Controller
{
    private const SESSION_KEY = 'medical_form_id';

    private const BP_PULSE_KEYS = [
        'bp_sitting_sys', 'bp_sitting_dias',
        'bp_recumbent_sys', 'bp_recumbent_dias',
        'bp_standing_3min_sys', 'bp_standing_3min_dias',
        'pulse_sitting', 'pulse_after_exercise', 'pulse_2min_after',
        'pulse_recumbent', 'pulse_standing_3min',
    ];

    public function __construct(
        private readonly MedicalFormPdfService $pdfService,
    ) {}

    private function syncPatientDataToForm(MedicalExamination $form, Patient $patient): void
    {
        $currentData = $form->form_data ?? [];
        
        $pFullName = trim(($patient->last_name ? $patient->last_name . ', ' : '') . $patient->first_name . ' ' . $patient->middle_name);
        $currentData['full_name'] = $pFullName ?: ($currentData['full_name'] ?? '');
        
        $pGrade = trim(($patient->serviceRecord?->rank?->rank_name ?? '') . ' ' . ($patient->serviceRecord?->serial_no ?? ''));
        $currentData['grade_component_position'] = $pGrade ?: ($currentData['grade_component_position'] ?? '');
        
        $pId = ($patient->applications->first()?->applicant_no) ?? '';
        $currentData['identification'] = $pId ?: ($currentData['identification'] ?? '');
        
        $currentData['home_address'] = trim((string)($patient->profile?->current_address ?? '')) ?: ($currentData['home_address'] ?? '');
        
        $currentData['purpose_of_examination'] = trim((string)($patient->applications->first()?->purpose ?? '')) ?: ($currentData['purpose_of_examination'] ?? '');
        
        $currentData['date_of_examination'] = $form->exam_date ? $form->exam_date->format('Y-m-d') : ($currentData['date_of_examination'] ?? '');
        
        $pSex = trim((string)$patient->sex);
        if (strtoupper($pSex) === 'M') $pSex = 'Male';
        if (strtoupper($pSex) === 'F') $pSex = 'Female';
        $currentData['sex'] = $pSex ?: ($currentData['sex'] ?? '');
        
        $currentData['race'] = trim((string)($patient->race?->race_name ?? '')) ?: ($currentData['race'] ?? '');
        
        $currentData['branch_of_service'] = trim((string)($patient->serviceRecord?->unit?->branch?->branch_name ?? '')) ?: ($currentData['branch_of_service'] ?? '');
        
        $currentData['organization_unit'] = trim((string)($patient->serviceRecord?->unit?->unit_name ?? '')) ?: ($currentData['organization_unit'] ?? '');
        
        $currentData['date_of_birth'] = $patient->birthdate ? $patient->birthdate->format('Y-m-d') : ($currentData['date_of_birth'] ?? '');

        $form->form_data = $currentData;
    }

    /**
     * Start a new medical form for the given patient.
     */
    public function start(int $patient_id): RedirectResponse
    {
        $patient = \App\Models\Patient::query()->with(["applications" => function($q) {
            $q->whereRaw("LOWER(status) = 'active'")->with("examinations");
        }])->find($patient_id);

        if (! $patient || $patient->applications->isEmpty()) {
            return redirect()->back()->with("warning", "Patient or active application not found.");
        }

        $this->endEditSession();

        $form = $patient->applications->first()->examinations->first();
        if ($form) {
            $this->syncPatientDataToForm($form, $patient);
            $form->status = \App\Models\MedicalExamination::STATUS_DRAFT;
            $form->started_by = session("staff.user_id");
            $form->save();
        } else {
            $form = \App\Models\MedicalExamination::create([
                "application_id" => $patient->applications->first()->application_id,
                "step" => 1,
                "status" => \App\Models\MedicalExamination::STATUS_DRAFT,
                "form_data" => [],
                "started_by" => session("staff.user_id"),
            ]);
            $this->syncPatientDataToForm($form, $patient);
            $form->save();
        }

        session([self::SESSION_KEY => $form->examination_id]);

        return redirect()->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => 1]);
    }

    /**
     * Display a specific step of the wizard.
     */
    public function showStep(Request $request, int $medical_form_id, int $step): View|RedirectResponse
    {
        if ($step < 1 || $step > 3) {
            abort(404);
        }

        $form = MedicalExamination::query()->find($medical_form_id);

        if (! $form) {
            abort(404);
        }

        // If a draft form is being picked up (e.g. via ?draft= query param), take ownership
        if ($form->status === MedicalExamination::STATUS_DRAFT && ! $form->isEditInProgress()) {
            session([self::SESSION_KEY => $form->examination_id]);
        }

        if ($step > 1 && ! session()->has(self::SESSION_KEY) && ! $form->isEditInProgress()) {
            return redirect()
                ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => 1])
                ->with('warning', 'Please start the form from Step 1.');
        }

        // Only enforce step order if not in edit mode
        if (! $form->isEditInProgress() && $step > (int) $form->step) {
            return redirect()
                ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => $form->step])
                ->with('warning', 'Complete each step in order. You cannot skip ahead.');
        }

        $data = $form->form_data ?? [];

        if ($step === 3) {
            $data = $this->prefillSpecialistAssignments($form, $data);
        }

        return view("medical_form.steps.step{$step}", [
            'step' => $step,
            'furthestStep' => $form->isEditInProgress() ? 3 : (int) $form->step,
            'form' => $form,
            'data' => $data,
        ]);
    }

    /**
     * Save data for a given step.
     */
    public function saveStep(MedicalFormStepRequest $request, int $medical_form_id, int $step): RedirectResponse
    {
        if ($step < 1 || $step > 3) {
            abort(404);
        }

        $form = $this->getActiveFormOrRedirect($medical_form_id);
        if (! $form instanceof MedicalExamination) {
            return $form;
        }

        $direction = $request->input('direction', 'next');
        $targetStep = $request->input('target_step', null);

        // Always clear stale old input so saved form_data drives what the user sees
        $request->session()->forget('_old_input');

        $payload = $request->stepPayload();

        $merged = array_merge($form->form_data ?? [], $payload);
        
        if ($step === 3) {
            $merged = $this->prefillSpecialistAssignments($form, $merged);
        }

        if ($step === 3) {
            // Auto-calculate P from ULHES
            $hierarchy = ['1' => 1, '2' => 2, '3' => 3, '3T' => 4];
            $reverseHierarchy = [1 => '1', 2 => '2', 3 => '3', 4 => '3T'];
            $maxVal = 0;
            $hasValue = false;
            foreach (['profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'] as $k) {
                if (!empty($merged[$k]) && isset($hierarchy[$merged[$k]])) {
                    $maxVal = max($maxVal, $hierarchy[$merged[$k]]);
                    $hasValue = true;
                }
            }
            if ($hasValue) {
                $merged['profile_p'] = $reverseHierarchy[$maxVal];
            }
        }

        $form->form_data = $merged;

        if ($direction === 'submit') {
            // Stamp the logged-in GPE doctor's signature into the form
            $gpeDoctor = \App\Models\StaffUser::where('username', session('staff.role'))->first();
            if ($gpeDoctor) {
                $sig = $gpeDoctor->signatureLine();
                $merged['physician_name'] = $sig;
                $merged['physician_1_name'] = $sig;
                $merged['gpe_name'] = $sig;
                $merged['physician_count'] = '1';
            }

            $form->form_data = $merged;
            $form->step = 3;
            $form->status = MedicalExamination::STATUS_COMPLETED;
            
            if ($form->isEditInProgress()) {
                $form->commitEditing();
            } else {
                $form->save();
            }

            session()->forget(self::SESSION_KEY);
            $request->session()->forget('_old_input');

            return redirect()
                ->route('doctor.show', ['station' => 'gpe'])
                ->with('success', 'Medical examination form submitted successfully.');
        }

        if ($direction === 'previous') {
            $form->save();

            return redirect()
                ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => $step - 1]);
        }

        $form->step = max((int) $form->step, $step + 1);
        $form->save();

        if ($targetStep) {
            $targetStep = (int) $targetStep;
            if ($targetStep >= 1 && $targetStep <= 3) {
                return redirect()
                    ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => $targetStep]);
            }
        }

        return redirect()
            ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => $step + 1]);
    }

    /**
     * Final submission (step 5 Submit button goes here).
     */
    public function submitForm(MedicalFormStepRequest $request, int $medical_form_id): RedirectResponse
    {
        $form = $this->getActiveFormOrRedirect($medical_form_id);
        if (! $form instanceof MedicalExamination) {
            return $form;
        }

        $payload = $request->stepPayload();
        $payload['attached_sheets'] = (string) \App\Support\FormSignatures::ATTACHED_SHEETS_COUNT;

        $merged = array_merge($form->form_data ?? [], $payload);
        
        // Auto-fill all signatures, including GPE main physician
        $merged = $this->prefillSpecialistAssignments($form, $merged);

        $form->form_data = $merged;
        $form->step = 3;
        $form->status = MedicalExamination::STATUS_COMPLETED;
        $form->commitEditing();

        session()->forget(self::SESSION_KEY);
        $request->session()->forget('_old_input');

        return redirect()
            ->route('doctor.show', ['station' => 'gpe'])
            ->with('success', 'Medical examination form submitted successfully.');
    }

    /**
     * Begin editing an existing completed form.
     */
    public function startEdit(int $medical_form_id): RedirectResponse
    {
        $this->restoreAbandonedEdits($medical_form_id);

        $form = MedicalExamination::query()->findOrFail($medical_form_id);

        if (! in_array($form->status, [MedicalExamination::STATUS_COMPLETED, MedicalExamination::STATUS_DRAFT])) {
            return redirect()->route('doctor.show', ['station' => 'gpe'])
                ->with('warning', 'Only draft or completed forms can be edited.');
        }

        if ($form->isEditInProgress()) {
            $form->cancelEditing();
        }

        // Always start editing from step 1
        $targetStep = 1;

        $form->beginEditing();
        
        // Sync the latest patient data into the form_data (which is now safely backed up by beginEditing)
        if ($form->patient) {
            $this->syncPatientDataToForm($form, $form->patient);
            $form->save();
        }

        session([self::SESSION_KEY => $form->examination_id]);

        return redirect()
            ->route('medical-form.step', ['medical_form_id' => $form->examination_id, 'step' => $targetStep])
            ->with('success', 'Editing form #'.$form->examination_id.'. Re-submit when finished.');
    }

    /**
     * Download the PDF for a completed form.
     */
    public function downloadPdf(int $medical_form_id): Response
    {
        $form = MedicalExamination::query()->findOrFail($medical_form_id);

        if (! $form->allAssessmentsCompleted()) {
            abort(404, 'All specialty assessments must be completed before downloading the PDF.');
        }

        if (session('staff.role') === 'EXECUTIVE') {
            \App\Models\ExecutiveLog::query()->create([
                'action' => 'Downloaded Red Form',
                'patient_no' => (string) $form->application->patient_id,
                'user_username' => session('staff.display_name')
            ]);
        }

        \App\Services\ActivityLogger::log('Downloaded Redform', $form->application->patient_id);
        $content = $this->pdfService->generate($form);
        $filename = 'AFPRG-165-3661-'.$form->examination_id.'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Restore any abandoned in-progress edits.
     */
    private function restoreAbandonedEdits(?int $exceptId = null): void
    {
        $exceptId ??= session(self::SESSION_KEY);

        $query = MedicalExamination::query()
            ->where('status', 'draft')
            ->when($exceptId, fn ($query) => $query->where('examination_id', '!=', $exceptId));

        if (session('staff.user_id')) {
            $query->where('started_by', session('staff.user_id'));
        }

        $query->each(fn (MedicalExamination $form) => $form->cancelEditing());
    }

    /**
     * @return MedicalExamination|RedirectResponse
     */
    private function getActiveFormOrRedirect(int $medical_form_id): MedicalExamination|RedirectResponse
    {
        $sessionFormId = session(self::SESSION_KEY);

        if (! $sessionFormId || $sessionFormId !== $medical_form_id) {
            return redirect()
                ->route('medical-form.step', ['medical_form_id' => $medical_form_id, 'step' => 1])
                ->with('warning', 'Your session expired. Please start again.');
        }

        $form = MedicalExamination::query()->find($medical_form_id);

        if (! $form || ! $this->isActiveForm($form)) {
            session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('medical-form.step', ['medical_form_id' => $medical_form_id, 'step' => 1])
                ->with('warning', 'No active form found. Starting a new form.');
        }

        return $form;
    }

    private function isActiveForm(MedicalExamination $form): bool
    {
        if ($form->status === MedicalExamination::STATUS_DRAFT && ! $form->isEditInProgress()) {
            return true;
        }

        return $form->isEditInProgress();
    }

    private function endEditSession(): void
    {
        $id = session(self::SESSION_KEY);

        if ($id) {
            $form = MedicalExamination::query()->find($id);
            if ($form?->isEditInProgress()) {
                $form->cancelEditing();
            }
        }

        session()->forget(self::SESSION_KEY);
        $this->restoreAbandonedEdits();
    }

    /**
     * Pre-fill specialist assignment fields from completed assessments.
     * Auto-enables and sets the specialist name for each specialty type
     * where a completed assessment exists.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prefillSpecialistAssignments(MedicalExamination $form, array $data): array
    {
        $types = [
            SpecialtyType::DENTAL,
            SpecialtyType::OCULAR,
            SpecialtyType::HEARING,
            SpecialtyType::PSYCH,
            SpecialtyType::ECG,
            SpecialtyType::LAB,
            SpecialtyType::XRAY,
        ];

        foreach ($types as $specialty) {
            $assessment = MedicalFormAssessment::latestForMedicalForm($form->examination_id, $specialty);

            if (! $assessment) {
                continue;
            }

            $assessedBy = $assessment->assessed_by;

            if (! $assessedBy) {
                continue;
            }

            try {
                $enabledKey = SpecialtyType::enabledKey($specialty);
                $nameKey = SpecialtyType::nameKey($specialty);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $data[$enabledKey] = '1';
            $data[$nameKey] = $assessedBy;
        }
        
        // Only prefill GPE physician name if not already set in form_data
        if (empty($data['physician_name'])) {
            $gpeAssessment = MedicalFormAssessment::latestForMedicalForm($form->examination_id, 'gpe');
            $gpeName = $gpeAssessment->assessed_by ?? '';

            if (!empty($gpeName)) {
                $data['physician_count'] = '1';
                $data['physician_name'] = $gpeName;
                $data['physician_1_name'] = $gpeName;
                $data['gpe_name'] = $gpeName;
            }
        }

        return $data;
    }
}











