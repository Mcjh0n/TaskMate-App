import { useEffect, useMemo, useRef, useState } from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import { ProblemReportForm } from '@/components/problem-report-form';
import { formatDateTime } from '@/lib/format';

type Message = {
    id: number;
    senderId: number;
    senderName: string;
    isMine: boolean;
    body: string;
    createdAt: string | null;
    flaggedForRecipient: boolean;
    reportUrl: string | null;
};
type EventItem = {
    id: number;
    event: 'closed' | 'reopened';
    actor: string;
    createdAt: string | null;
};
type Props = {
    conversation: {
        id: number;
        taskId: number;
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
        status: string;
        closedBy: string | null;
        closedAt: string | null;
        sendBlockedReason: string | null;
        canSend: boolean;
        canClose: boolean;
        canReopen: boolean;
        lastMessageId: number;
    };
    messages: Message[];
    events: EventItem[];
    messageMaxLength: number;
    pollSeconds: number;
    reportContextMessages: number;
    reportTypes: { value: string; label: string }[];
    status?: string | null;
};

const PRIVATE_NOTICE =
    'Use this private conversation to discuss the task. Confirm the terms on the agreement page before starting work. Administrators can see flagged messages or limited context from a report. Keep passwords and identity documents private.';
const FLAG_NOTICE =
    'This message contains a request to move outside TaskMate or skip its steps. Be careful, keep agreements and communications on record, and report anything that makes you uncomfortable.';

export default function ConversationShow(props: Props) {
    return <ConversationThread key={props.conversation.id} {...props} />;
}

