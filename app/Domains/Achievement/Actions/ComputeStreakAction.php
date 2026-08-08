<?php

namespace App\Domains\Achievement\Actions;

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;

class ComputeStreakAction
{
    /**
     * A missed day doesn't break the streak as long as no more than
     * GRACE_DAYS consecutive days are missed.
     */
    private const GRACE_DAYS = 1;

    public function execute(User $user): array
    {
        $days = Activity::where('user_id', $user->id)
            ->selectRaw('DATE(created_at) as day')
            ->distinct()
            ->orderByDesc('day')
            ->pluck('day')
            ->map(fn ($day) => CarbonImmutable::parse($day)->startOfDay())
            ->values();

        if ($days->isEmpty()) {
            return ['current' => 0, 'longest' => 0, 'grace_days' => self::GRACE_DAYS];
        }

        $today = CarbonImmutable::now()->startOfDay();

        // Current streak: walk backwards from today/yesterday while gaps stay within grace.
        $current = 0;
        $cursor = $today;
        if ($days->first()->lt($today->subDays(self::GRACE_DAYS + 1))) {
            $current = 0;
        } else {
            $current = 1;
            for ($i = 1; $i < $days->count(); $i++) {
                $gap = $days[$i - 1]->diffInDays($days[$i]);
                if ($gap <= self::GRACE_DAYS + 1) {
                    $current++;
                } else {
                    break;
                }
            }
        }

        // Longest streak ever, using the same grace rule, scanning oldest -> newest.
        $ascending = $days->sortBy(fn ($d) => $d->timestamp)->values();
        $longest = 1;
        $run = 1;
        for ($i = 1; $i < $ascending->count(); $i++) {
            $gap = $ascending[$i - 1]->diffInDays($ascending[$i]);
            if ($gap <= self::GRACE_DAYS + 1) {
                $run++;
            } else {
                $run = 1;
            }
            $longest = max($longest, $run);
        }

        return [
            'current' => $current,
            'longest' => $longest,
            'grace_days' => self::GRACE_DAYS,
        ];
    }
}
