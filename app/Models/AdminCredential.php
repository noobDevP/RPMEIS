<?php

namespace App\Models;

use App\Models\Concerns\HasTwoFactorAuthentication;
use Illuminate\Database\Eloquent\Model;

class AdminCredential extends Model
{
    use HasTwoFactorAuthentication;

    protected $table = 'admin_credential';

    protected $primaryKey = 'admin_id';

    public $timestamps = false;

    protected $fillable = [
        'admin_username',
        'admin_password',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'session_id',
    ];

    protected $hidden = [
        'admin_password',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'session_id',
    ];
}
