<?php

namespace App\Domains\Column\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Column\Requests\StoreColumnRequest;
use App\Domains\Column\Requests\UpdateColumnRequest;
use App\Domains\Column\Resources\ColumnResource;
use App\Events\ColumnCreated;
use App\Events\ColumnDeleted;
use App\Events\ColumnUpdated;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ColumnController
{
    use AuthorizesRequests;

    public function store(StoreColumnRequest $request, Board $board): JsonResponse
    {
        $this->authorize('create', [Column::class, $board]);

        $maxPosition = Column::where('board_id', $board->id)->max('position') ?? 0;
        $column = Column::create([
            'board_id' => $board->id,
            'title' => $request->title,
            'position' => $maxPosition + 1000,
        ]);

        (new LogActivityAction())->execute($request->user(), 'column.created', $board->workspace, $board, $column->id);
        broadcast(new ColumnCreated($column, $board->id))->toOthers();

        return response()->json(new ColumnResource($column), 201);
    }

    public function update(UpdateColumnRequest $request, Column $column): JsonResponse
    {
        $this->authorize('update', $column);

        $column->update($request->only('title', 'position'));

        broadcast(new ColumnUpdated($column, $column->board_id))->toOthers();

        return response()->json(new ColumnResource($column));
    }

    public function destroy(Column $column): JsonResponse
    {
        $this->authorize('delete', $column);

        $boardId = $column->board_id;
        $columnId = $column->id;
        $column->delete();

        broadcast(new ColumnDeleted($columnId, $boardId))->toOthers();

        return response()->json(null, 204);
    }
}
