<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBase64PhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('staff.logged_in', false);
    }

    public function rules(): array
    {
        return [
            'imageData' => ['required', 'string'],
        ];
    }
}
