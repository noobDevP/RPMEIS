<?php

namespace App\Services;

use App\Models\MedicalExamination;
use App\Models\MedicalFormAssessment;
use App\Support\FormFieldRegistry;
use App\Support\FormSignatures;
use App\Support\PdfLayout;
use Barryvdh\DomPDF\Facade\Pdf;

class MedicalFormPdfService
{
    public function generate(MedicalExamination $form): string
    {
        $data = $form->form_data ?? [];
        $data['attached_sheets'] = (string) FormSignatures::ATTACHED_SHEETS_COUNT;
        
        if (!empty($data['physician_name'])) {
            $data['physician_name'] = preg_replace('/DR\.?\s+/i', '', $data['physician_name']);
        }
        if (!empty($data['physician_1_name'])) {
            $data['physician_1_name'] = preg_replace('/DR\.?\s+/i', '', $data['physician_1_name']);
        }
        $data['physician_practitioner_id'] = $form->started_by_user_id ?? '1';
        $data['physician_1_practitioner_id'] = $form->started_by_user_id ?? '1';
        
        $defaultOfficer = \App\Models\ChiefSurgeon::where('is_default', true)->first();
        if ($defaultOfficer) {
            $data['reviewing_officer_name'] = $defaultOfficer->signatureLine();
            $data['reviewing_officer_enabled'] = '1';
        }

        if (empty($data['approving_authority_name'])) {
            $authorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
            $defaultAA = $authorities->firstWhere('is_default', true);
            if ($defaultAA) {
                $data['approving_authority_name'] = $defaultAA->signatureLine();
            }
        }

        if (!empty($data['reviewing_officer_name'])) {
            $data['reviewing_officer_name'] = preg_replace('/DR\.?\s+/i', '', $data['reviewing_officer_name']);
        }
        if (!empty($data['approving_authority_name'])) {
            $data['approving_authority_name'] = preg_replace('/DR\.?\s+/i', '', $data['approving_authority_name']);
        }

        $data = $this->mergeSpecialtyAssessments($form, $data);
        

        $backgrounds = $this->backgroundImages();

        FormFieldRegistry::flush();

        // Temporarily bypass pdf_layout_overrides.json for testing - set to false to re-enable overrides
        $bypassOverrides = false;

        if (!$bypassOverrides && file_exists(storage_path('app/pdf_layout_overrides.json')) && is_readable(storage_path('app/pdf_layout_overrides.json'))) {
            $raw = file_get_contents(storage_path('app/pdf_layout_overrides.json'));
            $overrides = json_decode($raw, true);
            $base = config('medical_form_pdf');

            $page1 = array_replace_recursive($base['page1'] ?? [], $overrides['page1'] ?? []);
            unset($page1['pelvic_pelvic_x']);

            $page2 = $base['page2'] ?? [];
            $overridesPage2 = $overrides['page2'] ?? [];
            foreach ($overridesPage2 as $key => $value) {
                $page2[$key] = $value;
            }

            $positions = [
                'template' => $base['template'],
                'font' => array_replace_recursive($base['font'] ?? [], $overrides['font'] ?? []),
                'page' => array_replace_recursive($base['page'] ?? [], $overrides['page'] ?? []),
                'x_offset' => (float) ($overrides['x_offset'] ?? $base['x_offset'] ?? 0),
                'y_offset' => (float) ($overrides['y_offset'] ?? $base['y_offset'] ?? 0),
                'page1' => $page1,
                'page2' => $page2,
            ];
        } else {
            $positions = FormFieldRegistry::pdfConfig();
        }

        $layout = PdfLayout::fromPositions($positions);

        $pdf = Pdf::loadView('medical_form.pdf-official', [
            'data' => $data,
            'form' => $form,
            'backgrounds' => $backgrounds,
            'positions' => $positions,
            'layout' => $layout,
        ])
            ->setPaper([0, 0, $layout->widthPoints(), $layout->heightPoints()], 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 96);

        return $pdf->output();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mergeSpecialtyAssessments(MedicalExamination $form, array $data): array
    {
        $types = ['dental', 'ecg', 'xray', 'ocular', 'hearing', 'psych', 'lab', 'gpe'];

        foreach ($types as $type) {
            $assessment = MedicalFormAssessment::latestForMedicalForm($form->examination_id, $type);
            if ($assessment) {
                $assessmentData = $assessment->toFormData();
                
                // Fetch dynamic signature based on user if they exist
                $assessorName = $assessment->assessed_by;
                if (!empty($assessorName)) {
                    // We don't have staff_user_id on assessments, so we lookup by checking if we have a match
                    // But wait, MedicalFormAssessment does not store staff_user_id!
                    // I will just use preg_replace on $assessorName to format it properly.
                    // But if it was generated with signatureLine(), it already contains the full string!
                    // E.g. "COL DR. CHARLIE (OPTHA/EENT) MC (GSC) PA - MEMBER"
                    // To clean it up, we can just run preg_replace on it!
                    $assessorName = preg_replace('/DR\.?\s+/i', '', $assessorName);
                }
                
                // Map it to the exact field FormSignatures looks for
                if ($type === 'gpe') {
                    $assessmentData['physician_name'] = $assessorName;
                    $assessmentData['physician_1_name'] = $assessorName;
                    $assessmentData['gpe_name'] = $assessorName;
                    $assessmentData['physician_count'] = '1';
                } else {
                    $sigKey = $type === 'ocular' ? 'optometrist_name' : ($type === 'hearing' ? 'ent_name' : ($type === 'psych' ? 'np_name' : $type . '_name'));
                    $assessmentData[$sigKey] = $assessorName;
                }
                
                $data = array_merge($data, $assessmentData);
            }
        }

        // Auto-calculate P from ULHES
        $hierarchy = ['1' => 1, '2' => 2, '3' => 3, '3T' => 4];
        $reverseHierarchy = [1 => '1', 2 => '2', 3 => '3', 4 => '3T'];
        $maxVal = 0;
        $hasValue = false;
        foreach (['profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'] as $k) {
            if (!empty($data[$k]) && isset($hierarchy[$data[$k]])) {
                $maxVal = max($maxVal, $hierarchy[$data[$k]]);
                $hasValue = true;
            }
        }
        if ($hasValue) {
            $data['profile_p'] = $reverseHierarchy[$maxVal];
        }

        return $data;
    }

    public function calculateAttachedSheets(array $data): int
    {
        return FormSignatures::ATTACHED_SHEETS_COUNT;
    }

    /**
     * @return array{page1: string, page2: string}
     */
    private function backgroundImages(): array
    {
        $paths = [
            'page1' => public_path('images/form-page-1.png'),
            'page2' => public_path('images/form-page-2.png'),
        ];

        foreach ($paths as $path) {
            if (! is_readable($path)) {
                throw new \RuntimeException(
                    'Form background image missing. Run: php artisan form:generate-backgrounds'
                );
            }
        }

        return [
            'page1' => 'data:image/png;base64,'.base64_encode(file_get_contents($paths['page1'])),
            'page2' => 'data:image/png;base64,'.base64_encode(file_get_contents($paths['page2'])),
        ];
    }
}
