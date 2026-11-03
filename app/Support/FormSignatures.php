<?php

namespace App\Support;

class FormSignatures
{
    public const ATTACHED_SHEETS_COUNT = 2;

    public const MAX_PER_ROLE = 1;

    /**
     * Maximum number of physicians allowed when no dentist is assigned.
     */
    public const MAX_PHYSICIANS_WITHOUT_DENTIST = 2;

    /**
     * @return list<string>
     */
    public static function formKeys(): array
    {
        return [
            'physician_practitioner_id',
            'physician_name',
            'physician_1_practitioner_id',
            'physician_1_name',
            'physician_count',
            'optometrist_practitioner_id',
            'optometrist_name',
            'optometrist_enabled',
            'ent_practitioner_id',
            'ent_name',
            'ent_enabled',
            'np_practitioner_id',
            'np_name',
            'np_enabled',
            'ecg_practitioner_id',
            'ecg_name',
            'ecg_enabled',
            'dentist_practitioner_id',
            'dentist_name',
            'dentist_enabled',
            'reviewing_officer_enabled',
            'reviewing_officer_name',
            'approving_authority_name',
            'attached_sheets',
        ];
    }

    public static function roleEnabled(array $data, string $role): bool
    {
        if ($role === 'physician') {
            $id = $data['physician_practitioner_id'] ?? $data['physician_1_practitioner_id'] ?? null;

            return $id !== null && $id !== '';
        }

        $specialty = match ($role) {
            'optometrist' => SpecialtyType::OCULAR,
            'ent' => SpecialtyType::HEARING,
            'np' => SpecialtyType::PSYCH,
            'ecg' => SpecialtyType::ECG,
            'dentist' => SpecialtyType::DENTAL,
            default => null,
        };

        if ($specialty === null) {
            return false;
        }

        return filter_var($data[SpecialtyType::enabledKey($specialty)] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function dentistEnabled(array $data): bool
    {
        return self::roleEnabled($data, 'dentist');
    }

    public static function reviewingOfficerEnabled(array $data): bool
    {
        return filter_var($data['reviewing_officer_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function physicianCount(array $data): int
    {
        return self::roleEnabled($data, 'physician') ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $payload): array
    {
        $physicianId = $payload['physician_practitioner_id'] ?? $payload['physician_1_practitioner_id'] ?? null;
        if ($physicianId === null || $physicianId === '') {
            $payload['physician_practitioner_id'] = null;
            $payload['physician_name'] = null;
            $payload['physician_1_practitioner_id'] = null;
            $payload['physician_1_name'] = null;
        } else {
            $payload['physician_practitioner_id'] = (string) $physicianId;
            $payload['physician_1_practitioner_id'] = (string) $physicianId;
        }
        $payload['physician_count'] = self::roleEnabled($payload, 'physician') ? '1' : '0';

        foreach ([SpecialtyType::OCULAR, SpecialtyType::HEARING, SpecialtyType::PSYCH, SpecialtyType::ECG, SpecialtyType::DENTAL] as $specialty) {
            $enabledKey = SpecialtyType::enabledKey($specialty);
            $assignmentKey = SpecialtyType::assignmentKey($specialty);
            $nameKey = SpecialtyType::nameKey($specialty);

            $enabled = filter_var($payload[$enabledKey] ?? false, FILTER_VALIDATE_BOOLEAN);
            $payload[$enabledKey] = $enabled ? '1' : '0';

            if (! $enabled) {
                $payload[$assignmentKey] = null;
                $payload[$nameKey] = null;
            }
        }

        $reviewing = filter_var($payload['reviewing_officer_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $payload['reviewing_officer_enabled'] = $reviewing ? '1' : '0';
        if (! $reviewing) {
            $payload['reviewing_officer_name'] = null;
        }

        $payload['attached_sheets'] = (string) self::ATTACHED_SHEETS_COUNT;

        return PractitionerSignatures::resolvePayload($payload);
    }

    /**
     * @return array<string, array{x: float, y: float, w?: float}>
     */
    public static function pdfPositions(): array
    {
        // Temporarily bypass pdf_layout_overrides.json for testing - set to false to re-enable overrides
        $bypassOverrides = false;

        $base = config('signatures_pdf', []);
        $overridePath = storage_path('app/pdf_layout_overrides.json');
        if (!$bypassOverrides && file_exists($overridePath) && is_readable($overridePath)) {
            $raw = file_get_contents($overridePath);
            $overrides = json_decode($raw, true);
            if (isset($overrides['signatures'])) {
                return array_replace_recursive($base, $overrides['signatures']);
            }
        }

        return $base;
    }

    /**
     * Certification lines for PDF — same placement pattern as dentist / reviewing_officer.
     *
     * @return list<array{role: string, field: string, position: ?array{x: float, y: float, w?: float}}>
     */
    public static function pdfSignatureLines(array $data): array
    {
        $sigPos = self::pdfPositions();
        $lines = [];

        $mappings = [
            'gpe' => ['gpe_name', 'physician_1_name'],
            'ecg_xray' => ['ecg_name', 'xray_name'],
            'optha_eent' => ['optometrist_name', 'ent_name'],
            'approving_authority' => ['approving_authority_name'],
            'np' => ['np_name'],
            'dental' => ['dental_name', 'dentist_name'],
            'lab_drugtest' => ['lab_name'],
            'reviewing_officer' => ['reviewing_officer_name'],
        ];

        // Backwards compatibility mappings for older JSON overrides
        if (isset($sigPos['physicians'][1]) && !isset($sigPos['gpe'])) {
            $sigPos['gpe'] = $sigPos['physicians'][1];
        }
        if (isset($sigPos['optometrist']) && !isset($sigPos['optha_eent'])) {
            $sigPos['optha_eent'] = $sigPos['optometrist'];
        }
        if (isset($sigPos['ent']) && !isset($sigPos['optha_eent'])) {
            $sigPos['optha_eent'] = $sigPos['ent'];
        }
        if (isset($sigPos['ecg']) && !isset($sigPos['ecg_xray'])) {
            $sigPos['ecg_xray'] = $sigPos['ecg'];
        }
        if (isset($sigPos['dentist']) && !isset($sigPos['dental'])) {
            $sigPos['dental'] = $sigPos['dentist'];
        }

        foreach ($mappings as $jsonKey => $dataFields) {
            $actualField = '';
            foreach ($dataFields as $field) {
                if (!empty($data[$field])) {
                    $actualField = $field;
                    break;
                }
            }

            if ($actualField !== '' && isset($sigPos[$jsonKey]['name'])) {
                $signatureImagePos = $sigPos[$jsonKey]['signature'] ?? null;
                $doctorName = $data[$actualField] ?? '';
                $signaturePath = null;
                
                if ($signatureImagePos && !empty($doctorName)) {
                    // Extract name from something like "JUAN DELA CRUZ, MD"
                    $cleanName = preg_replace('/,?\s*(MD|M\.D\.|RN|R\.N\.).*$/i', '', $doctorName);
                    $cleanName = trim($cleanName);
                    
                    // Normalize function to strip punctuation and spaces for robust matching
                    $normalize = function ($name) {
                        return preg_replace('/[^A-Z0-9]/', '', strtoupper($name));
                    };
                    $normDoctorName = $normalize($doctorName);

                    if ($jsonKey === 'reviewing_officer') {
                        $chiefs = \App\Models\ChiefSurgeon::all();
                        foreach ($chiefs as $chief) {
                            if (str_contains($normDoctorName, $normalize($chief->name))) {
                                if ($chief->e_signature_path) {
                                    $signaturePath = $chief->e_signature_path;
                                }
                                break;
                            }
                        }
                    } elseif ($jsonKey === 'approving_authority') {
                        $authorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();
                        foreach ($authorities as $auth) {
                            $authName = $auth->name ?? '';
                            if (empty($authName)) continue;
                            if (str_contains($normDoctorName, $normalize($authName))) {
                                if ($auth->e_signature_path) {
                                    $signaturePath = $auth->e_signature_path;
                                }
                                break;
                            }
                        }
                    } else {
                        $users = \App\Models\StaffUser::all();
                        foreach ($users as $u) {
                            $username = trim((string)$u->user_username);
                            if (!empty($username) && str_contains($normDoctorName, $normalize($username))) {
                                if ($u->e_signature_path) {
                                    $signaturePath = $u->e_signature_path;
                                }
                                break;
                            }
                        }
                    }
                }

                $lines[] = [
                    'role' => $jsonKey,
                    'field' => $actualField,
                    'position' => $sigPos[$jsonKey]['name'],
                    'signature_position' => $signatureImagePos,
                    'signature_path' => $signaturePath
                ];
            }
        }

        return $lines;
    }
}