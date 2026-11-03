@extends('layouts.medical-form')

@section('wizard-content')
<div class="card">
    <div class="card-header">Medical Examination Form — AFPRG 165-3661</div>
    <div class="card-body">
        <form method="POST" action="{{ route('medical-form.step.save', ['medical_form_id' => $form->examination_id, 'step' => 1]) }}">
            @csrf
            
            @include('medical_form.partials.step-progress', ['totalSteps' => 3])

            <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 4px;">Step 1: Personal &amp; Service Information</h2>
            <p style="font-size: 0.875rem; color: var(--text-muted);">Fields 1—16</p>

            @php
                $p = $form->patient;
                $roFullName = !empty(trim((string)(($p->lastName ? $p->lastName . ', ' : '') . $p->firstName . ' ' . $p->middleName)));
                $roGrade = !empty(trim((string)($p->rank . ' ' . $p->serialNo)));
                $roIdentification = !empty(trim((string)($p->applicant_no ?? $p->appcode)));
                $roAddress = !empty(trim((string)$p->currentAddress));
                $roPurpose = !empty(trim((string)$p->purpose));
                $roDateOfExam = !empty(trim((string)$p->dateOfExamination));
                $roSex = !empty(trim((string)$p->sex));
                $roRace = !empty(trim((string)$p->raceSelect));
                $roBranch = !empty(trim((string)$p->branch));
                $roUnit = !empty(trim((string)$p->unitorg));
                $roDob = !empty(trim((string)$p->birthdate));
                $roPob = !empty(trim((string)$p->placeOfBirth));
                $roFacility = !empty(trim((string)$p->placeOfExamination));
            @endphp

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;">
                @include('medical_form.partials.field', ['name' => 'full_name', 'label' => '1. Last Name - First Name - Middle Name', 'required' => true, 'class' => 'md:col-span-2', 'readonly' => $roFullName])
                @include('medical_form.partials.field', ['name' => 'grade_component_position', 'label' => '2. Grade and Component or Position', 'required' => true, 'readonly' => $roGrade])
                @include('medical_form.partials.field', ['name' => 'identification', 'label' => '3. Identification', 'readonly' => $roIdentification])
                @include('medical_form.partials.field', ['name' => 'home_address', 'label' => '4. Home Address', 'type' => 'textarea', 'rows' => 2, 'required' => true, 'class' => 'md:col-span-2', 'readonly' => $roAddress])
                @include('medical_form.partials.field', ['name' => 'purpose_of_examination', 'label' => '5. Purpose of Examination', 'required' => true, 'readonly' => $roPurpose])
                @include('medical_form.partials.field', ['name' => 'date_of_examination', 'label' => '6. Date of Examination', 'type' => 'date', 'required' => true, 'readonly' => $roDateOfExam])
                @include('medical_form.partials.field', [
                    'name' => 'sex', 'label' => '7. Sex', 'type' => 'select', 'required' => true,
                    'options' => ['Male' => 'Male', 'Female' => 'Female'],
                    'readonly' => $roSex
                ])
                @include('medical_form.partials.field', ['name' => 'race', 'label' => '8. Race', 'readonly' => $roRace])
                @include('medical_form.partials.field', [
                    'name' => 'govt_service_type', 'label' => '9. Govt. Service Type', 'type' => 'select',
                    'options' => ['military' => 'Military', 'civilian' => 'Civilian'],
                ])
                @include('medical_form.partials.field', ['name' => 'total_yrs_govt_service', 'label' => '9. Total Yrs. Govt. Service'])
                @include('medical_form.partials.field', ['name' => 'branch_of_service', 'label' => '10. Branch of Service', 'readonly' => $roBranch])
                @include('medical_form.partials.field', ['name' => 'organization_unit', 'label' => '11. Organization Unit / Major Service', 'readonly' => $roUnit])
                @include('medical_form.partials.field', ['name' => 'date_of_birth', 'label' => '12. Date of Birth', 'type' => 'date', 'required' => true, 'readonly' => $roDob])
                @include('medical_form.partials.field', ['name' => 'place_of_birth', 'label' => '13. Place of Birth', 'readonly' => $roPob])
                @include('medical_form.partials.field', ['name' => 'next_of_kin', 'label' => '14. Name, Relationship and Address of Next of Kin', 'type' => 'textarea', 'rows' => 2, 'class' => 'md:col-span-2'])
                @include('medical_form.partials.field', ['name' => 'examining_facility', 'label' => '15. Examining Facility or Examiner and Address', 'type' => 'textarea', 'rows' => 2, 'required' => true, 'class' => 'md:col-span-2', 'readonly' => $roFacility])
                @include('medical_form.partials.field', ['name' => 'others_information', 'label' => '16. Other Information', 'type' => 'textarea', 'rows' => 2, 'class' => 'md:col-span-2'])
            </div>

            @include('medical_form.partials.wizard-nav')
        </form>
    </div>
</div>
@endsection

