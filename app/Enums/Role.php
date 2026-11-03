<?php

namespace App\Enums;

enum Role: string
{
    case Recruiter = 'RECRUITER';
    case Registrar = 'REGISTRAR';
    case Dental = 'DENTAL';
    case Gpe = 'GPE';
    case Np = 'NP';
    case Laboratory = 'LABORATORY';
    case Eent = 'EENT';
    case Ecg = 'ECG';
    case Doctor = 'DOCTOR';
    case DoctorGpe = 'DOCTOR_GPE';
    case DoctorDental = 'DOCTOR_DENTAL';
    case DoctorNp = 'DOCTOR_NP';
    case DoctorEcgXray = 'DOCTOR_ECG_XRAY';
    case DoctorOpthaEent = 'DOCTOR_OPTHA_EENT';
    case DoctorLabDrugtest = 'DOCTOR_LAB_DRUGTEST';
    case Executive = 'EXECUTIVE';
    case Pme = 'PME';

    public static function tryFromUsername(string $username): ?self
    {
        return self::tryFrom(strtoupper(trim($username)));
    }


    public function routeName(): ?string
    {
        return config('roles.routes.'.$this->value);
    }

    public function isDoctor(): bool
    {
        return str_starts_with($this->value, 'DOCTOR');
    }

    public function isStationClerk(): bool
    {
        return in_array($this, [
            self::Dental,
            self::Gpe,
            self::Np,
            self::Laboratory,
            self::Eent,
            self::Ecg,
        ], true);
    }
}
