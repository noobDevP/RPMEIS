<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasTwoFactorAuthentication;
use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    use HasTwoFactorAuthentication;

    protected $table = 'user_table';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    protected $fillable = [
        'username',
        'password',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'session_id',
        'user_username',
        'active_stat',
        'rank',
        'afpos',
        'status',
        'position',
        'e_signature_path',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'session_id',
    ];

    public function role(): ?Role
    {
        return Role::tryFromUsername((string) $this->username);
    }

    public function signatureLine(): string
    {
        // Format: rank(abbreviation) Fullname AFPOS (STATUS) - PA Position
        // ex: COL JUAN G. DELA CRUZ MC (GSC) - PA MEMBER
        
        $parts = [];
        if ($this->rank) {
            $parts[] = strtoupper($this->rank);
        }
        
        // Remove 'Dr.' prefix if it exists to satisfy user request for full names
        $name = strtoupper($this->user_username ?: $this->username);
        $name = preg_replace('/^DR\.?\s+/i', '', $name);
        $parts[] = $name;
        
        if ($this->afpos && $this->status) {
            $parts[] = strtoupper($this->afpos);
            $parts[] = "(" . strtoupper($this->status) . ")";
            $parts[] = "PA -";
        } elseif (!$this->afpos && $this->status) {
            $parts[] = strtoupper($this->status);
            $parts[] = "(PA) -";
        } else {
            if ($this->afpos) {
                $parts[] = strtoupper($this->afpos);
            }
            $parts[] = "PA -";
        }
        
        if ($this->position) {
            $parts[] = strtoupper($this->position);
        }
        
        return implode(' ', $parts);
    }

    public function isActive(): bool
    {
        $val = strtolower(trim((string) $this->active_stat));
        // Supports both the DB default ('active') and legacy boolean ('1')
        return $val === 'active' || $val === '1';
    }

    public function displayName(): string
    {
        return (string) ($this->user_username ?: $this->username);
    }
}
