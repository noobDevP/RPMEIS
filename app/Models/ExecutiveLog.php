<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutiveLog extends Model
{
    protected $table = 'executive_logs';

    protected $fillable = [
        'action',
        'patient_no',
        'patient_id',
        'user_username',
    ];

    public function setPatientIdAttribute($value): void
    {
        $this->attributes['patient_no'] = (string) $value;
    }

    public function getPatientIdAttribute(): ?string
    {
        return $this->patient_no;
    }
}
