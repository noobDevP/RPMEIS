<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalFormAssessmentRequest;
use App\Models\MedicalExamination;
use App\Models\MedicalFormAssessment;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalFormAssessmentController extends Controller
{
    private static function typeToRole(): array
    {
        return [
            'dental' => 'DOCTOR_DENTAL',
            'ecg' => 'DOCTOR_ECG_XRAY',
            'xray' => 'DOCTOR_ECG_XRAY',
            'ocular' => 'DOCTOR_OPTHA_EENT',
            'hearing' => 'DOCTOR_OPTHA_EENT',
            'psych' => 'DOCTOR_NP',
            'lab' => 'DOCTOR_LAB_DRUGTEST',
            'gpe' => 'DOCTOR_GPE',
        ];
    }

    private static function typeToStation(): array
    {
        return [
            'dental' => 'dental',
            'ecg' => 'ecg_xray',
            'xray' => 'ecg_xray',
            'ocular' => 'optha_eent',
            'hearing' => 'optha_eent',
            'psych' => 'np',
            'lab' => 'lab_drugtest',
            'gpe' => 'gpe',
        ];
    }

    private static function typeLabels(): array
    {
        return [
            'dental' => 'Dental',
            'ecg' => 'ECG',
            'xray' => 'Chest X-Ray',
            'ocular' => 'Ocular',
            'hearing' => 'Hearing (ENT)',
            'psych' => 'Neuro / Psych',
            'lab' => 'Laboratory',
            'gpe' => 'General Physical Exam',
        ];
    }

    private function verifyRoleForType(string $type): void
    {
        $requiredRole = self::typeToRole()[$type] ?? null;

        abort_unless($requiredRole, 400, "Unknown assessment type: {$type}");
        abort_unless(session('staff.role') === $requiredRole, 403, 'You are not authorized to perform this assessment.');
    }

    private function getOrCreateMedicalExamination(int $patient_id): MedicalExamination
    {
        $patient = Patient::query()->findOrFail($patient_id);
        $app = $patient->applications()->first();
        if (!$app) {
            abort(404, "No application found for patient.");
        }

        $exam = $app->latestExamination();

        if (! $exam) {
            $exam = MedicalExamination::query()->create([
                'application_id' => $app->application_id,
                'step' => 1,
                'status' => 'draft',
                'form_data' => [],
                'started_by' => session('staff.display_name'),
            ]);
        }

        return $exam;
    }

    public function create(Request $request, string $type, int $patient_id): View
    {
        $this->verifyRoleForType($type);

        $exam = $this->getOrCreateMedicalExamination($patient_id);

        $assessment = MedicalFormAssessment::latestForMedicalForm($exam->examination_id, $type);

        return view('medical_form.assessment.create', [
            'form' => $exam, // keeping variable name $form for blade compatibility
            'type' => $type,
            'typeLabel' => self::typeLabels()[$type] ?? ucfirst($type),
            'assessment' => $assessment,
            'data' => $assessment?->toFormData() ?? [],
        ]);
    }

    public function store(MedicalFormAssessmentRequest $request, string $type, int $patient_id): RedirectResponse
    {
        $this->verifyRoleForType($type);

        $exam = $this->getOrCreateMedicalExamination($patient_id);

        $assessmentData = $request->assessmentData();

        $existing = MedicalFormAssessment::latestForMedicalForm($exam->examination_id, $type);

        $doctorSignature = \App\Models\StaffUser::where('username', session('staff.role'))->first()?->signatureLine() ?? session('staff.display_name');

        if ($existing) {
            $existing->update([
                'assessment_data' => $assessmentData,
                'assessed_by' => $doctorSignature,
                'status' => MedicalFormAssessment::STATUS_COMPLETED,
            ]);
        } else {
            MedicalFormAssessment::query()->create([
                'examination_id' => $exam->examination_id,
                'type' => $type,
                'assessed_by' => $doctorSignature,
                'assessment_data' => $assessmentData,
                'status' => MedicalFormAssessment::STATUS_COMPLETED,
            ]);
        }

        // Immediately sync this signature to the main MedicalExamination form_data
        $formData = $exam->form_data ?? [];
        if ($type === 'gpe') {
            $formData['physician_name'] = $doctorSignature;
            $formData['physician_1_name'] = $doctorSignature;
            $formData['gpe_name'] = $doctorSignature;
            $formData['physician_count'] = '1';
        } else {
            $sigKey = $type === 'ocular' ? 'optometrist_name' : ($type === 'hearing' ? 'ent_name' : ($type === 'psych' ? 'np_name' : $type . '_name'));
            $formData[$sigKey] = $doctorSignature;
        }
        $exam->update(['form_data' => $formData]);

        $station = self::typeToStation()[$type] ?? 'dental';
        $typeLabel = self::typeLabels()[$type] ?? ucfirst($type);

        return redirect()
            ->route('doctor.show', ['station' => $station])
            ->with('success', "{$typeLabel} assessment saved for patient #{$patient_id}.");
    }

    public function edit(Request $request, string $type, int $patient_id): View
    {
        $this->verifyRoleForType($type);

        $exam = $this->getOrCreateMedicalExamination($patient_id);

        $assessment = MedicalFormAssessment::latestForMedicalForm($exam->examination_id, $type);

        abort_if(! $assessment, 404, 'Assessment not found.');

        return view('medical_form.assessment.edit', [
            'form' => $exam,
            'type' => $type,
            'typeLabel' => self::typeLabels()[$type] ?? ucfirst($type),
            'assessment' => $assessment,
            'data' => $assessment->toFormData(),
        ]);
    }

    public function update(MedicalFormAssessmentRequest $request, string $type, int $patient_id): RedirectResponse
    {
        $this->verifyRoleForType($type);

        $exam = $this->getOrCreateMedicalExamination($patient_id);

        $assessment = MedicalFormAssessment::latestForMedicalForm($exam->examination_id, $type);

        abort_if(! $assessment, 404, 'Assessment not found.');

        $assessmentData = $request->assessmentData();

        $assessment->update([
            'assessment_data' => $assessmentData,
            'status' => MedicalFormAssessment::STATUS_COMPLETED,
        ]);

        $station = self::typeToStation()[$type] ?? 'dental';
        $typeLabel = self::typeLabels()[$type] ?? ucfirst($type);

        return redirect()
            ->route('doctor.show', ['station' => $station])
            ->with('success', "{$typeLabel} assessment updated for patient #{$patient_id}.");
    }
}