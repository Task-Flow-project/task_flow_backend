<?php

namespace App\Domains\Workspace\Actions;

use App\Models\Workspace;
use App\Models\Membership;

class CreateWorkspaceAction
{
    public function execute(string $name, int $ownerId): Workspace
    {
        $workspace = Workspace::create([
            'name' => $name,
            'owner_id' => $ownerId,
        ]);

        Membership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $ownerId,
            'role' => 'owner',
        ]);

        return $workspace;
    }
}