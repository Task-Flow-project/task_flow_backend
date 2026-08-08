<?php

namespace App\Domains\Attachment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'filename' => $this->filename,
            'mime' => $this->mime,
            'size' => $this->size,
            'url' => $this->url,
            'created_at' => $this->created_at,
        ];
    }
}