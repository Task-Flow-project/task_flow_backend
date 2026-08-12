<?php

namespace App\Domains\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional: which Stripe Price to swap to. Defaults to the
            // configured Pro price when omitted (this app currently only
            // offers one paid tier).
            'price' => ['sometimes', 'string', 'starts_with:price_'],
        ];
    }
}
