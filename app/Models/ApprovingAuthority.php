<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovingAuthority extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'rank',
        'afpos',
        'status',
        'position',
        'e_signature_path',
    ];

    public function signatureLine(): string
    {
        $parts = [];
        if ($this->rank) {
            $parts[] = strtoupper($this->rank);
        }
        
        // Remove 'Dr.' prefix if it exists to satisfy user request for full names
        $name = preg_replace('/^DR\.?\s+/i', '', $this->name ?? '');
        $parts[] = strtoupper($name);
        
        if ($this->afpos) {
            $parts[] = strtoupper($this->afpos);
        }
        if ($this->status) {
            $parts[] = "(" . strtoupper($this->status) . ")";
        }
        
        // Automatically append PA and POSITION
        $parts[] = "- PA";
        
        if ($this->position) {
            $parts[] = strtoupper($this->position);
        }
        
        return implode(' ', $parts);
    }

    public static function getAllWithChiefDoctor()
    {
        $authorities = self::orderBy('name')->get();
        $hasDefault = $authorities->contains('is_default', true);

        $chief = \App\Models\StaffUser::where('username', 'doctor')->first();
        if ($chief) {
            $chiefAuth = new self([
                'name' => $chief->user_username,
                'rank' => $chief->rank,
                'afpos' => $chief->afpos,
                'status' => $chief->status,
                'position' => $chief->position,
                'e_signature_path' => $chief->e_signature_path,
            ]);
            $chiefAuth->id = 'chief_doctor';
            $chiefAuth->is_chief = true;
            $chiefAuth->is_default = !$hasDefault;
            $authorities->prepend($chiefAuth);
        }

        return $authorities;
    }
}