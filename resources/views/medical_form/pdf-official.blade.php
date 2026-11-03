@php
    use App\Support\AudiometerChart;
    use App\Support\DentalRemarks;
    use App\Support\FormSignatures;
    use App\Support\PdfFieldRenderer;
    use App\Support\Step4FieldComposer;
    $d = $data;
    $p1 = $positions['page1'];
    $p2 = $positions['page2'];
    $clinical = $p1['clinical'];
    $layout = $layout ?? \App\Support\PdfLayout::fromPositions($positions);
    $font = $positions['font'] ?? [];
    $fontFamily = $font['family'] ?? 'DejaVu Sans';
    $fontSizePt = (float) ($font['size'] ?? 11);
    $markSizePt = (float) ($font['size_mark'] ?? $font['size_small'] ?? 7.5);
    $pageW = $layout->widthMm;
    $pageH = $layout->heightMm;

    $field = function (string $key, array $pos, bool $date = false) use ($d, $layout, $fontSizePt) {
        $value = $d[$key] ?? '';
        if ($value === '' || $value === null) {
            return;
        }
        if ($date) {
            try {
                $value = \Carbon\Carbon::parse($value)->format('d F Y');
            } catch (\Throwable) {
            }
            PdfFieldRenderer::render((string) $value, $pos, $layout, $fontSizePt, false);

            return;
        }
        PdfFieldRenderer::render((string) $value, $pos, $layout, $fontSizePt, true);
    };

        $renderSignature = function (?string $signaturePath, ?array $pos) use ($layout) {
        if (empty($signaturePath) || empty($pos)) return;
        $top = $layout->y((float) $pos['y']);
        $left = $layout->x((float) $pos['x']);
        $w = $pos['w'] ?? 40;
        $h = $pos['h'] ?? 15;
        
        // Use local absolute file path for DOMPDF to prevent HTTP blocking issues
        $src = storage_path('app/public/' . $signaturePath);
        if (!file_exists($src)) return;
        
        echo '<img src="'.$src.'" style="position:absolute; left:'.$left.'mm; top:'.$top.'mm; max-width:'.$w.'mm; max-height:'.$h.'mm; object-fit:contain;" />';
    };

    $mark = function (array $pos, ?float $sizePt = null) use ($layout, $markSizePt) {
        $top = $layout->y((float) $pos['y']);
        $left = $layout->x((float) $pos['x']);
        $size = $sizePt ?? $markSizePt;
        echo '<div class="mark" style="left:'.$left.'mm;top:'.$top.'mm;font-size:'.$size.'pt;">X</div>';
    };

