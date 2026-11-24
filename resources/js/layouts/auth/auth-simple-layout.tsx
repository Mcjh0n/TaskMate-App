import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import PrototypeBanner from '@/components/prototype-banner';
import SiteFooter from '@/components/site-footer';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <>
            <PrototypeBanner prominent />
            <div className="flex min-h-[80svh] items-center justify-center bg-background px-4 py-10 md:py-16">
                <div className="w-full max-w-md rounded-2xl border border-t-4 border-t-brand-gold bg-card p-6 shadow-sm sm:p-9">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4">
                            <Link
                                href={home()}
                                className="inline-flex items-center gap-2 text-xl font-bold tracking-tight"
                            >
                                <AppLogoIcon className="size-9" alt="" />
                                TaskMate
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-xl font-medium">{title}</h1>
                                <p className="text-center text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
            <SiteFooter />
        </>
    );
}
