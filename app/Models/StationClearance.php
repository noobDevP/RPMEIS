<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StationClearance extends Model
{
    protected $table = 'station_clearances';
    protected $primaryKey = 'clearance_id';
    public $timestamps = true;
    protected $guarded = [];

    public function examination(): BelongsTo
    {
        return $this->belongsTo(MedicalExamination::class, 'examination_id', 'examination_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClearanceAttachment::class, 'clearance_id', 'clearance_id');
    }

    // --- Legacy Accessors to prevent immediate breakage ---
    public function getStationKeyAttribute()
    {
        return $this->station->station_key;
    }
}
