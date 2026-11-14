import { PAYMENT_BOUNDARY_NOTICE, SAFETY_NOTICE } from '@/constants/notices';
import { Link } from '@inertiajs/react';

const links = [
    ['/how-it-works', 'How TaskMate works'],
    ['/task-guidelines', 'Task Guidelines'],
    ['/safety-and-limits', 'Safety and Limits'],
    ['/privacy', 'Privacy Notice'],
] as const;

export default function SiteFooter() {
    return <footer className="mt-10 border-t bg-muted/30 px-4 py-8 text-sm">
        <nav aria-label="Information" className="mx-auto flex max-w-6xl flex-wrap gap-x-5 gap-y-2">
            {links.map(([href, label]) => <Link key={href} href={href} className="underline underline-offset-4">{label}</Link>)}
        </nav>
        <div className="mx-auto mt-5 max-w-6xl space-y-2 text-muted-foreground">
            <p>{PAYMENT_BOUNDARY_NOTICE}</p>
            <p>{SAFETY_NOTICE}</p>
        </div>
    </footer>;
}
