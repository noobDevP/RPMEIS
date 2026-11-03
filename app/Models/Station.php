<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $table = 'stations';
    protected $primaryKey = 'station_id';
    public $timestamps = true;
    protected $guarded = [];
}
