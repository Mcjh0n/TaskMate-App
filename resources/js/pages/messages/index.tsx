import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/format';
import { VerificationStatusBadge } from '@/components/verification-status-badge';

type Item = {
    id: number;
    taskTitle: string;
    otherPerson: {
        name: string;
        type: string;
        verificationStatus:
            | 'pending'
            | 'verified'
            | 'rejected'
            | 'more_info_requested';
    };
    preview: string | null;
    latestAt: string | null;
    hasUnreadMessages: boolean;
    status: 'open' | 'closed' | 'read_only';
};

export default function MessagesIndex({
    conversations,
}: {
    conversations: {
        data: Item[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}) {
    return (
        <>
            <Head title="Messages" />
            <main className="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Messages</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Private conversations are available to the task poster
                        and applicant.
                    </p>
                </div>
                {conversations.data.length ? (
                    <div className="grid gap-3">
                        {conversations.data.map((item) => (
                            <Link
                                key={item.id}
                                href={`/messages/${item.id}`}
                                className="block rounded-xl border bg-card p-4 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            {item.taskTitle}
                                        </p>
                                        <h2 className="mt-1 font-semibold">
                                            {item.otherPerson.name}
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            {item.otherPerson.type}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <VerificationStatusBadge
                                            status={
                                                item.otherPerson
                                                    .verificationStatus
                                            }
                                        />
                                        {item.status !== 'open' && (
                                            <span className="rounded border px-2 py-1 text-xs">
                                                {item.status === 'closed'
                                                    ? 'Closed'
                                                    : 'Read-only'}
                                            </span>
                                        )}
                                        {item.hasUnreadMessages && (
                                            <span className="rounded-full bg-primary px-2 py-1 text-xs text-primary-foreground">
                                                Unread
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="mt-3 flex justify-between gap-3 text-sm">
                                    <p className="line-clamp-2 whitespace-pre-wrap text-muted-foreground">
                                        {item.preview ??
                                            'No messages yet. Start the conversation.'}
                                    </p>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {item.latestAt
                                            ? formatDateTime(item.latestAt)
                                            : '—'}
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <section className="rounded-xl border bg-card p-8 text-center">
                        <h2 className="font-semibold">No conversations yet</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            A private conversation opens when someone applies to
                            a task.
                        </p>
                    </section>
                )}
                {conversations.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2"
                        aria-label="Conversation pages"
                    >
                        {conversations.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-muted font-medium' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="rounded border px-3 py-1 text-sm text-muted-foreground"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </main>
        </>
    );
}
MessagesIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
