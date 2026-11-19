<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin-only route
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:hide_content,block_user,dismiss'],
        ];
    }
}
