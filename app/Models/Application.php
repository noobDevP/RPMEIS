<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'application_id';
    public $timestamps = true;
    protected $guarded = [];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(MedicalExamination::class, 'application_id', 'application_id');
    }

    public function latestExamination()
    {
        if ($this->relationLoaded('examinations')) {
            return $this->examinations->sortByDesc('examination_id')->first();
        }

        return $this->examinations()->latest('examination_id')->first();
    }
}
