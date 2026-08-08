<?php

namespace App\Domains\Label\Controllers;

use App\Domains\Label\Requests\StoreLabelRequest;
use App\Domains\Label\Resources\LabelResource;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class LabelController
{
    use AuthorizesRequests;

    public function index(Board $board): JsonResponse
    {
        $this->authorize('viewAny', [Label::class, $board]);

        $labels = Label::where('board_id', $board->id)->get();

        return response()->json(LabelResource::collection($labels));
    }

    public function store(StoreLabelRequest $request, Board $board): JsonResponse
    {
        $this->authorize('create', [Label::class, $board]);

        $label = Label::create([
            'board_id' => $board->id,
            'name' => $request->name,
            'color' => $request->color,
        ]);

        return response()->json(new LabelResource($label), 201);
    }

    public function destroy(Label $label): JsonResponse
    {
        $this->authorize('delete', $label);

        $label->delete();

        return response()->json(null, 204);
    }
}
