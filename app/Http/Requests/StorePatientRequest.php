<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('staff.logged_in', false);
    }

    public function rules(): array
    {
        return [
            'applicant_no' => ['required'],
            'rank' => ['nullable', 'string', 'max:255'],
            'serialNo' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'birthdate' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:255'],
            'raceSelect' => ['nullable', 'string', 'max:255'],
            'placeOfBirth' => ['nullable', 'string', 'max:255'],
            'currentAddress' => ['nullable', 'string', 'max:255'],
            'placeOfExamination' => ['nullable', 'string', 'max:255'],
            'dateOfExamination' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
            'unitorg' => ['nullable', 'string', 'max:255'],
            'appcode' => ['nullable', 'string', 'max:255'],
            'otherData' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
            'photo_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
