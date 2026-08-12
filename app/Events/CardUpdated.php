<?php

namespace App\Events;

use App\Domains\Card\Resources\CardResource;
use App\Models\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Also covers a move (column_id + position change) — the frontend
 * distinguishes a move from a plain edit by diffing column_id itself.
 */
class CardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card,
        public string $boardId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("board.{$this->boardId}")];
    }

    public function broadcastAs(): string
    {
        return 'card.updated';
    }

    public function broadcastWith(): array
    {
        return ['card' => new CardResource($this->card)];
    }
}
