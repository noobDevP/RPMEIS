<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->find(1);
    }
}
