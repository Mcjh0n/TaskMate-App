import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/format';

type Alert = { id: string; event: string; title: string; body: string; url: string; createdAt: string | null; readAt: string | null };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Props = { alerts: { data: Alert[]; links: PaginationLink[]; from: number | null; to: number | null; total: number }; filter: 'all' | 'unread'; unreadCount: number };

export default function NotificationsIndex({ alerts, filter, unreadCount }: Props) {
    return <><Head title="Notifications" /><main className="mx-auto max-w-3xl space-y-5 p-4 sm:p-6">
        <div className="flex flex-wrap items-end justify-between gap-3"><div><h1 className="text-2xl font-semibold">Notifications</h1><p className="mt-1 text-sm text-muted-foreground">{unreadCount} unread alert{unreadCount === 1 ? '' : 's'}</p></div>{unreadCount > 0 && <button type="button" onClick={() => router.post('/notifications/read-all')} className="rounded-md border px-3 py-2 text-sm hover:bg-muted">Mark all as read</button>}</div>
        <nav className="flex gap-3" aria-label="Notification filters"><Link className={filter === 'all' ? 'font-semibold underline' : 'underline'} href="/notifications?filter=all">All</Link><Link className={filter === 'unread' ? 'font-semibold underline' : 'underline'} href="/notifications?filter=unread">Unread</Link></nav>
        <section className="space-y-3" aria-label={`${filter === 'unread' ? 'Unread' : 'All'} alerts`}>
            {alerts.data.length ? alerts.data.map((alert) => <Link key={alert.id} href={alert.url} className={`block rounded-lg border p-4 hover:bg-muted/50 ${alert.readAt ? 'opacity-75' : 'border-primary/60'}`}><div className="flex items-start justify-between gap-4"><div><h2 className="font-medium">{alert.title}{!alert.readAt && <span className="sr-only">, unread</span>}</h2><p className="mt-1 text-sm text-muted-foreground">{alert.body}</p></div>{!alert.readAt && <span className="rounded border px-2 py-0.5 text-xs">Unread</span>}</div><p className="mt-2 text-xs text-muted-foreground">{alert.createdAt ? formatDateTime(alert.createdAt) : ''}</p></Link>) : <p className="rounded-lg border p-5 text-sm text-muted-foreground">{filter === 'unread' ? 'You have no unread alerts.' : 'You have no alerts yet.'}</p>}
        </section>
        {alerts.links.length > 3 && <nav aria-label="Notification pages" className="flex flex-wrap gap-2">{alerts.links.map((link, index) => <Link key={`${index}-${link.label}`} href={link.url ?? '#'} aria-current={link.active ? 'page' : undefined} className={`rounded border px-3 py-2 text-sm ${link.active ? 'bg-muted font-semibold' : ''} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}>{link.label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»')}</Link>)}</nav>}
    </main></>;
}

NotificationsIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
