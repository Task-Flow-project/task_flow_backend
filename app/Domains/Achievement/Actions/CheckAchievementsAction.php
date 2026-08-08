<?php

namespace App\Domains\Achievement\Actions;

use App\Domains\Notification\Actions\CreateNotificationAction;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserAchievement;

class CheckAchievementsAction
{
    /**
     * Achievement `key` -> a counter callback resolving the user's current progress.
     */
    private function counters(User $user): array
    {
        $completedTasks = fn () => Activity::where('user_id', $user->id)->where('type', 'card.completed')->count();
        $longestStreak = fn () => (new ComputeStreakAction())->execute($user)['longest'];

        return [
            'first_board' => fn () => Activity::where('user_id', $user->id)->where('type', 'board.created')->count(),
            'first_card' => fn () => Activity::where('user_id', $user->id)->where('type', 'card.created')->count(),
            'tasks_10' => $completedTasks,
            'tasks_100' => $completedTasks,
            'streak_7' => $longestStreak,
            'streak_30' => $longestStreak,
        ];
    }

    public function execute(User $user): void
    {
        $counters = $this->counters($user);
        $achievements = Achievement::whereIn('key', array_keys($counters))->get();

        foreach ($achievements as $achievement) {
            $progress = (int) ($counters[$achievement->key])();

            $userAchievement = UserAchievement::firstOrNew([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ]);

            $wasUnlocked = $userAchievement->unlocked_at !== null;
            $userAchievement->progress = $progress;

            if (! $wasUnlocked && $progress >= $achievement->threshold) {
                $userAchievement->unlocked_at = now();
            }

            $userAchievement->save();

            if (! $wasUnlocked && $userAchievement->unlocked_at !== null) {
                (new CreateNotificationAction())->execute($user, 'achievement.unlocked', [
                    'achievement_id' => $achievement->id,
                    'key' => $achievement->key,
                    'name' => $achievement->name,
                ]);
            }
        }
    }
}
