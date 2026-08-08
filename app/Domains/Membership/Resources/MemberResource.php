<?php

namespace App\Domains\Membership\Resources;

use App\Domains\User\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}