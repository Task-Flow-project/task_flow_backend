<?php

namespace App\Domains\Label\Policies;

use App\Models\Board;
use App\Models\Label;
use App\Models\User;

class LabelPolicy
{
    public function viewAny(User $user, Board $board): bool
    {
        return $board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function create(User $user, Board $board): bool
    {
        return $board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Label $label): bool
    {
        return $label->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }
}
