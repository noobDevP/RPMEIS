<?php

namespace App\Support;

use App\Models\StaffUser;

class PractitionerSignatures
{
    /**
     * @return list<string>
     */
    public static function practitionerIdKeys(): array
    {
        $keys = [
            'physician_practitioner_id',
            'physician_1_practitioner_id',
            'optometrist_practitioner_id',
            'ent_practitioner_id',
            'np_practitioner_id',
            'dentist_practitioner_id',
        ];

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function resolvePayload(array $payload): array
    {
        $physicianId = (int) ($payload['physician_practitioner_id'] ?? $payload['physician_1_practitioner_id'] ?? 0);
        if ($physicianId > 0) {
            $doctor = StaffUser::find($physicianId);
            if ($doctor) {
                $line = $doctor->signatureLine();
                $payload['physician_name'] = $line;
                $payload['physician_1_name'] = $line;
            }
        }

        foreach ([
            SpecialtyType::OCULAR,
            SpecialtyType::HEARING,
            SpecialtyType::PSYCH,
            SpecialtyType::ECG,
            SpecialtyType::DENTAL,
        ] as $specialty) {
            $idKey = SpecialtyType::assignmentKey($specialty);
            $nameKey = SpecialtyType::nameKey($specialty);
            $id = (int) ($payload[$idKey] ?? 0);

            if ($id > 0) {
                $practitioner = StaffUser::find($id);
                if ($practitioner) {
                    $payload[$nameKey] = $practitioner->signatureLine();
                }
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyAssignedSpecialist(array $data, string $specialty, ?int $practitionerId): array
    {
        if (! $practitionerId) {
            return $data;
        }

        $assignmentKey = SpecialtyType::assignmentKey($specialty);
        if (! empty($data[$assignmentKey])) {
            return $data;
        }

        $practitioner = StaffUser::find($practitionerId);
        if (! $practitioner) {
            return $data;
        }

        $data[$assignmentKey] = (string) $practitionerId;
        $data[SpecialtyType::enabledKey($specialty)] = '1';
        $data[SpecialtyType::nameKey($specialty)] = $practitioner->signatureLine();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyAssignedDentist(array $data, ?int $practitionerId): array
    {
        return self::applyAssignedSpecialist($data, SpecialtyType::DENTAL, $practitionerId);
    }
}