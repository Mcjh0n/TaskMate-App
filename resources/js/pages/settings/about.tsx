import { Head, Link } from "@inertiajs/react";
import {
    ArrowUpRight,
    BookOpenCheck,
    CircleCheck,
    ClipboardCheck,
    MessageCircle,
    ShieldCheck,
    WalletCards,
} from "lucide-react";
import { PAYMENT_BOUNDARY_NOTICE, RATINGS_NOTICE, SAFETY_NOTICE } from "@/constants/notices";

const workflow = [
    {
        title: "Create and verify your account",
        description:
            "Administrators review account details. Students can browse approved tasks before verification, but must be verified to apply.",
    },
    {
        title: "Post a task for review",
        description:
            "Eligible posters describe the work, duration, budget, and work mode. Every new or edited task waits for an administrator decision.",
    },
    {
        title: "Browse and apply",
        description:
            "Students review approved listings and their disclosed details, then send an application to the poster.",
    },
    {
        title: "Agree before work begins",
        description:
            "The poster and selected applicant confirm the same agreement version. Work can begin after it is locked.",
    },
    {
        title: "Record the outcome",
        description:
            "The poster records completion and payment. The student separately records whether payment was received.",
    },
    {
        title: "Rate or report",
        description:
            "After completion, both people can leave contextual feedback. Serious concerns should be reported through the reporting tools.",
    },
];

const resources = [
    {
        href: "/how-it-works",
        label: "How TaskMate works",
        description: "A short overview of the task journey.",
    },
    {
        href: "/task-guidelines",
        label: "Task Guidelines",
        description: "What makes a task clear and appropriate to post.",
    },
    {
        href: "/safety-and-limits",
        label: "Safety and Limits",
        description: "What verification, review, and reporting can and cannot do.",
    },
    {
        href: "/privacy",
        label: "Privacy Notice",
        description: "How the prototype handles account and report information.",
    },
];

export default function AboutTaskMate() {
    return (
        <>
            <Head title="About TaskMate" />
            <div className="about-taskmate-page">
                <header className="about-taskmate-hero">
                    <span className="about-taskmate-hero-icon">
                        <BookOpenCheck aria-hidden="true" />
                    </span>
                    <div>
                        <p>Central Mindanao University</p>
                        <h1>About TaskMate</h1>
                        <span>
                            Campus tasks, clear terms. A simple place to find work, post a task, and
                            agree on the details first.
                        </span>
                    </div>
                </header>

                <div className="about-taskmate-layout">
                    <main className="about-taskmate-main">
                        <section className="about-taskmate-panel">
                            <div className="about-taskmate-heading">
                                <span>
                                    <ClipboardCheck aria-hidden="true" />
                                </span>
                                <div>
                                    <h2>What TaskMate helps you do</h2>
                                    <p>
                                        Useful tools for finding work and agreeing on clear terms.
                                    </p>
                                </div>
                            </div>
                            <ul className="about-taskmate-steps">
                                {workflow.map((step) => (
                                    <li key={step.title}>
                                        <span>
                                            <CircleCheck aria-hidden="true" />
                                        </span>
                                        <div>
                                            <h3>{step.title}</h3>
                                            <p>{step.description}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <section className="about-taskmate-panel">
                            <div className="about-taskmate-heading">
                                <span>
                                    <MessageCircle aria-hidden="true" />
                                </span>
                                <div>
                                    <h2>Who can use TaskMate</h2>
                                    <p>What you can do depends on your account and verification.</p>
                                </div>
                            </div>
                            <div className="about-taskmate-roles">
                                <article>
                                    <strong>Students</strong>
                                    <p>
                                        Browse approved tasks. Verified students can apply. Students
                                        may also post when posting is enabled.
                                    </p>
                                </article>
                                <article>
                                    <strong>Faculty and staff</strong>
                                    <p>
                                        Verified faculty and staff can post tasks. They cannot apply
                                        as workers.
                                    </p>
                                </article>
                                <article>
                                    <strong>Organizations</strong>
                                    <p>
                                        Approved organization representatives can post tasks.
                                        Organization accounts cannot apply.
                                    </p>
                                </article>
                            </div>
                        </section>
                    </main>

                    <aside className="about-taskmate-aside">
                        <section className="about-taskmate-boundaries">
                            <p className="about-taskmate-aside-label">Good to know</p>
                            <article>
                                <WalletCards aria-hidden="true" />
                                <div>
                                    <h2>Payment is direct</h2>
                                    <p>{PAYMENT_BOUNDARY_NOTICE}</p>
                                </div>
                            </article>
                            <article>
                                <ShieldCheck aria-hidden="true" />
                                <div>
                                    <h2>Review is not a guarantee</h2>
                                    <p>
                                        {SAFETY_NOTICE} Admin approval does not guarantee a task is
                                        safe.
                                    </p>
                                </div>
                            </article>
                            <article>
                                <MessageCircle aria-hidden="true" />
                                <div>
                                    <h2>Report serious concerns</h2>
                                    <p>{RATINGS_NOTICE}</p>
                                </div>
                            </article>
                        </section>

                        <section className="about-taskmate-links">
                            <div className="about-taskmate-heading">
                                <span>
                                    <BookOpenCheck aria-hidden="true" />
                                </span>
                                <div>
                                    <h2>Explore the guides</h2>
                                    <p>More details about using the service.</p>
                                </div>
                            </div>
                            {resources.map((resource) => (
                                <Link key={resource.href} href={resource.href}>
                                    <span>
                                        <strong>{resource.label}</strong>
                                        <small>{resource.description}</small>
                                    </span>
                                    <ArrowUpRight aria-hidden="true" />
                                </Link>
                            ))}
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}

AboutTaskMate.layout = {
    breadcrumbs: [
        {
            title: "About TaskMate",
            href: "/settings/about",
        },
    ],
};
