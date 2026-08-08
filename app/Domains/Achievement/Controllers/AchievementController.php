<?php

namespace App\Domains\Achievement\Controllers;

use App\Domains\Achievement\Resources\AchievementResource;
use App\Domains\Subscription\Support\PlanLimits;
use App\Domains\Achievement\Actions\ComputeStreakAction;
use App\Models\Achievement;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController
{
    public function index(): JsonResponse
    {
        $achievements = Achievement::with(['users' => fn ($q) => $q->where('user_id', auth()->id())])->get();

        return response()->json(AchievementResource::collection($achievements));
    }

    public function streak(Request $request): JsonResponse
    {
        return response()->json((new ComputeStreakAction())->execute($request->user()));
    }

    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();

        $from = $request->date('from');
        $to = $request->date('to') ?? now();

        $retentionDays = PlanLimits::activityRetentionDays($user);
        $earliestAllowed = $retentionDays !== null ? now()->subDays($retentionDays)->startOfDay() : null;

        if ($from === null || ($earliestAllowed !== null && $from->lt($earliestAllowed))) {
            $from = $earliestAllowed ?? now()->subDays(30)->startOfDay();
        }

        $days = Activity::where('user_id', $user->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($days);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $completedActivity = fn () => Activity::where('user_id', $user->id)->where('type', 'card.completed');

        $completedWeek = $completedActivity()->where('created_at', '>=', now()->startOfWeek())->count();
        $completedMonth = $completedActivity()->where('created_at', '>=', now()->startOfMonth())->count();

        $totalAssigned = $user->assignedCards()->count();
        $totalCompleted = $user->assignedCards()->whereNotNull('completed_at')->count();
        $rate = $totalAssigned > 0 ? round($totalCompleted / $totalAssigned, 2) : 0;

        return response()->json([
            'completed_week' => $completedWeek,
            'completed_month' => $completedMonth,
            'rate' => $rate,
        ]);
    }
}
