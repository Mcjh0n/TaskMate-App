import { formatDate, formatAmount } from '@/lib/format';
import { PAYMENT_BOUNDARY_NOTICE, STUDENT_TASK_SAFETY_NOTICE } from '@/constants/notices';
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

type Task = {
    id: number; title: string; category: string; description: string; requiredActivities: string; deliverables: string;
    estimatedDurationHours: string; skillRequirements: string | null; complexity: string; budget: string; workMode: string;
    workplaceName: string | null; workplaceAddress: string | null; workplaceSetting: string | null; deadline: string | null;
    physicalDemand: string | null; academicInvolvement: string | null; hazardExposure: boolean; hazardDescription: string | null;
    privateMeetingRequired: boolean;
};
type Poster = { displayName: string; accountType: string; affiliation: string | null; verificationStatus: 'pending' | 'verified' | 'rejected' | 'more_info_requested'; memberSince: string | null; profileUrl: string; ratingSummary: import('@/components/rating-summary').RatingSummaryData };
type Application = { status: string; statusLabel: string; appliedAt: string | null };
type Props = { task: Task; poster: Poster; application: Application | null; conversation?: { id: number; sendBlockedReason: string | null } | null; canApply?: boolean; limitedView?: boolean; currentTaskStatus?: string; status?: string | null; messageMaxLength: number; problemReport?: { action: string; types: { value: string; label: string }[] }; offPlatformWarning?: boolean };

