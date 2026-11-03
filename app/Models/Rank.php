<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $table = 'ranks';
    protected $primaryKey = 'rank_id';
    public $timestamps = false;
    protected $guarded = [];
}
