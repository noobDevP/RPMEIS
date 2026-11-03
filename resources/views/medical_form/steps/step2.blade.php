@extends('layouts.medical-form')

@section('wizard-content')
<div class="card">
    <div class="card-header">Medical Examination Form — AFPRG 165-3661</div>
    <div class="card-body">
        <form method="POST" action="{{ route('medical-form.step.save', ['medical_form_id' => $form->examination_id, 'step' => 2]) }}">
            @csrf
            
            @include('medical_form.partials.step-progress', ['totalSteps' => 3])

            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 4px;">Step 2: Clinical Evaluation & Measurements</h2>
            <p style="font-size: 0.875rem; color: var(--text-muted);">Fields 17–43, 51–56</p>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                @include('medical_form.partials.field', ['name' => 'rating_specialty', 'label' => '17. Rating or Specialty of Examinee'])
                @include('medical_form.partials.field', ['name' => 'time_in_capacity_total', 'label' => 'Time in This Capacity (Total)'])
                @include('medical_form.partials.field', ['name' => 'time_last_six_months', 'label' => 'Last Six Months'])
            </div>

            <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: var(--radius); margin-bottom: 24px;">
                <table style="width: 100%; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.4);">
                            <th style="padding: 10px 12px; text-align: left; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color);">#</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color);">Clinical Item</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color);">Normal</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color);">Abnormal</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color);">N.E.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $clinicalItems = [
                                18 => 'HEAD, FACE, NECK, AND SCALP',
                                19 => 'NOSE',
                                20 => 'SINUSES',
                                21 => 'MOUTH AND THROAT',
                                22 => 'EARS - GENERAL (Int. & ext. canals)',
                                23 => 'DRUMS (Perforation)',
                                24 => 'EYES - GENERAL',
                                25 => 'OPHTHALMOSCOPIC',
                                26 => 'PUPILS (Acuity and reaction)',
                                27 => 'OCULAR MOTILITY',
                                28 => 'LUNGS AND CHEST (Breath sounds)',
                                29 => 'HEART (Rate, rhythm, sound)',
                                30 => 'VASCULAR SYSTEM',
                                31 => 'ABDOMEN AND VISCERA',
                                32 => 'ANUS AND RECTUM',
                                33 => 'ENDOCRINE SYSTEM',
                                34 => 'G-U SYSTEM',
                                35 => 'UPPER EXTREMITIES',
                                36 => 'FEET',
                                37 => 'LOWER EXTREMITIES (Except feet)',
                                38 => 'SPINE / OTHER MUSCULOSKELETAL',
                                39 => 'IDENTIFYING BODY MARKS, SCARS, TATTOOS',
                                40 => 'SKIN / LYMPHATICS',
                                41 => 'NEUROLOGIC',
                                42 => 'PSYCHIATRIC',
                            ];
                        @endphp
                        @foreach($clinicalItems as $num => $label)
                            @include('medical_form.partials.clinical-row', [
                                'num' => $num,
                                'label' => $label,
                                'field' => 'clinical_'.$num,
                            ])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 24px;">
                @include('medical_form.partials.field', [
                    'name' => 'clinical_evaluation_notes',
                    'label' => 'Clinical Evaluation Notes',
                    'type' => 'textarea',
                    'rows' => 5,
                ])
            </div>

            <fieldset style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; margin-bottom: 16px;">
                <legend style="font-size: 0.875rem; font-weight: 600;">43. Female Only — Vaginal / Rectal Examination</legend>
                @php
                    $pelvic = old('pelvic_exam', $data['pelvic_exam'] ?? []);
                    if (! is_array($pelvic)) {
                        $pelvic = [];
                    }
                @endphp
                <div style="display: flex; gap: 24px; margin-top: 8px; font-size: 0.875rem;">
                    @foreach(['vaginal' => 'Vaginal', 'rectal' => 'Rectal'] as $val => $lbl)
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="pelvic_exam[]" value="{{ $val }}"
                                @checked(in_array($val, $pelvic, true))
                                style="border-color: var(--border-color);">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 12px; margin-top: 20px;">Measurements</h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
                @include('medical_form.partials.field', ['name' => 'height', 'label' => '51. Height', 'required' => true])
                @include('medical_form.partials.field', ['name' => 'weight', 'label' => '52. Weight', 'required' => true])
                @include('medical_form.partials.field', ['name' => 'color_hair', 'label' => '53. Color Hair'])
                @include('medical_form.partials.field', ['name' => 'color_eye', 'label' => '54. Color Eye'])
                @include('medical_form.partials.field', [
                    'name' => 'build',
                    'label' => '55. Build',
                    'type' => 'select',
                    'required' => true,
                    'options' => ['slender' => 'Slender', 'medium' => 'Medium', 'heavy' => 'Heavy', 'obese' => 'Obese'],
                ])
                @include('medical_form.partials.field', ['name' => 'temperature', 'label' => '56. Temperature (°C)'])
            </div>

            @include('medical_form.partials.wizard-nav')
        </form>
    </div>
</div>
@endsection

