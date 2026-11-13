<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Common validation rules shared by staff and admin login requests.
     */
    protected function baseRules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\.\@\s]+$/',
                'not_regex:/\.\./',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $this->hasPathShapedPassword($value)) {
                        $fail('Invalid characters detected.');
                    }
                },
            ],
        ];
    }

    /**
     * Additional username-like fields checked for scanner payloads in subclasses.
     *
     * @return array<int, string>
     */
    protected function additionalCheckedInputs(): array
    {
        return [];
    }

    /**
     * Reject scanner injection and path-traversal payloads with HTTP 400 JSON
     * so automated scanners receive a uniform 400 status rather than following
     * 302 redirects to HTML views with reflected/escaped input.
     */
    protected function failedValidation(Validator $validator): void
    {
        $inputsToCheck = array_merge(
            [(string) $this->input('username', '')],
            $this->additionalCheckedInputs(),
        );

        foreach ($inputsToCheck as $value) {
            if ($this->hasUnsafeScannerPayload($value)) {
                $this->throwBadRequest();
            }
        }

        if ($this->hasPathShapedPassword((string) $this->input('password', ''))) {
            $this->throwBadRequest();
        }

        parent::failedValidation($validator);
    }

    private function throwBadRequest(): never
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Bad Request',
            'message' => 'Invalid characters detected in login parameters.',
        ], 400));
    }

    private function hasUnsafeScannerPayload(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return str_contains($value, '..')
            || str_contains($value, "\0")
            || preg_match('/[^a-zA-Z0-9_\-\.\@\s]|--/', $value) === 1;
    }

    /**
     * Passwords are only ever hashed/compared, never used in file paths, so legitimate
     * passwords may contain "/" or "%". Only reject values that are path-shaped:
     * a leading path separator, "../" or "..\\" sequences, or NUL bytes.
     */
    private function hasPathShapedPassword(string $value): bool
    {
        return preg_match('#^[/\\\\]|\.\.[/\\\\]#', $value) === 1
            || str_contains($value, "\0");
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
