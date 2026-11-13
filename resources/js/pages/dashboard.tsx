import { Head, Link, usePage } from '@inertiajs/react';
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
};

export default function Dashboard({ pendingInvitations = [], status, verificationNote, verifiedAt }: Props) {
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
                <div className="flex flex-wrap items-center gap-3">
                    <VerificationStatusBadge status={auth.user.verification_status} />
                    {auth.user.verification_status === 'pending' && (
                        <div role="status" className="rounded-lg border px-4 py-3 text-sm">
                            Your account is awaiting review. Some actions are unavailable until then.
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
