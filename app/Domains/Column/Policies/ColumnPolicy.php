<?php

namespace App\Domains\Column\Policies;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;

class ColumnPolicy
{
    public function create(User $user, Board $board): bool
    {
        return $board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Column $column): bool
    {
        return $column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Column $column): bool
    {
        return $column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }
}