export default function BrowseTaskDetail({ task, poster, application, conversation, canApply = false, limitedView = false, currentTaskStatus, status, messageMaxLength, problemReport, offPlatformWarning }: Props) {
    return (
        <>
            <Head title={task.title} />
            <div className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6">
                {status && <div role="status" className="rounded-lg border px-4 py-3 text-sm">{status}</div>}
                {limitedView && <div className="rounded-lg border p-4 text-sm">This task is currently {currentTaskStatus?.toLowerCase()}. You can review the task details and your application status.</div>}
                <div><p className="text-sm text-muted-foreground">{task.category}</p><h1 className="mt-1 text-2xl font-semibold">{task.title}</h1></div>
                <section aria-label="Task summary" className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-xl border bg-muted/30 p-5 sm:col-span-2">
                        <p className="text-sm text-muted-foreground">Estimated budget</p>
                        <p className="mt-1 text-2xl font-semibold">₱{formatAmount(task.budget)}</p>
                        <p className="mt-3 text-sm text-muted-foreground">{PAYMENT_BOUNDARY_NOTICE}</p>
                    </div>
                    <div className="rounded-xl border p-4"><p className="text-sm text-muted-foreground">Work mode</p><p className="mt-1 font-medium">{task.workMode}</p></div>
                    <div className="rounded-xl border p-4"><p className="text-sm text-muted-foreground">Deadline</p><p className="mt-1 font-medium">{task.deadline ? formatDate(task.deadline) : 'No deadline listed'}</p></div>
                    {(task.workplaceName || task.workplaceAddress) && <div className="rounded-xl border p-4 sm:col-span-2"><p className="text-sm text-muted-foreground">Workplace</p>{task.workplaceName && <p className="mt-1 font-medium">{task.workplaceName}</p>}{task.workplaceAddress && <p className="mt-1 text-sm">{task.workplaceAddress}</p>}</div>}
                </section>
                <section className="space-y-4 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">About the work</h2>
                    <p className="whitespace-pre-wrap">{task.description}</p>
                    <div><h3 className="font-medium">Required activities</h3><p className="mt-1 whitespace-pre-wrap text-sm">{task.requiredActivities}</p></div>
                    <div><h3 className="font-medium">Deliverables</h3><p className="mt-1 whitespace-pre-wrap text-sm">{task.deliverables}</p></div>
                    {task.skillRequirements && <p><strong>Skills:</strong> {task.skillRequirements}</p>}
                    <p><strong>Estimated duration:</strong> {task.estimatedDurationHours} hours</p>
                    <p><strong>Complexity:</strong> {task.complexity}</p>
                </section>
                <section className="space-y-2 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">Work conditions</h2>
                    {task.workplaceSetting && <p><strong>Workplace setting:</strong> {task.workplaceSetting.replaceAll('_', ' ')}</p>}
                    <p><strong>Physical demand:</strong> {task.physicalDemand}</p>
                    <p><strong>Academic involvement:</strong> {task.academicInvolvement}</p>
                    <p><strong>Hazards involved:</strong> {task.hazardExposure ? `Yes — ${task.hazardDescription}` : 'No'}</p>
                    <p><strong>Private or isolated one-on-one meeting required:</strong> {task.privateMeetingRequired ? 'Yes' : 'No'}</p>
                </section>
                <section className="space-y-2 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">About the poster</h2>
                    <p className="font-medium"><Link href={poster.profileUrl} className="underline">{poster.displayName}</Link></p>
                    <p className="text-sm">{poster.accountType}{poster.affiliation ? ` · ${poster.affiliation}` : ''}</p>
                    <VerificationStatusBadge status={poster.verificationStatus} />
                    <p className="text-sm text-muted-foreground">Member since {poster.memberSince ? formatDate(poster.memberSince) : '—'}</p>
                    <RatingSummary summary={poster.ratingSummary} />
                </section>
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">{STUDENT_TASK_SAFETY_NOTICE}</div>
                {problemReport && <ProblemReportForm action={problemReport.action} types={problemReport.types} />}
                {offPlatformWarning && <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">This message contains a request to move outside TaskMate or skip its steps. Be careful, keep agreements and communications on record, and report anything that makes you uncomfortable.</div>}
                {application ? (
                    <section className="space-y-2 rounded-xl border p-5">
                        <h2 className="font-semibold">Your application</h2>
                        <ApplicationStatusBadge status={application.status} label={application.statusLabel} />
                        {application.status === 'accepted' && <p className="text-sm">You were accepted. The next step is to confirm the agreement with the poster.</p>}
                        {conversation && <Button variant="outline" asChild><Link href={`/messages/${conversation.id}`}>{conversation.sendBlockedReason ? 'View conversation' : 'Message poster'}</Link></Button>}
                        {conversation?.sendBlockedReason && <p className="text-sm text-muted-foreground">{conversation.sendBlockedReason}</p>}
                    </section>
                ) : canApply && (
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Apply to this task</h2>
                        <p className="text-sm text-muted-foreground">You can apply once to this task. Review the work details before sending your message.</p>
                        <Form {...applications.store.form(task.id)} className="space-y-4">
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2"><Label htmlFor="message">Message to the poster</Label><Textarea id="message" name="message" maxLength={messageMaxLength} required /><p className="text-xs text-muted-foreground">Maximum {messageMaxLength} characters.</p><InputError message={errors.message} /></div>
                                    <div className="grid gap-2"><Label htmlFor="proposed_amount">Suggested amount (optional)</Label><Input id="proposed_amount" name="proposed_amount" type="number" min="1" max="1000000" step="0.01" /><p className="text-xs text-muted-foreground">This is only a suggestion. The final amount is agreed later between you and the poster.</p><InputError message={errors.proposed_amount} /></div>
                                    <div className="flex items-start gap-2"><input id="work_details_confirmed" name="work_details_confirmed" type="checkbox" value="1" required className="mt-1" /><Label htmlFor="work_details_confirmed">I have read the work details and conditions.</Label></div>
                                    <InputError message={errors.work_details_confirmed} />
                                    <Button type="submit" disabled={processing}>Submit application</Button>
                                </>
                            )}
                        </Form>
                    </section>
                )}
                <Link href={browse.index()} className="text-sm underline underline-offset-4">Back to Browse Tasks</Link>
            </div>
        </>
    );
}
