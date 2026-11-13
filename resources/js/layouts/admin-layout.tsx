import type { PropsWithChildren } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url } = usePage();
    const links = [
        { label: 'Dashboard', href: admin.dashboard().url },
        { label: 'Users', href: admin.users.index().url },
        { label: 'Tasks', href: admin.tasks.index().url },
        { label: 'Reports', href: admin.reports.index().url },
    ];

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Link href={admin.dashboard()} className="text-lg font-semibold">TaskMate Administration</Link>
                        <p className="text-sm text-muted-foreground">Account review</p>
                    </div>
                    <nav aria-label="Administrator navigation" className="flex gap-2">
                        {links.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={cn(
                                    'rounded-md px-3 py-2 text-sm font-medium hover:bg-muted',
                                    url === link.href || (link.href !== admin.dashboard().url && url.startsWith(link.href)) ? 'bg-muted' : '',
                                )}
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div>
    );
}
