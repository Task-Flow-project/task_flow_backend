<?php

namespace App\Domains\Board\Policies;

use App\Models\User;
use App\Models\Board;

class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        return $board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Board $board): bool
    {
        return $board->workspace->memberships()->where('user_id', $user->id)->whereIn('role', ['member', 'admin', 'owner'])->exists();
    }

    public function delete(User $user, Board $board): bool
    {
        $membership = $board->workspace->memberships()->where('user_id', $user->id)->first();

        return $membership && in_array($membership->role, ['admin', 'owner']);
    }
}