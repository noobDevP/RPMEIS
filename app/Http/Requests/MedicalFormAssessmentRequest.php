<?php

namespace App\Http\Requests;

use App\Models\MedicalForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicalFormAssessmentRequest extends FormRequest
{
    private const VALID_CLASSIFICATIONS = ['Class 1', 'Class 2', 'Class 3', 'Class 4'];

    public function authorize(): bool
    {
        return session()->has('staff.logged_in');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = $this->route('type');

        $typeRules = match ($type) {
            'ecg' => $this->ecgRules(),
            'xray' => $this->xrayRules(),
            'dental' => $this->dentalRules(),
            'ocular' => $this->ocularRules(),
            'hearing' => $this->hearingRules(),
            'psych' => $this->psychRules(),
            'lab' => $this->labRules(),
            'gpe' => $this->gpeRules(),
            default => [],
        };

        return $typeRules;
    }

    private function gpeRules(): array
    {
        return [
            'profile_u' => ['nullable', 'in:1,2,3,3T'],
            'profile_l' => ['nullable', 'in:1,2,3,3T'],
            'pelvic_exam' => ['nullable', 'string', 'in:normal,abnormal,n.e.'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function xrayRules(): array
    {
        return [
            'chest_xray' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function ecgRules(): array
    {
        $stringMax = ['nullable', 'string', 'max:20'];

        return [
            'bp_sitting_sys' => $stringMax,
            'bp_sitting_dias' => $stringMax,
            'bp_recumbent_sys' => $stringMax,
            'bp_recumbent_dias' => $stringMax,
            'bp_standing_3min_sys' => $stringMax,
            'bp_standing_3min_dias' => $stringMax,
            'pulse_sitting' => $stringMax,
            'pulse_after_exercise' => $stringMax,
            'pulse_2min_after' => $stringMax,
            'pulse_recumbent' => $stringMax,
            'pulse_standing_3min' => $stringMax,
            'ekg' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function labRules(): array
    {
        $stringMax = ['nullable', 'string', 'max:100'];

        return [
            'urinalysis_sp_gr' => $stringMax,
            'urinalysis_albumin' => $stringMax,
            'urinalysis_sugar' => $stringMax,
            'urinalysis_microscopic' => $stringMax,
            'serology' => ['nullable', 'string', 'max:1000'],
            'blood_type_rh' => $stringMax,
            'other_tests' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function dentalRules(): array
    {
        return [
            'dental_classification' => [
                'nullable',
                Rule::in(self::VALID_CLASSIFICATIONS),
            ],
            'dental_remarks_detail' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function ocularRules(): array
    {
        return [
            // Distant vision (field 59)
            'vision_distant_right' => ['nullable', 'string', 'max:50'],
            'vision_distant_right_corr' => ['nullable', 'string', 'max:50'],
            'vision_distant_left' => ['nullable', 'string', 'max:50'],
            'vision_distant_left_corr' => ['nullable', 'string', 'max:50'],
            // Refraction (field 60)
            'refraction_right_by' => ['nullable', 'string', 'max:50'],
            'refraction_right_s' => ['nullable', 'string', 'max:50'],
            'refraction_right_cx' => ['nullable', 'string', 'max:50'],
            'refraction_left_by' => ['nullable', 'string', 'max:50'],
            'refraction_left_s' => ['nullable', 'string', 'max:50'],
            'refraction_left_cx' => ['nullable', 'string', 'max:50'],
            // Near vision (field 61)
            'near_vision_right_color_to' => ['nullable', 'string', 'max:50'],
            'near_vision_right_by' => ['nullable', 'string', 'max:50'],
            'near_vision_left_color_to' => ['nullable', 'string', 'max:50'],
            'near_vision_left_by' => ['nullable', 'string', 'max:50'],
            // Heterophoria (field 62)
            'heterophoria_distance' => ['nullable', 'string', 'max:50'],
            'heterophoria_es' => ['nullable', 'string', 'max:50'],
            'heterophoria_ex' => ['nullable', 'string', 'max:50'],
            'heterophoria_rh' => ['nullable', 'string', 'max:50'],
            'heterophoria_lh' => ['nullable', 'string', 'max:50'],
            'heterophoria_prism' => ['nullable', 'string', 'max:50'],
            'heterophoria_dv' => ['nullable', 'string', 'max:50'],
            'heterophoria_conv' => ['nullable', 'string', 'max:50'],
            'heterophoria_pc' => ['nullable', 'string', 'max:50'],
            'heterophoria_pd' => ['nullable', 'string', 'max:50'],
            // Accommodation (field 63)
            'accommodation_right' => ['nullable', 'string', 'max:50'],
            'accommodation_left' => ['nullable', 'string', 'max:50'],
            // Color vision (field 64)
            'color_vision' => ['nullable', 'string', 'max:100'],
            // Depth perception (field 65)
            'depth_uncorrected_test_used' => ['nullable', 'string', 'max:100'],
            'depth_uncorrected_score' => ['nullable', 'string', 'max:100'],
            'depth_corrected_test_used' => ['nullable', 'string', 'max:100'],
            'depth_corrected_score' => ['nullable', 'string', 'max:100'],
            // Field of vision (field 66)
            'field_of_vision' => ['nullable', 'string', 'max:100'],
            // Night vision (field 67)
            'night_vision' => ['nullable', 'string', 'max:100'],
            // Red lens (field 68)
            'red_lens' => ['nullable', 'string', 'max:100'],
            // Intraocular tension (field 69)
            'intraocular_tension' => ['nullable', 'string', 'max:100'],
            'profile_e' => ['nullable', 'in:1,2,3,3T'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function hearingRules(): array
    {
        $stringMax = ['nullable', 'string', 'max:20'];

        $rules = [
            // Whispered / spoken voice (field 70)
            'hearing_right_wv' => $stringMax,
            'hearing_right_sv' => $stringMax,
            'hearing_left_wv' => $stringMax,
            'hearing_left_sv' => $stringMax,
            'profile_h' => ['nullable', 'in:1,2,3,3T'],
        ];
        
        foreach (\App\Support\AudiometerChart::formKeys() as $key) {
            $rules[$key] = ['nullable', 'string', 'max:20'];
        }
        
        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function psychRules(): array
    {
        return [
            'psychological_test' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'profile_s' => ['nullable', 'in:1,2,3,3T'],
        ];
    }

    /**
     * Collect only the fields that are relevant to the current assessment type.
     *
     * @return array<string, mixed>
     */
    public function assessmentData(): array
    {
        $type = $this->route('type');

        $allKeys = match ($type) {
            'xray' => [
                'chest_xray',
            ],
            'ecg' => [
                'bp_sitting_sys', 'bp_sitting_dias',
                'bp_recumbent_sys', 'bp_recumbent_dias',
                'bp_standing_3min_sys', 'bp_standing_3min_dias',
                'pulse_sitting', 'pulse_after_exercise', 'pulse_2min_after',
                'pulse_recumbent', 'pulse_standing_3min',
                'ekg',
            ],
            'dental' => [
                'dental_classification', 'dental_remarks_detail',
            ],
            'ocular' => [
                'vision_distant_right', 'vision_distant_right_corr',
                'vision_distant_left', 'vision_distant_left_corr',
                'refraction_right_by', 'refraction_right_s', 'refraction_right_cx',
                'refraction_left_by', 'refraction_left_s', 'refraction_left_cx',
                'near_vision_right_color_to', 'near_vision_right_by',
                'near_vision_left_color_to', 'near_vision_left_by',
                'heterophoria_distance', 'heterophoria_es', 'heterophoria_ex',
                'heterophoria_rh', 'heterophoria_lh', 'heterophoria_prism',
                'heterophoria_dv', 'heterophoria_conv', 'heterophoria_pc', 'heterophoria_pd',
                'accommodation_right', 'accommodation_left',
                'color_vision',
                'depth_uncorrected_test_used', 'depth_uncorrected_score',
                'depth_corrected_test_used', 'depth_corrected_score',
                'field_of_vision', 'night_vision', 'red_lens', 'intraocular_tension',
                'profile_e',
            ],
            'hearing' => array_merge([
                'hearing_right_wv', 'hearing_right_sv',
                'hearing_left_wv', 'hearing_left_sv',
                'profile_h',
            ], \App\Support\AudiometerChart::formKeys()),
            'psych' => [
                'psychological_test', 'notes', 'profile_s',
            ],
            'lab' => [
                'urinalysis_sp_gr', 'urinalysis_albumin', 'urinalysis_sugar', 'urinalysis_microscopic',
                'serology',
                'blood_type_rh',
                'other_tests',
            ],
            'gpe' => [
                'profile_u', 'profile_l', 'pelvic_exam',
            ],
            default => [],
        };

        $data = [];
        foreach ($allKeys as $key) {
            if ($this->has($key)) {
                $data[$key] = $this->input($key);
            }
        }

        return $data;
    }
}