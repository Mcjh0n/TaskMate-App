<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TaskMateAlert extends Notification
{
    /** @param array{event: string, title: string, body: string, url: string, related_ids: array<string, int>} $payload */
    public function __construct(private readonly array $payload) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
