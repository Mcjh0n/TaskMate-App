import type { PropsWithChildren } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import PrototypeBanner from '@/components/prototype-banner';
import SiteFooter from '@/components/site-footer';
import { NotificationBell } from '@/components/notification-bell';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';

export default function AdminLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage();
    const auth = props.auth;
    const pollSeconds = (props as { notificationPollSeconds?: number }).notificationPollSeconds ?? 10;
    const initials = useInitials();
    const links = [
        { label: 'Dashboard', href: admin.dashboard().url },
        { label: 'Users', href: admin.users.index().url },
        { label: 'Tasks', href: admin.tasks.index().url },
        { label: 'Reports', href: admin.reports.index().url },
        { label: 'Flags', href: '/admin/flags' },
        { label: 'Ratings', href: admin.ratings.index().url },
    ];

    return (
        <><PrototypeBanner /><div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Link href={admin.dashboard()} className="text-lg font-semibold">TaskMate Administration</Link>
                        <p className="text-sm text-muted-foreground">Account review</p>
                    </div>
                    <nav aria-label="Administrator navigation" className="flex flex-wrap items-center gap-2">
                        <NotificationBell pollSeconds={pollSeconds} showMessages={false} />
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
                        {auth.user && <DropdownMenu><DropdownMenuTrigger asChild><Button variant="ghost" size="icon" aria-label="Open account menu"><Avatar><AvatarFallback>{initials(auth.user.name)}</AvatarFallback></Avatar></Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-56"><UserMenuContent user={auth.user} /></DropdownMenuContent></DropdownMenu>}
                    </nav>
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div><SiteFooter /></>
    );
}
