<?php

namespace App\Domains\Card\Policies;

use App\Models\Column;
use App\Models\User;
use App\Models\Card;

class CardPolicy
{
    public function create(User $user, Column $column): bool
    {
        return $column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function view(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Card $card): bool
    {
        return $card->column->board->workspace->memberships()->where('user_id', $user->id)->exists();
    }
}