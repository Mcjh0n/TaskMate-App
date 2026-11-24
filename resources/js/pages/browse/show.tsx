import { formatDate } from '@/lib/format';
import {
    PAYMENT_BOUNDARY_NOTICE,
    STUDENT_TASK_SAFETY_NOTICE,
} from '@/constants/notices';
import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import { ApplicationStatusBadge } from '@/components/application-status-badge';
import browse from '@/routes/browse';
import applications from '@/routes/applications';
import { RatingSummary } from '@/components/rating-summary';
import { ProblemReportForm } from '@/components/problem-report-form';
import { Clock3, MapPin, Monitor, ArrowLeft, UserRound } from 'lucide-react';

type Task = {
    id: number;
    title: string;
    category: string;
    description: string;
    requiredActivities: string | null;
    deliverables: string | null;
    estimatedDurationDays: number | null;
    estimatedDurationLabel: string | null;
    skillRequirements: string | null;
    budget: string | null;
    budgetMin: string | null;
    budgetMax: string | null;
    budgetDisplay: string;
    workMode: string;
    workplaceName: string | null;
    workplaceAddress: string | null;
};
type Poster = {
    displayName: string;
    accountType: string;
    affiliation: string | null;
    verificationStatus:
        | 'pending'
        | 'verified'
        | 'rejected'
        | 'more_info_requested';
    memberSince: string | null;
    profileUrl: string;
    ratingSummary: import('@/components/rating-summary').RatingSummaryData;
};
type Application = {
    status: string;
    statusLabel: string;
    appliedAt: string | null;
};
type Props = {
    task: Task;
    poster: Poster;
    application: Application | null;
    conversation?: { id: number; sendBlockedReason: string | null } | null;
    canApply?: boolean;
    applicantVerificationStatus?: string;
    limitedView?: boolean;
    currentTaskStatus?: string;
    status?: string | null;
    messageMaxLength: number;
    problemReport?: {
        action: string;
        types: { value: string; label: string }[];
    };
    offPlatformWarning?: boolean;
};

