import { Form, Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { ReportDetails } from '@/components/report-details';
import admin from '@/routes/admin';

function DecisionForm({ reportId, decision, label, noteRequired, showOutcome = false }: { reportId: number; decision: string; label: string; noteRequired: boolean; showOutcome?: boolean }) {
    return <Form {...admin.reports.decision.form(reportId)} className="space-y-3 rounded-lg border p-4">{({ errors, processing }) => <><input type="hidden" name="decision" value={decision} /><h3 className="font-medium">{label}</h3>{showOutcome && <div><label className="mb-1 block text-sm" htmlFor="outcome">Outcome</label><select id="outcome" name="outcome" required className="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Choose an outcome</option><option value="supported">Supported</option><option value="not_supported">Not supported</option><option value="inconclusive">Inconclusive</option></select><InputError message={errors.outcome} /></div>}<Textarea name="note" required={noteRequired} placeholder={noteRequired ? 'Required note for the parties' : 'Optional note'} /><InputError message={errors.note} /><Button type="submit" disabled={processing}>{label}</Button></>}</Form>;
}

function AdminReportShow({ report, canReview, notice }: { report: any; canReview: boolean; notice: string }) {
    return <><Head title={`Review report #${report.id}`} /><div className="space-y-5"><p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">{notice}</p><ReportDetails report={report} />{canReview && <section className="space-y-3 rounded-xl border p-5"><h2 className="text-lg font-semibold">Review decision</h2><div className="grid gap-3 md:grid-cols-3"><DecisionForm reportId={report.id} decision="start_review" label="Start review" noteRequired={false} /><DecisionForm reportId={report.id} decision="resolve" label="Resolve report" noteRequired showOutcome /><DecisionForm reportId={report.id} decision="dismiss" label="Dismiss report" noteRequired /></div></section>}{!canReview && ['open', 'under_review'].includes(report.status) && <p className="rounded border p-4 text-sm">This report is not available for review by this administrator.</p>}</div></>;
}
AdminReportShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
export default AdminReportShow;
