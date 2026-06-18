<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationDispatcher
{
    public function __construct(private readonly NotificationProducer $producer) {}

    /**
     * @param  list<string>  $recipientIds
     */
    public function dispatch(
        NotificationChannel $channel,
        string $message,
        array $recipientIds,
        NotificationPriority $priority,
        ?string $idempotencyKey = null,
    ): DispatchResult {
        $dispatchId = (string) Str::uuid7();

        if ($idempotencyKey !== null) {
            $reserved = Cache::add(
                $this->idempotencyCacheKey($idempotencyKey),
                $dispatchId,
                config()->integer('notification.idempotency_ttl'),
            );

            if (! $reserved) {
                $existingDispatchId = Cache::get($this->idempotencyCacheKey($idempotencyKey));

                if (is_string($existingDispatchId)) {
                    return new DispatchResult(
                        dispatchId: $existingDispatchId,
                        notificationCount: Notification::query()->where('dispatch_id', $existingDispatchId)->count(),
                        duplicate: true,
                    );
                }
            }
        }

        try {
            $notifications = DB::transaction(
                fn () => collect($recipientIds)->map(fn (string $recipientId): Notification => Notification::query()->create([
                    'dispatch_id' => $dispatchId,
                    'idempotency_key' => $idempotencyKey,
                    'recipient_id' => $recipientId,
                    'channel' => $channel,
                    'message' => $message,
                    'priority' => $priority,
                    'status' => NotificationStatus::Queued,
                ]))
            );

            $notifications->each(fn (Notification $notification) => $this->producer->produce($notification));
        } catch (\Throwable $exception) {
            if ($idempotencyKey !== null) {
                Cache::forget($this->idempotencyCacheKey($idempotencyKey));
            }

            throw $exception;
        }

        return new DispatchResult(
            dispatchId: $dispatchId,
            notificationCount: $notifications->count(),
            duplicate: false,
        );
    }

    private function idempotencyCacheKey(string $idempotencyKey): string
    {
        return "notifications:idempotency:{$idempotencyKey}";
    }
}
