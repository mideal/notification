<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\Gateways\FakeEmailGateway;
use App\Services\NotificationSender;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class NotificationSendingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_queued_notification_is_sent_and_marked_delivered(): void
    {
        $notification = Notification::factory()->create();

        $this->sender()->send($notification->id);

        $notification->refresh();
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->sent_at);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_rejected_recipient_marks_notification_failed(): void
    {
        $notification = Notification::factory()->forRecipient('invalid-address')->create();

        $this->sender()->send($notification->id);

        $notification->refresh();
        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertNotNull($notification->error);
        $this->assertNotNull($notification->failed_at);
        $this->assertNull($notification->sent_at);
    }

    public function test_transient_gateway_failure_requeues_notification(): void
    {
        Kafka::fake();
        $notification = Notification::factory()->forRecipient('unavailable-now')->create();

        $this->sender()->send($notification->id);

        $notification->refresh();
        $this->assertSame(NotificationStatus::Queued, $notification->status);
        $this->assertSame(1, $notification->attempts);

        Kafka::assertPublishedOn('notifications.marketing');
    }

    public function test_notification_fails_after_max_attempts(): void
    {
        Kafka::fake();
        $maxAttempts = config()->integer('notification.max_attempts');
        $notification = Notification::factory()
            ->forRecipient('unavailable-now')
            ->create(['attempts' => $maxAttempts - 1]);

        $this->sender()->send($notification->id);

        $notification->refresh();
        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame($maxAttempts, $notification->attempts);
        $this->assertStringContainsString('Gateway unavailable', (string) $notification->error);

        Kafka::assertNothingPublished();
    }

    public function test_already_processed_notification_is_skipped(): void
    {
        $notification = Notification::factory()->delivered()->create();

        $this->mock(FakeEmailGateway::class)->shouldNotReceive('send');

        $this->sender()->send($notification->id);

        $notification->refresh();
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(1, $notification->attempts);
    }

    public function test_unknown_notification_id_is_ignored(): void
    {
        $this->sender()->send('00000000-0000-0000-0000-000000000000');

        $this->assertDatabaseCount('notifications', 0);
    }

    private function sender(): NotificationSender
    {
        return $this->app->make(NotificationSender::class);
    }
}
