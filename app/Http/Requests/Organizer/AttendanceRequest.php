<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checked with EventPolicy::manage in the controller
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:attended,absent'],
        ];
    }
}
