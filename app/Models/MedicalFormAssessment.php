<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalFormAssessment extends Model
{
    protected $table = 'medical_form_assessments';
    protected $guarded = [];

    protected $casts = [
        'assessment_data' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';

    public function examination(): BelongsTo
    {
        return $this->belongsTo(MedicalExamination::class, 'examination_id', 'examination_id');
    }

    public static function latestForMedicalForm(int $examinationId, string $type)
    {
        return self::where('examination_id', $examinationId)
            ->where('type', $type)
            ->latest()
            ->first();
    }

    public function toFormData(): array
    {
        $data = $this->assessment_data ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }
}