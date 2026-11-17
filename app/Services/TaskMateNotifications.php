<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TaskMateAlert;
use App\Notifications\TaskMateAlertCatalog;
use Illuminate\Support\Facades\DB;

class TaskMateNotifications
{
    /** @param array<string, int> $relatedIds */
    public function send(User|iterable $recipients, string $event, string $url, array $relatedIds = [], array $values = [], ?int $actorId = null): void
    {
        $recipients = $recipients instanceof User ? collect([$recipients]) : collect($recipients);
        $copy = TaskMateAlertCatalog::get($event, $values);
        $payload = [
            'event' => $event,
            ...$copy,
            'url' => $this->safeUrl($url),
            'related_ids' => array_filter($relatedIds, fn ($id) => is_int($id)),
        ];
        $ids = $recipients->filter(fn ($recipient) => $recipient instanceof User && $recipient->id !== $actorId)->pluck('id')->unique()->values();

        foreach ($ids as $id) {
            DB::afterCommit(function () use ($id, $payload): void {
                if ($recipient = User::query()->find($id)) {
                    $recipientPayload = $payload;
                    if (! $recipient->isAdmin() && ! $recipient->isVerified()
                        && ! str_starts_with($recipientPayload['event'], 'account.')) {
                        $recipientPayload['url'] = route('notifications.index', [], false);
                    }
                    $recipient->notify(new TaskMateAlert($recipientPayload));
                }
            });
        }
    }

    /** @param array<string, int> $relatedIds */
    public function admins(string $event, string $url, array $relatedIds = [], array $values = [], ?int $actorId = null): void
    {
        $this->send(User::query()->where('is_admin', true)->get(), $event, $url, $relatedIds, $values, $actorId);
    }

    private function safeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        abort_unless(is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//'), 500, 'Notification targets must be local paths.');
        return $url;
    }
}
