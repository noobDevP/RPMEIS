<?php

namespace App\Support;

/**
 * Renders PDF overlay text with width / max-line limits and optional continuation boxes.
 */
class PdfFieldRenderer
{
    public static function lineHeightMm(): float
    {
        return 3.2;
    }

    public static function render(string $value, array $pos, PdfLayout $layout, float $fontSizePt = 11, bool $uppercase = true): void
    {
        $value = trim($value);

        // Ignore empty values and obvious placeholder strings that some users
        // accidentally leave in (e.g. "refraction text", "heterophoria text",
        // or generic "placeholder" markers). These should not appear on PDFs.
        if ($value === '') {
            return;
        }

        if (preg_match('/^\s*(?:(?:refraction|heterophoria)\s+(?:text|placeholder)|(?:text|placeholder|enter text))\s*\.?\s*$/i', $value)) {
            return;
        }

        if ($uppercase) {
            $value = mb_strtoupper($value, 'UTF-8');
        }

        foreach (self::segments($value, $pos, $fontSizePt) as $segment) {
            $top = $layout->y((float) $segment['y']);
            $left = $layout->x((float) $segment['x']);
            $width = $layout->fieldW((float) $segment['w']).'mm';
            $maxHeight = ((int) $segment['max_lines']) * self::lineHeightMm();
            $text = $segment['text'];

            echo '<div class="field" style="left:'.$left.'mm;top:'.$top.'mm;width:'.$width
                .';max-height:'.$maxHeight."mm;\">".e($text).'</div>';
        }
    }

    /**
     * @return list<array{x: float, y: float, w: float, max_lines: int, text: string}>
     */
    public static function segments(string $value, array $pos, float $fontSizePt): array
    {
        $x = (float) ($pos['x'] ?? 0);
        $y = (float) ($pos['y'] ?? 0);
        $w = (float) ($pos['w'] ?? 80);
        $maxLines = max(1, (int) ($pos['max_lines'] ?? 1));
        $continue = is_array($pos['continue'] ?? null) ? $pos['continue'] : null;

        $lines = self::wrapToLines($value, self::charsPerLine($w, $fontSizePt));

        if ($continue === null) {
            $slice = array_slice($lines, 0, $maxLines);

            return [[
                'x' => $x,
                'y' => $y,
                'w' => $w,
                'max_lines' => $maxLines,
                'text' => implode("\n", $slice),
            ]];
        }

        $firstLines = array_slice($lines, 0, $maxLines);
        $remainder = trim(implode(' ', array_slice($lines, $maxLines)));

        $segments = [[
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'max_lines' => $maxLines,
            'text' => implode("\n", $firstLines),
        ]];

        if ($remainder === '') {
            return $segments;
        }

        $contW = (float) ($continue['w'] ?? $w);
        $contMax = max(1, (int) ($continue['max_lines'] ?? 99));
        $contX = isset($continue['x']) ? (float) $continue['x'] : $x;
        $contY = isset($continue['y'])
            ? (float) $continue['y']
            : $y + ($maxLines * self::lineHeightMm()) + (float) ($continue['y_gap'] ?? 0);

        $contLines = self::wrapToLines($remainder, self::charsPerLine($contW, $fontSizePt));

        $segments[] = [
            'x' => $contX,
            'y' => $contY,
            'w' => $contW,
            'max_lines' => $contMax,
            'text' => implode("\n", array_slice($contLines, 0, $contMax)),
        ];

        return $segments;
    }

    public static function charsPerLine(float $widthMm, float $fontSizePt): int
    {
        return max(8, (int) floor($widthMm / max(1.5, $fontSizePt * 0.25)));
    }

    /**
     * @return list<string>
     */
    public static function wrapToLines(string $text, int $charsPerLine): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        if ($text === '') {
            return [];
        }

        $lines = [];
        $words = preg_split('/\s+/u', $text) ?: [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($candidate) <= $charsPerLine) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            while (mb_strlen($word) > $charsPerLine) {
                $lines[] = mb_substr($word, 0, $charsPerLine);
                $word = mb_substr($word, $charsPerLine);
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
