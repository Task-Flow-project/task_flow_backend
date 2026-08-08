<?php

namespace App\Domains\Card\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'column_id' => ['sometimes', 'uuid', 'exists:columns,id'],
            'position' => ['sometimes', 'numeric', 'min:0'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['uuid', 'exists:users,id'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['uuid', 'exists:labels,id'],
        ];
    }
}