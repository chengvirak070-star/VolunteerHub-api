<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'role' => $this->input('role', 'volunteer'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::in(['volunteer', 'organizer'])],
            'organization_name' => ['required_if:role,organizer', 'nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username can only use lowercase letters, numbers, dots and underscores.',
            'organization_name.required_if' => 'Organization name is required for organizers.',
        ];
    }
}
