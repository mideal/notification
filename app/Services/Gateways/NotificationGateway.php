<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Exceptions\GatewayUnavailableException;
use App\Models\Notification;

interface NotificationGateway
{
    /**
     * @throws GatewayUnavailableException when the provider is temporarily unreachable
     */
    public function send(Notification $notification): GatewayResult;
}
