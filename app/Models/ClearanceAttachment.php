<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceAttachment extends Model
{
    protected $table = 'clearance_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = true;
    protected $guarded = [];

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(StationClearance::class, 'clearance_id', 'clearance_id');
    }
}
