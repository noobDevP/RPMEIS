<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SigningDoctor extends Model
{
    protected $fillable = [
        'name',
        'rank',
        'afpos',
        'status',
        'position',
    ];

    public function signatureLine(): string
    {
        $parts = [];
        if ($this->rank) $parts[] = strtoupper($this->rank);
        $name = strtoupper($this->name ?? '');
        $name = preg_replace('/^DR\.?\s+/i', '', $name);
        $parts[] = $name;
        if ($this->afpos && $this->status) {
            $parts[] = strtoupper($this->afpos) . " (" . strtoupper($this->status) . ") PA -";
        } elseif (!$this->afpos && $this->status) {
            $parts[] = strtoupper($this->status) . " (PA) -";
        } else {
            if ($this->afpos) $parts[] = strtoupper($this->afpos);
            $parts[] = "PA -";
        }
        if ($this->position) $parts[] = strtoupper($this->position);
        
        return implode(' ', $parts);
    }
}
