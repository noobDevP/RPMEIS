<?php

namespace App\Services;

use App\Models\Patient;
use App\Support\MedCertPdfLayoutOverrides;
use App\Support\PdfLayout;
use setasign\Fpdi\Fpdi;

class MedCertPdfService
{
    public function generate(Patient $patient, array $doctorData): string
    {
        $config = MedCertPdfLayoutOverrides::composePdfConfig();
        $templatePath = $config['template'] ?? config('med_cert_pdf.template');

        $pdf = new Fpdi();
        $pdf->SetMargins(0, 0, 0); // Disable default margins!
        $pdf->SetAutoPageBreak(false); // Disable automatic page breaks!
        
        $pageCount = $pdf->setSourceFile($templatePath);
        $tplIdx = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplIdx);

        // Add page with same size as template!
        $pdf->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx);

        $fontFamily = $config['font']['family'] ?? 'Helvetica';
        $fontSize = (float) ($config['font']['size'] ?? 12);
        $pdf->SetFont($fontFamily, '', $fontSize);

        $xOffset = (float) ($config['x_offset'] ?? 0);
        $yOffset = (float) ($config['y_offset'] ?? 0);

        $profileP = '';
        if ($patient->applications->first()?->latestExamination()) {
            $merged = app(\App\Services\MedicalFormPdfService::class)->mergeSpecialtyAssessments($patient->applications->first()?->latestExamination(), $patient->applications->first()?->latestExamination()?->form_data ?? []);
            $pVal = trim((string) ($merged['profile_p'] ?? ''));
            if ($pVal !== '') {
                // Ensure we don't end up with PP1 if it already has a P
                $pVal = ltrim($pVal, 'Pp');
                $profileP = 'P' . $pVal;
            }
        }
        $remarks = $patient->applications->first()?->latestExamination() ? ($patient->applications->first()?->latestExamination()?->form_data['doctors_remarks'] ?? '') : '';
        
        $fields = [
            'full_name' => $patient->fullNameWithInitial(),
            'date_1' => date('d M Y'),
            'date_2' => date('d M Y'),
            'date_3' => date('d M Y'),
            'physical_profile' => $profileP,
            'remarks' => $remarks,
            'doctor_name' => $doctorData['name'] ?? '',
            'doctor_rank' => $doctorData['rank'] ?? '',
            'doctor_afpos' => $doctorData['afpos'] ?? '',
            'doctor_license' => $doctorData['license'] ?? '',
        ];

        foreach ($config['fields'] ?? [] as $key => $fieldConfig) {
            if (!isset($fields[$key]) || trim($fields[$key]) === '') {
                continue;
            }
            $x = (float) $fieldConfig['x'] + $xOffset;
            $y = (float) $fieldConfig['y'] + $yOffset;
            $w = (float) $fieldConfig['w'] ?? 80;

            $pdf->SetXY($x, $y);
            // Use MultiCell so the text wraps automatically based on $w (width).
            // Line height is set to 5 (adjust as needed).
            $pdf->MultiCell($w, 5, $fields[$key], 0, 'L');
        }

        return $pdf->Output('S');
    }
}

