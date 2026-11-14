import type { PropsWithChildren } from 'react';
import PrototypeBanner from '@/components/prototype-banner';
import SiteFooter from '@/components/site-footer';

export default function PublicLayout({ children }: PropsWithChildren) {
    return <div className="min-h-screen bg-background text-foreground"><PrototypeBanner /><main>{children}</main><SiteFooter /></div>;
}
