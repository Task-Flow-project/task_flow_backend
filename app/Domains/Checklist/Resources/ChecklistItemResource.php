<?php

namespace App\Domains\Checklist\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'text' => $this->text,
            'done' => $this->done,
            'position' => $this->position,
        ];
    }
}