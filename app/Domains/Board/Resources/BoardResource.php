<?php

namespace App\Domains\Board\Resources;

use App\Domains\Column\Resources\ColumnResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'archived' => $this->archived,
            'created_at' => $this->created_at,
            'columns' => ColumnResource::collection($this->whenLoaded('columns')),
        ];
    }
}