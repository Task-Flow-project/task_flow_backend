<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['key' => 'first_board', 'name' => 'Getting Started', 'description' => 'Create your first board.', 'threshold' => 1],
            ['key' => 'first_card', 'name' => 'First Task', 'description' => 'Create your first card.', 'threshold' => 1],
            ['key' => 'tasks_10', 'name' => 'Task Runner', 'description' => 'Complete 10 tasks.', 'threshold' => 10],
            ['key' => 'tasks_100', 'name' => 'Centurion', 'description' => 'Complete 100 tasks.', 'threshold' => 100],
            ['key' => 'streak_7', 'name' => 'On a Roll', 'description' => 'Keep a 7-day activity streak.', 'threshold' => 7],
            ['key' => 'streak_30', 'name' => 'Unstoppable', 'description' => 'Keep a 30-day activity streak.', 'threshold' => 30],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
