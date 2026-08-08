<?php

namespace App\Domains\Comment\Policies;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->author_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->author_id === $user->id;
    }
}
