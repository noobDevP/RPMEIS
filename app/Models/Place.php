<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $table = 'place';

    protected $primaryKey = 'place_id';

    public $timestamps = false;

    protected $fillable = [
        'place_name',
    ];
}
