<?php

namespace App\Console\Commands;

use App\Domains\Notification\Actions\CreateNotificationAction;
use App\Models\Card;
use Illuminate\Console\Command;

class NotifyDueSoonCards extends Command
{
    protected $signature = 'cards:notify-due-soon';

    protected $description = 'Notify assignees of cards due within the next 24 hours.';

    public function handle(): int
    {
        $cards = Card::whereNotNull('due_date')
            ->whereNull('completed_at')
            ->whereBetween('due_date', [now(), now()->addDay()])
            ->with('assignees')
            ->get();

        $action = new CreateNotificationAction();

        foreach ($cards as $card) {
            foreach ($card->assignees as $assignee) {
                $alreadyNotified = $assignee->notifications()
                    ->where('type', 'card.due_soon')
                    ->whereJsonContains('data->card_id', $card->id)
                    ->exists();

                if (! $alreadyNotified) {
                    $action->execute($assignee, 'card.due_soon', [
                        'card_id' => $card->id,
                        'card_title' => $card->title,
                        'due_date' => $card->due_date->toIso8601String(),
                    ]);
                }
            }
        }

        $this->info("Checked {$cards->count()} card(s) due within 24 hours.");

        return self::SUCCESS;
    }
}
