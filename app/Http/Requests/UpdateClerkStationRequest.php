<?php

namespace App\Http\Requests;

use App\Support\StationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClerkStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('staff.logged_in', false);
    }

    public function rules(): array
    {
        return [
            'station' => ['required', 'string', Rule::in(StationRegistry::keys())],
            'patient_no' => ['required', 'integer'],
            'diagnosis' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'action' => ['nullable', 'string', 'max:255'],
            'user_username' => ['nullable', 'string', 'max:255'],
            'fileAttachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
