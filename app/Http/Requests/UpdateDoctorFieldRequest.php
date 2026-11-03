<?php

namespace App\Http\Requests;

use App\Support\StationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('staff.logged_in', false);
    }

    public function rules(): array
    {
        $station = $this->input('station');
        $allowedFields = ['diagnosis', 'remarks', 'action'];

        return [
            'station' => ['required', 'string', Rule::in(StationRegistry::keys())],
            'patient_no' => ['required', 'integer'],
            'field' => ['required', 'string', Rule::in($allowedFields)],
            'value' => ['nullable', 'string'],
        ];
    }
}
