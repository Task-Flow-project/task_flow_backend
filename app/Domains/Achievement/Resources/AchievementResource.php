<?php

namespace App\Domains\Achievement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->users->first()?->pivot;

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'unlocked' => $pivot?->unlocked_at !== null,
            'unlocked_at' => $pivot?->unlocked_at,
            'threshold' => $this->threshold,
            'progress' => $pivot?->progress ?? 0,
        ];
    }
}