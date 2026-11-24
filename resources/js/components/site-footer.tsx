import { PAYMENT_BOUNDARY_NOTICE, SAFETY_NOTICE } from '@/constants/notices';
import { Link } from '@inertiajs/react';

const links = [
    ['/how-it-works', 'How TaskMate works'],
    ['/task-guidelines', 'Task Guidelines'],
    ['/safety-and-limits', 'Safety and Limits'],
    ['/privacy', 'Privacy Notice'],
] as const;

export default function SiteFooter() {
    return (
        <footer className="border-t bg-card px-5 py-8 text-sm sm:px-8">
            <nav
                aria-label="Information"
                className="mx-auto flex max-w-6xl flex-wrap gap-x-6 gap-y-3 font-semibold text-primary"
            >
                {links.map(([href, label]) => (
                    <Link
                        key={href}
                        href={href}
                        className="underline underline-offset-4"
                    >
                        {label}
                    </Link>
                ))}
            </nav>
            <div className="mx-auto mt-5 max-w-6xl space-y-1 border-t pt-4 text-xs leading-relaxed text-muted-foreground">
                <p>{PAYMENT_BOUNDARY_NOTICE}</p>
                <p>{SAFETY_NOTICE}</p>
            </div>
        </footer>
    );
}
