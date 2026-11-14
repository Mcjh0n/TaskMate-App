import { PAYMENT_BOUNDARY_NOTICE } from '@/constants/notices';
import { Head, Link } from '@inertiajs/react';
import { register, login } from '@/routes';

type Props = { page: string; title: string; guidelines: string[]; privacyDraft: string | null };
export default function Information({ page, title, guidelines, privacyDraft }: Props) {
    return <><Head title={title} /><header className="mx-auto flex max-w-4xl justify-between px-4 py-6"><Link href="/" className="font-bold">TaskMate</Link><nav className="flex gap-4"><Link href={login()}>Log in</Link><Link href={register()}>Register</Link></nav></header><main className="mx-auto max-w-4xl px-4 pb-16"><h1 className="text-3xl font-bold">{title}</h1>
        {page === 'how-it-works' && <div className="mt-6 space-y-4"><p>Accounts are reviewed before verified users can take part in posting or applying.</p><p>Eligible posters submit tasks for review. Verified students can browse approved tasks and apply.</p><p>The poster and student record and confirm an agreement before work begins. They later record completion and payment status separately.</p><p>After completion, both parties may rate each other. Use reports for serious concerns.</p></div>}
        {page === 'task-guidelines' && <><p className="mt-4 rounded border border-amber-500 bg-amber-50 p-4 text-amber-950">These guidelines are provisional and must be reviewed by the team and university.</p><ul className="mt-6 list-disc space-y-3 pl-6">{guidelines.map((rule) => <li key={rule}>{rule}</li>)}</ul></>}
        {page === 'safety-and-limits' && <div className="mt-6 space-y-4"><p>Verification, screening, and ratings help inform decisions but do not guarantee a person’s future behavior or the safety of a task.</p><p>Review the disclosed work location and conditions. Do not agree to anything that makes you uncomfortable. Use the report tools to raise concerns.</p><p>{PAYMENT_BOUNDARY_NOTICE}</p></div>}
        {page === 'privacy' && <><p className="mt-4 rounded border border-amber-500 bg-amber-50 p-4 text-amber-950">DRAFT PRIVACY NOTICE — not approved. Retention, deletion, consent, and protected-storage rules are still to be decided.</p><pre className="mt-5 whitespace-pre-wrap break-words rounded border p-4 text-sm">{privacyDraft}</pre></>}
    </main></>;
}