@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: {{ $pageW }}mm {{ $pageH }}mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: {{ $pageW }}mm; margin: 0; padding: 0; }
        body { font-family: {{ $fontFamily }}, sans-serif; font-size: {{ $fontSizePt }}pt; color: #000; }
        .page {
            position: relative;
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
            page-break-after: always;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .page:last-child { page-break-after: avoid; }
        .bg {
            position: absolute;
            left: 0;
            top: 0;
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
            z-index: 0;
        }
        .field, .mark {
            position: absolute;
            z-index: 1;
            line-height: 1.1;
            overflow: hidden;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .mark { font-weight: bold; font-size: {{ $markSizePt }}pt; }
    </style>
</head>
<body>

{{-- PAGE 1 --}}
<div class="page">
    <img class="bg" src="{{ $backgrounds['page1'] }}" alt="">

    @php
        $field('full_name', $p1['full_name']);
        $field('grade_component_position', $p1['grade_component_position']);
        $field('identification', $p1['identification']);
        $field('home_address', $p1['home_address']);
        $field('purpose_of_examination', $p1['purpose_of_examination']);
        $field('date_of_examination', $p1['date_of_examination'], true);
        $field('sex', $p1['sex']);
        $field('race', $p1['race']);
        if (($d['govt_service_type'] ?? '') === 'military') { $mark($p1['govt_service_military_x']); }
        if (($d['govt_service_type'] ?? '') === 'civilian') { $mark($p1['govt_service_civilian_x']); }
        $field('total_yrs_govt_service', $p1['total_yrs_govt_service']);
        $field('branch_of_service', $p1['branch_of_service']);
        $field('organization_unit', $p1['organization_unit']);
        $field('date_of_birth', $p1['date_of_birth'], true);
        $field('place_of_birth', $p1['place_of_birth']);
        $field('next_of_kin', $p1['next_of_kin']);
        $field('examining_facility', $p1['examining_facility']);
        $field('others_information', $p1['others_information']);
        $field('rating_specialty', $p1['rating_specialty']);
        $field('time_in_capacity_total', $p1['time_in_capacity_total']);
        $field('time_last_six_months', $p1['time_last_six_months']);

        for ($i = $clinical['first_item']; $i <= $clinical['last_item']; $i++) {
            $val = $d['clinical_'.$i] ?? '';
            if (! in_array($val, ['normal', 'abnormal', 'ne'], true)) {
                continue;
            }
            $itemY = $clinical['item_y'][$i] ?? $clinical['item_y'][(string) $i] ?? null;
            if ($itemY !== null) {
                $y = (float) $itemY;
            } else {
                $rowAdjust = (float) ($clinical['row_adjustments'][$i] ?? $clinical['row_adjustments'][(string) $i] ?? 0);
                $y = $clinical['start_y'] + (($i - $clinical['first_item']) * $clinical['row_height']) + $rowAdjust;
            }
            if ($val === 'ne') {
                $top = $layout->y($y);
                $left = $layout->x((float) $clinical['normal_x']);
                echo '<div class="field" style="left:'.$left.'mm;top:'.$top.'mm;white-space:nowrap;">N. E</div>';
                continue;
            }
            $x = $val === 'abnormal' ? $clinical['abnormal_x'] : $clinical['normal_x'];
            $mark(['x' => $x, 'y' => $y], $fontSizePt);
        }

        $field('clinical_evaluation_notes', $p1['clinical_evaluation_notes'] ?? ['x' => 100, 'y' => 89, 'w' => 95, 'max_lines' => 18]);

        $pelvic = $d['pelvic_exam'] ?? '';
        if ($pelvic === 'normal' || $pelvic === 'n.e.') {
            $mark(['x' => 58.1, 'y' => 285.4]);
        } elseif ($pelvic === 'abnormal') {
            $mark(['x' => 79, 'y' => 285.4]);
        }

        $dentalRemarksText = DentalRemarks::composed($d);
        if ($dentalRemarksText !== '') {
            PdfFieldRenderer::render(
                $dentalRemarksText,
                $p1['dental_remarks'] ?? ['x' => 218, 'y' => 310, 'w' => 204, 'max_lines' => 4],
                $layout,
                $fontSizePt,
                true
            );
        }

        $dentalClass = trim((string) ($d['dental_classification'] ?? ''));
        if ($dentalClass !== '') {
            PdfFieldRenderer::render(
                $dentalClass,
                $p1['dental_classification'] ?? ['x' => 172, 'y' => 235.5, 'w' => 35],
                $layout,
                $fontSizePt,
                true
            );
        }
        $field('urinalysis_sp_gr', $p1['urinalysis_sp_gr']);
        $field('urinalysis_albumin', $p1['urinalysis_albumin']);
        $field('urinalysis_sugar', $p1['urinalysis_sugar']);
        $field('urinalysis_microscopic', $p1['urinalysis_microscopic']);
        $field('ekg', $p1['ekg']);
        $field('serology', $p1['serology']);
        $field('chest_xray', $p1['chest_xray']);
        $field('blood_type_rh', $p1['blood_type_rh']);
        $field('other_tests', $p1['other_tests']);
    @endphp
</div>

{{-- PAGE 2 --}}
<div class="page">
    <img class="bg" src="{{ $backgrounds['page2'] }}" alt="">

    @php
        $field('height', $p2['height']);
        $field('weight', $p2['weight']);
        $field('color_hair', $p2['color_hair']);
        $field('color_eye', $p2['color_eye']);
        $build = $d['build'] ?? '';
        $buildMap = ['slender' => 'build_slender_x', 'medium' => 'build_medium_x', 'heavy' => 'build_heavy_x', 'obese' => 'build_obese_x'];
        if (isset($buildMap[$build])) { $mark($p2[$buildMap[$build]]); }
        $field('temperature', $p2['temperature']);

        foreach ([
            'bp_sitting_sys', 'bp_sitting_dias', 'bp_recumbent_sys', 'bp_recumbent_dias',
            'bp_standing_3min_sys', 'bp_standing_3min_dias',
            'pulse_sitting', 'pulse_after_exercise', 'pulse_2min_after', 'pulse_recumbent', 'pulse_standing_3min',
            'vision_distant_right', 'vision_distant_right_corr', 'vision_distant_left', 'vision_distant_left_corr',
            ...Step4FieldComposer::allSubfieldKeys(),
            'accommodation_right', 'accommodation_left',
            'color_vision', 'field_of_vision', 'night_vision', 'red_lens', 'intraocular_tension',
            'hearing_right_wv', 'hearing_right_sv', 'hearing_left_wv', 'hearing_left_sv', 'psychological_test',
            'notes', 'summary_defects', 'recommendations',
            'disqualifying_defects',
        ] as $key) {
            if (! isset($p2[$key])) {
                continue;
            }

            $field($key, $p2[$key]);
        }

        // Legacy combined fields (older saved forms only)
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::refractionKeys()) && isset($p2['refraction_right'])) {
            $field('refraction_right', $p2['refraction_right']);
        }
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::refractionKeys()) && isset($p2['refraction_left'])) {
            $field('refraction_left', $p2['refraction_left']);
        }
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::nearVisionKeys()) && isset($p2['near_vision_right'])) {
            $field('near_vision_right', $p2['near_vision_right']);
        }
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::nearVisionKeys()) && isset($p2['near_vision_left'])) {
            $field('near_vision_left', $p2['near_vision_left']);
        }
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::heterophoriaKeys()) && isset($p2['heterophoria'])) {
            $field('heterophoria', $p2['heterophoria']);
        }
        if (Step4FieldComposer::subfieldsEmpty($d, Step4FieldComposer::depthPerceptionKeys()) && isset($p2['depth_perception'])) {
            $field('depth_perception', $p2['depth_perception']);
        }

        foreach (['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'] as $key) {
            $field($key, $p2[$key]);
        }

        foreach (['rtdo_r', 'rtdo_t', 'rtdo_d', 'rtdo_o'] as $key) {
            $field($key, $p2[$key]);
        }

        // Legacy single-field fallbacks for older saved forms
        if (
            trim((string) ($d['physical_profile'] ?? '')) !== '' &&
            Step4FieldComposer::subfieldsEmpty($d, ['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'])
        ) {
            $legacy = $p2['profile_p'] ?? ['x' => 188, 'y' => 213.1, 'w' => 55];
            $field('physical_profile', array_merge($legacy, ['w' => 55]));
        }
        if (trim((string) ($d['rtdo'] ?? '')) !== '' && empty($d['rtdo_r']) && empty($d['rtdo_t'])) {
            $field('rtdo', array_merge($p2['rtdo_r'] ?? ['x' => 188, 'y' => 234.7], ['w' => 28]));
        }

        $fitness = $d['fitness_status'] ?? '';
        $fitnessMap = ['A' => 'fitness_a_x', 'B' => 'fitness_b_x', 'C' => 'fitness_c_x', 'D' => 'fitness_d_x'];
        if (isset($fitnessMap[$fitness])) { $mark($p2[$fitnessMap[$fitness]]); }

        foreach (AudiometerChart::formKeys() as $key) {
            $val = $d[$key] ?? '';
            if (trim((string)$val) === '') {
                continue;
            }
            $pos = $p2[$key] ?? null;
            if (!$pos) {
                $id = str_replace('audiometer_', '', $key);
                $fallbackPos = \App\Support\AudiometerChart::positions()[$id] ?? null;
                if ($fallbackPos) {
                    $pos = $fallbackPos;
                } else {
                    continue;
                }
            }
            $top = $layout->y((float) $pos['y']);
            $left = $layout->x((float) $pos['x']);
            $width = isset($pos['w']) ? (float)$pos['w'] . 'mm' : '4mm';
            $height = isset($pos['h']) ? (float)$pos['h'] . 'mm' : '4mm';
            
            echo '<div class="field" style="position:absolute;left:'.$left.'mm;top:'.$top.'mm;width:'.$width.';height:'.$height.';background-color:#D75100;"></div>';
        }

        foreach (FormSignatures::pdfSignatureLines($d) as $sigLine) {
            $field($sigLine['field'], $sigLine['position']);
            
            // Render the actual signature image if present and mapped
            if (!empty($sigLine['signature_path']) && !empty($sigLine['signature_position'])) {
                $renderSignature($sigLine['signature_path'], $sigLine['signature_position']);
            }
        }

    @endphp
</div>

</body>
</html>
