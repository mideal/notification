<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $dispatch_id
 * @property string|null $idempotency_key
 * @property string $recipient_id
 * @property NotificationChannel $channel
 * @property string $message
 * @property NotificationPriority $priority
 * @property NotificationStatus $status
 * @property int $attempts
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'dispatch_id',
        'idempotency_key',
        'recipient_id',
        'channel',
        'message',
        'priority',
        'status',
        'attempts',
        'error',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
