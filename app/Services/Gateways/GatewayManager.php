<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Enums\NotificationChannel;
use Illuminate\Contracts\Container\Container;

class GatewayManager
{
    public function __construct(private readonly Container $container) {}

    public function forChannel(NotificationChannel $channel): NotificationGateway
    {
        return match ($channel) {
            NotificationChannel::Sms => $this->container->make(FakeSmsGateway::class),
            NotificationChannel::Email => $this->container->make(FakeEmailGateway::class),
        };
    }
}
