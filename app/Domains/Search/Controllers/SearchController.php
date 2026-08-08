<?php

namespace App\Domains\Search\Controllers;

use App\Domains\Board\Resources\BoardResource;
use App\Domains\Card\Resources\CardResource;
use App\Domains\Comment\Resources\CommentResource;
use App\Models\Board;
use App\Models\Card;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['boards' => [], 'cards' => [], 'comments' => []]);
        }

        $userId = auth()->id();
        $memberOfWorkspace = fn ($query) => $query->whereHas(
            'memberships',
            fn ($m) => $m->where('user_id', $userId)
        );

        $boards = Board::whereHas('workspace', $memberOfWorkspace)
            ->where('title', 'LIKE', "%{$q}%")
            ->limit(20)
            ->get();

        $cards = Card::whereHas('column.board.workspace', $memberOfWorkspace)
            ->where(fn ($query) => $query->where('title', 'LIKE', "%{$q}%")->orWhere('description', 'LIKE', "%{$q}%"))
            ->limit(20)
            ->get();

        $comments = Comment::whereHas('card.column.board.workspace', $memberOfWorkspace)
            ->where('body', 'LIKE', "%{$q}%")
            ->limit(20)
            ->get();

        return response()->json([
            'boards' => BoardResource::collection($boards),
            'cards' => CardResource::collection($cards),
            'comments' => CommentResource::collection($comments),
        ]);
    }
}
