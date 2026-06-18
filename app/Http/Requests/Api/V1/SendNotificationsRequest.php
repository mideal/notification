<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'message' => ['required', 'string', 'max:65535'],
            'recipients' => ['required', 'array', 'min:1', 'max:10000'],
            'recipients.*' => ['required', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'idempotency_key' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::from($this->string('channel')->value());
    }

    public function priority(): NotificationPriority
    {
        return NotificationPriority::tryFrom($this->string('priority')->value()) ?? NotificationPriority::Marketing;
    }

    public function message(): string
    {
        return $this->string('message')->value();
    }

    public function idempotencyKey(): ?string
    {
        return $this->filled('idempotency_key') ? $this->string('idempotency_key')->value() : null;
    }

    /**
     * @return list<string>
     */
    public function recipients(): array
    {
        return array_values(array_unique(array_map(strval(...), $this->array('recipients'))));
    }
}
