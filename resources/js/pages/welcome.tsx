import { Head, Link, usePage } from '@inertiajs/react';
import { register, login } from '@/routes';
import browse from '@/routes/browse';
import tasks from '@/routes/tasks';

export default function Welcome() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const actions = user?.is_admin
        ? [{ label: 'Open admin dashboard', href: '/admin', primary: true }]
        : user
          ? [
                ...(user.can_browse_tasks
                    ? [
                          {
                              label: 'Browse tasks',
                              href: browse.index().url,
                              primary: true,
                          },
                      ]
                    : []),
                ...(user.can_post_tasks
                    ? [
                          {
                              label: 'Post a task',
                              href: tasks.create().url,
                              primary: !user.can_apply_tasks,
                          },
                      ]
                    : []),
                ...(!user.can_browse_tasks && !user.can_post_tasks
                    ? [
                          {
                              label: 'Open dashboard',
                              href: '/dashboard',
                              primary: true,
                          },
                      ]
                    : []),
            ]
          : [
                {
                    label: 'Create an account',
                    href: register().url,
                    primary: true,
                },
                { label: 'Log in', href: login().url, primary: false },
            ];
    return (
        <>
            <Head title="Campus tasks with clearer details" />
            <header className="border-b bg-card">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-8">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-2 text-2xl font-bold tracking-tight"
                    >
                        <img src="/favicon.svg" width="36" height="36" alt="" />
                        TaskMate
                    </Link>
                    <nav
                        aria-label="Main navigation"
                        className="flex items-center gap-3 text-sm font-semibold sm:gap-6"
                    >
                        <Link
                            href="/how-it-works"
                            className="hidden hover:underline sm:inline"
                        >
                            How it works
                        </Link>
                        {user ? (
                            <Link
                                className="brand-nav-action"
                                href="/dashboard"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="hover:underline"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="brand-nav-action"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <section
                aria-labelledby="welcome-title"
                className="mx-auto grid max-w-6xl items-center gap-10 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[1.12fr_1fr] lg:gap-16 lg:py-20"
            >
                <div className="welcome-intro">
                    <p className="flex items-center gap-2.5 text-xs font-bold tracking-[0.13em] text-muted-foreground uppercase">
                        <span
                            aria-hidden="true"
                            className="h-1 w-7 rounded-full bg-brand-gold"
                        />
                        Central Mindanao University
                    </p>
                    <h1
                        id="welcome-title"
                        className="mt-5 text-[clamp(3.3rem,6.1vw,5.3rem)] leading-[0.98] font-semibold tracking-[-0.065em]"
                    >
                        Campus tasks,
                        <br />
                        <em className="welcome-title-accent">clear terms.</em>
                    </h1>
                    <p className="mt-6 max-w-md text-lg leading-relaxed text-muted-foreground">
                        Find a side job or post a task for the CMU community.
                        Review the work, time, and pay before you agree.
                    </p>
                    <div className="mt-7 flex flex-wrap gap-3">
                        {actions.map((action) => (
                            <Link
                                key={action.label}
                                className={
                                    action.primary
                                        ? 'welcome-action welcome-action-primary'
                                        : 'welcome-action welcome-action-secondary'
                                }
                                href={action.href}
                            >
                                {action.label}
                                {action.primary && (
                                    <span
                                        aria-hidden="true"
                                        className="text-lg leading-none"
                                    >
                                        →
                                    </span>
                                )}
                            </Link>
                        ))}
                    </div>
                    {!user && (
                        <p className="mt-4 max-w-md text-sm leading-relaxed text-muted-foreground">
                            Students can browse after signing in. Applying and
                            posting require account verification.
                        </p>
                    )}
                </div>
                <aside
                    className="task-board"
                    aria-labelledby="sample-task-title"
                >
                    <div className="task-paper">
                        <p className="text-xs font-bold tracking-[0.16em] uppercase">
                            Sample task
                        </p>
                        <h2
                            id="sample-task-title"
                            className="my-4 max-w-64 text-3xl leading-tight font-bold tracking-tight sm:text-4xl"
                        >
                            Design a campus event poster
                        </h2>
                        <dl className="grid grid-cols-2 gap-x-3 gap-y-4 border-t border-dashed border-[#a8b29f] pt-4">
                            <div>
                                <dt>Work mode</dt>
                                <dd>Online</dd>
                            </div>
                            <div>
                                <dt>Duration</dt>
                                <dd>3 hours</dd>
                            </div>
                            <div className="col-span-2">
                                <dt>Budget</dt>
                                <dd className="task-budget">₱600</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </section>
            <section aria-labelledby="flow-title" className="border-y bg-card">
                <div className="mx-auto max-w-6xl px-5 py-12 sm:px-8">
                    <h2
                        id="flow-title"
                        className="text-3xl font-semibold tracking-tight"
                    >
                        How TaskMate works
                    </h2>
                    <ol className="mt-7 grid gap-6 md:grid-cols-3 md:gap-8">
                        {[
                            [
                                'Find a task',
                                'Eligible posters submit tasks for review. Students can browse approved tasks and apply after verification.',
                            ],
                            [
                                'Agree on the details',
                                'The poster and selected student confirm the same work and amount before the task begins.',
                            ],
                            [
                                'Record the outcome',
                                'Record completion and payment status. Leave a rating or report a concern when needed.',
                            ],
                        ].map(([title, detail], index) => (
                            <li
                                key={title}
                                className="border-t border-border pt-4"
                            >
                                <div className="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        className="grid size-8 shrink-0 place-items-center rounded-full bg-secondary text-sm font-bold text-secondary-foreground"
                                    >
                                        {index + 1}
                                    </span>
                                    <h3 className="text-lg font-semibold">
                                        {title}
                                    </h3>
                                </div>
                                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                    {detail}
                                </p>
                            </li>
                        ))}
                    </ol>
                    <p className="mt-7 text-sm leading-relaxed text-muted-foreground">
                        Verified faculty and staff and approved organizations
                        can post tasks. Students can also post when student
                        posting is enabled.
                    </p>
                </div>
            </section>
        </>
    );
}
