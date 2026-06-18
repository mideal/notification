<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Exceptions\GatewayUnavailableException;
use App\Models\Notification;
use App\Services\Gateways\GatewayManager;
use App\Services\Gateways\GatewayResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationSender
{
    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly NotificationProducer $producer,
    ) {}

    public function send(string $notificationId): void
    {
        $notification = Notification::query()->find($notificationId);

        if ($notification === null) {
            Log::warning('Notification not found, skipping', ['notification_id' => $notificationId]);

            return;
        }

        $lock = Cache::lock("notifications:sending:{$notificationId}", 30);

        if (! $lock->get()) {
            return;
        }

        try {
            if ($notification->status !== NotificationStatus::Queued) {
                return;
            }

            $this->deliver($notification);
        } finally {
            $lock->release();
        }
    }

    private function deliver(Notification $notification): void
    {
        $notification->attempts++;

        try {
            $result = $this->gateways->forChannel($notification->channel)->send($notification);
        } catch (GatewayUnavailableException $exception) {
            $this->retry($notification, $exception);

            return;
        }

        if ($result === GatewayResult::Rejected) {
            $notification->forceFill([
                'status' => NotificationStatus::Failed,
                'error' => 'Rejected by provider: invalid recipient',
                'failed_at' => now(),
            ])->save();

            return;
        }

        $notification->forceFill([
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
        ])->save();

        $notification->forceFill([
            'status' => NotificationStatus::Delivered,
            'delivered_at' => now(),
        ])->save();
    }

    private function retry(Notification $notification, GatewayUnavailableException $exception): void
    {
        if ($notification->attempts >= config()->integer('notification.max_attempts')) {
            $notification->forceFill([
                'status' => NotificationStatus::Failed,
                'error' => "Gateway unavailable after {$notification->attempts} attempts: {$exception->getMessage()}",
                'failed_at' => now(),
            ])->save();

            return;
        }

        $notification->save();
        $this->producer->produce($notification);

        Log::info('Notification requeued after transient gateway failure', [
            'notification_id' => $notification->id,
            'attempt' => $notification->attempts,
        ]);
    }
}
