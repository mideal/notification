<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispatch_id' => (string) Str::uuid7(),
            'idempotency_key' => null,
            'recipient_id' => $this->faker->uuid(),
            'channel' => NotificationChannel::Email,
            'message' => $this->faker->sentence(),
            'priority' => NotificationPriority::Marketing,
            'status' => NotificationStatus::Queued,
            'attempts' => 0,
        ];
    }

    public function forRecipient(string $recipientId): static
    {
        return $this->state(['recipient_id' => $recipientId]);
    }

    public function sms(): static
    {
        return $this->state(['channel' => NotificationChannel::Sms]);
    }

    public function transactional(): static
    {
        return $this->state(['priority' => NotificationPriority::Transactional]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => NotificationStatus::Sent,
            'attempts' => 1,
            'sent_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state([
            'status' => NotificationStatus::Delivered,
            'attempts' => 1,
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => NotificationStatus::Failed,
            'attempts' => 3,
            'error' => 'Recipient address rejected by provider',
            'failed_at' => now(),
        ]);
    }
}
