<?php

namespace App\Domains\Column\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Column\Requests\StoreColumnRequest;
use App\Domains\Column\Requests\UpdateColumnRequest;
use App\Domains\Column\Resources\ColumnResource;
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

        return response()->json(new ColumnResource($column), 201);
    }

    public function update(UpdateColumnRequest $request, Column $column): JsonResponse
    {
        $this->authorize('update', $column);

        $column->update($request->only('title', 'position'));

        return response()->json(new ColumnResource($column));
    }

    public function destroy(Column $column): JsonResponse
    {
        $this->authorize('delete', $column);

        $column->delete();

        return response()->json(null, 204);
    }
}
