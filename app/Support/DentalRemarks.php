<?php

namespace App\Support;

/**
 * Field 44 — classification (1–4) + remarks text composed for PDF dental_remarks.
 */
class DentalRemarks
{
    public const CLASSIFICATIONS = ['1', '2', '3', '4'];

    public static function formKeys(): array
    {
        return ['dental_classification', 'dental_remarks_detail'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function composed(array $data): string
    {
        $class = trim((string) ($data['dental_classification'] ?? ''));
        $detail = trim((string) ($data['dental_remarks_detail'] ?? ''));

        if ($class === '' && $detail === '') {
            $legacy = trim((string) ($data['dental_remarks'] ?? ''));

            return $legacy;
        }

        if ($class === '') {
            return $detail;
        }

        $line = 'classification: '.$class;
        if ($detail !== '') {
            $line .= ', '.$detail;
        }

        return $line;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{classification: string, detail: string}
     */
    public static function splitForForm(array $data): array
    {
        $class = (string) ($data['dental_classification'] ?? '');
        $detail = (string) ($data['dental_remarks_detail'] ?? '');

        if ($class !== '' || $detail !== '') {
            return ['classification' => $class, 'detail' => $detail];
        }

        $legacy = trim((string) ($data['dental_remarks'] ?? ''));
        if (preg_match('/^classification:\s*([1-4])\s*,\s*(.+)$/iu', $legacy, $m)) {
            return ['classification' => $m[1], 'detail' => $m[2]];
        }
        if (preg_match('/^classification:\s*([1-4])\s*$/iu', $legacy, $m)) {
            return ['classification' => $m[1], 'detail' => ''];
        }

        return ['classification' => '', 'detail' => $legacy];
    }
}