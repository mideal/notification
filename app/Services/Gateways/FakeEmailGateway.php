<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Exceptions\GatewayUnavailableException;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class FakeEmailGateway implements NotificationGateway
{
    public function send(Notification $notification): GatewayResult
    {
        if (str_starts_with($notification->recipient_id, 'unavailable')) {
            throw new GatewayUnavailableException('Email gateway is temporarily unavailable');
        }

        if (str_starts_with($notification->recipient_id, 'invalid')) {
            return GatewayResult::Rejected;
        }

        Log::info('Email sent via fake gateway', [
            'notification_id' => $notification->id,
            'recipient_id' => $notification->recipient_id,
        ]);

        return GatewayResult::Delivered;
    }
}
