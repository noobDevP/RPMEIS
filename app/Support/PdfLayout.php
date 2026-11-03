<?php

namespace App\Support;

/**
 * Page layout for official PDF output (mm coordinates on a full-size template background).
 */
class PdfLayout
{
    public function __construct(
        public readonly float $widthMm,
        public readonly float $heightMm,
        public readonly float $fieldOffsetXMm = 0,
        public readonly float $fieldOffsetYMm = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $positions  Output from FormFieldRegistry::pdfConfig()
     */
    public static function fromPositions(array $positions): self
    {
        $page = is_array($positions['page'] ?? null)
            ? $positions['page']
            : config('medical_form_pdf.page', []);

        return new self(
            widthMm: (float) ($page['width_mm'] ?? 297),
            heightMm: (float) ($page['height_mm'] ?? 420),
            fieldOffsetXMm: (float) ($positions['x_offset'] ?? 0),
            fieldOffsetYMm: (float) ($positions['y_offset'] ?? 0),
        );
    }

    public function x(float $mm): float
    {
        return $mm + $this->fieldOffsetXMm;
    }

    public function y(float $mm): float
    {
        return $mm + $this->fieldOffsetYMm;
    }

    public function fieldW(float $mm): float
    {
        return $mm;
    }

    public function widthPoints(): float
    {
        return self::mmToPoints($this->widthMm);
    }

    public function heightPoints(): float
    {
        return self::mmToPoints($this->heightMm);
    }

    public static function mmToPoints(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}