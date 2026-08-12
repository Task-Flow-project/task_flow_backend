<?php

namespace Tests\Feature;

use App\Domains\Subscription\Support\PlanLimits;
use App\Models\Board;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkspace(User $owner): Workspace
    {
        $workspace = Workspace::create(['name' => 'Acme', 'owner_id' => $owner->id]);
        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return $workspace;
    }

    public function test_subscribing_with_stripes_test_card_upgrades_the_plan_and_lifts_limits(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        for ($i = 0; $i < 3; $i++) {
            Board::create(['workspace_id' => $workspace->id, 'title' => "Board {$i}"]);
        }

        $this->assertSame('free', PlanLimits::planFor($owner->fresh()));

        // A 4th board is blocked on the Free plan.
        $this->actingAs($owner)
            ->postJson("/api/workspaces/{$workspace->id}/boards", ['title' => 'Fourth'])
            ->assertStatus(403)
            ->assertJson(['code' => 'PLAN_LIMIT']);

        // Stripe's documented test payment method — works against any test-mode
        // secret key, no real card involved. https://stripe.com/docs/testing
        $response = $this->actingAs($owner)
            ->postJson('/api/me/subscription', ['payment_method' => 'pm_card_visa'])
            ->assertCreated();

        $this->assertSame('active', $response->json('stripe_status'));
        $this->assertSame('pro', PlanLimits::planFor($owner->fresh()));

        // The 4th board is now allowed.
        $this->actingAs($owner)
            ->postJson("/api/workspaces/{$workspace->id}/boards", ['title' => 'Fourth'])
            ->assertCreated();

        // Cancel — reverts to Free (Cashier cancels at period end, so the
        // subscription row still exists but is no longer "active").
        $this->actingAs($owner)
            ->deleteJson('/api/me/subscription')
            ->assertNoContent();

        $this->assertTrue($owner->fresh()->subscription('default')->onGracePeriod());
    }
}
