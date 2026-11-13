import { Form, Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { RiskLevelBadge } from '@/components/risk-level-badge';
import { TaskStatusBadge } from '@/components/task-status-badge';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import { SafetyNotice } from '@/components/safety-notice';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import admin from '@/routes/admin';
import adminUsers from '@/routes/admin/users';

type Hit = { phrase: string; type: string; field: string; start: number; length: number };
type Factor = { key: string; label: string; points: number; reason: string };
type Screening = { riskLevel: string; riskScore: number; factors: Factor[]; keywordHits: Hit[]; hardTriggers: { key: string; reason: string }[]; outcome: string; createdAt?: string | null };
type Task = {
    id: number; title: string; category: string; categoryKey: string; description: string; requiredActivities: string; deliverables: string;
    estimatedDurationHours: string; skillRequirements: string | null; complexity: string; budget: string; workMode: string;
    workplaceName: string | null; workplaceAddress: string | null; workplaceSetting: string | null; deadline: string | null;
    physicalDemand: string | null; academicInvolvement: string | null; hazardExposure: boolean; hazardDescription: string | null;
    privateMeetingRequired: boolean; status: string; statusLabel: string; reviewNote: string | null; submittedAt: string | null;
    poster: { id: number; name: string; type: string; verificationStatus: 'pending' | 'verified' | 'rejected' | 'more_info_requested'; otherTasksCount: number };
};
type Action = { id: number; action: string; adminName: string | null; note: string | null; createdAt: string | null };
type AdminApplication = { id: number; applicantName: string; status: string; appliedAt: string | null; userId: number };
type Props = { task: Task; screening: Screening | null; screeningHistory: Screening[]; adminActions: Action[]; applications: AdminApplication[]; agreement: { id: number; status: string; locked_at: string | null } | null; canReview: boolean; riskQuestions: Record<string, { value: string; label: string }[]>; status?: string | null };

function HighlightedText({ text, field, hits }: { text: string | null; field: string; hits: Hit[] }) {
    if (!text) return <span>—</span>;
    const sorted = hits.filter((hit) => hit.field === field).sort((a, b) => a.start - b.start);
    const parts: React.ReactNode[] = [];
    let cursor = 0;
    for (const [index, hit] of sorted.entries()) {
        if (hit.start < cursor || hit.start >= text.length) continue;
        parts.push(text.slice(cursor, hit.start));
        parts.push(<mark key={`${hit.phrase}-${index}`} title={`${hit.type} keyword indicator`}>{text.slice(hit.start, hit.start + hit.length)}</mark>);
        cursor = hit.start + hit.length;
    }
    parts.push(text.slice(cursor));
    return <span className="whitespace-pre-wrap">{parts}</span>;
}

function DecisionForm({ taskId, decision, label, noteRequired }: { taskId: number; decision: string; label: string; noteRequired: boolean }) {
    return <Form {...admin.tasks.decision.form(taskId)} className="space-y-2 rounded-lg border p-4">
        {({ errors, processing }) => <><input type="hidden" name="decision" value={decision} /><label className="block text-sm font-medium" htmlFor={`${decision}-note`}>Admin note {noteRequired ? '(required)' : '(optional)'}</label><Textarea id={`${decision}-note`} name="note" required={noteRequired} placeholder={decision === 'approve' ? 'Explain why this task is safe to approve.' : 'Explain the decision to the poster.'} /><InputError message={errors.note} /><Button type="submit" disabled={processing}>{label}</Button></>}
    </Form>;
}

export default function AdminTaskReview({ task, screening, screeningHistory, adminActions, applications, canReview, riskQuestions, status }: Props) {
    const hits = screening?.keywordHits ?? [];
    const hasProhibitedHit = hits.some((hit) => hit.type === 'prohibited');
    const requiresOverrideNote = screening?.riskLevel === 'high' || hasProhibitedHit;
    const labels = (key: string, value: string | null) => riskQuestions[key]?.find((option) => option.value === value)?.label ?? value ?? 'Not provided';

    return <><Head title={`Review: ${task.title}`} /><div className="space-y-6">{status && <div role="status" className="rounded-lg border px-4 py-3 text-sm">{status}</div>}<div className="flex flex-wrap items-start justify-between gap-3"><div><Link href={admin.tasks.index()} className="text-sm underline underline-offset-4">Back to task queue</Link><h1 className="mt-2 text-2xl font-semibold"><HighlightedText text={task.title} field="title" hits={hits} /></h1><p className="mt-1 text-sm text-muted-foreground">Submitted {task.submittedAt ? new Date(task.submittedAt).toLocaleString() : '—'}</p></div><div className="flex gap-2"><RiskLevelBadge level={screening?.riskLevel ?? null} /><TaskStatusBadge status={task.status} label={task.statusLabel} /></div></div><SafetyNotice /><p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Screening helps identify concerns but cannot guarantee that a task is safe.</p>
        <section className="grid gap-4 lg:grid-cols-3"><div className="space-y-4 rounded-xl border p-5 lg:col-span-2"><h2 className="text-lg font-semibold">Task details</h2><p><strong>Category:</strong> {task.category}</p><p><strong>Description:</strong> <HighlightedText text={task.description} field="description" hits={hits} /></p><p><strong>Required activities:</strong> <HighlightedText text={task.requiredActivities} field="required_activities" hits={hits} /></p><p><strong>Deliverables:</strong> <HighlightedText text={task.deliverables} field="deliverables" hits={hits} /></p><p><strong>Skills:</strong> <HighlightedText text={task.skillRequirements} field="skill_requirements" hits={hits} /></p><p><strong>Estimated duration:</strong> {task.estimatedDurationHours} hours</p><p><strong>Complexity:</strong> {task.complexity}</p><p><strong>Budget:</strong> ₱{Number(task.budget).toLocaleString()}</p><p className="text-sm text-muted-foreground">TaskMate records agreements and payment status only. Payments are made directly between users. TaskMate does not hold, process, or guarantee payment.</p>{task.reviewNote && <p className="rounded border p-3 text-sm">Current review note: {task.reviewNote}</p>}</div><aside className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Poster</h2><p>{task.poster.name}</p><p>{task.poster.type}</p><VerificationStatusBadge status={task.poster.verificationStatus} /><p className="text-sm">Other tasks: {task.poster.otherTasksCount}</p></aside></section>
        <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Workplace details</h2><p><strong>Work mode:</strong> {task.workMode}</p><p><strong>Workplace setting:</strong> {labels('workplace_setting', task.workplaceSetting)}</p><p><strong>Workplace:</strong> {task.workplaceName ?? '—'}</p><p><strong>Workplace address:</strong> {task.workplaceAddress ?? '—'}</p><p className="text-sm text-muted-foreground">This is a stated place of work. TaskMate does not track locations or collect coordinates.</p></section>
        <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Safety answers</h2><p><strong>Physical demand:</strong> {labels('physical_demand', task.physicalDemand)}</p><p><strong>Academic involvement:</strong> {labels('academic_involvement', task.academicInvolvement)}</p><p><strong>Hazard exposure:</strong> {task.hazardExposure ? 'Yes' : 'No'}{task.hazardExposure && task.hazardDescription && <span>: {task.hazardDescription}</span>}</p><p><strong>Private or isolated one-on-one meeting required:</strong> {task.privateMeetingRequired ? 'Yes' : 'No'}</p></section>
        <section className="space-y-4 rounded-xl border p-5"><div className="flex flex-wrap items-center justify-between gap-2"><h2 className="text-lg font-semibold">Screening breakdown</h2>{screening && <><RiskLevelBadge level={screening.riskLevel} /><span>Score: {screening.riskScore}</span></>}</div>{screening ? <><p className="text-sm">Screening outcome: {screening.outcome.replaceAll('_', ' ')}</p><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b"><th className="py-2">Factor</th><th className="py-2">Points</th><th className="py-2">Reason</th></tr></thead><tbody>{screening.factors.map((factor) => <tr key={factor.key} className="border-b"><td className="py-2">{factor.label}</td><td className="py-2">{factor.points}</td><td className="py-2">{factor.reason}</td></tr>)}</tbody></table></div>{screening.keywordHits.length > 0 && <div><h3 className="font-medium">Keyword indicators</h3><ul className="list-disc pl-5 text-sm">{screening.keywordHits.map((hit, index) => <li key={`${hit.phrase}-${hit.field}-${index}`}><mark>{hit.phrase}</mark> ({hit.type}, in {hit.field.replaceAll('_', ' ')})</li>)}</ul></div>}{screening.hardTriggers.length > 0 && <div><h3 className="font-medium">Hard triggers</h3><ul className="list-disc pl-5 text-sm">{screening.hardTriggers.map((trigger) => <li key={trigger.key}>{trigger.reason}</li>)}</ul></div>}</> : <p className="text-sm text-muted-foreground">No screening history is available.</p>}</section>
        {screeningHistory.length > 1 && <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Screening history</h2>{screeningHistory.map((item, index) => <div key={`${item.createdAt}-${index}`} className="border-t pt-3 text-sm"><div className="flex flex-wrap gap-3"><RiskLevelBadge level={item.riskLevel} /><span>Score {item.riskScore}</span><span>{item.outcome.replaceAll('_', ' ')}</span><span>{item.createdAt ? new Date(item.createdAt).toLocaleString() : ''}</span></div></div>)}</section>}
        {agreement && <section className="space-y-2 rounded-xl border p-5"><h2 className="text-lg font-semibold">Agreement</h2><p>Status: {agreement.status.replaceAll('_', ' ')}</p>{agreement.locked_at && <p>Locked {new Date(agreement.locked_at).toLocaleString()}</p>}<Link className="text-sm underline" href={admin.agreements.show(agreement.id)}>View agreement versions and confirmations</Link></section>}
        <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Applications</h2>{applications.length ? applications.map((application) => <div key={application.id} className="flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-sm"><Link className="underline underline-offset-4" href={adminUsers.show(application.userId)}>{application.applicantName}</Link><span>{application.status}</span><span>{application.appliedAt ? new Date(application.appliedAt).toLocaleString() : '—'}</span></div>) : <p className="text-sm text-muted-foreground">No applications have been submitted.</p>}</section>
        <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Admin action history</h2>{adminActions.length ? adminActions.map((action) => <div key={action.id} className="border-t pt-3 text-sm"><p className="font-medium">{action.action.replaceAll('_', ' ')} · {action.adminName ?? 'Administrator'} · {action.createdAt ? new Date(action.createdAt).toLocaleString() : ''}</p>{action.note && <p className="mt-1 whitespace-pre-wrap">{action.note}</p>}</div>) : <p className="text-sm text-muted-foreground">No admin decisions have been recorded for this task.</p>}</section>
        {canReview ? <section className="space-y-4 rounded-xl border p-5"><h2 className="text-lg font-semibold">Review decision</h2><div className="grid gap-3 md:grid-cols-3"><DecisionForm taskId={task.id} decision="approve" label="Approve task" noteRequired={requiresOverrideNote} /><DecisionForm taskId={task.id} decision="reject" label="Reject task" noteRequired /><DecisionForm taskId={task.id} decision="request_changes" label="Request changes" noteRequired /></div></section> : task.status === 'pending_review' ? <p className="rounded-lg border p-4 text-sm">You cannot review a task you posted yourself.</p> : null}</div></>;
}

AdminTaskReview.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
