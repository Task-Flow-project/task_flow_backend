<?php

namespace App\Domains\Comment\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Comment\Requests\StoreCommentRequest;
use App\Domains\Comment\Requests\UpdateCommentRequest;
use App\Domains\Comment\Resources\CommentResource;
use App\Domains\Notification\Actions\CreateNotificationAction;
use App\Events\CommentCreated;
use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CommentController
{
    use AuthorizesRequests;

    public function store(StoreCommentRequest $request, Card $card): JsonResponse
    {
        $this->authorize('create', [Comment::class, $card]);

        $comment = Comment::create([
            'card_id' => $card->id,
            'author_id' => auth()->id(),
            'body' => $request->body,
        ]);

        $board = $card->column->board;
        (new LogActivityAction())->execute($request->user(), 'comment.created', $board->workspace, $board, $comment->id);
        broadcast(new CommentCreated($comment, $board->id))->toOthers();

        $this->notifyMentions($comment, $card);

        return response()->json(new CommentResource($comment), 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment->update($request->only('body'));

        return response()->json(new CommentResource($comment));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, 204);
    }

    private function notifyMentions(Comment $comment, Card $card): void
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->body, $matches);
        $usernames = array_unique($matches[1] ?? []);

        if (empty($usernames)) {
            return;
        }

        $mentioned = User::whereIn('username', $usernames)->where('id', '!=', auth()->id())->get();

        foreach ($mentioned as $user) {
            (new CreateNotificationAction())->execute($user, 'comment.mention', [
                'card_id' => $card->id,
                'comment_id' => $comment->id,
                'author_id' => auth()->id(),
            ]);
        }
    }
}
