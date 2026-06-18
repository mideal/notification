<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Junges\Kafka\Facades\Kafka;
use Tests\TestCase;

class SendNotificationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dispatches_notifications_and_publishes_to_kafka(): void
    {
        Kafka::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'email',
            'message' => 'Your order has been shipped',
            'recipients' => ['user-1', 'user-2'],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('notifications_count', 2)
            ->assertJsonPath('duplicate', false);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertSame(2, Notification::query()->where('status', NotificationStatus::Queued)->count());

        Kafka::assertPublishedTimes(2);
        Kafka::assertPublishedOn('notifications.marketing');
    }

    public function test_transactional_priority_is_routed_to_dedicated_topic(): void
    {
        Kafka::fake();

        $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Your access code is 1234',
            'recipients' => ['user-1'],
            'priority' => 'transactional',
        ])->assertAccepted();

        Kafka::assertPublishedOn('notifications.transactional');
    }

    public function test_duplicate_idempotency_key_does_not_create_duplicates(): void
    {
        Kafka::fake();

        $payload = [
            'channel' => 'email',
            'message' => 'Promo!',
            'recipients' => ['user-1', 'user-2'],
            'idempotency_key' => 'dispatch-abc',
        ];

        $first = $this->postJson('/api/v1/notifications', $payload);
        $second = $this->postJson('/api/v1/notifications', $payload);

        $first->assertAccepted()->assertJsonPath('duplicate', false);
        $second->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('dispatch_id', $first->json('dispatch_id'))
            ->assertJsonPath('notifications_count', 2);

        $this->assertDatabaseCount('notifications', 2);
        Kafka::assertPublishedTimes(2);
    }

    public function test_recipients_are_deduplicated_within_request(): void
    {
        Kafka::fake();

        $this->postJson('/api/v1/notifications', [
            'channel' => 'email',
            'message' => 'Hello',
            'recipients' => ['user-1', 'user-1', 'user-1'],
        ])->assertAccepted()->assertJsonPath('notifications_count', 1);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_validation_rejects_unknown_channel_and_empty_recipients(): void
    {
        $this->postJson('/api/v1/notifications', [
            'channel' => 'pigeon',
            'message' => 'Hello',
            'recipients' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['channel', 'recipients']);
    }
}
