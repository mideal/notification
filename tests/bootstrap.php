<?php

declare(strict_types=1);

/*
 * Запуск тестов без расширения rdkafka (CI образ shivammathur и хост):
 * пакет laravel-kafka ссылается на константы librdkafka, которых нет,
 * если расширение не установлено. Kafka::fake() при этом не требует
 * реального брокера.
 */
if (! defined('RD_KAFKA_PARTITION_UA')) {
    define('RD_KAFKA_PARTITION_UA', -1);
}

require __DIR__.'/../vendor/autoload.php';
