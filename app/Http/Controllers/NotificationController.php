<?php

namespace App\Http\Controllers;

use App\Services\ConversationWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]]);
        $query = $request->user()->notifications()->latest('created_at');
        if (($filters['filter'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }
        $alerts = $query->paginate((int) config('taskmate.notifications.per_page', 20))->withQueryString()
            ->through(fn (DatabaseNotification $notification) => $this->item($notification));

        return Inertia::render('notifications/index', [
            'alerts' => $alerts,
            'filter' => $filters['filter'] ?? 'all',
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function summary(Request $request, ConversationWorkflow $conversations): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unreadCount' => $user->unreadNotifications()->count(),
            'unreadMessages' => $conversations->unreadCount($user),
            'latest' => $user->notifications()->latest('created_at')->limit(5)->get()->map(fn (DatabaseNotification $notification) => $this->item($notification))->values(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All alerts were marked as read.');
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $alert = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $alert->markAsRead();
        $target = $alert->data['url'] ?? route('notifications.index', [], false);
        if (! is_string($target) || ! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = route('notifications.index', [], false);
        }

        return redirect($target);
    }

    private function item(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'event' => $data['event'] ?? 'activity',
            'title' => $data['title'] ?? 'TaskMate activity',
            'body' => $data['body'] ?? 'There is a new update for you.',
            'count' => max(1, (int) ($data['count'] ?? 1)),
            'url' => route('notifications.open', $notification->id, false),
            'createdAt' => $notification->created_at?->toDateTimeString(),
            'readAt' => $notification->read_at?->toDateTimeString(),
        ];
    }
}
