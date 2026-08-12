<?php

namespace App\Domains\Subscription\Support;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Workspace;

class PlanLimits
{
    private const LIMITS = [
        'free' => [
            'workspaces' => 1,
            'boards_per_workspace' => 3,
            'members_per_workspace' => 3,
            'attachment_bytes' => 10 * 1024 * 1024,
            'activity_retention_days' => 7,
        ],
        'pro' => [
            'workspaces' => null,
            'boards_per_workspace' => null,
            'members_per_workspace' => null,
            'attachment_bytes' => 5 * 1024 * 1024 * 1024,
            'activity_retention_days' => null,
        ],
    ];

    public static function planFor(User $user): string
    {
        return $user->subscribed('default', config('services.stripe.price_pro')) ? 'pro' : 'free';
    }

    public static function workspacesAllowed(User $user): ?int
    {
        return self::LIMITS[self::planFor($user)]['workspaces'];
    }

    public static function boardsAllowed(Workspace $workspace): ?int
    {
        return self::LIMITS[self::planFor($workspace->owner)]['boards_per_workspace'];
    }

    public static function membersAllowed(Workspace $workspace): ?int
    {
        return self::LIMITS[self::planFor($workspace->owner)]['members_per_workspace'];
    }

    public static function attachmentBytesAllowed(Workspace $workspace): ?int
    {
        return self::LIMITS[self::planFor($workspace->owner)]['attachment_bytes'];
    }

    public static function activityRetentionDays(User $user): ?int
    {
        return self::LIMITS[self::planFor($user)]['activity_retention_days'];
    }

    public static function attachmentBytesUsed(Workspace $workspace): int
    {
        return (int) Attachment::whereHas(
            'card.column.board',
            fn ($q) => $q->where('workspace_id', $workspace->id)
        )->sum('size');
    }
}
