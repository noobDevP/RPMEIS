<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\.\@\s]+$/',
            ],
            'password' => ['required', 'string', 'max:255'],
            'user_username' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\@\s]*$/'],
        ];
    }

    /**
     * Handle failed validation.
     * Reject suspicious path traversal attempts with HTTP 400 Bad Request
     * so security scanners (e.g., OWASP ZAP) receive immediate error status rather than
     * following 302 redirects to HTML views that trigger heuristic false positives.
     */
    protected function failedValidation(Validator $validator)
    {
        $rawUsername = (string) $this->input('username', '');
        $rawUserUsername = (string) $this->input('user_username', '');

        if ($this->hasTraversalSequence($rawUsername) || $this->hasTraversalSequence($rawUserUsername)) {
            throw new HttpResponseException(response()->json([
                'error' => 'Bad Request',
                'message' => 'Invalid characters detected in login parameters.',
            ], 400));
        }

        parent::failedValidation($validator);
    }

    private function hasTraversalSequence(string $value): bool
    {
        return str_contains($value, '..')
            || str_contains($value, '/')
            || str_contains($value, '\\')
            || str_contains($value, '%')
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
