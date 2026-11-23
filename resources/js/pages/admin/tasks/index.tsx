import { formatDate } from '@/lib/format';
import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { TaskStatusBadge } from '@/components/task-status-badge';
import admin from '@/routes/admin';

type Option = { value: string; label: string };
type Row = {
    id: number;
    title: string;
    posterName: string;
    posterType: string;
    status: string;
    statusLabel: string;
    workMode: string;
    submittedAt: string | null;
};
type Props = {
    tasks: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search?: string; status?: string; work_mode?: string };
    statuses: Option[];
    workModes: Option[];
};

export default function AdminTaskQueue({
    tasks,
    filters,
    statuses,
    workModes,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [workMode, setWorkMode] = useState(filters.work_mode ?? '');
    const filter = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            admin.tasks.index().url,
            { search, status, work_mode: workMode },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Task review queue" />
            <div className="space-y-5">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Task review queue
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Pending tasks appear first, with the newest submissions
                        at the top.
                    </p>
                </div>
                <form
                    onSubmit={filter}
                    className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <input
                        aria-label="Search title or poster"
                        placeholder="Search title or poster"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    />
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
                        aria-label="Filter work mode"
                        value={workMode}
                        onChange={(event) => setWorkMode(event.target.value)}
                        className="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All work modes</option>
                        {workModes.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <button
                        className="h-10 rounded-md border px-4 text-sm font-medium hover:bg-muted"
                        type="submit"
                    >
                        Apply filters
                    </button>
                </form>
                {tasks.data.length ? (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th className="p-3">Title</th>
                                    <th className="p-3">Poster</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Work mode</th>
                                    <th className="p-3">Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tasks.data.map((task) => (
                                    <tr key={task.id} className="border-t">
                                        <td className="p-3">
                                            <Link
                                                className="font-medium underline underline-offset-4"
                                                href={admin.tasks.show(task.id)}
                                            >
                                                {task.title}
                                            </Link>
                                        </td>
                                        <td className="p-3">
                                            {task.posterName}
                                            <div className="text-xs text-muted-foreground">
                                                {task.posterType}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            <TaskStatusBadge
                                                status={task.status}
                                                label={task.statusLabel}
                                            />
                                        </td>
                                        <td className="p-3">{task.workMode}</td>
                                        <td className="p-3 whitespace-nowrap">
                                            {task.submittedAt
                                                ? formatDate(task.submittedAt)
                                                : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                        No tasks match these filters.
                    </div>
                )}
                {tasks.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2"
                        aria-label="Pagination"
                    >
                        {tasks.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-muted font-medium' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="rounded border px-3 py-1 text-sm text-muted-foreground"
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

AdminTaskQueue.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
