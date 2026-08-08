<?php

namespace App\Domains\Attachment\Controllers;

use App\Domains\Attachment\Requests\StoreAttachmentRequest;
use App\Domains\Attachment\Resources\AttachmentResource;
use App\Domains\Subscription\Support\PlanLimits;
use App\Exceptions\PlanLimitExceededException;
use App\Models\Attachment;
use App\Models\Card;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AttachmentController
{
    use AuthorizesRequests;

    public function store(StoreAttachmentRequest $request, Card $card): JsonResponse
    {
        $this->authorize('create', [Attachment::class, $card]);

        $workspace = $card->column->board->workspace;
        $file = $request->file('file');

        $limit = PlanLimits::attachmentBytesAllowed($workspace);
        if ($limit !== null && (PlanLimits::attachmentBytesUsed($workspace) + $file->getSize()) > $limit) {
            throw new PlanLimitExceededException('This workspace has reached its Free plan attachment storage limit.');
        }

        $path = $file->store('attachments', 'local');

        $attachment = Attachment::create([
            'card_id' => $card->id,
            'uploader_id' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'url' => Storage::disk('local')->url($path),
        ]);

        return response()->json(new AttachmentResource($attachment), 201);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        $path = str_replace(Storage::disk('local')->url(''), '', $attachment->url);
        Storage::disk('local')->delete($path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
