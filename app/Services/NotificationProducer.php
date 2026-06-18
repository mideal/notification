<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationPriority;
use App\Models\Notification;
use Junges\Kafka\Facades\Kafka;

class NotificationProducer
{
    public function produce(Notification $notification): void
    {
        Kafka::publish()
            ->onTopic($this->topicFor($notification->priority))
            ->withKafkaKey($notification->recipient_id)
            ->withBody(['notification_id' => $notification->id])
            ->send();
    }

    public function topicFor(NotificationPriority $priority): string
    {
        return config()->string("notification.topics.{$priority->value}");
    }
}
