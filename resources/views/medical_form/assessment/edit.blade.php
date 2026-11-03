@extends('layouts.portal')

@section('title', $typeLabel . ' Assessment')

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rightBoxes = document.querySelectorAll('input[name$="_right"][name^="audiometer_"]');
    const leftBoxes = document.querySelectorAll('input[name$="_left"][name^="audiometer_"]');
    
    rightBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                rightBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
    
    leftBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                leftBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
});
</script>
@endsection

@section('header-title', 'ARO VISAYAS — ' . strtoupper($typeLabel) . ' ASSESSMENT')

@section('header-actions')
<a href="{{ route('doctor.show', [
    'station' => [
        'dental' => 'dental',
        'ecg' => 'ecg_xray',
        'ocular' => 'optha_eent',
        'hearing' => 'optha_eent',
        'psych' => 'np',
        'lab' => 'lab_drugtest',
    ][$type] ?? 'dental'
]) }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back to Station
</a>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rightBoxes = document.querySelectorAll('input[name$="_right"][name^="audiometer_"]');
    const leftBoxes = document.querySelectorAll('input[name$="_left"][name^="audiometer_"]');
    
    rightBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                rightBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
    
    leftBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                leftBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
});
</script>
@endsection

@section('content')
<div class="card" style="width: 100%; max-width: 1400px; margin: 0 auto;">

    {{-- Patient Info Header --}}
    @if($form->patient)
    <div class="card-header" style="background: rgba(255, 255, 255, 0.4);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.875rem;">
            <div>
                <strong style="color: var(--text-muted);">Patient No.</strong><br>
                {{ $form->patient->patient_id }}
            </div>
            <div>
                <strong style="color: var(--text-muted);">Applicant Code</strong><br>
                {{ $form->patient->appcode ?? 'N/A' }}
            </div>
            <div>
                <strong style="color: var(--text-muted);">Name</strong><br>
                {{ $form->patient->lastName }}, {{ $form->patient->firstName }} {{ $form->patient->middleName }}
            </div>
            <div>
                <strong style="color: var(--text-muted);">Form ID</strong><br>
                #{{ $form->examination_id }}
            </div>
        </div>
    </div>
    @endif

    <div class="card-header">
        <i class="fas fa-clipboard-check" style="margin-right: 8px; color: var(--primary-color);"></i>
        {{ $typeLabel }} Assessment Form
    </div>

    <div class="card-body">

        {{-- Success / Error Messages --}}
        @if(session('success'))
            <div class="badge badge-success" style="display: block; padding: 12px; margin-bottom: 16px; font-size: 0.9rem;">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="badge badge-danger" style="display: block; padding: 12px; margin-bottom: 16px; font-size: 0.9rem;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('medical-form.assessment.update', ['type' => $type, 'patient_no' => $form->patient->patient_id]) }}">
            @method('PUT')
            @csrf

            {{-- ========================== --}}
            {{-- ECG ASSESSMENT --}}
            {{-- ========================== --}}
            @if($type === 'ecg')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Blood Pressure
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">

                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Sitting (Sys)</label>
                        <input type="text" name="bp_sitting_sys" class="input-modern" value="{{ old('bp_sitting_sys', $data['bp_sitting_sys'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Sitting (Dias)</label>
                        <input type="text" name="bp_sitting_dias" class="input-modern" value="{{ old('bp_sitting_dias', $data['bp_sitting_dias'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Recumbent (Sys)</label>
                        <input type="text" name="bp_recumbent_sys" class="input-modern" value="{{ old('bp_recumbent_sys', $data['bp_recumbent_sys'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Recumbent (Dias)</label>
                        <input type="text" name="bp_recumbent_dias" class="input-modern" value="{{ old('bp_recumbent_dias', $data['bp_recumbent_dias'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Standing 3min (Sys)</label>
                        <input type="text" name="bp_standing_3min_sys" class="input-modern" value="{{ old('bp_standing_3min_sys', $data['bp_standing_3min_sys'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Standing 3min (Dias)</label>
                        <input type="text" name="bp_standing_3min_dias" class="input-modern" value="{{ old('bp_standing_3min_dias', $data['bp_standing_3min_dias'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                </div>

                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Pulse Rate
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Sitting</label>
                        <input type="text" name="pulse_sitting" class="input-modern" value="{{ old('pulse_sitting', $data['pulse_sitting'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">After Exercise</label>
                        <input type="text" name="pulse_after_exercise" class="input-modern" value="{{ old('pulse_after_exercise', $data['pulse_after_exercise'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">2min After Exercise</label>
                        <input type="text" name="pulse_2min_after" class="input-modern" value="{{ old('pulse_2min_after', $data['pulse_2min_after'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Recumbent</label>
                        <input type="text" name="pulse_recumbent" class="input-modern" value="{{ old('pulse_recumbent', $data['pulse_recumbent'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem;">Standing 3min</label>
                        <input type="text" name="pulse_standing_3min" class="input-modern" value="{{ old('pulse_standing_3min', $data['pulse_standing_3min'] ?? '') }}" placeholder="enter" maxlength="20">
                    </div>
                </div>


                
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    EKG
                </div>
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">EKG</label>
                    <textarea name="ekg" class="input-modern" rows="3" style="max-width: 100%; resize: vertical;">{{ old('ekg', $data['ekg'] ?? '') }}</textarea>
                </div>

            {{-- ========================== --}}
            {{-- GPE ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'gpe')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Physical Profile (GPE)
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Upper Extremities (U)</label>
                        <select name="profile_u" class="select-modern" style="width: 100%;">
                            <option value="">— Select —</option>
                            @foreach(['1', '2', '3', '3T'] as $val)
                                <option value="{{ $val }}" {{ old('profile_u', $data['profile_u'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Lower Extremities (L)</label>
                        <select name="profile_l" class="select-modern" style="width: 100%;">
                            <option value="">— Select —</option>
                            @foreach(['1', '2', '3', '3T'] as $val)
                                <option value="{{ $val }}" {{ old('profile_l', $data['profile_l'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($patient->gender === 'Female' || $patient->gender === 'F' || strtolower($patient->gender) === 'female')
                        <div>
                            <label class="field-label">Pelvic Vaginal/Rectal (Female)</label>
                            <select name="pelvic_exam" class="select-modern" style="width: 100%;">
                                <option value="">— Select —</option>
                                <option value="normal" {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="abnormal" {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'abnormal' ? 'selected' : '' }}>Abnormal</option>
                                <option value="n.e." {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'n.e.' ? 'selected' : '' }}>N.E.</option>
                            </select>
                        </div>
                    @endif
                </div>

            {{-- ========================== --}}
            {{-- GPE ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'gpe')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Physical Profile (GPE)
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Upper Extremities (U)</label>
                        <select name="profile_u" class="select-modern" style="width: 100%;">
                            <option value="">— Select —</option>
                            @foreach(['1', '2', '3', '3T'] as $val)
                                <option value="{{ $val }}" {{ old('profile_u', $data['profile_u'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Lower Extremities (L)</label>
                        <select name="profile_l" class="select-modern" style="width: 100%;">
                            <option value="">— Select —</option>
                            @foreach(['1', '2', '3', '3T'] as $val)
                                <option value="{{ $val }}" {{ old('profile_l', $data['profile_l'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($patient->gender === 'Female' || $patient->gender === 'F' || strtolower($patient->gender) === 'female')
                        <div>
                            <label class="field-label">Pelvic Vaginal/Rectal (Female)</label>
                            <select name="pelvic_exam" class="select-modern" style="width: 100%;">
                                <option value="">— Select —</option>
                                <option value="normal" {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="abnormal" {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'abnormal' ? 'selected' : '' }}>Abnormal</option>
                                <option value="n.e." {{ old('pelvic_exam', $data['pelvic_exam'] ?? '') === 'n.e.' ? 'selected' : '' }}>N.E.</option>
                            </select>
                        </div>
                    @endif
                </div>

            {{-- ========================== --}}
            {{-- DENTAL ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'dental')
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">
                        Dental Classification <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="dental_classification" class="select-modern" style="max-width: 400px;">
                        <option value="">— Select Classification —</option>
                        @foreach(['Class 1', 'Class 2', 'Class 3', 'Class 4'] as $cls)
                            <option value="{{ $cls }}" {{ old('dental_classification', $data['dental_classification'] ?? '') === $cls ? 'selected' : '' }}>
                                {{ $cls }}
                            </option>
                        @endforeach
                    </select>
                    @error('dental_classification')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">Dental Remarks / Detail</label>
                    <textarea name="dental_remarks_detail" class="input-modern" rows="5" style="max-width: 100%; resize: vertical;" placeholder="enter">{{ old('dental_remarks_detail', $data['dental_remarks_detail'] ?? '') }}</textarea>
                    @error('dental_remarks_detail')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

            {{-- ========================== --}}
                        {{-- XRAY ASSESSMENT --}}
            @elseif($type === 'xray')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Chest X-Ray
                </div>
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">Chest X-Ray Results</label>
                    <textarea name="chest_xray" class="input-modern" rows="4" style="max-width: 100%; resize: vertical;" placeholder="enter">{{ old('chest_xray', $data['chest_xray'] ?? '') }}</textarea>
                </div>
            {{-- OCULAR ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'ocular')
                {{-- Distant Vision --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Distant Vision
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Right (UC)</label>
                        <input type="text" name="vision_distant_right" class="input-modern" value="{{ old('vision_distant_right', $data['vision_distant_right'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Right (Corr)</label>
                        <input type="text" name="vision_distant_right_corr" class="input-modern" value="{{ old('vision_distant_right_corr', $data['vision_distant_right_corr'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Left (UC)</label>
                        <input type="text" name="vision_distant_left" class="input-modern" value="{{ old('vision_distant_left', $data['vision_distant_left'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Left (Corr)</label>
                        <input type="text" name="vision_distant_left_corr" class="input-modern" value="{{ old('vision_distant_left_corr', $data['vision_distant_left_corr'] ?? '') }}" placeholder="enter">
                    </div>
                </div>

                {{-- Refraction --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Refraction
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 24px;">
                    <div><label class="field-label">R — BY</label><input type="text" name="refraction_right_by" class="input-modern" value="{{ old('refraction_right_by', $data['refraction_right_by'] ?? '') }}"></div>
                    <div><label class="field-label">R — S</label><input type="text" name="refraction_right_s" class="input-modern" value="{{ old('refraction_right_s', $data['refraction_right_s'] ?? '') }}"></div>
                    <div><label class="field-label">R — Cx</label><input type="text" name="refraction_right_cx" class="input-modern" value="{{ old('refraction_right_cx', $data['refraction_right_cx'] ?? '') }}"></div>
                    <div><label class="field-label">L — BY</label><input type="text" name="refraction_left_by" class="input-modern" value="{{ old('refraction_left_by', $data['refraction_left_by'] ?? '') }}"></div>
                    <div><label class="field-label">L — S</label><input type="text" name="refraction_left_s" class="input-modern" value="{{ old('refraction_left_s', $data['refraction_left_s'] ?? '') }}"></div>
                    <div><label class="field-label">L — Cx</label><input type="text" name="refraction_left_cx" class="input-modern" value="{{ old('refraction_left_cx', $data['refraction_left_cx'] ?? '') }}"></div>
                </div>

                {{-- Near Vision --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Near Vision
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px;">
                    <div><label class="field-label">R — Color TO</label><input type="text" name="near_vision_right_color_to" class="input-modern" value="{{ old('near_vision_right_color_to', $data['near_vision_right_color_to'] ?? '') }}"></div>
                    <div><label class="field-label">R — BY</label><input type="text" name="near_vision_right_by" class="input-modern" value="{{ old('near_vision_right_by', $data['near_vision_right_by'] ?? '') }}"></div>
                    <div><label class="field-label">L — Color TO</label><input type="text" name="near_vision_left_color_to" class="input-modern" value="{{ old('near_vision_left_color_to', $data['near_vision_left_color_to'] ?? '') }}"></div>
                    <div><label class="field-label">L — BY</label><input type="text" name="near_vision_left_by" class="input-modern" value="{{ old('near_vision_left_by', $data['near_vision_left_by'] ?? '') }}"></div>
                </div>

                {{-- Heterophoria --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Heterophoria
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 24px;">
                    <div><label class="field-label">Distance</label><input type="text" name="heterophoria_distance" class="input-modern" value="{{ old('heterophoria_distance', $data['heterophoria_distance'] ?? '') }}"></div>
                    <div><label class="field-label">ES</label><input type="text" name="heterophoria_es" class="input-modern" value="{{ old('heterophoria_es', $data['heterophoria_es'] ?? '') }}"></div>
                    <div><label class="field-label">EX</label><input type="text" name="heterophoria_ex" class="input-modern" value="{{ old('heterophoria_ex', $data['heterophoria_ex'] ?? '') }}"></div>
                    <div><label class="field-label">RH</label><input type="text" name="heterophoria_rh" class="input-modern" value="{{ old('heterophoria_rh', $data['heterophoria_rh'] ?? '') }}"></div>
                    <div><label class="field-label">LH</label><input type="text" name="heterophoria_lh" class="input-modern" value="{{ old('heterophoria_lh', $data['heterophoria_lh'] ?? '') }}"></div>
                    <div><label class="field-label">Prism</label><input type="text" name="heterophoria_prism" class="input-modern" value="{{ old('heterophoria_prism', $data['heterophoria_prism'] ?? '') }}"></div>
                    <div><label class="field-label">DV</label><input type="text" name="heterophoria_dv" class="input-modern" value="{{ old('heterophoria_dv', $data['heterophoria_dv'] ?? '') }}"></div>
                    <div><label class="field-label">Conv</label><input type="text" name="heterophoria_conv" class="input-modern" value="{{ old('heterophoria_conv', $data['heterophoria_conv'] ?? '') }}"></div>
                    <div><label class="field-label">PC</label><input type="text" name="heterophoria_pc" class="input-modern" value="{{ old('heterophoria_pc', $data['heterophoria_pc'] ?? '') }}"></div>
                    <div><label class="field-label">PD</label><input type="text" name="heterophoria_pd" class="input-modern" value="{{ old('heterophoria_pd', $data['heterophoria_pd'] ?? '') }}"></div>
                </div>

                {{-- Accommodation --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Accommodation
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div><label class="field-label">Right</label><input type="text" name="accommodation_right" class="input-modern" value="{{ old('accommodation_right', $data['accommodation_right'] ?? '') }}"></div>
                    <div><label class="field-label">Left</label><input type="text" name="accommodation_left" class="input-modern" value="{{ old('accommodation_left', $data['accommodation_left'] ?? '') }}"></div>
                </div>

                {{-- Color Vision, Depth Perception, etc --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Additional Tests
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Color Vision</label>
                        <input type="text" name="color_vision" class="input-modern" value="{{ old('color_vision', $data['color_vision'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Field of Vision</label>
                        <input type="text" name="field_of_vision" class="input-modern" value="{{ old('field_of_vision', $data['field_of_vision'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Night Vision</label>
                        <input type="text" name="night_vision" class="input-modern" value="{{ old('night_vision', $data['night_vision'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Red Lens</label>
                        <input type="text" name="red_lens" class="input-modern" value="{{ old('red_lens', $data['red_lens'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Intraocular Tension</label>
                        <input type="text" name="intraocular_tension" class="input-modern" value="{{ old('intraocular_tension', $data['intraocular_tension'] ?? '') }}">
                    </div>
                </div>

                {{-- Depth Perception --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Depth Perception
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div><label class="field-label">Uncorrected — Test Used</label><input type="text" name="depth_uncorrected_test_used" class="input-modern" value="{{ old('depth_uncorrected_test_used', $data['depth_uncorrected_test_used'] ?? '') }}"></div>
                    <div><label class="field-label">Uncorrected — Score</label><input type="text" name="depth_uncorrected_score" class="input-modern" value="{{ old('depth_uncorrected_score', $data['depth_uncorrected_score'] ?? '') }}"></div>
                    <div><label class="field-label">Corrected — Test Used</label><input type="text" name="depth_corrected_test_used" class="input-modern" value="{{ old('depth_corrected_test_used', $data['depth_corrected_test_used'] ?? '') }}"></div>
                    <div><label class="field-label">Corrected — Score</label><input type="text" name="depth_corrected_score" class="input-modern" value="{{ old('depth_corrected_score', $data['depth_corrected_score'] ?? '') }}"></div>
                </div>

                {{-- Ocular Physical Profile --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Physical Profile (Ocular)
                </div>
                <div style="margin-bottom: 24px; max-width: 200px;">
                    <label class="field-label">Eyes (E)</label>
                    <select name="profile_e" class="select-modern" style="width: 100%;">
                        <option value="">— Select —</option>
                        @foreach(['1', '2', '3', '3T'] as $val)
                            <option value="{{ $val }}" {{ old('profile_e', $data['profile_e'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>
                </div>



            {{-- ========================== --}}
            {{-- HEARING ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'hearing')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Whispered / Spoken Voice
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Right — Whispered Voice</label>
                        <input type="text" name="hearing_right_wv" class="input-modern" value="{{ old('hearing_right_wv', $data['hearing_right_wv'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Right — Spoken Voice</label>
                        <input type="text" name="hearing_right_sv" class="input-modern" value="{{ old('hearing_right_sv', $data['hearing_right_sv'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Left — Whispered Voice</label>
                        <input type="text" name="hearing_left_wv" class="input-modern" value="{{ old('hearing_left_wv', $data['hearing_left_wv'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Left — Spoken Voice</label>
                        <input type="text" name="hearing_left_sv" class="input-modern" value="{{ old('hearing_left_sv', $data['hearing_left_sv'] ?? '') }}" placeholder="enter">
                    </div>
                </div>
                
                {{-- Audiometer --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    71. Audiometer (Mark to apply orange rectangle)
                </div>
                <div style="margin-bottom: 24px; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 400px; text-align: center; border: 1px solid var(--border-color);">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 8px; border: 1px solid var(--border-color);">Ear</th>
                                @foreach(\App\Support\AudiometerChart::frequencies() as $freq)
                                    <th style="padding: 8px; border: 1px solid var(--border-color);">{{ $freq['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 8px; border: 1px solid var(--border-color); font-weight: bold;">Right</td>
                                @foreach(\App\Support\AudiometerChart::frequencies() as $freq)
                                    <td style="padding: 8px; border: 1px solid var(--border-color);">
                                        <input type="checkbox" name="audiometer_{{ $freq['key'] }}_right" value="1" {{ old('audiometer_' . $freq['key'] . '_right', $data['audiometer_' . $freq['key'] . '_right'] ?? '') ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid var(--border-color); font-weight: bold;">Left</td>
                                @foreach(\App\Support\AudiometerChart::frequencies() as $freq)
                                    <td style="padding: 8px; border: 1px solid var(--border-color);">
                                        <input type="checkbox" name="audiometer_{{ $freq['key'] }}_left" value="1" {{ old('audiometer_' . $freq['key'] . '_left', $data['audiometer_' . $freq['key'] . '_left'] ?? '') ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                {{-- Hearing Physical Profile --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Physical Profile (Hearing)
                </div>
                <div style="margin-bottom: 24px; max-width: 200px;">
                    <label class="field-label">Hearing (H)</label>
                    <select name="profile_h" class="select-modern" style="width: 100%;">
                        <option value="">— Select —</option>
                        @foreach(['1', '2', '3', '3T'] as $val)
                            <option value="{{ $val }}" {{ old('profile_h', $data['profile_h'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>

            {{-- ========================== --}}
            {{-- PSYCHOLOGICAL ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'psych')
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">
                        Psychological / Psychomotor Test Results
                    </label>
                    <textarea name="psychological_test" class="input-modern" rows="8" style="max-width: 100%; resize: vertical;" placeholder="enter">{{ old('psychological_test', $data['psychological_test'] ?? '') }}</textarea>
                    @error('psychological_test')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">
                        Notes (73)
                    </label>
                    <textarea name="notes" class="input-modern" rows="5" style="max-width: 100%; resize: vertical;" placeholder="enter">{{ old('notes', $data['notes'] ?? '') }}</textarea>
                    @error('notes')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Psych Physical Profile --}}
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Physical Profile (Psych)
                </div>
                <div style="margin-bottom: 24px; max-width: 200px;">
                    <label class="field-label">Psychiatric (S)</label>
                    <select name="profile_s" class="select-modern" style="width: 100%;">
                        <option value="">— Select —</option>
                        @foreach(['1', '2', '3', '3T'] as $val)
                            <option value="{{ $val }}" {{ old('profile_s', $data['profile_s'] ?? '') === $val ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>

            {{-- ========================== --}}
            {{-- LABORATORY ASSESSMENT --}}
            {{-- ========================== --}}
            @elseif($type === 'lab')
                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Urinalysis
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Specific Gravity</label>
                        <input type="text" name="urinalysis_sp_gr" class="input-modern" value="{{ old('urinalysis_sp_gr', $data['urinalysis_sp_gr'] ?? '') }}" placeholder="enter">
                    </div>
                    <div>
                        <label class="field-label">Albumin</label>
                        <input type="text" name="urinalysis_albumin" class="input-modern" value="{{ old('urinalysis_albumin', $data['urinalysis_albumin'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Sugar</label>
                        <input type="text" name="urinalysis_sugar" class="input-modern" value="{{ old('urinalysis_sugar', $data['urinalysis_sugar'] ?? '') }}">
                    </div>
                    <div>
                        <label class="field-label">Microscopic</label>
                        <input type="text" name="urinalysis_microscopic" class="input-modern" value="{{ old('urinalysis_microscopic', $data['urinalysis_microscopic'] ?? '') }}">
                    </div>
                </div>

                <div style="margin-bottom: 8px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    Additional Lab Tests
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div>
                        <label class="field-label">Blood Type & RH Factor</label>
                        <input type="text" name="blood_type_rh" class="input-modern" value="{{ old('blood_type_rh', $data['blood_type_rh'] ?? '') }}" placeholder="enter">
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">Other Tests</label>
                    <textarea name="other_tests" class="input-modern" rows="4" style="max-width: 100%; resize: vertical;" placeholder="enter">{{ old('other_tests', $data['other_tests'] ?? '') }}</textarea>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div style="display: flex; align-items: center; gap: 12px; padding-top: 8px; border-top: 1px solid var(--border-color);">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Assessment
                </button>
                <a href="{{ route('doctor.show', [
                    'station' => [
                        'dental' => 'dental',
                        'ecg' => 'ecg_xray',
                        'ocular' => 'optha_eent',
                        'hearing' => 'optha_eent',
                        'psych' => 'np',
                        'lab' => 'lab_drugtest',
                    ][$type] ?? 'dental'
                ]) }}" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    .field-label {
        display: block;
        font-weight: 500;
        margin-bottom: 4px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rightBoxes = document.querySelectorAll('input[name$="_right"][name^="audiometer_"]');
    const leftBoxes = document.querySelectorAll('input[name$="_left"][name^="audiometer_"]');
    
    rightBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                rightBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
    
    leftBoxes.forEach(box => {
        box.addEventListener('change', function() {
            if (this.checked) {
                leftBoxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
});
</script>
@endsection


