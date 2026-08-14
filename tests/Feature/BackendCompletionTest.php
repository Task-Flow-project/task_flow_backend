<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkspace(User $owner): Workspace
    {
        $workspace = Workspace::create(['name' => 'Acme', 'owner_id' => $owner->id]);
        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return $workspace;
    }

    public function test_previously_fatal_workspace_routes_now_respond(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);

        $this->actingAs($owner)
            ->getJson("/api/workspaces/{$workspace->id}/members")
            ->assertOk();

        $invite = $this->actingAs($owner)
            ->postJson("/api/workspaces/{$workspace->id}/invites", ['email' => 'new@example.com', 'role' => 'member'])
            ->assertCreated();

        $this->actingAs($owner)
            ->getJson("/api/workspaces/{$workspace->id}/invites")
            ->assertOk()
            ->assertJsonCount(1);

        $invitee = User::factory()->create(['email' => 'new@example.com']);
        $token = $invite->json('token');

        $this->actingAs($invitee)
            ->postJson('/api/invites/accept', ['token' => $token])
            ->assertOk();

        $this->assertDatabaseHas('memberships', ['workspace_id' => $workspace->id, 'user_id' => $invitee->id, 'role' => 'member']);
    }

    public function test_previously_fatal_achievement_and_subscription_routes_now_respond(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/me/streak')->assertOk()->assertJsonStructure(['current', 'longest', 'grace_days']);
        $this->actingAs($user)->getJson('/api/me/activity')->assertOk();
        $this->actingAs($user)->getJson('/api/me/stats')->assertOk()->assertJsonStructure(['completed_week', 'completed_month', 'rate']);
        $this->actingAs($user)->getJson('/api/me/invoices')->assertOk()->assertJson(['data' => []]);
    }

    public function test_creating_a_workspace_via_the_real_endpoint_succeeds(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)
            ->postJson('/api/workspaces', ['name' => 'Acme Inc'])
            ->assertCreated();

        $this->assertSame('Acme Inc', $response->json('name'));
        $this->assertDatabaseHas('memberships', [
            'workspace_id' => $response->json('id'),
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_workspace_plan_limit_blocks_second_free_workspace(): void
    {
        $owner = User::factory()->create();
        $this->makeWorkspace($owner);

        $this->actingAs($owner)
            ->postJson('/api/workspaces', ['name' => 'Second'])
            ->assertStatus(403)
            ->assertJson(['code' => 'PLAN_LIMIT']);
    }

    public function test_board_plan_limit_blocks_fourth_free_board(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        for ($i = 0; $i < 3; $i++) {
            Board::create(['workspace_id' => $workspace->id, 'title' => "Board {$i}"]);
        }

        $this->actingAs($owner)
            ->postJson("/api/workspaces/{$workspace->id}/boards", ['title' => 'Fourth'])
            ->assertStatus(403)
            ->assertJson(['code' => 'PLAN_LIMIT']);
    }

    public function test_non_member_cannot_read_a_foreign_workspace_board(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/boards/{$board->id}")
            ->assertForbidden();
    }

    public function test_only_comment_author_can_update_their_comment(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $column = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);
        $card = Card::create(['column_id' => $column->id, 'title' => 'Task', 'position' => 1000]);

        $commentId = $this->actingAs($owner)
            ->postJson("/api/cards/{$card->id}/comments", ['body' => 'hello'])
            ->assertCreated()
            ->json('id');

        $other = User::factory()->create();
        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $other->id, 'role' => 'member']);

        $this->actingAs($other)
            ->patchJson("/api/comments/{$commentId}", ['body' => 'edited'])
            ->assertForbidden();
    }

    public function test_completing_a_card_unlocks_first_card_achievement_and_notifies(): void
    {
        $this->seed(\Database\Seeders\AchievementSeeder::class);

        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $column = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);

        $cardId = $this->actingAs($owner)
            ->postJson("/api/columns/{$column->id}/cards", ['title' => 'Ship it'])
            ->assertCreated()
            ->json('id');

        $this->actingAs($owner)
            ->patchJson("/api/cards/{$cardId}", ['completed_at' => now()->toIso8601String()])
            ->assertOk();

        $achievements = $this->actingAs($owner)->getJson('/api/me/achievements')->assertOk()->json();
        $firstCard = collect($achievements)->firstWhere('key', 'first_card');

        $this->assertNotNull($firstCard);
        $this->assertTrue($firstCard['unlocked']);

        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'achievement.unlocked']);
    }

    public function test_search_is_scoped_to_the_users_workspaces(): void
    {
        $me = User::factory()->create();
        $myWorkspace = $this->makeWorkspace($me);
        Board::create(['workspace_id' => $myWorkspace->id, 'title' => 'Roadmap Alpha']);

        $stranger = User::factory()->create();
        $strangerWorkspace = $this->makeWorkspace($stranger);
        Board::create(['workspace_id' => $strangerWorkspace->id, 'title' => 'Roadmap Beta']);

        $results = $this->actingAs($me)
            ->getJson('/api/search?q=Roadmap')
            ->assertOk()
            ->json('boards');

        $this->assertCount(1, $results);
        $this->assertEquals('Roadmap Alpha', $results[0]['title']);
    }

    public function test_full_register_otp_login_flow_over_real_http(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $user = User::where('email', 'sara@example.com')->firstOrFail();

        $this->postJson('/api/login', ['email' => 'sara@example.com', 'password' => 'password123'])
            ->assertStatus(403);

        $this->postJson('/api/verify-otp', ['email' => 'sara@example.com', 'otp_code' => $user->otp_code])
            ->assertOk()
            ->assertJsonStructure(['user', 'token']);

        $this->postJson('/api/login', ['email' => 'sara@example.com', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }
}
