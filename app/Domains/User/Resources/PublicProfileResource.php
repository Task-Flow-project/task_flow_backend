<?php

namespace App\Domains\User\Resources;

use App\Domains\Achievement\Actions\ComputeStreakAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $streak = (new ComputeStreakAction())->execute($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'joined_at' => $this->created_at,
            'stats' => [
                'boards' => $this->ownedWorkspaces()->withCount('boards')->get()->sum('boards_count'),
                'current_streak' => $streak['current'],
                'longest_streak' => $streak['longest'],
                'achievements_unlocked' => $this->achievements()->wherePivotNotNull('unlocked_at')->count(),
            ],
        ];
    }
}
