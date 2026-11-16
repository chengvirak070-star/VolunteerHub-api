<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checked with EventPolicy::manage in the controller
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'location' => ['sometimes', 'required', 'string', 'max:200'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date', 'after:starts_at'],
            'hours' => ['sometimes', 'required', 'numeric', 'min:0.5', 'max:24'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'cover' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'in:open,closed'], // organizer may close an event; admin handles approval
        ];
    }
}
