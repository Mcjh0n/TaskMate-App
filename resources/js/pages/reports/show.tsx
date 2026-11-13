import { Head } from '@inertiajs/react';
import { ReportDetails } from '@/components/report-details';

const notice = 'TaskMate records completion and payment status only. Payments are made directly between the poster and the student. TaskMate does not hold, process, recover, or guarantee payment.';

export default function ShowReport({ report, canReply, status, statementMaxLength }: { report: any; canReply: boolean; status?: string | null; statementMaxLength: number }) {
    return <><Head title={`Report #${report.id}`} /><main className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6"><p className="rounded-lg border bg-muted/40 p-4 text-sm">{notice}</p>{status && <div role="status" className="rounded-lg border p-3 text-sm">{status}</div>}<ReportDetails report={report} canReply={canReply} statementMaxLength={statementMaxLength} /></main></>;
}
