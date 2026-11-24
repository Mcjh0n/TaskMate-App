import { useEffect, useRef, useState } from 'react';
import { Bell } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

type Alert = {
    id: string;
    event: string;
    title: string;
    body: string;
    url: string;
    createdAt: string | null;
    readAt: string | null;
};
type Summary = { unreadCount: number; unreadMessages: number; latest: Alert[] };

export function NotificationBell({
    pollSeconds = 10,
    showMessages = true,
}: {
    pollSeconds?: number;
    showMessages?: boolean;
}) {
    const [summary, setSummary] = useState<Summary>({
        unreadCount: 0,
        unreadMessages: 0,
        latest: [],
    });
    const authUser = (
        usePage().props as {
            auth?: {
                user?: {
                    is_admin?: boolean;
                    verification_status?: string;
                } | null;
            };
        }
    ).auth?.user;
    const backoff = useRef(0);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;
        let stopped = false;
        let request: AbortController | undefined;

        const schedule = (delay: number) => {
            if (stopped || document.visibilityState !== 'visible') return;
            timer = setTimeout(poll, delay);
        };

        const poll = async () => {
            if (stopped || document.visibilityState !== 'visible') return;
            request = new AbortController();
            try {
                const response = await fetch('/notifications/summary', {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: request.signal,
                });
                if (response.redirected || response.status === 401) {
                    stopped = true;
                    return;
                }
                if (!response.ok)
                    throw new Error(
                        `Notification check failed (${response.status}).`,
                    );
                setSummary((await response.json()) as Summary);
                backoff.current = 0;
                schedule(Math.max(1, pollSeconds) * 1000);
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                )
                    return;
                backoff.current = Math.min(backoff.current + 1, 6);
                schedule(
                    Math.min(
                        Math.max(1, pollSeconds) * 1000 * 2 ** backoff.current,
                        300_000,
                    ),
                );
            }
        };

        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                if (timer) clearTimeout(timer);
                void poll();
            } else {
                if (timer) clearTimeout(timer);
                request?.abort();
            }
        };

        document.addEventListener('visibilitychange', onVisibility);
        if (document.visibilityState === 'visible') void poll();
        return () => {
            stopped = true;
            if (timer) clearTimeout(timer);
            request?.abort();
            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [pollSeconds]);

    return (
        <div className="relative flex items-center gap-2">
            {showMessages &&
                authUser &&
                !authUser.is_admin &&
                authUser.verification_status === 'verified' && (
                    <Link
                        href="/messages"
                        className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm underline-offset-4 hover:underline"
                    >
                        Messages
                        {summary.unreadMessages > 0 && (
                            <>
                                <span className="sr-only">, new messages</span>
                                <span
                                    className="size-2 rounded-full bg-primary"
                                    aria-hidden="true"
                                />
                            </>
                        )}
                    </Link>
                )}
            <details className="relative">
                <summary
                    aria-label={`Notifications, ${summary.unreadCount} unread`}
                    className="flex size-10 cursor-pointer list-none items-center justify-center rounded-md hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Bell className="size-5" aria-hidden="true" />
                    <span className="sr-only">Notifications</span>
                    {summary.unreadCount > 0 && (
                        <span
                            className="absolute top-0 right-0 min-w-5 rounded-full bg-primary px-1 text-center text-xs text-primary-foreground"
                            aria-hidden="true"
                        >
                            {summary.unreadCount > 99
                                ? '99+'
                                : summary.unreadCount}
                        </span>
                    )}
                </summary>
                <div className="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-lg border bg-popover p-3 text-popover-foreground shadow-lg">
                    <div className="flex items-center justify-between gap-2">
                        <h2 className="font-semibold">Recent alerts</h2>
                        <Link
                            href="/notifications"
                            className="text-sm underline"
                        >
                            View all
                        </Link>
                    </div>
                    <div className="mt-2 max-h-96 space-y-2 overflow-y-auto">
                        {summary.latest.length ? (
                            summary.latest.map((alert) => (
                                <Link
                                    key={alert.id}
                                    href={alert.url}
                                    className={`block rounded-md border p-3 hover:bg-muted ${alert.readAt ? 'opacity-75' : ''}`}
                                >
                                    <p className="text-sm font-medium">
                                        {alert.title}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {alert.body}
                                    </p>
                                </Link>
                            ))
                        ) : (
                            <p className="rounded-md border p-3 text-sm text-muted-foreground">
                                You have no alerts yet.
                            </p>
                        )}
                    </div>
                </div>
            </details>
        </div>
    );
}
