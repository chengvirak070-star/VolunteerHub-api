<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:post,comment,user'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
