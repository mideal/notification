<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function store(SendNotificationsRequest $request, NotificationDispatcher $dispatcher): JsonResponse
    {
        $result = $dispatcher->dispatch(
            channel: $request->channel(),
            message: $request->message(),
            recipientIds: $request->recipients(),
            priority: $request->priority(),
            idempotencyKey: $request->idempotencyKey(),
        );

        return response()->json([
            'dispatch_id' => $result->dispatchId,
            'notifications_count' => $result->notificationCount,
            'duplicate' => $result->duplicate,
        ], $result->duplicate ? 200 : 202);
    }

    public function index(string $recipientId): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            Notification::query()
                ->where('recipient_id', $recipientId)
                ->latest('created_at')
                ->paginate(25)
        );
    }
}
