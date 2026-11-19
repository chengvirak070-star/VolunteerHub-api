<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // users can only ever update their own profile (route has no user id)
    }

    /**
     * skills can be sent as a comma-separated string ("Photography, Teaching") — easy with
     * multipart forms — or as an array. Telegram is stored without the leading "@".
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('skills') && is_string($this->input('skills'))) {
            $skills = array_values(array_unique(array_filter(array_map(
                fn ($s) => trim(preg_replace('/\s+/u', ' ', $s)),
                explode(',', $this->input('skills')),
            ), fn ($s) => $s !== '')));
            $this->merge(['skills' => $skills]);
        }

        if ($this->filled('telegram')) {
            $tg = trim((string) $this->input('telegram'));
            $tg = preg_replace('#^(https?://)?(t\.me/|telegram\.me/)#i', '', $tg);
            $this->merge(['telegram' => ltrim($tg, '@')]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'school' => ['sometimes', 'nullable', 'string', 'max:150'],
            'skills' => ['sometimes', 'nullable', 'array', 'max:10'],
            'skills.*' => ['string', 'max:30'],
            'telegram' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z][A-Za-z0-9_]{4,31}$/'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+\s\-()]{6,30}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'skills.max' => 'You can add up to 10 skills.',
            'skills.*.max' => 'Each skill can be at most 30 characters.',
            'telegram.regex' => 'Telegram username: 5–32 letters, numbers or _ (for example: chengvirak).',
            'phone.regex' => 'Please enter a valid phone number (digits, spaces, + or -).',
        ];
    }
}
