<?php

namespace App\Domains\Notification\Actions;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;

class CreateNotificationAction
{
    public function execute(User $recipient, string $type, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'data' => $data,
        ]);

        // No ->toOthers() here: unlike board mutations, the recipient is often
        // the same user who triggered the action (e.g. unlocking their own
        // achievement), and they still need the real-time push — there's no
        // optimistic local update for a notification toast to reconcile against.
        broadcast(new NotificationCreated($notification));

        return $notification;
    }
}
