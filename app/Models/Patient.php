<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $timestamps = true;
    protected $guarded = [];
    // Auto eager-load profile for fast access to photo_path & details without N+1
    protected $with = ['profile'];

    // Computed/virtual attributes serialized into JSON responses
    protected $appends = ['applicant_no', 'patient_no', 'overall_stat', 'appcode', 'batch', 'photo_url', 'photo_path', 'age'];

    protected $casts = [
        'birthdate' => 'date',
        'is_activated' => 'boolean',
    ];

    // --- Relationships ---
    public function profile(): HasOne
    {
        return $this->hasOne(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function serviceRecord(): HasOne
    {
        return $this->hasOne(ServiceRecord::class, 'patient_id', 'patient_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'patient_id', 'patient_id');
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    // --- Helpers ---
    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function fullNameWithInitial(): string
    {
        $middleInitial = null;
        if (!empty($this->middle_name)) {
            $middleInitial = strtoupper(substr(trim($this->middle_name), 0, 1)) . '.';
        }

        return trim(implode(' ', array_filter([
            $this->first_name,
            $middleInitial,
            $this->last_name,
        ])));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate ? $this->birthdate->age : null;
    }

    public function isActive(): bool
    {
        // Check latest application status or user activation
        return $this->is_activated;
    }

    // --- Legacy Helpers (Virtual attributes for API/JS backward compatibility) ---
    public function getApplicantNoAttribute() { return $this->applications()->latest()->first()?->applicant_no; }
    public function getPatientNoAttribute() { return $this->patient_id; }
    public function getOverallStatAttribute() { return $this->applications()->latest()->first()?->latestExamination()?->overall_stat ?? 'NOT DONE'; }
    public function getAppcodeAttribute() { return $this->applications()->latest()->first()?->app_code; }
    public function getBatchAttribute() { return $this->applications()->latest()->first()?->batch; }
    public function getPurposeAttribute() { return $this->applications()->latest()->first()?->purpose; }
    public function getMedicalFormAttribute(): ?MedicalExamination
    {
        $app = $this->relationLoaded('applications') ? $this->applications->first() : $this->applications()->latest()->first();
        return $app?->latestExamination();
    }
    public function getDoctorsDiagnosisAttribute(): string
    {
        return (string) ($this->medical_form?->form_data['doctors_diagnosis'] ?? '');
    }
    public function getDoctorsRemarksAttribute(): string
    {
        return (string) ($this->medical_form?->form_data['doctors_remarks'] ?? '');
    }
    public function getApprovingAuthorityNameAttribute(): string
    {
        return (string) ($this->medical_form?->form_data['approving_authority_name'] ?? '');
    }
    public function getPhotoPathAttribute(): ?string
    {
        return $this->profile?->photo_path;
    }

    public function getPhotoUrlAttribute(): string
    {
        $path = trim((string) ($this->profile?->photo_path ?? ''));
        if ($path === '') {
            return '/images/blank.jpg';
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '//')
        ) {
            return $path;
        }

        $cleaned = ltrim($path, '/');

        // 1. Direct file in public directory (e.g. profile_pic/..., uploads/...)
        if (file_exists(public_path($cleaned))) {
            return '/' . $cleaned;
        }

        // 2. File in storage/app/public/
        if (file_exists(storage_path('app/public/' . $cleaned))) {
            return '/storage/' . $cleaned;
        }

        // 3. Path already starts with storage/
        if (str_starts_with($cleaned, 'storage/')) {
            return '/' . $cleaned;
        }

        return '/' . $cleaned;
    }
    public function getFirstNameAttribute() { return $this->attributes['first_name'] ?? null; }
    public function getMiddleNameAttribute() { return $this->attributes['middle_name'] ?? null; }
    public function getLastNameAttribute() { return $this->attributes['last_name'] ?? null; }

    public function approvalValue(string $stationKey): ?string
    {
        $exam = $this->applications->first()?->latestExamination();
        if (! $exam) {
            return null;
        }

        return $exam->clearances
            ->first(fn ($c) => $c->station?->station_key === $stationKey)
            ?->approval_status;
    }
}


