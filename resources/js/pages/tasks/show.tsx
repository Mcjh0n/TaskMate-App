import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { TaskStatusBadge } from '@/components/task-status-badge';
import { ApplicationStatusBadge } from '@/components/application-status-badge';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import taskApplications from '@/routes/tasks/applications';
import { edit, mine, cancel } from '@/routes/tasks';

type Applicant = {
    id: number;
    applicant: { id: number; name: string; type: string; affiliation: string | null; verificationStatus: 'pending' | 'verified' | 'rejected' | 'more_info_requested' };
    message: string; proposedAmount: string | null; status: string; statusLabel: string; appliedAt: string | null;
};
type Task = {
    id: number; title: string; category: string; budget: string; workMode: string; status: string; description: string;
    requiredActivities: string; deliverables: string; estimatedDurationHours: string; skillRequirements: string | null;
    complexity: string; workplaceName: string | null; workplaceAddress: string | null; workplaceSetting: string | null;
    physicalDemand: string | null; academicInvolvement: string | null; hazardExposure: boolean; hazardDescription: string | null;
    privateMeetingRequired: boolean; deadline: string | null; reviewNote: string | null; createdAt: string | null;
    hasActiveApplications: boolean; applicantsCount: number;
};
type Props = { task: Task; statusLabel: string; canEdit: boolean; canCancel: boolean; status?: string | null; applicants: Applicant[] };

export default function ShowTask({ task, statusLabel, canEdit, canCancel, status, applicants }: Props) {
    return (
        <>
            <Head title={task.title} />
            <div className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6">
                {status && <div role="status" className="rounded-lg border px-4 py-3 text-sm">{status}</div>}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div><p className="text-sm text-muted-foreground">{task.category}</p><h1 className="mt-1 text-2xl font-semibold">{task.title}</h1></div>
                    <TaskStatusBadge status={task.status} label={statusLabel} />
                </div>
                <section className="space-y-4 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">Task details</h2>
                    <p className="whitespace-pre-wrap">{task.description}</p>
                    <div><h3 className="font-medium">Required activities</h3><p className="mt-1 whitespace-pre-wrap text-sm">{task.requiredActivities}</p></div>
                    <div><h3 className="font-medium">Deliverables</h3><p className="mt-1 whitespace-pre-wrap text-sm">{task.deliverables}</p></div>
                    <p className="text-sm">Estimated duration: {task.estimatedDurationHours} hours</p>
                    {task.skillRequirements && <p className="text-sm">Skills: {task.skillRequirements}</p>}
                    <p className="text-sm">Complexity: {task.complexity}</p>
                    <p className="text-sm">Physical demand: {task.physicalDemand}</p>
                    <p className="text-sm">Academic involvement: {task.academicInvolvement}</p>
                    <p className="text-sm">Hazard exposure: {task.hazardExposure ? `Yes — ${task.hazardDescription}` : 'No'}</p>
                    <p className="text-sm">Private or isolated one-on-one meeting required: {task.privateMeetingRequired ? 'Yes' : 'No'}</p>
                </section>
                <section className="space-y-2 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">Work details</h2><p>{task.workMode}</p>
                    {task.workplaceName && <p>{task.workplaceName}</p>}{task.workplaceAddress && <p>{task.workplaceAddress}</p>}
                    {task.workplaceSetting && <p>{task.workplaceSetting.replaceAll('_', ' ')}</p>}
                    <p className="text-sm text-muted-foreground">In-person work details are shown to students before they apply. Workplace information should describe a place of work, not a home address.</p>
                    {task.deadline && <p>Deadline: {new Date(task.deadline).toLocaleDateString()}</p>}
                </section>
                <section className="space-y-2 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">Compensation</h2><p>Estimated budget: ₱{Number(task.budget).toLocaleString()}</p>
                    <p className="text-sm text-muted-foreground">TaskMate records agreements and payment status only. Payments are made directly between users. TaskMate does not hold, process, or guarantee payment.</p>
                </section>
                {task.reviewNote && <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Review note: {task.reviewNote}</div>}
                {applicants.length > 0 && <section className="space-y-4 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">Applicants ({applicants.length})</h2>
                    {applicants.map((application) => <article key={application.id} className="space-y-3 border-t pt-4">
                        <div className="flex flex-wrap items-center justify-between gap-2"><div><h3 className="font-medium">{application.applicant.name}</h3><p className="text-sm text-muted-foreground">{application.applicant.type}{application.applicant.affiliation ? ` · ${application.applicant.affiliation}` : ''}</p></div><VerificationStatusBadge status={application.applicant.verificationStatus} /></div>
                        <p className="whitespace-pre-wrap text-sm">{application.message}</p>
                        <p className="text-sm">Suggested amount: {application.proposedAmount ? `₱${Number(application.proposedAmount).toLocaleString()}` : 'No suggestion'} · Applied {application.appliedAt ? new Date(application.appliedAt).toLocaleDateString() : '—'}</p>
                        <div className="flex items-center gap-2"><ApplicationStatusBadge status={application.status} label={application.statusLabel} />{application.status === 'pending' && task.status === 'approved' && <><Form {...taskApplications.accept.form({ task: task.id, application: application.id })}><Button size="sm" type="submit">Accept</Button></Form><Form {...taskApplications.decline.form({ task: task.id, application: application.id })}><Button size="sm" variant="outline" type="submit">Decline</Button></Form></>}</div>
                    </article>)}
                </section>}
                {task.hasActiveApplications && task.status !== 'assigned' && <p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">This task has active applicants. Cancel it and post a new task to change its details.</p>}
                {task.status === 'assigned' && <p className="rounded-lg border p-4 text-sm">This task has been assigned and cannot be edited.</p>}
                <div className="flex flex-wrap gap-2"><Button variant="outline" asChild><Link href={mine()}>My tasks</Link></Button>{canEdit && !task.hasActiveApplications && task.status !== 'assigned' && <Button asChild><Link href={edit(task.id)}>Edit task</Link></Button>}{canCancel && <Form {...cancel.form(task.id)} onSubmit={(event) => { if (!window.confirm('Cancel this task?')) event.preventDefault(); }}><Button variant="outline" type="submit">Cancel task</Button></Form>}</div>
            </div>
        </>
    );
}
