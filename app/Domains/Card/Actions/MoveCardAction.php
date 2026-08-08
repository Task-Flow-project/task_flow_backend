<?php

namespace App\Domains\Card\Actions;

use App\Models\Card;

class MoveCardAction
{
    public function execute(Card $card, string $newColumnId, float $newPosition): void
    {
        $prev = Card::where('column_id', $newColumnId)->where('position', '<', $newPosition)->orderBy('position', 'desc')->first();
        $next = Card::where('column_id', $newColumnId)->where('position', '>', $newPosition)->orderBy('position', 'asc')->first();

        if ($prev && $next) {
            $position = ($prev->position + $next->position) / 2;
        } elseif ($prev) {
            $position = $prev->position + 1000;
        } elseif ($next) {
            $position = $next->position / 2;
        } else {
            $position = 1000;
        }

        $card->update([
            'column_id' => $newColumnId,
            'position' => $position,
        ]);
    }
}