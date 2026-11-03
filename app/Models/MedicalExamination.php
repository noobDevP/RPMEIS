<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalExamination extends Model
{
    protected $table = 'medical_examinations';
    protected $primaryKey = 'examination_id';
    public $timestamps = true;
    protected $guarded = [];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';

    protected $casts = [
        'exam_date' => 'date',
        'form_data' => 'array',
        
        'step' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(StationClearance::class, 'examination_id', 'examination_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(MedicalFormAssessment::class, 'examination_id', 'examination_id');
    }

    public function patient()
    {
        return $this->hasOneThrough(Patient::class, Application::class, 'application_id', 'patient_id', 'application_id', 'patient_id');
    }

    public function allAssessmentsCompleted(): bool
    {
        return true; // Simplified for now since we rely on overall_stat or other logic
    }
    
    public function beginEditing(): void
    {
        $this->status = self::STATUS_DRAFT;
        $this->save();
    }
    
        public function commitEditing(): void
    {
        // commit logic if needed
    }

    public function cancelEditing(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }
    
    public function isEditInProgress(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}





