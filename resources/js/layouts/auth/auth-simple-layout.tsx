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
            <div className="mx-auto w-full px-3 py-4 sm:px-5 sm:py-6">
                <div className="mx-auto w-full max-w-[22rem] rounded-xl border border-t-4 border-t-brand-gold bg-card p-4 shadow-sm sm:p-5 sm:has-[.registration-form]:max-w-[30rem]">
                    <div className="flex flex-col gap-3">
                        <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b pb-3">
                            <Link
                                href={home()}
                                className="inline-flex items-center gap-2 text-lg font-bold tracking-tight"
                            >
                                <AppLogoIcon className="size-7" alt="" />
                                TaskMate
                            </Link>

                            <div className="space-y-0.5 text-center">
                                <h1 className="text-base leading-tight font-semibold">
                                    {title}
                                </h1>
                                {description && (
                                    <p className="text-center text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                )}
                            </div>
                        </div>
                        <Link
                            href={home()}
                            className="inline-flex w-fit items-center gap-2 rounded-md px-2 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <span aria-hidden="true">←</span>
                            Back to home
                        </Link>
                        {children}
                    </div>
                </div>
            </div>
            <SiteFooter />
        </>
    );
}
