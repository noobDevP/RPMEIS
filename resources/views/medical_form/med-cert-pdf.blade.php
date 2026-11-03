@php
    use App\Support\PdfFieldRenderer;
    use App\Support\PdfLayout;
    $d = $data;
    $positions = $positions;
    $layout = $layout ?? PdfLayout::fromPositions($positions);
    $font = $positions['font'] ?? [];
    $fontFamily = $font['family'] ?? 'DejaVu Sans';
    $fontSizePt = (float) ($font['size'] ?? 11);
    $pageW = $layout->widthMm;
    $pageH = $layout->heightMm;

    $field = function (string $key, array $pos) use ($d, $layout, $fontSizePt) {
        $value = $d[$key] ?? '';
        $value = trim($value);
        if ($value !== '' && !preg_match('/^\s*(?:(?:refraction|heterophoria)\s+(?:text|placeholder)|(?:text|placeholder|enter text))\s*\.?\s*$/i', $value)) {
            PdfFieldRenderer::render((string) $value, $pos, $layout, $fontSizePt, true);
        } else {
            $top = $layout->y((float) ($pos['y'] ?? 0));
            $left = $layout->x((float) ($pos['x'] ?? 0));
            $width = $layout->fieldW((float) ($pos['w'] ?? 80)).'mm';
            $maxLines = max(1, (int) ($pos['max_lines'] ?? 1));
            $maxHeight = $maxLines * PdfFieldRenderer::lineHeightMm();
            echo '<div class="field" style="left:'.$left.'mm;top:'.$top.'mm;width:'.$width.';max-height:'.$maxHeight.'mm;">&nbsp;</div>';
        }
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
        .field {
            position: absolute;
            z-index: 1;
            line-height: 1.1;
            overflow: hidden;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

{{-- PAGE 1 --}}
<div class="page">
    @if (isset($backgrounds['page1']))
        <img class="bg" src="{{ $backgrounds['page1'] }}" alt="">
    @endif
    @php
        $field('full_name', $positions['fields']['full_name']);
        $field('applicant_number', $positions['fields']['applicant_number']);
        $field('purpose', $positions['fields']['purpose']);
        $field('batch', $positions['fields']['batch']);
        $field('doctor_name', $positions['fields']['doctor_name']);
        $field('doctor_rank', $positions['fields']['doctor_rank']);
        $field('doctor_license', $positions['fields']['doctor_license']);
    @endphp
</div>

</body>
</html>
