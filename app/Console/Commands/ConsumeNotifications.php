<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NotificationSender;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

#[Signature('notifications:consume
    {--topic=* : Kafka-топики (по умолчанию — все топики уведомлений)}
    {--group= : Consumer group id}')]
#[Description('Consume notification messages from Kafka and deliver them through gateways')]
class ConsumeNotifications extends Command
{
    public function handle(NotificationSender $sender): int
    {
        /** @var list<string> $topics */
        $topics = $this->option('topic') !== []
            ? $this->option('topic')
            : array_values(config()->array('notification.topics'));

        /** @var string $group */
        $group = $this->option('group') ?? config()->string('kafka.consumer_group_id');

        $this->info(sprintf('Consuming topics [%s] as group [%s]', implode(', ', $topics), $group));

        $consumer = Kafka::consumer($topics, $group)
            ->withHandler(function (ConsumerMessage $message) use ($sender): void {
                $body = $message->getBody();
                $notificationId = is_array($body) ? ($body['notification_id'] ?? null) : null;

                if (is_string($notificationId)) {
                    $sender->send($notificationId);
                }
            })
            ->withAutoCommit()
            ->build();

        $consumer->consume();

        return self::SUCCESS;
    }
}
