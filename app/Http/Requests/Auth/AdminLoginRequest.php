<?php

namespace App\Http\Requests\Auth;

class AdminLoginRequest extends BaseLoginRequest
{
    public function rules(): array
    {
        return $this->baseRules();
    }
}
