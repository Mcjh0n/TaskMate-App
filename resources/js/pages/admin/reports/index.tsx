import { formatDate } from '@/lib/format';
import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { ReportStatusBadge } from '@/components/report-status-badge';
import admin from '@/routes/admin';

type Option = { value: string; label: string };
type Item = {
    id: number;
    typeLabel: string;
    status: string;
    statusLabel: string;
    reporterName: string;
    reportedUserName: string;
    taskTitle: string;
    createdAt: string | null;
    priority: string;
    escalated: boolean;
};
type Props = {
    reports: {
        data: Item[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        search?: string;
        type?: string;
        status?: string;
        priority?: string;
        escalated?: string;
        has_evidence?: string;
    };
    types: Option[];
    statuses: Option[];
};
function AdminReportsQueue({ reports, filters, types, statuses }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [priority, setPriority] = useState(filters.priority ?? '');
    const [escalated, setEscalated] = useState(filters.escalated ?? '');
    const [hasEvidence, setHasEvidence] = useState(filters.has_evidence ?? '');
    const filter = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            admin.reports.index().url,
            {
                search,
                type,
                status,
                priority,
                escalated,
                has_evidence: hasEvidence,
            },
            { preserveState: true, replace: true },
        );
    };
    return (
        <>
            <Head title="Report review queue" />
            <div className="space-y-5">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Report review queue
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Review reports and related TaskMate records.
                    </p>
                </div>
                <p className="rounded-lg border p-4 text-sm">
                    This review is a platform record, not a legal ruling.
                    TaskMate cannot recover or guarantee payment.
                </p>
                <form
                    onSubmit={filter}
                    className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <input
                        aria-label="Search parties"
                        placeholder="Search reporter or reported person"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    />
                    <select
                        aria-label="Filter type"
                        value={type}
                        onChange={(event) => setType(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All types</option>
                        {types.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Filter status"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        {statuses.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Filter priority"
                        value={priority}
                        onChange={(event) => setPriority(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All priorities</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                    </select>
                    <select
                        aria-label="Filter escalation"
                        value={escalated}
                        onChange={(event) => setEscalated(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">Any escalation</option>
                        <option value="1">Escalated</option>
                        <option value="0">Not escalated</option>
                    </select>
                    <select
                        aria-label="Filter evidence"
                        value={hasEvidence}
                        onChange={(event) => setHasEvidence(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">Any evidence</option>
                        <option value="1">Has evidence</option>
                        <option value="0">No evidence</option>
                    </select>
                    <button
                        className="h-10 rounded-md border px-4 text-sm font-medium"
                        type="submit"
                    >
                        Apply filters
                    </button>
                </form>
                {reports.data.length ? (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="p-3">Type / Task</th>
                                    <th className="p-3">Priority</th>
                                    <th className="p-3">Reporter</th>
                                    <th className="p-3">Reported person</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Filed</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reports.data.map((item) => (
                                    <tr key={item.id} className="border-t">
                                        <td className="p-3">
                                            <Link
                                                className="underline"
                                                href={admin.reports.show(
                                                    item.id,
                                                )}
                                            >
                                                {item.typeLabel} ·{' '}
                                                {item.taskTitle}
                                            </Link>
                                            {item.escalated && (
                                                <span className="ml-2 text-xs">
                                                    Escalated
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">{item.priority}</td>
                                        <td className="p-3">
                                            {item.reporterName}
                                        </td>
                                        <td className="p-3">
                                            {item.reportedUserName}
                                        </td>
                                        <td className="p-3">
                                            <ReportStatusBadge
                                                status={item.status}
                                                label={item.statusLabel}
                                            />
                                        </td>
                                        <td className="p-3">
                                            {item.createdAt
                                                ? formatDate(item.createdAt)
                                                : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                        No reports match these filters.
                    </p>
                )}
                {reports.links.length > 3 && (
                    <nav className="flex flex-wrap gap-2">
                        {reports.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className="rounded border px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="rounded border px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}
AdminReportsQueue.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
export default AdminReportsQueue;
