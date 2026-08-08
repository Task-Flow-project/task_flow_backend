<?php

namespace App\Providers;

use App\Domains\Attachment\Policies\AttachmentPolicy;
use App\Domains\Board\Policies\BoardPolicy;
use App\Domains\Card\Policies\CardPolicy;
use App\Domains\Checklist\Policies\ChecklistPolicy;
use App\Domains\Column\Policies\ColumnPolicy;
use App\Domains\Comment\Policies\CommentPolicy;
use App\Domains\Label\Policies\LabelPolicy;
use App\Domains\Workspace\Policies\WorkspacePolicy;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\ChecklistItem;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(Column::class, ColumnPolicy::class);
        Gate::policy(Card::class, CardPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(ChecklistItem::class, ChecklistPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(Label::class, LabelPolicy::class);

        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
