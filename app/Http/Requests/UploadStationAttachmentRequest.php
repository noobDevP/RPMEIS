<?php

namespace App\Http\Requests;

use App\Support\StationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadStationAttachmentRequest extends FormRequest
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
            'attachment' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }
}
