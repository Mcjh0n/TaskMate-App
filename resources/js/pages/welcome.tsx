import { PAYMENT_BOUNDARY_NOTICE, SAFETY_NOTICE } from '@/constants/notices';
import { Head, Link, usePage } from '@inertiajs/react';
import { register, login } from '@/routes';
import browse from '@/routes/browse';
import tasks from '@/routes/tasks';

export default function Welcome() {
    const { auth } = usePage<any>().props;
    const user = auth?.user;
    const primaryHref = user?.is_admin ? '/admin' : user?.can_apply_tasks ? browse.index().url : user?.can_post_tasks ? tasks.create().url : user ? '/dashboard' : register().url;
    const primaryLabel = user?.is_admin || user ? (user?.can_apply_tasks ? 'Browse tasks' : user?.can_post_tasks ? 'Post a task' : 'Dashboard') : 'Register';
    return <>
        <Head title="Campus tasks with clearer details" />
        <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5"><Link href="/" className="text-xl font-bold">TaskMate</Link><nav className="flex gap-4">{user ? <Link href="/dashboard">Dashboard</Link> : <><Link href={login()}>Log in</Link><Link href={register()}>Register</Link></>}</nav></header>
        <section className="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <p className="font-medium text-primary">For the Central Mindanao University community</p>
            <h1 className="mt-3 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">Find campus tasks with the details you need before you agree.</h1>
            <p className="mt-6 max-w-2xl text-lg text-muted-foreground">TaskMate helps students and eligible posters share clear work details, review applications, and record agreements.</p>
            <div className="mt-8 flex flex-wrap gap-3"><Link className="rounded-md bg-primary px-5 py-3 font-semibold text-primary-foreground" href={primaryHref}>{primaryLabel}</Link>{!user && <Link className="rounded-md border px-5 py-3 font-semibold" href={login()}>Log in</Link>}</div>
        </section>
        <section className="border-y bg-muted/30 px-4 py-14"><div className="mx-auto max-w-6xl"><h2 className="text-2xl font-semibold">How it works</h2><ol className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{['Verify your account', 'Post a task for review', 'Browse details and apply', 'Agree on the work before it begins', 'Record completion and payment status', 'Rate the experience or report a concern'].map((step, i) => <li className="rounded-lg border bg-background p-5" key={step}><span className="font-semibold">{i + 1}. </span>{step}</li>)}</ol></div></section>
        <section className="mx-auto max-w-6xl space-y-4 px-4 py-12"><h2 className="text-xl font-semibold">Clear limits</h2><p>{PAYMENT_BOUNDARY_NOTICE}</p><p>{SAFETY_NOTICE}</p></section>
    </>;
}
