@extends('layouts.medical-form')

@section('wizard-content')
@php
    $isEditMode = isset($form) && $form->isEditInProgress();
@endphp
<div class="card">
    <div class="card-header">Medical Examination Form — AFPRG 165-3661</div>
    <div class="card-body">
        <form method="POST" action="{{ route('medical-form.step.save', ['medical_form_id' => $form->examination_id, 'step' => 3]) }}">
            @csrf
            
            @include('medical_form.partials.step-progress', ['totalSteps' => 3])

            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 4px;">Step 3: Diagnoses, Recommendations &amp; Profiles</h2>
            <p style="font-size: 0.875rem; color: var(--text-muted);">Fields 74–82</p>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                @include('medical_form.partials.field', ['name' => 'summary_defects', 'label' => '74. Summary of Defects and Diagnoses', 'type' => 'textarea', 'rows' => 3, 'required' => true])
                @include('medical_form.partials.field', ['name' => 'recommendations', 'label' => '75. Recommendations / Further Specialist Exam', 'type' => 'textarea', 'rows' => 3])
                @include('medical_form.partials.pulhes-fields')
                @include('medical_form.partials.field', [
                    'name' => 'fitness_status', 'label' => '77. Examinee Fitness Status', 'type' => 'select', 'required' => true,
                    'options' => [
                        'A' => '(A) Fit for General Military Service',
                        'B' => '(B) Fit for Gen Mil Svc w/ Waiver',
                        'C' => '(C) Fit or Limited Svc for 6 Mos.',
                        'D' => '(D) Unfit for General Military Service',
                    ],
                ])
                @include('medical_form.partials.field', ['name' => 'disqualifying_defects', 'label' => '78. Disqualifying Defects by Item Number', 'type' => 'textarea', 'rows' => 2])
                @include('medical_form.partials.rtdo-fields')
            </div>

            @include('medical_form.partials.wizard-nav')
        </form>
    </div>
</div>
@endsection

