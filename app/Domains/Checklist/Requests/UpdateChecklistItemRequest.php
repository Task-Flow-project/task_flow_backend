<?php

namespace App\Domains\Checklist\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['sometimes', 'string', 'max:255'],
            'done' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}