export default function BrowseTaskDetail({
    task,
    poster,
    application,
    conversation,
    canApply = false,
    applicantVerificationStatus,
    limitedView = false,
    currentTaskStatus,
    status,
    messageMaxLength,
    problemReport,
    offPlatformWarning,
}: Props) {
    const applicationPanel = application ? (
        <section className="space-y-3 rounded-2xl border bg-card p-5 shadow-sm">
            <div>
                <p className="text-xs font-semibold tracking-wide text-primary uppercase">
                    Your application
                </p>
                <h2 className="mt-1 text-lg font-semibold">
                    Application status
                </h2>
            </div>
            <ApplicationStatusBadge
                status={application.status}
                label={application.statusLabel}
            />
            {application.status === 'accepted' && (
                <p className="text-sm text-muted-foreground">
                    You were accepted. The next step is to confirm the agreement
                    with the poster.
                </p>
            )}
            {conversation && (
                <Button className="w-full" variant="outline" asChild>
                    <Link href={`/messages/${conversation.id}`}>
                        {conversation.sendBlockedReason
                            ? 'View conversation'
                            : 'Message poster'}
                    </Link>
                </Button>
            )}
            {conversation?.sendBlockedReason && (
                <p className="text-xs text-muted-foreground">
                    {conversation.sendBlockedReason}
                </p>
            )}
        </section>
    ) : canApply ? (
        <section
            id="application-form"
            className="space-y-4 rounded-2xl border bg-card p-5 shadow-sm"
        >
            <div>
                <p className="text-xs font-semibold tracking-wide text-primary uppercase">
                    Interested?
                </p>
                <h2 className="mt-1 text-lg font-semibold">
                    Apply to this task
                </h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    Introduce yourself and explain how you can help. You can
                    apply only once.
                </p>
            </div>
            <Form {...applications.store.form(task.id)} className="space-y-4">
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="message">
                                Message to the poster
                            </Label>
                            <Textarea
                                id="message"
                                name="message"
                                maxLength={messageMaxLength}
                                required
                                placeholder="Briefly introduce yourself and explain how you can help."
                            />
                            <p className="text-xs text-muted-foreground">
                                Up to {messageMaxLength} characters.
                            </p>
                            <InputError message={errors.message} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="proposed_amount">
                                Suggested amount (optional)
                            </Label>
                            <Input
                                id="proposed_amount"
                                name="proposed_amount"
                                type="number"
                                min="1"
                                max="1000000"
                                step="0.01"
                            />
                            <p className="text-xs text-muted-foreground">
                                This is only a suggestion. You and the poster
                                agree on the final amount later.
                            </p>
                            <InputError message={errors.proposed_amount} />
                        </div>
                        <div>
                            <div className="flex items-start gap-2">
                                <input
                                    id="work_details_confirmed"
                                    name="work_details_confirmed"
                                    type="checkbox"
                                    value="1"
                                    required
                                    className="mt-1"
                                />
                                <Label htmlFor="work_details_confirmed">
                                    I have read the work details and conditions.
                                </Label>
                            </div>
                            <InputError
                                message={errors.work_details_confirmed}
                            />
                        </div>
                        <Button
                            className="w-full"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? 'Sending application…' : 'Apply now'}
                        </Button>
                    </>
                )}
            </Form>
        </section>
    ) : applicantVerificationStatus &&
      applicantVerificationStatus !== 'verified' ? (
        <section
            role="status"
            className="space-y-2 rounded-2xl border border-brand-gold/60 bg-brand-gold/10 p-5"
        >
            <h2 className="font-semibold">You can browse this task</h2>
            <p className="text-sm text-muted-foreground">
                Your account must be verified by an administrator before you can
                apply.
            </p>
        </section>
    ) : null;

    return (
        <>
            <Head title={task.title} />
            <div className="mx-auto w-full max-w-7xl space-y-5 p-4 sm:p-6 lg:p-8">
                <Link
                    href={browse.index()}
                    className="inline-flex min-h-10 items-center gap-2 rounded-md px-2 text-sm font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Browse tasks
                </Link>
                {status && (
                    <div
                        role="status"
                        className="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm"
                    >
                        {status}
                    </div>
                )}
                {limitedView && (
                    <div className="rounded-xl border border-brand-gold/60 bg-brand-gold/10 p-4 text-sm">
                        This task is currently{' '}
                        {currentTaskStatus?.toLowerCase()}. You can review the
                        task details and your application status.
                    </div>
                )}
                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_21rem]">
                    <main className="min-w-0 space-y-5">
                        <section className="rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div className="min-w-0">
                                    <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary uppercase">
                                        {task.category}
                                    </span>
                                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                                        {task.title}
                                    </h1>
                                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                                        Review the work, location, duration, and
                                        budget before you decide to apply.
                                    </p>
                                </div>
                                {canApply && !application && (
                                    <a
                                        href="#application-form"
                                        className="inline-flex min-h-10 items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    >
                                        Apply to this task
                                    </a>
                                )}
                            </div>
                            <div className="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 border-t pt-4 text-sm">
                                <UserRound
                                    className="size-4 text-primary"
                                    aria-hidden="true"
                                />
                                <Link
                                    href={poster.profileUrl}
                                    className="font-semibold underline-offset-4 hover:underline"
                                >
                                    {poster.displayName}
                                </Link>
                                <span className="text-muted-foreground">
                                    {poster.accountType}
                                    {poster.affiliation
                                        ? ` · ${poster.affiliation}`
                                        : ''}
                                </span>
                                <VerificationStatusBadge
                                    status={poster.verificationStatus}
                                />
                            </div>
                        </section>

                        <section
                            aria-label="Work details at a glance"
                            className="grid gap-3 sm:grid-cols-2"
                        >
                            <div className="flex items-start gap-3 rounded-2xl border bg-card p-4 shadow-sm">
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                                    <Monitor
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Work mode
                                    </p>
                                    <p className="mt-1 font-semibold">
                                        {task.workMode}
                                    </p>
                                </div>
                            </div>
                            {task.estimatedDurationLabel && (
                                <div className="flex items-start gap-3 rounded-2xl border bg-card p-4 shadow-sm">
                                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-gold/20 text-foreground">
                                        <Clock3
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Estimated duration
                                        </p>
                                        <p className="mt-1 font-semibold">
                                            {task.estimatedDurationLabel}
                                        </p>
                                    </div>
                                </div>
                            )}
                            {(task.workplaceName || task.workplaceAddress) && (
                                <div className="flex items-start gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:col-span-2">
                                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                                        <MapPin
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Workplace location · {task.workMode}
                                        </p>
                                        {task.workplaceName && (
                                            <p className="mt-1 font-semibold">
                                                {task.workplaceName}
                                            </p>
                                        )}
                                        {task.workplaceAddress && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {task.workplaceAddress}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="space-y-5 rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-primary uppercase">
                                    Task overview
                                </p>
                                <h2 className="mt-1 text-xl font-semibold">
                                    About the work
                                </h2>
                            </div>
                            <p className="leading-relaxed whitespace-pre-wrap">
                                {task.description}
                            </p>
                            {task.requiredActivities && (
                                <div className="border-t pt-4">
                                    <h3 className="font-semibold">
                                        Main activities
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {task.requiredActivities}
                                    </p>
                                </div>
                            )}
                            {task.deliverables && (
                                <div className="border-t pt-4">
                                    <h3 className="font-semibold">
                                        Expected result
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {task.deliverables}
                                    </p>
                                </div>
                            )}
                            {task.skillRequirements && (
                                <div className="border-t pt-4">
                                    <h3 className="font-semibold">Skills</h3>
                                    <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {task.skillRequirements}
                                    </p>
                                </div>
                            )}
                        </section>

                        <div className="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                            {STUDENT_TASK_SAFETY_NOTICE}
                        </div>
                        {offPlatformWarning && (
                            <div className="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                                This message contains a request to move outside
                                TaskMate or skip its steps. Be careful, keep
                                agreements and communications on record, and
                                report anything that makes you uncomfortable.
                            </div>
                        )}
                        {problemReport && (
                            <ProblemReportForm
                                action={problemReport.action}
                                types={problemReport.types}
                            />
                        )}
                    </main>

                    <aside className="space-y-4 lg:sticky lg:top-6">
                        <section className="rounded-2xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Estimated budget
                            </p>
                            <p className="mt-1 text-3xl font-bold tracking-tight">
                                {task.budgetDisplay}
                            </p>
                            <p className="mt-3 border-t border-primary/15 pt-3 text-xs leading-relaxed text-muted-foreground">
                                {PAYMENT_BOUNDARY_NOTICE}
                            </p>
                        </section>

                        {applicationPanel}

                        <section className="space-y-3 rounded-2xl border bg-card p-5 shadow-sm">
                            <h2 className="font-semibold">About the poster</h2>
                            <p className="text-sm text-muted-foreground">
                                Member since{' '}
                                {poster.memberSince
                                    ? formatDate(poster.memberSince)
                                    : '—'}
                            </p>
                            <RatingSummary summary={poster.ratingSummary} />
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}
