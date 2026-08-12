<?php

namespace App\Domains\Subscription\Controllers;

use App\Domains\Subscription\Requests\StoreSubscriptionRequest;
use App\Domains\Subscription\Requests\UpdateSubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->subscription('default'));
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $subscription = $user->newSubscription('default', config('services.stripe.price_pro'))
                ->create($request->payment_method);
        } catch (IncompletePayment $exception) {
            return response()->json([
                'message' => 'The payment could not be confirmed automatically.',
                'payment_intent' => $exception->payment->id,
                'client_secret' => $exception->payment->clientSecret(),
            ], 402);
        }

        return response()->json($subscription, 201);
    }

    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->user()->subscription('default');

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to update.'], 404);
        }

        $subscription->swap($request->price ?? config('services.stripe.price_pro'));

        return response()->json($subscription);
    }

    public function destroy(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription('default');

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to cancel.'], 404);
        }

        $subscription->cancel();

        return response()->json(null, 204);
    }

    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return response()->json(['data' => []]);
        }

        $invoices = $user->invoices()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'total' => $invoice->total(),
            'date' => $invoice->date()->toIso8601String(),
            'paid' => $invoice->asStripeInvoice()->status === 'paid',
            'hosted_invoice_url' => $invoice->asStripeInvoice()->hosted_invoice_url,
        ]);

        return response()->json(['data' => $invoices]);
    }
}
