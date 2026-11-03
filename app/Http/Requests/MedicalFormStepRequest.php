<?php

namespace App\Http\Requests;

use App\Support\FormFieldRegistry;
use App\Support\FormSignatures;
use Illuminate\Foundation\Http\FormRequest;

class MedicalFormStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->input('direction') === 'previous' || $this->has('target_step')) {
            return [];
        }

        $step = $this->routeIs('medical-form.submit')
            ? 5
            : (int) $this->route('step', 0);

        return FormFieldRegistry::validationRulesForStep($step);
    }

    /**
     * @return list<string>
     */
    public function stepFieldKeys(): array
    {
        $step = $this->routeIs('medical-form.submit')
            ? 5
            : (int) $this->route('step', 0);

        return FormFieldRegistry::keysForStep($step);
    }

    /**
     * @return array<string, mixed>
     */
    public function stepPayload(): array
    {
        $payload = [];
        $signatureKeys = FormSignatures::formKeys();

        foreach ($this->stepFieldKeys() as $key) {
            if (in_array($key, $signatureKeys, true)) {
                $payload[$key] = $this->input($key);

                continue;
            }

            if ($this->has($key)) {
                $payload[$key] = $this->input($key);
            }
        }

        return $payload;
    }
}