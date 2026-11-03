<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    protected $table = 'races';
    protected $primaryKey = 'race_id';
    public $timestamps = false;
    protected $guarded = [];
}
