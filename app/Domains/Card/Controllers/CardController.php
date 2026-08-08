<?php

namespace App\Domains\Card\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Card\Actions\MoveCardAction;
use App\Domains\Card\Requests\StoreCardRequest;
use App\Domains\Card\Requests\UpdateCardRequest;
use App\Domains\Card\Resources\CardResource;
use App\Domains\Notification\Actions\CreateNotificationAction;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CardController
{
    use AuthorizesRequests;

    public function store(StoreCardRequest $request, Column $column): JsonResponse
    {
        $this->authorize('create', [Card::class, $column]);

        $maxPosition = Card::where('column_id', $column->id)->max('position') ?? 0;
        $card = Card::create([
            'column_id' => $column->id,
            'title' => $request->title,
            'position' => $maxPosition + 1000,
        ]);

        $board = $column->board;
        (new LogActivityAction())->execute($request->user(), 'card.created', $board->workspace, $board, $card->id);

        return response()->json(new CardResource($card), 201);
    }

    public function show(Card $card): JsonResponse
    {
        $this->authorize('view', $card);

        $card->load(['labels', 'assignees', 'comments.author', 'checklistItems', 'attachments']);

        return response()->json(new CardResource($card));
    }

    public function update(UpdateCardRequest $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $board = $card->column->board;
        $wasCompleted = $card->completed_at !== null;
        $previousAssigneeIds = $card->assignees()->pluck('users.id')->all();

        $moved = $request->has('column_id') && $request->column_id !== $card->column_id;

        if ($moved) {
            (new MoveCardAction())->execute($card, $request->column_id, (float) ($request->position ?? 0));
            (new LogActivityAction())->execute($request->user(), 'card.moved', $board->workspace, $board, $card->id);
        }

        $fields = $request->except('assignee_ids', 'label_ids');
        if ($moved) {
            unset($fields['column_id'], $fields['position']);
        }

        $card->update($fields);

        if ($request->has('assignee_ids')) {
            $card->assignees()->sync($request->assignee_ids);

            $newlyAssigned = array_diff($request->assignee_ids, $previousAssigneeIds);
            foreach ($card->assignees()->whereIn('users.id', $newlyAssigned)->get() as $assignee) {
                if ($assignee->id !== $request->user()->id) {
                    (new CreateNotificationAction())->execute($assignee, 'card.assigned', [
                        'card_id' => $card->id,
                        'card_title' => $card->title,
                        'assigned_by' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($request->has('label_ids')) {
            $card->labels()->sync($request->label_ids);
        }

        if (! $wasCompleted && $card->fresh()->completed_at !== null) {
            (new LogActivityAction())->execute($request->user(), 'card.completed', $board->workspace, $board, $card->id);
        }

        return response()->json(new CardResource($card->fresh(['labels', 'assignees'])));
    }

    public function destroy(Card $card): JsonResponse
    {
        $this->authorize('delete', $card);

        $card->delete();

        return response()->json(null, 204);
    }
}
