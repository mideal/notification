<?php

declare(strict_types=1);

namespace App\Services\Gateways;

enum GatewayResult: string
{
    case Delivered = 'delivered';
    case Rejected = 'rejected';
}
