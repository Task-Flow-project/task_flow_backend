<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| These match the channel contract in the audit doc: a private channel per
| board for mutations, a presence channel per board for "who's viewing",
| and a private per-user channel for personal notifications. All three
| reuse the same workspace-membership check the REST policies already use.
|
*/

Broadcast::channel('board.{boardId}', function (User $user, string $boardId) {
    $board = Board::find($boardId);

    return $board && $board->workspace->memberships()->where('user_id', $user->id)->exists();
});

Broadcast::channel('presence-board.{boardId}', function (User $user, string $boardId) {
    $board = Board::find($boardId);

    if (! $board || ! $board->workspace->memberships()->where('user_id', $user->id)->exists()) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatar_url];
});

Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});
