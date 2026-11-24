import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';

export default function FlagQueue({
    flags,
    filters,
    categories,
    scanningEnabled,
}: {
    flags: { data: any[]; links: any[] };
    filters: any;
    categories: { value: string; label: string }[];
    scanningEnabled: boolean;
}) {
    return (
        <>
            <Head title="Content flags" />
            <main className="space-y-4">
                <h1 className="text-2xl font-semibold">Content flags</h1>
                {!scanningEnabled && (
                    <p className="text-sm text-muted-foreground">
                        Automatic flagging is paused. Review past flags here and
                        open{' '}
                        <Link href="/admin/reports" className="underline">
                            Reports
                        </Link>{' '}
                        for concerns submitted by users.
                    </p>
                )}
                <form className="flex flex-wrap gap-2">
                    <select
                        name="category"
                        defaultValue={filters.category ?? ''}
                        className="h-10 rounded border bg-background px-3"
                    >
                        <option value="">All categories</option>
                        {categories.map(({ value, label }) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <select
                        name="status"
                        defaultValue={filters.status ?? ''}
                        className="h-10 rounded border bg-background px-3"
                    >
                        <option value="">All statuses</option>
                        <option value="open">Open</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="dismissed">Dismissed</option>
                    </select>
                    <button className="rounded border px-4">Filter</button>
                </form>
                {flags.data.length ? (
                    <div className="overflow-x-auto rounded border bg-card">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr>
                                    <th className="p-3">Category</th>
                                    <th className="p-3">Author</th>
                                    <th className="p-3">Content</th>
                                    <th className="p-3">Status</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {flags.data.map((flag) => (
                                    <tr key={flag.id} className="border-t">
                                        <td className="p-3">
                                            {flag.categoryLabel}
                                        </td>
                                        <td className="p-3">{flag.author}</td>
                                        <td className="p-3">
                                            {flag.context} · {flag.field}
                                        </td>
                                        <td className="p-3">{flag.status}</td>
                                        <td className="p-3">
                                            <Link
                                                className="underline"
                                                href={`/admin/flags/${flag.id}`}
                                            >
                                                Review
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="rounded border bg-card p-6">
                        No content flags match these filters.
                    </p>
                )}
            </main>
        </>
    );
}
FlagQueue.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
