import { PAYMENT_BOUNDARY_NOTICE, SAFETY_NOTICE } from '@/constants/notices';
import { Head, Link, usePage } from '@inertiajs/react';
import { register, login } from '@/routes';
import browse from '@/routes/browse';
import tasks from '@/routes/tasks';

export default function Welcome() {
    const { auth } = usePage<any>().props;
    const user = auth?.user;
    const actions = user?.is_admin
        ? [{ label: 'Open admin dashboard', href: '/admin', primary: true }]
        : user
            ? [
                ...(user.can_apply_tasks ? [{ label: 'Browse tasks', href: browse.index().url, primary: true }] : []),
                ...(user.can_post_tasks ? [{ label: 'Post a task', href: tasks.create().url, primary: !user.can_apply_tasks }] : []),
                ...(!user.can_apply_tasks && !user.can_post_tasks ? [{ label: 'Open dashboard', href: '/dashboard', primary: true }] : []),
            ]
            : [{ label: 'Create an account', href: register().url, primary: true }, { label: 'Log in', href: login().url, primary: false }];
    return <>
        <Head title="Campus tasks with clearer details" />
        <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5"><Link href="/" className="text-xl font-bold">TaskMate</Link><nav className="flex gap-4">{user ? <Link href="/dashboard">Dashboard</Link> : <><Link href={login()}>Log in</Link><Link href={register()}>Register</Link></>}</nav></header>
        <section className="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <p className="font-medium text-primary">For the Central Mindanao University community</p>
            <h1 className="mt-3 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">Find campus tasks with the details you need before you agree.</h1>
            <p className="mt-6 max-w-2xl text-lg text-muted-foreground">TaskMate helps students find side jobs and helps eligible posters share clear work details, review applicants, and agree on the work before it begins.</p>
            <div className="mt-8 flex flex-wrap gap-3">{actions.map((action) => <Link key={action.label} className={`rounded-md px-5 py-3 font-semibold ${action.primary ? 'bg-primary text-primary-foreground' : 'border'}`} href={action.href}>{action.label}</Link>)}</div>
            {!user && <p className="mt-4 max-w-2xl text-sm text-muted-foreground">Browse and posting require a verified account. Create an account to start the review process.</p>}
        </section>
        <section className="border-y bg-muted/30 px-4 py-14"><div className="mx-auto max-w-6xl"><h2 className="text-2xl font-semibold">The main TaskMate flow</h2><p className="mt-2 max-w-3xl text-sm text-muted-foreground">TaskMate focuses on helping students and posters reach clear, recorded agreements for individual side jobs.</p><ol className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{['Create an account and complete administrator verification', 'Eligible posters submit a clear task for review', 'Verified students review approved tasks and apply', 'The poster and selected student agree on the work and amount', 'Both people confirm the same agreement before work begins', 'Record completion and payment status, then rate or report if needed'].map((step, i) => <li className="rounded-lg border bg-background p-5" key={step}><span className="font-semibold">{i + 1}. </span>{step}</li>)}</ol><p className="mt-5 text-sm text-muted-foreground">Only verified students can apply. Verified faculty/staff and approved organizations can post; student posting depends on the current configuration.</p></div></section>
        <section className="mx-auto max-w-6xl space-y-4 px-4 py-12"><h2 className="text-xl font-semibold">Clear limits</h2><p>{PAYMENT_BOUNDARY_NOTICE}</p><p>{SAFETY_NOTICE}</p></section>
    </>;
}
