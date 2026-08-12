<?php

namespace Tests\Feature;

use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardUpdated;
use App\Events\ColumnCreated;
use App\Events\CommentCreated;
use App\Events\NotificationCreated;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkspace(User $owner): Workspace
    {
        $workspace = Workspace::create(['name' => 'Acme', 'owner_id' => $owner->id]);
        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return $workspace;
    }

    public function test_creating_a_column_broadcasts_column_created_on_the_board_channel(): void
    {
        Event::fake([ColumnCreated::class]);

        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson("/api/boards/{$board->id}/columns", ['title' => 'To Do'])
            ->assertCreated();

        Event::assertDispatched(ColumnCreated::class, fn ($e) => $e->boardId === $board->id);
    }

    public function test_moving_a_card_broadcasts_card_updated(): void
    {
        Event::fake([CardCreated::class, CardUpdated::class]);

        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $col1 = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);
        $col2 = Column::create(['board_id' => $board->id, 'title' => 'Done', 'position' => 2000]);
        $card = Card::create(['column_id' => $col1->id, 'title' => 'Ship it', 'position' => 1000]);

        $this->actingAs($owner)
            ->patchJson("/api/cards/{$card->id}", ['column_id' => $col2->id, 'position' => 1000])
            ->assertOk();

        Event::assertDispatched(CardUpdated::class, fn ($e) => $e->boardId === $board->id && $e->card->id === $card->id);
    }

    public function test_deleting_a_card_broadcasts_card_deleted_with_the_right_board(): void
    {
        Event::fake([CardDeleted::class]);

        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $col = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);
        $card = Card::create(['column_id' => $col->id, 'title' => 'Ship it', 'position' => 1000]);

        $this->actingAs($owner)
            ->deleteJson("/api/cards/{$card->id}")
            ->assertNoContent();

        Event::assertDispatched(CardDeleted::class, fn ($e) => $e->cardId === $card->id && $e->boardId === $board->id);
    }

    public function test_new_comment_broadcasts_comment_created(): void
    {
        Event::fake([CommentCreated::class]);

        $owner = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $col = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);
        $card = Card::create(['column_id' => $col->id, 'title' => 'Ship it', 'position' => 1000]);

        $this->actingAs($owner)
            ->postJson("/api/cards/{$card->id}/comments", ['body' => 'looks good'])
            ->assertCreated();

        Event::assertDispatched(CommentCreated::class, fn ($e) => $e->boardId === $board->id);
    }

    public function test_a_new_notification_broadcasts_on_the_recipients_personal_channel(): void
    {
        Event::fake([NotificationCreated::class]);

        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $workspace = $this->makeWorkspace($owner);
        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $assignee->id, 'role' => 'member']);
        $board = Board::create(['workspace_id' => $workspace->id, 'title' => 'Roadmap']);
        $col = Column::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 1000]);
        $card = Card::create(['column_id' => $col->id, 'title' => 'Ship it', 'position' => 1000]);

        $this->actingAs($owner)
            ->patchJson("/api/cards/{$card->id}", ['assignee_ids' => [$assignee->id]])
            ->assertOk();

        Event::assertDispatched(
            NotificationCreated::class,
            fn ($e) => $e->notification->user_id === $assignee->id && $e->notification->type === 'card.assigned'
        );
    }
}
