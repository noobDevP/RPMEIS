<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiefSurgeon extends Model
{
    protected $fillable = [
        'name',
        'rank',
        'afpos',
        'status',
        'position',
        'e_signature_path',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function signatureLine(): string
    {
        $parts = [];
        if ($this->rank) {
            $parts[] = strtoupper($this->rank);
        }
        
        $name = preg_replace('/^DR\.?\s+/i', '', $this->name ?? '');
        $parts[] = strtoupper($name);
        
        if ($this->afpos) {
            $parts[] = strtoupper($this->afpos);
        }
        if ($this->status) {
            $parts[] = "(" . strtoupper($this->status) . ")";
        }
        
        $parts[] = "- PA";
        
        if ($this->position) {
            $parts[] = strtoupper($this->position);
        }
        
        return implode(' ', $parts);
    }
}
