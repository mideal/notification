<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispatch_id' => $this->dispatch_id,
            'recipient_id' => $this->recipient_id,
            'channel' => $this->channel,
            'message' => $this->message,
            'priority' => $this->priority,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }
}
