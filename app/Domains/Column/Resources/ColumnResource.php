<?php

namespace App\Domains\Column\Resources;

use App\Domains\Card\Resources\CardResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'title' => $this->title,
            'position' => $this->position,
            'cards' => CardResource::collection($this->whenLoaded('cards')),
        ];
    }
}