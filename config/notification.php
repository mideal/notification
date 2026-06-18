<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Kafka Topics
    |--------------------------------------------------------------------------
    |
    | Each priority level is routed to its own Kafka topic so that dedicated
    | consumers can process transactional messages without waiting for the
    | marketing backlog.
    |
    */

    'topics' => [
        'transactional' => env('KAFKA_TOPIC_TRANSACTIONAL', 'notifications.transactional'),
        'marketing' => env('KAFKA_TOPIC_MARKETING', 'notifications.marketing'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    */

    'max_attempts' => (int) env('NOTIFICATION_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) a dispatch idempotency key is remembered in Redis.
    |
    */

    'idempotency_ttl' => (int) env('NOTIFICATION_IDEMPOTENCY_TTL', 86400),

];
