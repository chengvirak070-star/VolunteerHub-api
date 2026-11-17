<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is limited to organizers and admins
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name_en' => trim((string) $this->input('name_en')),
            'name_km' => trim((string) $this->input('name_km')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'min:2', 'max:50', 'regex:/[\p{L}\p{N}]/u'],
            'name_km' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'Please enter the category name in English.',
            'name_en.regex' => 'The category name must contain letters or numbers.',
        ];
    }
}
