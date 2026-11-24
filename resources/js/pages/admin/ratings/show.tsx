import { formatAmount } from '@/lib/format';
import { Form, Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { ratingNotice } from '@/components/rating-summary';

type Rating = {
    id: number;
    overall: number;
    comment: string | null;
    direction: string;
    scores: { dimension: string; label: string; score: number }[];
    taskTitle: string;
    agreementId: number;
    recordReference: string | null;
    agreedAmount: string | null;
    paymentArrangement: string | null;
    rater: string;
    ratee: string;
    hidden: boolean;
    hiddenReason: string | null;
    visibleToOthers: boolean;
};
function Page({ rating, notice }: { rating: Rating; notice: string }) {
    return (
        <>
            <Head title="Review rating" />
            <div className="space-y-5">
                <Link className="text-sm underline" href="/admin/ratings">
                    Back to ratings
                </Link>
                <h1 className="text-2xl font-semibold">
                    Rating for {rating.taskTitle}
                </h1>
                <p className="rounded-lg border p-4 text-sm">{ratingNotice}</p>
                <p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    {notice}
                </p>
                <section className="space-y-2 rounded-xl border bg-card p-5">
                    <p>
                        {rating.rater} rated {rating.ratee}
                    </p>
                    <p>
                        Direction: {rating.direction.replaceAll('_', ' ')} ·
                        Overall: {rating.overall}/5
                    </p>
                    {rating.scores.map((score) => (
                        <p key={score.dimension}>
                            {score.label}: {score.score}/5
                        </p>
                    ))}
                    {rating.comment && (
                        <p className="whitespace-pre-wrap">{rating.comment}</p>
                    )}
                    <p>
                        Status:{' '}
                        {rating.hidden
                            ? `Hidden — ${rating.hiddenReason ?? ''}`
                            : rating.visibleToOthers
                              ? 'Visible to others'
                              : 'Waiting for both ratings or window close'}
                    </p>
                    <p>
                        Agreed amount:{' '}
                        {rating.agreedAmount
                            ? `₱${formatAmount(rating.agreedAmount)}`
                            : '—'}
                    </p>
                    <p className="whitespace-pre-wrap">
                        Payment arrangement: {rating.paymentArrangement ?? '—'}
                    </p>
                    <p>
                        Agreement record reference:{' '}
                        <span className="font-mono break-all">
                            {rating.recordReference ?? 'Unavailable'}
                        </span>
                    </p>
                    <p>
                        <Link
                            className="underline"
                            href={`/admin/agreements/${rating.agreementId}`}
                        >
                            View agreement
                        </Link>
                    </p>
                </section>
                {rating.hidden ? (
                    <Form
                        action={`/admin/ratings/${rating.id}/moderate`}
                        method="post"
                    >
                        <input type="hidden" name="action" value="restore" />
                        <button className="rounded border px-4 py-2">
                            Restore rating
                        </button>
                    </Form>
                ) : (
                    <Form
                        action={`/admin/ratings/${rating.id}/moderate`}
                        method="post"
                        className="space-y-3 rounded-xl border bg-card p-5"
                    >
                        <input type="hidden" name="action" value="hide" />
                        <label
                            className="block text-sm font-medium"
                            htmlFor="reason"
                        >
                            Reason for hiding (required)
                        </label>
                        <textarea
                            id="reason"
                            name="reason"
                            required
                            maxLength={2000}
                            className="min-h-24 w-full rounded border bg-background p-3"
                        />
                        <button className="rounded bg-destructive px-4 py-2 text-destructive-foreground">
                            Hide rating
                        </button>
                    </Form>
                )}
            </div>
        </>
    );
}
Page.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
export default Page;
