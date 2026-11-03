<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class FormFieldRegistry
{
    private const SETTINGS_PATH = 'app/form_field_settings.json';

    private static ?array $registry = null;

    private static ?array $settings = null;

    private static ?array $keyToNumber = null;

    public static function registry(): array
    {
        if (self::$registry === null) {
            self::$registry = config('form_field_registry', []);
        }

        return self::$registry;
    }

    public static function settingsPath(): string
    {
        return storage_path(self::SETTINGS_PATH);
    }

    public static function settings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        self::ensureSettingsFile();

        $raw = File::get(self::settingsPath());
        self::$settings = json_decode($raw, true) ?: [];

        return self::$settings;
    }

    public static function saveSettings(array $settings): void
    {
        self::$settings = $settings;
        File::ensureDirectoryExists(dirname(self::settingsPath()));
        File::put(
            self::settingsPath(),
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }

    public static function flush(): void
    {
        self::$settings = null;
        self::$keyToNumber = null;
    }

    public static function exists(int $number): bool
    {
        return isset(self::registry()[$number]);
    }

    public static function meta(int $number): ?array
    {
        return self::registry()[$number] ?? null;
    }

    public static function numberForKey(string $key): ?int
    {
        if (self::$keyToNumber === null) {
            self::$keyToNumber = [];
            foreach (self::registry() as $num => $meta) {
                if (isset($meta['key'])) {
                    self::$keyToNumber[$meta['key']] = (int) $num;
                }
                foreach ($meta['keys'] ?? [] as $k) {
                    self::$keyToNumber[$k] = (int) $num;
                }
            }
        }

        return self::$keyToNumber[$key] ?? null;
    }

    public static function keysForNumber(int $number): array
    {
        $meta = self::meta($number);
        if (! $meta) {
            return [];
        }

        if (($meta['type'] ?? '') === 'dental') {
            return DentalRemarks::formKeys();
        }

        if (($meta['type'] ?? '') === 'audiometer') {
            return AudiometerChart::formKeys();
        }

        if (($meta['type'] ?? '') === 'pulhes') {
            return ['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'];
        }

        if (($meta['type'] ?? '') === 'signatures') {
            return FormSignatures::formKeys();
        }

        if (isset($meta['key'])) {
            return [$meta['key']];
        }

        return $meta['keys'] ?? [];
    }

    public static function inWizard(int $number): bool
    {
        $meta = self::meta($number);

        return $meta && ($meta['wizard'] ?? true) && ($meta['type'] ?? '') !== 'signature';
    }

    public static function isRequired(int $number): bool
    {
        $settings = self::settings();
        $key = (string) $number;

        if (array_key_exists('required', $settings[$key] ?? [])) {
            return (bool) $settings[$key]['required'];
        }

        return self::defaultRequired($number);
    }

    public static function isRequiredForKey(string $key): bool
    {
        $num = self::numberForKey($key);

        return $num !== null && self::isRequired($num);
    }

    public static function setRequired(int $number, bool $required): void
    {
        $settings = self::settings();
        $key = (string) $number;
        $settings[$key] = array_merge($settings[$key] ?? [], ['required' => $required]);
        self::saveSettings($settings);
    }

    /**
     * @return array{page1: array<string, mixed>, page2: array<string, mixed>, template: string, font: array<string, mixed>}
     */
    public static function pdfConfig(): array
    {
        return self::buildPdfConfigFromSettings();
    }

    /**
     * Merge config/medical_form_pdf.php with storage/app/form_field_settings.json (legacy path).
     *
     * @return array<string, mixed>
     */
    public static function buildPdfConfigFromSettings(): array
    {
        $base = config('medical_form_pdf');
        $page1 = [];
        $page2 = [];

        foreach (self::registry() as $num => $meta) {
            $pdf = self::pdfForNumber((int) $num);
            if ($pdf === null) {
                continue;
            }

            foreach ($pdf as $dataKey => $pos) {
                if ($dataKey === 'clinical') {
                    $page1['clinical'] = array_replace_recursive(
                        $base['page1']['clinical'] ?? [],
                        $page1['clinical'] ?? [],
                        $pos
                    );

                    continue;
                }
                if ($dataKey === 'pelvic') {
                    foreach ($pos as $k => $v) {
                        if ($k === 'pelvic_pelvic_x') {
                            continue;
                        }
                        $page1[$k] = $v;
                    }

                    continue;
                }

                $page = (int) ($pos['page'] ?? 1);
                unset($pos['page']);
                if ($page === 2) {
                    $page2[$dataKey] = $pos;
                } else {
                    $page1[$dataKey] = $pos;
                }
            }
        }

        return [
            'template' => $base['template'],
            'font' => $base['font'],
            'page' => $base['page'] ?? [],
            'x_offset' => (float) ($base['x_offset'] ?? 0),
            'y_offset' => (float) ($base['y_offset'] ?? 0),
            'page1' => array_merge($base['page1'], $page1),
            'page2' => array_merge($base['page2'], $page2),
        ];
    }

    /**
     * @return array<string, array<string, float|int>>|null
     */
    public static function pdfForNumber(int $number): ?array
    {
        $settings = self::settings();
        $key = (string) $number;

        if (isset($settings[$key]['pdf'])) {
            return self::normalizePdfBlock($settings[$key]['pdf'], $number);
        }

        return self::defaultPdfForNumber($number);
    }



    public static function keysForStep(int $step): array
    {
        $keys = [];
        foreach (self::registry() as $num => $meta) {
            if ((int) ($meta['step'] ?? 0) !== $step || ! self::inWizard((int) $num)) {
                continue;
            }
            $keys = array_merge($keys, self::keysForNumber((int) $num));
        }

        return array_values(array_unique($keys));
    }

    public static function validationRulesForStep(int $step): array
    {
        $rules = [];
        foreach (self::registry() as $num => $meta) {
            if ((int) ($meta['step'] ?? 0) !== $step || ! self::inWizard((int) $num)) {
                continue;
            }
            $rules = array_merge($rules, self::validationRulesForNumber((int) $num));
        }

        return $rules;
    }

    public static function validationRulesForNumber(int $number): array
    {
        $meta = self::meta($number);
        if (! $meta || ! self::inWizard($number)) {
            return [];
        }

        $required = self::isRequired($number);
        $req = $required ? 'required' : 'nullable';
        $type = $meta['type'] ?? 'text';
        $rules = [];

        switch ($type) {
            case 'clinical':
                foreach (self::keysForNumber($number) as $key) {
                    $rules[$key] = [$req, Rule::in(['normal', 'abnormal', 'ne'])];
                }
                break;

            case 'pelvic':
                $rules['pelvic_exam'] = ['nullable', 'array'];
                $rules['pelvic_exam.*'] = [Rule::in(['vaginal', 'rectal'])];
                break;

            case 'dental':
                $rules['dental_classification'] = array_merge([$req, Rule::in(DentalRemarks::CLASSIFICATIONS)]);
                $rules['dental_remarks_detail'] = ['nullable', 'string', 'max:2000'];
                break;

            case 'audiometer':
                foreach (AudiometerChart::formKeys() as $key) {
                    $rules[$key] = ['nullable', 'string', 'max:20'];
                }
                break;

            case 'pulhes':
                foreach (['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'] as $key) {
                    $rules[$key] = ['nullable', 'string', 'max:10'];
                }
                break;

            case 'signatures':
                $rules['physician_count'] = ['nullable', 'integer', 'min:0', 'max:1'];
                $rules['physician_practitioner_id'] = ['nullable', 'integer', 'exists:practitioners,id'];
                $rules['physician_name'] = ['nullable', 'string', 'max:255'];
                foreach (['optometrist', 'ent', 'np', 'dentist'] as $role) {
                    $rules[$role.'_enabled'] = ['nullable', 'in:0,1,on'];
                    $rules[$role.'_practitioner_id'] = ['nullable', 'integer', 'exists:practitioners,id'];
                    $rules[$role.'_name'] = ['nullable', 'string', 'max:255'];
                }
                $rules['reviewing_officer_enabled'] = ['nullable', 'in:0,1,on'];
                $rules['reviewing_officer_name'] = ['nullable', 'string', 'max:255'];
                $rules['attached_sheets'] = ['nullable', 'string', 'max:5'];
                break;

            case 'group':
                foreach (self::keysForNumber($number) as $key) {
                    $rules[$key] = array_merge(
                        [$req],
                        self::ruleSuffixForKey($key)
                    );
                }
                break;

            case 'select':
                foreach (self::keysForNumber($number) as $key) {
                    if ($key === 'sex') {
                        $rules[$key] = [$req, Rule::in(['Male', 'Female'])];
                    } elseif ($key === 'govt_service_type') {
                        $rules[$key] = ['nullable', Rule::in(['military', 'civilian'])];
                    } else {
                        $rules[$key] = [$req, 'string', 'max:255'];
                    }
                }
                break;

            case 'build':
                $rules['build'] = [$req, Rule::in(['slender', 'medium', 'heavy', 'obese'])];
                break;

            case 'fitness':
                $rules['fitness_status'] = [$req, Rule::in(['A', 'B', 'C', 'D'])];
                break;

            case 'date':
                foreach (self::keysForNumber($number) as $key) {
                    $rules[$key] = [$req, 'date'];
                }
                break;

            case 'textarea':
                foreach (self::keysForNumber($number) as $key) {
                    $rules[$key] = array_merge([$req, 'string'], [self::maxLengthForKey($key)]);
                }
                break;

            default:
                foreach (self::keysForNumber($number) as $key) {
                    $rules[$key] = array_merge([$req, 'string'], [self::maxLengthForKey($key)]);
                }
        }

        return $rules;
    }

    public static function ensureSettingsFile(): void
    {
        if (File::exists(self::settingsPath())) {
            return;
        }

        self::saveSettings(self::buildDefaultSettings());
    }

    public static function resetSettings(): void
    {
        self::saveSettings(self::buildDefaultSettings());
        self::flush();
    }

    /**
     * @return array<string, array{required: bool, pdf?: array}>
     */
    public static function buildDefaultSettings(): array
    {
        $pdf = config('medical_form_pdf');
        $settings = [];

        foreach (self::registry() as $num => $meta) {
            $n = (int) $num;
            $entry = ['required' => self::defaultRequired($n)];
            $block = self::defaultPdfForNumber($n);
            if ($block !== null) {
                $entry['pdf'] = $block;
            }
            $settings[(string) $n] = $entry;
        }

        return $settings;
    }

    public static function defaultRequired(int $number): bool
    {
        return match ($number) {
            1, 2, 4, 5, 6, 7, 12, 15 => true,
            18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42 => true,
            51, 52, 55, 74, 77 => true,
            default => false,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function defaultPdfForNumber(int $number): ?array
    {
        $p1 = config('medical_form_pdf.page1', []);
        $p2 = config('medical_form_pdf.page2', []);

        if ($number >= 18 && $number <= 42) {
            if ($number === 18) {
                return ['clinical' => $p1['clinical']];
            }

            return null;
        }

        if ($number === 43) {
            return [
                'pelvic' => [
                    'pelvic_vaginal_x' => array_merge($p1['pelvic_vaginal_x'], ['page' => 1]),
                    'pelvic_rectal_x' => array_merge($p1['pelvic_rectal_x'], ['page' => 1]),
                ],
            ];
        }

        if ($number === 71) {
            $block = [];
            foreach (AudiometerChart::positions() as $id => $pos) {
                $block['audiometer_'.$id] = array_merge($p2['audiometer_'.$id] ?? $pos, ['page' => 2]);
            }

            return $block;
        }

        $meta = self::meta($number);
        if (! $meta) {
            return null;
        }

        $keys = $meta['pdf_keys'] ?? self::keysForNumber($number);
        if (($meta['type'] ?? '') === 'build') {
            $keys = ['build_slender_x', 'build_medium_x', 'build_heavy_x', 'build_obese_x'];
        }
        if (($meta['type'] ?? '') === 'fitness') {
            $keys = ['fitness_a_x', 'fitness_b_x', 'fitness_c_x', 'fitness_d_x'];
        }

        $block = [];
        foreach ($keys as $key) {
            if (isset($p1[$key])) {
                $block[$key] = array_merge($p1[$key], ['page' => 1]);
            } elseif (isset($p2[$key])) {
                $block[$key] = array_merge($p2[$key], ['page' => 2]);
            }
        }

        if ($number === 44 && isset($p1['dental_remarks'])) {
            $block['dental_remarks'] = array_merge($p1['dental_remarks'], ['page' => 1]);
        }

        return $block === [] ? null : $block;
    }

    /**
     * @param  array<string, mixed>  $pdf
     * @return array<string, mixed>
     */
    private static function normalizePdfBlock(array $pdf, int $number): array
    {
        if (isset($pdf['x']) || isset($pdf['y'])) {
            $keys = self::primaryPdfKeys($number);
            $key = $keys[0] ?? 'field_'.$number;

            return [$key => array_merge($pdf, ['page' => $pdf['page'] ?? self::defaultPageForKey($key)])];
        }

        return $pdf;
    }

    /** @return list<string> */
    public static function primaryPdfKeys(int $number): array
    {
        $meta = self::meta($number);
        if (! $meta) {
            return [];
        }

        if ($number >= 18 && $number <= 42) {
            return ['clinical'];
        }

        if ($number === 43) {
            return ['pelvic'];
        }

        if (($meta['type'] ?? '') === 'dental') {
            return ['dental_remarks'];
        }

        if (($meta['type'] ?? '') === 'pulhes') {
            return ['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'];
        }

        if (($meta['type'] ?? '') === 'signatures') {
            return ['attached_sheets'];
        }

        return $meta['pdf_keys'] ?? self::keysForNumber($number);
    }

    public static function defaultPageForKey(string $key): int
    {
        return isset(config('medical_form_pdf.page2', [])[$key]) ? 2 : 1;
    }

    /** @return array<int, string> */
    private static function ruleSuffixForKey(string $key): array
    {
        $max = match (true) {
            in_array($key, ['home_address', 'next_of_kin', 'examining_facility'], true) => 'max:500',
            in_array($key, ['notes'], true) => 'max:5000',
            in_array($key, ['profile_p', 'profile_u', 'profile_l', 'profile_h', 'profile_e', 'profile_s'], true) => 'in:1,2,3,3T',
            in_array($key, ['rtdo_r', 'rtdo_t', 'rtdo_d', 'rtdo_o'], true) => 'in:P1,P2,P3,P3T',
            $key === 'others_information' => 'max:1000',
            $key === 'clinical_evaluation_notes' => 'max:2000',
            in_array($key, ['summary_defects', 'dental_remarks'], true) => 'max:2000',
            in_array($key, ['recommendations', 'serology', 'chest_xray'], true) => 'max:500',
            in_array($key, ['audiometer_freqs', 'disqualifying_defects'], true) => 'max:1000',
            in_array($key, ['psychological_test', 'other_tests'], true) => 'max:500',
            str_starts_with($key, 'bp_'), str_starts_with($key, 'pulse_'), str_starts_with($key, 'hearing_') => 'max:20',
            str_starts_with($key, 'refraction_'), str_starts_with($key, 'near_vision_') => 'max:50',
            str_starts_with($key, 'heterophoria_') => 'max:50',
            str_starts_with($key, 'depth_') => 'max:100',
            $key === 'race' => 'max:100',
            in_array($key, ['height', 'weight', 'temperature'], true) => 'max:50',
            $key === 'total_yrs_govt_service' => 'max:50',
            default => 'max:255',
        };

        if ($key === 'govt_service_type') {
            return [Rule::in(['military', 'civilian'])];
        }

        return ['string', $max];
    }

    private static function maxLengthForKey(string $key): string
    {
        $suffix = self::ruleSuffixForKey($key);

        return $suffix[1] ?? 'max:255';
    }
}