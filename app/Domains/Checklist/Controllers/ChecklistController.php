<?php

namespace App\Domains\Checklist\Controllers;

use App\Domains\Checklist\Requests\StoreChecklistItemRequest;
use App\Domains\Checklist\Requests\UpdateChecklistItemRequest;
use App\Domains\Checklist\Resources\ChecklistItemResource;
use App\Models\Card;
use App\Models\ChecklistItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ChecklistController
{
    use AuthorizesRequests;

    public function store(StoreChecklistItemRequest $request, Card $card): JsonResponse
    {
        $this->authorize('create', [ChecklistItem::class, $card]);

        $maxPosition = ChecklistItem::where('card_id', $card->id)->max('position') ?? 0;
        $item = ChecklistItem::create([
            'card_id' => $card->id,
            'text' => $request->text,
            'position' => $maxPosition + 1000,
        ]);

        return response()->json(new ChecklistItemResource($item), 201);
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistItem): JsonResponse
    {
        $this->authorize('update', $checklistItem);

        $checklistItem->update($request->only('text', 'done', 'position'));

        return response()->json(new ChecklistItemResource($checklistItem));
    }

    public function destroy(ChecklistItem $checklistItem): JsonResponse
    {
        $this->authorize('delete', $checklistItem);

        $checklistItem->delete();

        return response()->json(null, 204);
    }
}
