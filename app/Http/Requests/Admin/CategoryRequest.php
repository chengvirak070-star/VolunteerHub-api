<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin-only route
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name_en' => ['required', 'string', 'min:2', 'max:50'],
            'name_km' => ['nullable', 'string', 'max:50'],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/', Rule::unique('categories', 'slug')->ignore($id)],
        ];
    }
}
