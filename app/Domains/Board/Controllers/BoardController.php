<?php

namespace App\Domains\Board\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Board\Requests\StoreBoardRequest;
use App\Domains\Board\Requests\UpdateBoardRequest;
use App\Domains\Board\Resources\BoardResource;
use App\Domains\Subscription\Support\PlanLimits;
use App\Exceptions\PlanLimitExceededException;
use App\Models\Board;
use App\Models\Workspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class BoardController
{
    use AuthorizesRequests;

    public function index(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $boards = Board::where('workspace_id', $workspace->id)->get();

        return response()->json(BoardResource::collection($boards));
    }

    public function store(StoreBoardRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $limit = PlanLimits::boardsAllowed($workspace);
        if ($limit !== null && $workspace->boards()->count() >= $limit) {
            throw new PlanLimitExceededException("This workspace has reached the Free plan limit of {$limit} board(s).");
        }

        $board = Board::create(['workspace_id' => $workspace->id, 'title' => $request->title]);

        (new LogActivityAction())->execute($request->user(), 'board.created', $workspace, $board, $board->id);

        return response()->json(new BoardResource($board), 201);
    }

    public function show(Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $board->load(['columns' => fn ($q) => $q->orderBy('position'), 'columns.cards' => fn ($q) => $q->orderBy('position')]);

        return response()->json(new BoardResource($board));
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $board->update($request->only('title', 'archived'));

        return response()->json(new BoardResource($board));
    }

    public function destroy(Board $board): JsonResponse
    {
        $this->authorize('delete', $board);

        $board->delete();

        return response()->json(null, 204);
    }
}
