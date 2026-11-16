<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // owner check: PostPolicy::update in the controller
    }

    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'string', 'max:2000'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'event_id' => ['sometimes', 'nullable', 'integer', 'exists:events,id'],
        ];
    }
}
