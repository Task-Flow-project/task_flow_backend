<?php

namespace App\Domains\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A Stripe PaymentMethod id (pm_...) collected client-side via
            // Stripe.js/Elements — the raw card number never reaches this API.
            'payment_method' => ['required', 'string', 'starts_with:pm_'],
        ];
    }
}
