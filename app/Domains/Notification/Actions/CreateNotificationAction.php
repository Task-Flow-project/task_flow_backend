<?php

namespace App\Domains\Notification\Actions;

use App\Models\Notification;
use App\Models\User;

class CreateNotificationAction
{
    public function execute(User $recipient, string $type, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'data' => $data,
        ]);
    }
}
