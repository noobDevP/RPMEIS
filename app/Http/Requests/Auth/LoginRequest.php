<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function username(): string
    {
        return strtoupper(trim((string) $this->input('username')));
    }

    public function password(): string
    {
        return trim((string) $this->input('password'));
    }
}
