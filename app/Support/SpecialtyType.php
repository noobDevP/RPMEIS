<?php

namespace App\Support;

final class SpecialtyType
{
    public const DENTAL = 'dental';

    public const OCULAR = 'ocular';

    public const HEARING = 'hearing';

    public const PSYCH = 'psych';

    public const ECG = 'ecg';

    public const XRAY = 'xray';

    public const LAB = 'lab';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DENTAL,
            self::OCULAR,
            self::HEARING,
            self::PSYCH,
            self::ECG,
            self::XRAY,
            self::LAB,
        ];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::DENTAL => 'Dental',
            self::OCULAR => 'Ocular',
            self::HEARING => 'Hearing',
            self::PSYCH => 'Psychomotor',
            self::ECG => 'ECG',
            self::XRAY => 'Chest X-Ray',
            self::LAB => 'Laboratory',
            default => ucfirst($type),
        };
    }

    public static function userRole(string $type): string
    {
        return match ($type) {
            self::DENTAL => \App\Models\User::ROLE_DENTIST,
            self::OCULAR => \App\Models\User::ROLE_OPTOMETRIST,
            self::HEARING => \App\Models\User::ROLE_ENT,
            self::PSYCH => \App\Models\User::ROLE_NP,
            self::ECG => \App\Models\User::ROLE_ECG,
            self::XRAY => \App\Models\User::ROLE_ECG,
            self::LAB => 'DOCTOR_LAB_DRUGTEST',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    public static function practitionerPosition(string $type): string
    {
        return match ($type) {
            self::DENTAL => \App\Models\Practitioner::POSITION_DENTIST,
            self::OCULAR => \App\Models\Practitioner::POSITION_OPTOMETRIST,
            self::HEARING => \App\Models\Practitioner::POSITION_ENT,
            self::PSYCH => \App\Models\Practitioner::POSITION_NP,
            self::ECG => \App\Models\Practitioner::POSITION_ECG,
            self::XRAY => \App\Models\Practitioner::POSITION_ECG,
            self::LAB => 'LABORATORY',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    public static function assignmentKey(string $type): string
    {
        return match ($type) {
            self::DENTAL => 'dentist_practitioner_id',
            self::OCULAR => 'optometrist_practitioner_id',
            self::HEARING => 'ent_practitioner_id',
            self::PSYCH => 'np_practitioner_id',
            self::ECG => 'ecg_practitioner_id',
            self::XRAY => 'ecg_practitioner_id',
            self::LAB => 'lab_practitioner_id',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    public static function enabledKey(string $type): string
    {
        return match ($type) {
            self::DENTAL => 'dentist_enabled',
            self::OCULAR => 'optometrist_enabled',
            self::HEARING => 'ent_enabled',
            self::PSYCH => 'np_enabled',
            self::ECG => 'ecg_enabled',
            self::XRAY => 'ecg_enabled',
            self::LAB => 'lab_enabled',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    public static function nameKey(string $type): string
    {
        return match ($type) {
            self::DENTAL => 'dentist_name',
            self::OCULAR => 'optometrist_name',
            self::HEARING => 'ent_name',
            self::PSYCH => 'np_name',
            self::ECG => 'ecg_name',
            self::XRAY => 'ecg_name',
            self::LAB => 'lab_name',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    /**
     * Registry field numbers filled by this specialty (outside the medical wizard).
     *
     * @return list<int>
     */
    public static function fieldNumbers(string $type): array
    {
        return match ($type) {
            self::DENTAL => [44],
            self::OCULAR => range(59, 69),
            self::HEARING => [70, 71],
            self::PSYCH => [72],
            self::ECG => [57, 58],
            self::XRAY => [48],
            self::LAB => [45, 46, 47, 49, 50],
            default => [],
        };
    }

    public static function routePrefix(string $type): string
    {
        return match ($type) {
            self::DENTAL => 'dental-assessment',
            self::OCULAR => 'ocular-assessment',
            self::HEARING => 'hearing-assessment',
            self::PSYCH => 'psych-assessment',
            self::ECG => 'ecg-assessment',
            self::XRAY => 'xray-assessment',
            self::LAB => 'lab-assessment',
            default => throw new \InvalidArgumentException("Unknown specialty: {$type}"),
        };
    }

    public static function hasUploadDownload(string $type): bool
    {
        return $type === self::DENTAL;
    }
}