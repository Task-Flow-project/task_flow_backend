<?php

namespace App\Domains\Attachment\Policies;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;

class AttachmentPolicy
{
    public function create(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($attachment->uploader_id === $user->id) {
            return true;
        }

        $membership = $attachment->card->column->board->workspace->memberships()
            ->where('user_id', $user->id)->first();

        return $membership && in_array($membership->role, ['admin', 'owner']);
    }
}
