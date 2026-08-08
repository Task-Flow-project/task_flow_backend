<?php

namespace App\Domains\Activity\Actions;

use App\Domains\Achievement\Actions\CheckAchievementsAction;
use App\Models\Activity;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class LogActivityAction
{
    public function execute(
        User $user,
        string $type,
        ?Workspace $workspace = null,
        ?Board $board = null,
        ?string $subjectId = null,
    ): Activity {
        $activity = Activity::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'board_id' => $board?->id,
            'type' => $type,
            'subject_id' => $subjectId,
        ]);

        (new CheckAchievementsAction())->execute($user);

        return $activity;
    }
}
