import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type CSSProperties } from 'react';
import { register, login } from '@/routes';
import browse from '@/routes/browse';
import tasks from '@/routes/tasks';

export default function Welcome() {
    const [introComplete, setIntroComplete] = useState(false);
    const [animationRun, setAnimationRun] = useState(0);
    const [workflowVisible, setWorkflowVisible] = useState(false);
    const workflowSectionRef = useRef<HTMLElement | null>(null);
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

    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setIntroComplete(true);

            return;
        }

        const timeout = window.setTimeout(() => {
            setIntroComplete(true);
        }, 5600);

        return () => window.clearTimeout(timeout);
    }, []);

    useEffect(() => {
        if (!introComplete) {
            return;
        }

        const section = workflowSectionRef.current;

        if (!section) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            setWorkflowVisible(true);

            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setWorkflowVisible(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.12 },
        );

        observer.observe(section);

        return () => observer.disconnect();
    }, [introComplete]);

    return (
        <>
            <Head title="Campus tasks with clearer details" />
            <div
                className="landing-page-content"
                data-intro-complete={introComplete}
                aria-hidden={!introComplete}
                inert={!introComplete}
            >
                <header className="welcome-header reveal">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-2.5 sm:px-8">
                        <Link
                            href="/"
                            className="welcome-brand inline-flex items-center gap-2 rounded-lg text-2xl font-bold tracking-tight"
                        >
                            <img
                                src="/favicon.svg"
                                width="36"
                                height="36"
                                alt=""
                            />
                            TaskMate
                        </Link>
                        <nav
                            aria-label="Main navigation"
                            className="welcome-nav flex items-center gap-1 text-sm font-semibold sm:gap-1.5"
                        >
                            <Link
                                href="/how-it-works"
                                className="welcome-nav-link hidden sm:inline-flex"
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
                                        className="welcome-nav-link"
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
                <div className="cmu-photo-hero">
                    <section
                        aria-labelledby="welcome-title"
                        className="mx-auto grid max-w-6xl items-center gap-10 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[1.12fr_1fr] lg:gap-16 lg:py-10"
                    >
                        <div className="welcome-intro reveal">
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
                                <em className="welcome-title-accent">
                                    clear terms.
                                </em>
                            </h1>
                            <p className="mt-6 max-w-md text-lg leading-relaxed text-muted-foreground">
                                Find a side job or post a task for the CMU
                                community. Review the work, time, and pay before
                                you agree.
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
                        </div>
                        <aside
                            className="task-board reveal"
                            aria-label="Animated example TaskMate task"
                        >
                            <div
                                key={animationRun}
                                className="task-paper task-paper-animated"
                                aria-hidden="true"
                            >
                                <div className="task-paper-header">
                                    <p className="task-paper-label">
                                        <span /> Sample task
                                    </p>
                                    <span className="task-paper-mode">
                                        Online
                                    </span>
                                </div>
                                <h2
                                    className="task-paper-title"
                                    id="sample-task-title"
                                >
                                    Design a campus event poster
                                </h2>
                                <p className="task-paper-summary">
                                    Create a poster for a campus event and
                                    deliver a print-ready file.
                                </p>
                                <div className="task-paper-poster">
                                    <span className="task-paper-avatar">
                                        SA
                                    </span>
                                    <span>
                                        <span className="task-paper-poster-label">
                                            Posted by
                                        </span>
                                        CMU Student Affairs
                                    </span>
                                    <span className="task-paper-poster-type">
                                        Organization
                                    </span>
                                </div>
                                <dl className="task-paper-facts">
                                    <div>
                                        <dt>Duration</dt>
                                        <dd>3 hours</dd>
                                    </div>
                                    <div>
                                        <dt>Budget</dt>
                                        <dd className="task-budget">₱600</dd>
                                    </div>
                                </dl>
                            </div>
                            <p className="sr-only">
                                Sample online task: Design a campus event poster
                                for CMU Student Affairs. Estimated duration: 3
                                hours. Budget: 600 pesos.
                            </p>
                            <button
                                type="button"
                                className="task-card-replay"
                                onClick={() =>
                                    setAnimationRun((run) => run + 1)
                                }
                            >
                                <span aria-hidden="true">↻</span>
                                Replay animation
                            </button>
                        </aside>
                    </section>
                </div>
                <section
                    ref={workflowSectionRef}
                    aria-labelledby="flow-title"
                    className="workflow-section"
                    data-visible={workflowVisible}
                >
                    <div className="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-16">
                        <h2
                            id="flow-title"
                            className="workflow-title reveal"
                            data-reveal="title"
                        >
                            How TaskMate works
                        </h2>
                        <ol className="workflow-grid">
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
                                    className="workflow-card reveal"
                                    data-reveal="card"
                                >
                                    <span
                                        className="workflow-number"
                                        aria-hidden="true"
                                    >
                                        0{index + 1}
                                    </span>
                                    <h3>{title}</h3>
                                    <p>{detail}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>
            </div>
            {!introComplete && (
                <p className="sr-only" role="status" aria-live="polite">
                    TaskMate workflow: a task is posted, a student applies, both
                    agree on the terms, and payment status is recorded.
                </p>
            )}
            <div className="landing-intro-overlay" aria-hidden="true">
                <div className="landing-intro-content">
                    <p className="landing-intro-brand">
                        <span aria-hidden="true">T</span> TaskMate
                    </p>
                    <p className="landing-intro-kicker">
                        CAMPUS TASKS, CLEAR TERMS
                    </p>
                    <h1 className="landing-intro-headline">
                        <span>Campus</span>
                        <span>tasks,</span>
                        <span className="landing-intro-accent">clear</span>
                        <span className="landing-intro-accent">terms.</span>
                    </h1>
                    <div className="landing-intro-workflow">
                        <span className="landing-intro-travel-dot" />
                        <ol>
                            {[
                                'Task posted',
                                'Student applies',
                                'Terms agreed',
                                'Paid',
                            ].map((step, index) => (
                                <li
                                    key={step}
                                    style={
                                        {
                                            '--check-delay': `${850 + index * 800}ms`,
                                        } as CSSProperties
                                    }
                                >
                                    <span className="landing-intro-check">
                                        ✓
                                    </span>
                                    <span>{step}</span>
                                </li>
                            ))}
                        </ol>
                    </div>
                </div>
            </div>
        </>
    );
}
