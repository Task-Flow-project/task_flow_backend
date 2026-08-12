<?php

namespace App\Events;

use App\Domains\Column\Resources\ColumnResource;
use App\Models\Column;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Column $column,
        public string $boardId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("board.{$this->boardId}")];
    }

    public function broadcastAs(): string
    {
        return 'column.created';
    }

    public function broadcastWith(): array
    {
        return ['column' => new ColumnResource($this->column)];
    }
}
