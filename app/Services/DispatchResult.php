<?php

declare(strict_types=1);

namespace App\Services;

final readonly class DispatchResult
{
    public function __construct(
        public string $dispatchId,
        public int $notificationCount,
        public bool $duplicate,
    ) {}
}
