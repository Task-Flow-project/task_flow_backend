<?php

namespace App\Domains\Subscription\Controllers;

use App\Models\Subscription;
use App\Domains\Subscription\Requests\StoreSubscriptionRequest;
use App\Domains\Subscription\Requests\UpdateSubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController
{
    public function show(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription;

        return response()->json($subscription);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = Subscription::create([
            'user_id' => auth()->id(),
            'plan' => $request->plan,
            'status' => 'active',
        ]);

        return response()->json($subscription, 201);
    }

    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->user()->subscription;
        $subscription->update($request->only('plan'));

        return response()->json($subscription);
    }

    public function destroy(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription;
        $subscription->update(['status' => 'canceled']);

        return response()->json(null, 204);
    }

    /**
     * Stub: no billing provider (Stripe/Cashier) is wired up yet, so there is
     * no invoices table or provider data to read from. Returns an empty list
     * rather than 500ing, until billing is integrated in a future pass.
     */
    public function invoices(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }
}