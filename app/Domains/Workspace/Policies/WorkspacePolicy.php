<?php

namespace App\Domains\Workspace\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->memberships()->where('user_id', $user->id)->whereIn('role', ['member', 'admin', 'owner'])->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->memberships()->where('user_id', $user->id)->whereIn('role', ['owner', 'admin'])->exists();
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->memberships()->where('user_id', $user->id)->whereIn('role', ['owner', 'admin'])->exists();
    }

    public function changeRole(User $user, Workspace $workspace): bool
    {
        return $this->manageMembers($user, $workspace);
    }

    public function transfer(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }
}