<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamCenter extends Model
{
    protected $table = 'exam_centers';
    protected $primaryKey = 'center_id';
    public $timestamps = false;
    protected $guarded = [];
}
