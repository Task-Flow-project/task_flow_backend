<?php

use App\Domains\Auth\Controllers\AuthController;
use App\Domains\Achievement\Controllers\AchievementController;
use App\Domains\Attachment\Controllers\AttachmentController;
use App\Domains\Board\Controllers\BoardController;
use App\Domains\Card\Controllers\CardController;
use App\Domains\Checklist\Controllers\ChecklistController;
use App\Domains\Column\Controllers\ColumnController;
use App\Domains\Comment\Controllers\CommentController;
use App\Domains\Label\Controllers\LabelController;
use App\Domains\Notification\Controllers\NotificationController;
use App\Domains\Search\Controllers\SearchController;
use App\Domains\Subscription\Controllers\SubscriptionController;
use App\Domains\User\Controllers\UserController;
use App\Domains\Workspace\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::get('/users/{username}', [UserController::class, 'show']);

// Called by Stripe, not the frontend — Cashier verifies the Stripe-Signature
// header itself instead of going through our normal auth.
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::patch('/me', [UserController::class, 'update']);
    Route::post('/me/avatar', [UserController::class, 'updateAvatar']);
    Route::patch('/me/password', [UserController::class, 'updatePassword']);
    Route::get('/me/sessions', [UserController::class, 'sessions']);
    Route::delete('/me/sessions/{id}', [UserController::class, 'revokeSession']);
    Route::delete('/me', [UserController::class, 'destroy']);
    Route::get('/me/settings', [UserController::class, 'settings']);
    Route::patch('/me/settings', [UserController::class, 'updateSettings']);

    Route::get('/me/streak', [AchievementController::class, 'streak']);
    Route::get('/me/activity', [AchievementController::class, 'activity']);
    Route::get('/me/achievements', [AchievementController::class, 'index']);
    Route::get('/me/stats', [AchievementController::class, 'stats']);

    Route::get('/me/subscription', [SubscriptionController::class, 'show']);
    Route::post('/me/subscription', [SubscriptionController::class, 'store']);
    Route::patch('/me/subscription', [SubscriptionController::class, 'update']);
    Route::delete('/me/subscription', [SubscriptionController::class, 'destroy']);

    Route::get('/me/invoices', [SubscriptionController::class, 'invoices']);

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);

    Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
    Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'updateMember']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember']);
    Route::post('/workspaces/{workspace}/transfer', [WorkspaceController::class, 'transfer']);

    Route::post('/workspaces/{workspace}/invites', [WorkspaceController::class, 'invite']);
    Route::get('/workspaces/{workspace}/invites', [WorkspaceController::class, 'invites']);
    Route::delete('/invites/{invitation}', [WorkspaceController::class, 'revokeInvite']);
    Route::post('/invites/accept', [WorkspaceController::class, 'acceptInvite']);

    Route::get('/workspaces/{workspace}/boards', [BoardController::class, 'index']);
    Route::post('/workspaces/{workspace}/boards', [BoardController::class, 'store']);
    Route::get('/boards/{board}', [BoardController::class, 'show']);
    Route::patch('/boards/{board}', [BoardController::class, 'update']);
    Route::delete('/boards/{board}', [BoardController::class, 'destroy']);

    Route::get('/boards/{board}/labels', [LabelController::class, 'index']);
    Route::post('/boards/{board}/labels', [LabelController::class, 'store']);
    Route::delete('/labels/{label}', [LabelController::class, 'destroy']);

    Route::post('/boards/{board}/columns', [ColumnController::class, 'store']);
    Route::patch('/columns/{column}', [ColumnController::class, 'update']);
    Route::delete('/columns/{column}', [ColumnController::class, 'destroy']);

    Route::post('/columns/{column}/cards', [CardController::class, 'store']);
    Route::get('/cards/{card}', [CardController::class, 'show']);
    Route::patch('/cards/{card}', [CardController::class, 'update']);
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);

    Route::post('/cards/{card}/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/cards/{card}/checklist', [ChecklistController::class, 'store']);
    Route::patch('/checklist/{checklistItem}', [ChecklistController::class, 'update']);
    Route::delete('/checklist/{checklistItem}', [ChecklistController::class, 'destroy']);

    Route::post('/cards/{card}/attachments', [AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    Route::get('/search', [SearchController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
