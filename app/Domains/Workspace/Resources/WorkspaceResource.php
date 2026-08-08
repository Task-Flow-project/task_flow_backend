<?php

namespace App\Domains\Workspace\Resources;

use App\Domains\Membership\Resources\MemberResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->owner_id,
            'created_at' => $this->created_at,
            'members' => MemberResource::collection($this->whenLoaded('memberships')),
        ];
    }
}