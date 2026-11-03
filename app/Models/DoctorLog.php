<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorLog extends Model
{
    protected $table = 'logs_doctors';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'patient_no_logs' => 'integer',
            'timestamp_dental_logs' => 'datetime',
            'timestamp_ophtha_eent_logs' => 'datetime',
            'timestamp_ecg_xray_logs' => 'datetime',
            'timestamp_drugtest_laboratory_logs' => 'datetime',
            'timestamp_np_logs' => 'datetime',
            'timestamp_gpe' => 'datetime',
        ];
    }
}
