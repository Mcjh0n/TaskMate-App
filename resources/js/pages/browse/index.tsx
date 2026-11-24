import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ApplicationStatusBadge } from '@/components/application-status-badge';
import { VerificationStatusBadge } from '@/components/verification-status-badge';
import browse from '@/routes/browse';

type Option = { value: string; label: string };
type Listing = {
    id: number;
    title: string;
    descriptionPreview: string;
    category: string;
    budgetDisplay: string;
    workMode: string;
    estimatedDurationDays: number | null;
    estimatedDurationLabel: string | null;
    deliverablesPreview: string | null;
    workplaceName: string | null;
    workplaceAddress: string | null;
    posterName: string;
    posterType: string;
    posterVerificationStatus:
        | 'pending'
        | 'verified'
        | 'rejected'
        | 'more_info_requested';
    hasApplied: boolean;
    canApply: boolean;
};
type Props = {
    tasks: {
        data: Listing[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        search?: string;
        category?: string;
        work_mode?: string;
        min_budget?: string;
        max_budget?: string;
        sort?: string;
    };
    options: { categories: Option[]; workModes: Option[]; sorts: Option[] };
};

export default function BrowseTasks({ tasks, filters, options }: Props) {
    const budgetFiltersActive = Boolean(
        filters.min_budget || filters.max_budget,
    );
    const [values, setValues] = useState({
        search: filters.search ?? '',
        category: filters.category ?? '',
        work_mode: filters.work_mode ?? '',
        min_budget: filters.min_budget ?? '',
        max_budget: filters.max_budget ?? '',
        sort: filters.sort ?? 'newest',
    });
    const [budgetFiltersOpen, setBudgetFiltersOpen] = useState(
        budgetFiltersActive,
    );
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(browse.index().url, values, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Browse tasks" />
            <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Browse tasks</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Search approved tasks and review the work details before
                        you apply.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-xl border bg-card p-4 sm:p-5"
                >
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label
                            className="grid gap-1.5 text-sm font-medium lg:col-span-2"
                            htmlFor="search-tasks"
                        >
                            Search tasks
                            <input
                                id="search-tasks"
                                name="search"
                                placeholder="Try tutoring or spreadsheet skills"
                                value={values.search}
                                onChange={(event) =>
                                    setValues({
                                        ...values,
                                        search: event.target.value,
                                    })
                                }
                                className="h-10 rounded-md border bg-background px-3 font-normal"
                            />
                        </label>
                        <label
                            className="grid gap-1.5 text-sm font-medium"
                            htmlFor="filter-category"
                        >
                            Category
                            <select
                                id="filter-category"
                                name="category"
                                value={values.category}
                                onChange={(event) =>
                                    setValues({
                                        ...values,
                                        category: event.target.value,
                                    })
                                }
                                className="h-10 rounded-md border bg-background px-3 font-normal"
                            >
                                <option value="">All categories</option>
                                {options.categories.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label
                            className="grid gap-1.5 text-sm font-medium"
                            htmlFor="filter-work-mode"
                        >
                            Work mode
                            <select
                                id="filter-work-mode"
                                name="work_mode"
                                value={values.work_mode}
                                onChange={(event) =>
                                    setValues({
                                        ...values,
                                        work_mode: event.target.value,
                                    })
                                }
                                className="h-10 rounded-md border bg-background px-3 font-normal"
                            >
                                <option value="">All work modes</option>
                                {options.workModes.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label
                            className="grid gap-1.5 text-sm font-medium sm:col-span-2 lg:col-span-2"
                            htmlFor="sort-tasks"
                        >
                            Sort by
                            <select
                                id="sort-tasks"
                                name="sort"
                                value={values.sort}
                                onChange={(event) =>
                                    setValues({
                                        ...values,
                                        sort: event.target.value,
                                    })
                                }
                                className="h-10 rounded-md border bg-background px-3 font-normal"
                            >
                                {options.sorts.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>

                    <details
                        open={budgetFiltersOpen}
                        onToggle={(event) =>
                            setBudgetFiltersOpen(event.currentTarget.open)
                        }
                        className="rounded-lg border px-4 py-3"
                    >
                        <summary className="cursor-pointer text-sm font-medium">
                            Budget filters
                            {budgetFiltersActive ? ' · active' : ''}
                        </summary>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                className="grid gap-1.5 text-sm font-medium"
                                htmlFor="min-budget"
                            >
                                Minimum budget (PHP)
                                <input
                                    id="min-budget"
                                    name="min_budget"
                                    type="number"
                                    min="0"
                                    max="1000000"
                                    step="0.01"
                                    value={values.min_budget}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            min_budget: event.target.value,
                                        })
                                    }
                                    className="h-10 rounded-md border bg-background px-3 font-normal"
                                />
                            </label>
                            <label
                                className="grid gap-1.5 text-sm font-medium"
                                htmlFor="max-budget"
                            >
                                Maximum budget (PHP)
                                <input
                                    id="max-budget"
                                    name="max_budget"
                                    type="number"
                                    min="0"
                                    max="1000000"
                                    step="0.01"
                                    value={values.max_budget}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            max_budget: event.target.value,
                                        })
                                    }
                                    className="h-10 rounded-md border bg-background px-3 font-normal"
                                />
                            </label>
                        </div>
                    </details>

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            className="min-h-10 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                        >
                            Show tasks
                        </button>
                        <Link
                            href={browse.index().url}
                            className="text-sm underline underline-offset-4"
                        >
                            Clear filters
                        </Link>
                    </div>
                </form>

                {tasks.data.length ? (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {tasks.data.map((task) => (
                            <article
                                key={task.id}
                                className="flex flex-col gap-3 rounded-xl border bg-card p-5"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            {task.category}
                                        </p>
                                        <h2 className="mt-1 text-lg font-semibold">
                                            {task.title}
                                        </h2>
                                    </div>
                                    <div className="flex flex-col items-end gap-1">
                                        <span className="shrink-0 rounded-full border px-3 py-1 text-sm font-semibold">
                                            {task.workMode}
                                        </span>
                                        {task.hasApplied && (
                                            <ApplicationStatusBadge
                                                status="pending"
                                                label="Applied"
                                            />
                                        )}
                                    </div>
                                </div>
                                <p className="text-sm">
                                    <span className="font-medium">Poster:</span>{' '}
                                    {task.posterName} · {task.posterType}
                                </p>
                                <p className="line-clamp-3 text-sm text-muted-foreground">
                                    {task.descriptionPreview}
                                </p>
                                {task.deliverablesPreview && (
                                    <p className="text-sm">
                                        <span className="font-medium">
                                            Deliverable:
                                        </span>{' '}
                                        {task.deliverablesPreview}
                                    </p>
                                )}
                                <p className="text-sm">
                                    <span className="font-medium">
                                        Estimated duration:
                                    </span>{' '}
                                    {task.estimatedDurationLabel ?? 'Not provided'}
                                </p>
                                {(task.workplaceName ||
                                    task.workplaceAddress) && (
                                    <p className="text-sm">
                                        <span className="font-medium">
                                            {task.workMode === 'On-campus'
                                                ? 'On-campus location:'
                                                : 'Off-campus location:'}
                                        </span>{' '}
                                        {[
                                            task.workplaceName,
                                            task.workplaceAddress,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                )}
                                <div className="mt-auto border-t pt-3">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="text-xl font-semibold">
                                                {task.budgetDisplay}
                                            </p>
                                            <div className="mt-1">
                                                <VerificationStatusBadge
                                                    status={
                                                        task.posterVerificationStatus
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <Link
                                            href={
                                                task.canApply &&
                                                !task.hasApplied
                                                    ? `/browse/${task.id}#application-form`
                                                    : browse.show(task.id)
                                            }
                                            className="inline-flex min-h-10 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                                        >
                                            {task.hasApplied
                                                ? 'View application'
                                                : task.canApply
                                                  ? 'Apply'
                                                  : 'View task'}
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-xl border bg-card p-10 text-center">
                        <h2 className="font-semibold">
                            No tasks match your search
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Try changing or clearing your filters.
                        </p>
                        <Link
                            href={browse.index().url}
                            className="mt-4 inline-block text-sm underline underline-offset-4"
                        >
                            Clear filters
                        </Link>
                    </div>
                )}

                {tasks.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2"
                        aria-label="Task pages"
                    >
                        {tasks.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-muted font-medium' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    aria-disabled="true"
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
