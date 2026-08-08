<?php

namespace App\Domains\Card\Resources;

use App\Domains\Label\Resources\LabelResource;
use App\Domains\User\Resources\UserResource;
use App\Domains\Comment\Resources\CommentResource;
use App\Domains\Checklist\Resources\ChecklistItemResource;
use App\Domains\Attachment\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'column_id' => $this->column_id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'checklist_items' => ChecklistItemResource::collection($this->whenLoaded('checklistItems')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}