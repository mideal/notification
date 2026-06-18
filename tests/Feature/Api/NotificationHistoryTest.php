<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_recipient_notifications_with_statuses(): void
    {
        Notification::factory()->forRecipient('user-1')->delivered()->create();
        Notification::factory()->forRecipient('user-1')->failed()->create();
        Notification::factory()->forRecipient('user-2')->create();

        $response = $this->getJson('/api/v1/recipients/user-1/notifications');

        $response->assertOk()->assertJsonCount(2, 'data');

        $statuses = collect($response->json('data'))->pluck('status');
        $this->assertEqualsCanonicalizing(['delivered', 'failed'], $statuses->all());
    }

    public function test_history_is_ordered_from_newest_to_oldest(): void
    {
        $old = Notification::factory()->forRecipient('user-1')->create(['created_at' => now()->subDay()]);
        $new = Notification::factory()->forRecipient('user-1')->create(['created_at' => now()]);

        $response = $this->getJson('/api/v1/recipients/user-1/notifications');

        $this->assertSame([$new->id, $old->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_history_is_paginated(): void
    {
        Notification::factory()->count(30)->forRecipient('user-1')->create();

        $response = $this->getJson('/api/v1/recipients/user-1/notifications');

        $response->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.total', 30);
    }

    public function test_returns_empty_list_for_unknown_recipient(): void
    {
        $this->getJson('/api/v1/recipients/ghost/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
