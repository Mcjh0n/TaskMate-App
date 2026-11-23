import { formatDate, formatDateTime } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { SafetyNotice } from '@/components/safety-notice';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import applications from '@/routes/applications';
import browse from '@/routes/browse';
import { create as createTask, mine as myTasks } from '@/routes/tasks';
import verificationDetails from '@/routes/verification/details';

type Props = {
    status?: string | null;
    accountIdentity: { email: string; idNumber: string | null };
    studentsCanPost?: boolean;
    verificationNote?: string | null;
    verifiedAt?: string | null;
    ratingsWaiting?: { id: number; taskTitle: string; daysLeft: number }[];
    warningSanction?: { id: number; reason: string } | null;
    activeSuspension?: { id: number; reason: string; endsAt: string | null } | null;
    sanctionContact?: string;
    recentAlerts?: { id: string; title: string; body: string; url: string; readAt: string | null; createdAt: string | null }[];
};

export default function Dashboard({ status, accountIdentity, studentsCanPost = true, verificationNote, verifiedAt, ratingsWaiting = [], warningSanction, activeSuspension, sanctionContact, recentAlerts = [] }: Props) {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-5 p-4 md:p-6">
                {status && <div role="status" className="rounded-lg border px-4 py-3 text-sm">{status}</div>}

                <section className="rounded-xl border p-5 md:p-6" aria-labelledby="next-step-title">
                    <div className="max-w-3xl">
                        <p className="text-sm font-medium text-muted-foreground">TaskMate</p>
                        <h1 id="next-step-title" className="mt-1 text-2xl font-semibold">What would you like to do next?</h1>

                        {user.is_admin ? (
                            <>
                                <p className="mt-2 text-sm text-muted-foreground">Review accounts, tasks, and reports that need administrator attention.</p>
                                <Link className="mt-4 inline-flex min-h-10 items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground underline-offset-4 hover:underline" href={admin.dashboard().url}>Open admin dashboard</Link>
                            </>
                        ) : activeSuspension ? (
                            <>
                                <p className="mt-2 text-sm text-muted-foreground">Posting, applying, and browsing are paused while your account is suspended. You can still review your account status and records.</p>
                                <Link className="mt-4 inline-flex min-h-10 items-center rounded-md border px-4 py-2 text-sm font-medium underline-offset-4 hover:underline" href="/my-sanctions">View account actions</Link>
                            </>
                        ) : user.verification_status !== 'verified' ? (
                            <>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {user.verification_status === 'pending'
                                        ? user.user_type === 'student'
                                            ? `Your account is awaiting administrator review. You can browse approved tasks now; verification is required before applying${studentsCanPost ? '. You can also post tasks after approval' : ''}.`
                                            : user.user_type === 'faculty_staff'
                                                ? 'Your account is awaiting administrator review. After approval, you can post tasks. Faculty and staff accounts cannot apply to tasks.'
                                                : 'Your organization details are awaiting administrator review. After approval, you can post tasks. Organization accounts cannot browse or apply to tasks.'
                                        : 'Update the submitted details requested by the administrator. Your account will return to pending review, and task actions remain unavailable until it is approved.'}
                                </p>
                                {user.user_type === 'student' && user.can_browse_tasks && <Link className="mt-4 inline-flex min-h-10 items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" href={browse.index().url}>Browse tasks</Link>}
                                {['rejected', 'more_info_requested'].includes(user.verification_status) && (
                                    <Link className="mt-4 inline-flex min-h-10 items-center rounded-md border px-4 py-2 text-sm font-medium underline-offset-4 hover:underline" href={verificationDetails.edit()}>Update submitted details</Link>
                                )}
                            </>
                        ) : (
                            <>
                                <p className="mt-2 text-sm text-muted-foreground">Find a suitable task, post work, or check the applications and tasks you already have.</p>
                                <div className="mt-4 flex flex-wrap gap-3">
                                    {user.can_browse_tasks && <Link className="inline-flex min-h-10 items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground underline-offset-4 hover:underline" href={browse.index().url}>Browse tasks</Link>}
                                    {user.can_post_tasks && <Link className="inline-flex min-h-10 items-center rounded-md border px-4 py-2 text-sm font-medium underline-offset-4 hover:underline" href={createTask().url}>Post a task</Link>}
                                    {user.can_apply_tasks && <Link className="inline-flex min-h-10 items-center rounded-md border px-4 py-2 text-sm font-medium underline-offset-4 hover:underline" href={applications.mine().url}>My applications</Link>}
                                    {user.can_post_tasks && <Link className="inline-flex min-h-10 items-center rounded-md border px-4 py-2 text-sm font-medium underline-offset-4 hover:underline" href={myTasks().url}>My tasks</Link>}
                                </div>
                            </>
                        )}
                    </div>
                </section>

                {warningSanction && <div className="rounded-lg border border-amber-400 bg-amber-50 p-4 text-sm text-amber-950"><p><strong>Account warning:</strong> {warningSanction.reason}</p><button className="mt-2 underline" type="button" onClick={() => router.post(`/my-sanctions/${warningSanction.id}/acknowledge`)}>Acknowledge warning</button></div>}
                {activeSuspension && <div role="status" className="rounded-lg border border-red-300 p-4 text-sm"><p>Your account is suspended until {activeSuspension.endsAt ? formatDateTime(activeSuspension.endsAt) : 'further notice'}.</p><p className="mt-1">{activeSuspension.reason}</p><p className="mt-1">{sanctionContact}</p></div>}

                <section className="flex flex-wrap items-center gap-3 rounded-xl border p-4" aria-label="Account verification">
                    <VerificationStatusBadge status={user.verification_status} />
                    {user.verification_status === 'verified' ? (
                        <p role="status" className="text-sm text-green-900 dark:text-green-200">Your account is verified{verifiedAt ? ` as of ${formatDate(verifiedAt)}` : ''}.</p>
                    ) : user.verification_status === 'pending' ? (
                        <p role="status" className="text-sm">Your account is awaiting administrator review. Students can browse approved tasks now, but verification is required to apply. Posting remains unavailable until approval.</p>
                    ) : (
                        <div role="status" className="text-sm">
                            <p>{user.verification_status === 'more_info_requested' ? 'The administrator needs more information before completing the review. Update the details below to return your account to review.' : 'Your account verification was rejected. Review the note and update your submitted details if you want another review.'}</p>
                            {verificationNote && <p className="mt-1">{verificationNote}</p>}
                        </div>
                    )}
                </section>

                <section className="space-y-2 rounded-xl border p-5" aria-label="Your account details"><h2 className="text-lg font-semibold">Your account details</h2><p><strong>Email:</strong> {accountIdentity.email}</p>{accountIdentity.idNumber && <p><strong>ID number:</strong> {accountIdentity.idNumber}</p>}</section>

                <SafetyNotice />

                {ratingsWaiting.length > 0 && <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Ratings waiting for you</h2>{ratingsWaiting.map((rating) => <div key={rating.id} className="flex flex-wrap items-center justify-between gap-3 border-t pt-3"><div><p className="font-medium">{rating.taskTitle}</p><p className="text-sm text-muted-foreground">{rating.daysLeft} day{rating.daysLeft === 1 ? '' : 's'} left</p></div><Link className="text-sm underline" href={`/agreements/${rating.id}/rate`}>Rate your experience</Link></div>)}</section>}

                <section className="space-y-3 rounded-xl border p-5" aria-label="Recent activity">
                    <div className="flex items-center justify-between"><h2 className="text-lg font-semibold">Recent activity</h2><Link className="text-sm underline" href="/notifications">View all alerts</Link></div>
                    {recentAlerts.length ? recentAlerts.map((alert) => <Link key={alert.id} href={alert.url} className="block border-t pt-3"><p className="font-medium">{alert.title}</p><p className="text-sm text-muted-foreground">{alert.body}</p></Link>) : <p className="rounded border p-3 text-sm text-muted-foreground">No recent activity yet.</p>}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/dashboard',
        },
    ],
});
