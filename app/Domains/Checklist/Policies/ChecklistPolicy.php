<?php

namespace App\Domains\Checklist\Policies;

use App\Models\Card;
use App\Models\ChecklistItem;
use App\Models\User;

class ChecklistPolicy
{
    public function create(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, ChecklistItem $checklistItem): bool
    {
        return $checklistItem->card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, ChecklistItem $checklistItem): bool
    {
        return $checklistItem->card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }
}
