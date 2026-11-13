<?php

namespace App\Http\Requests\Auth;

class LoginRequest extends BaseLoginRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'user_username' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\@\s]*$/'],
        ]);
    }

    protected function additionalCheckedInputs(): array
    {
        return [(string) $this->input('user_username', '')];
    }
}
