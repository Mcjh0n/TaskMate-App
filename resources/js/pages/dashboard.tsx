import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { SafetyNotice } from '@/components/safety-notice';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import verificationDetails from '@/routes/verification/details';
import type { DashboardInvitation } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    status?: string | null;
    verificationNote?: string | null;
    verifiedAt?: string | null;
    ratingsWaiting?: { id: number; taskTitle: string; daysLeft: number }[];
    warningSanction?: { id: number; reason: string } | null;
    activeSuspension?: { id: number; reason: string; endsAt: string | null } | null;
    sanctionContact?: string;
};

export default function Dashboard({ pendingInvitations = [], status, verificationNote, verifiedAt, ratingsWaiting = [], warningSanction, activeSuspension, sanctionContact }: Props) {
    const { auth } = usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {status && (
                    <div role="status" className="rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                {warningSanction && <div className="w-full rounded-lg border border-amber-400 bg-amber-50 p-4 text-sm text-amber-950"><p><strong>Account warning:</strong> {warningSanction.reason}</p><button className="mt-2 underline" type="button" onClick={() => router.post(`/my-sanctions/${warningSanction.id}/acknowledge`)}>Acknowledge warning</button></div>}
                {activeSuspension && <div role="status" className="w-full rounded-lg border border-red-300 p-4 text-sm"><p>Your account is suspended until {activeSuspension.endsAt ? new Date(activeSuspension.endsAt).toLocaleString() : 'further notice'}.</p><p className="mt-1">{activeSuspension.reason}</p><p className="mt-1">{sanctionContact}</p><Link className="mt-2 inline-block underline" href="/my-sanctions">View account actions</Link></div>}
                <div className="flex flex-wrap items-center gap-3">
                    <VerificationStatusBadge status={auth.user.verification_status} />
                    {auth.user.verification_status === 'pending' && (
                        <div role="status" className="rounded-lg border px-4 py-3 text-sm">
                            Your account is awaiting review. Posting and applying are unavailable until your account is verified.
                        </div>
                    )}
                    {auth.user.user_type === 'student' && auth.user.verification_status !== 'verified' && (
                        <div role="status" className="w-full rounded-lg border px-4 py-3 text-sm">
                            Task browsing and applications are available after your account is verified.
                        </div>
                    )}
                    {auth.user.verification_status === 'more_info_requested' && (
                        <div role="status" className="w-full rounded-lg border border-blue-300 px-4 py-3 text-sm dark:border-blue-800">
                            <p>The administrator needs more information before completing the review.</p>
                            {verificationNote && <p className="mt-2">{verificationNote}</p>}
                            <Link className="mt-3 inline-block underline underline-offset-4" href={verificationDetails.edit()}>Update your submitted details</Link>
                        </div>
                    )}
                    {auth.user.verification_status === 'rejected' && (
                        <div role="status" className="w-full rounded-lg border border-red-300 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:text-red-200">
                            <p>Your account verification was rejected. Please review the note and update your submitted details.</p>
                            {verificationNote && <p className="mt-2">{verificationNote}</p>}
                            <Link className="mt-3 inline-block underline underline-offset-4" href={verificationDetails.edit()}>Update your submitted details</Link>
                        </div>
                    )}
                    {auth.user.verification_status === 'verified' && (
                        <div role="status" className="rounded-lg border border-green-300 px-4 py-3 text-sm text-green-900 dark:border-green-800 dark:text-green-200">
                            Your account is verified{verifiedAt ? ` as of ${new Date(verifiedAt).toLocaleDateString()}` : ''}.
                        </div>
                    )}
                </div>
                <SafetyNotice />
                {ratingsWaiting.length > 0 && <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Ratings waiting for you</h2>{ratingsWaiting.map((rating) => <div key={rating.id} className="flex flex-wrap items-center justify-between gap-3 border-t pt-3"><div><p className="font-medium">{rating.taskTitle}</p><p className="text-sm text-muted-foreground">{rating.daysLeft} day{rating.daysLeft === 1 ? '' : 's'} left</p></div><Link className="text-sm underline" href={`/agreements/${rating.id}/rate`}>Rate your experience</Link></div>)}</section>}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