function ConversationThread(props: Props) {
    const { conversation } = props;
    const [polledMessages, setPolledMessages] = useState<Message[]>([]);
    const listRef = useRef<HTMLDivElement>(null);
    const cursor = useRef(conversation.lastMessageId);
    const backoff = useRef(0);
    const lastSavedMessage = props.messages.at(-1);
    const timeline = useMemo(
        () =>
            [
                ...[
                    ...props.messages,
                    ...polledMessages.filter(
                        (message) => message.id > conversation.lastMessageId,
                    ),
                ].map((message) => ({
                    kind: 'message' as const,
                    at: message.createdAt,
                    id: message.id,
                    message,
                })),
                ...props.events.map((event) => ({
                    kind: 'event' as const,
                    at: event.createdAt,
                    id: event.id,
                    event,
                })),
            ].sort(
                (a, b) => (a.at ?? '').localeCompare(b.at ?? '') || a.id - b.id,
            ),
        [
            props.messages,
            polledMessages,
            conversation.lastMessageId,
            props.events,
        ],
    );

    useEffect(() => {
        listRef.current?.scrollTo({ top: listRef.current.scrollHeight });
    }, []);

    useEffect(() => {
        cursor.current = Math.max(cursor.current, conversation.lastMessageId);
        if (lastSavedMessage?.isMine) {
            listRef.current?.scrollTo({ top: listRef.current.scrollHeight });
        }
    }, [conversation.lastMessageId, lastSavedMessage?.isMine]);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;
        let stopped = false;
        let request: AbortController | undefined;
        const schedule = (delay: number) => {
            if (!stopped && document.visibilityState === 'visible')
                timer = setTimeout(poll, delay);
        };
        const poll = async () => {
            if (stopped || document.visibilityState !== 'visible') return;
            request = new AbortController();
            try {
                const response = await fetch(
                    `/messages/${conversation.id}/updates?after_id=${cursor.current}`,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: request.signal,
                    },
                );
                if (response.redirected || response.status === 401) {
                    stopped = true;
                    return;
                }
                if (!response.ok)
                    throw new Error(
                        `Message refresh failed (${response.status}).`,
                    );
                const data = (await response.json()) as { messages: Message[] };
                if (data.messages.length) {
                    const shouldStick =
                        !!listRef.current &&
                        listRef.current.scrollHeight -
                            listRef.current.scrollTop -
                            listRef.current.clientHeight <
                            80;
                    cursor.current = Math.max(
                        cursor.current,
                        ...data.messages.map((message) => message.id),
                    );
                    setPolledMessages((current) => [
                        ...current,
                        ...data.messages.filter(
                            (message) =>
                                !current.some(
                                    (existing) => existing.id === message.id,
                                ),
                        ),
                    ]);
                    if (shouldStick)
                        requestAnimationFrame(() =>
                            listRef.current?.scrollTo({
                                top: listRef.current.scrollHeight,
                                behavior: 'smooth',
                            }),
                        );
                }
                backoff.current = 0;
                schedule(Math.max(1, props.pollSeconds) * 1000);
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                )
                    return;
                backoff.current = Math.min(backoff.current + 1, 6);
                schedule(
                    Math.min(
                        Math.max(1, props.pollSeconds) *
                            1000 *
                            2 ** backoff.current,
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
    }, [conversation.id, props.pollSeconds]);

    return (
        <>
            <Head title={`Messages · ${conversation.taskTitle}`} />
            <main className="mx-auto max-w-4xl space-y-4 p-4 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Link href="/messages" className="text-sm underline">
                            Back to messages
                        </Link>
                        <h1 className="mt-2 text-2xl font-semibold">
                            {conversation.otherPerson.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {conversation.otherPerson.type} · Task:{' '}
                            {conversation.taskTitle}
                        </p>
                    </div>
                    <VerificationStatusBadge
                        status={conversation.otherPerson.verificationStatus}
                    />
                </div>
                {props.status && (
                    <div
                        role="status"
                        className="rounded-lg border px-4 py-3 text-sm"
                    >
                        {props.status}
                    </div>
                )}
                <p className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                    {PRIVATE_NOTICE}
                </p>
                {conversation.sendBlockedReason && (
                    <p
                        role="status"
                        className="rounded-lg border bg-muted p-3 text-sm"
                    >
                        {conversation.sendBlockedReason}
                    </p>
                )}
                <div
                    ref={listRef}
                    aria-label="Conversation messages"
                    className="max-h-[60vh] min-h-56 space-y-4 overflow-y-auto rounded-xl border bg-card p-4"
                >
                    {timeline.length ? (
                        timeline.map((item) =>
                            item.kind === 'event' ? (
                                <p
                                    key={`event-${item.id}`}
                                    className="text-center text-xs text-muted-foreground"
                                >
                                    {item.event.event === 'closed'
                                        ? `${item.event.actor} closed this conversation`
                                        : `${item.event.actor} reopened this conversation`}{' '}
                                    ·{' '}
                                    {item.event.createdAt
                                        ? formatDateTime(item.event.createdAt)
                                        : '—'}
                                </p>
                            ) : (
                                <article
                                    key={`message-${item.id}`}
                                    className={`max-w-[90%] rounded-lg border p-3 ${item.message.isMine ? 'ml-auto bg-muted' : ''}`}
                                >
                                    <p className="text-xs font-medium">
                                        {item.message.senderName} ·{' '}
                                        {item.message.createdAt
                                            ? formatDateTime(
                                                  item.message.createdAt,
                                              )
                                            : '—'}
                                    </p>
                                    <p className="mt-2 text-sm break-words whitespace-pre-wrap">
                                        {item.message.body}
                                    </p>
                                    {item.message.flaggedForRecipient &&
                                        !item.message.isMine && (
                                            <div className="mt-3 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950">
                                                {FLAG_NOTICE}
                                            </div>
                                        )}
                                    {!item.message.isMine &&
                                        item.message.reportUrl && (
                                            <div className="mt-3">
                                                <ProblemReportForm
                                                    action={
                                                        item.message.reportUrl
                                                    }
                                                    types={props.reportTypes}
                                                    reportLabel="Report this message"
                                                    contextMessages={
                                                        props.reportContextMessages
                                                    }
                                                />
                                            </div>
                                        )}
                                </article>
                            ),
                        )
                    ) : (
                        <p className="py-10 text-center text-sm text-muted-foreground">
                            No messages yet. Send a message to start the
                            conversation.
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    {conversation.canReopen && (
                        <Form
                            action={`/messages/${conversation.id}/reopen`}
                            method="post"
                        >
                            <Button type="submit" variant="outline">
                                Reopen conversation
                            </Button>
                        </Form>
                    )}
                    {conversation.closedBy && (
                        <p className="self-center text-sm text-muted-foreground">
                            Closed by {conversation.closedBy}
                            {conversation.closedAt
                                ? ` on ${formatDateTime(conversation.closedAt)}`
                                : ''}
                            .
                        </p>
                    )}
                </div>
                {conversation.canSend && (
                    <Form
                        action={`/messages/${conversation.id}/send`}
                        method="post"
                        resetOnSuccess={['body']}
                        className="space-y-2 rounded-xl border bg-card p-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <label
                                    htmlFor="body"
                                    className="block text-sm font-medium"
                                >
                                    Message
                                </label>
                                <Textarea
                                    id="body"
                                    name="body"
                                    required
                                    readOnly={processing}
                                    maxLength={props.messageMaxLength}
                                    rows={3}
                                    placeholder="Write a message about this task"
                                />
                                <InputError message={errors.body} />
                                <p className="text-xs text-muted-foreground">
                                    Links appear as plain text.
                                </p>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Sending…' : 'Send message'}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
                {conversation.canClose && (
                    <details className="rounded-lg border px-4 py-3 text-sm">
                        <summary className="cursor-pointer font-medium">
                            Conversation options
                        </summary>
                        <Form
                            action={`/messages/${conversation.id}/close`}
                            method="post"
                            className="mt-3"
                            onBefore={() =>
                                window.confirm(
                                    'Close this conversation for both participants?',
                                )
                            }
                        >
                            <Button type="submit" variant="outline">
                                Close conversation
                            </Button>
                        </Form>
                    </details>
                )}
            </main>
        </>
    );
}
ConversationShow.layout = (page: React.ReactNode) => (
    <AppLayout>{page}</AppLayout>
);
