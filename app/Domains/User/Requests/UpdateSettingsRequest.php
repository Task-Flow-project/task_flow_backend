<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'string', 'in:light,dark,system'],
            'language' => ['sometimes', 'string', 'max:10'],
            'notification_prefs' => ['sometimes', 'array'],
        ];
    }
}
