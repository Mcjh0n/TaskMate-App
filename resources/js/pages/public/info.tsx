import { PAYMENT_BOUNDARY_NOTICE } from '@/constants/notices';
import { Head, Link } from '@inertiajs/react';
import { register, login } from '@/routes';

type Props = { page: string; title: string; guidelines: string[]; privacyDraft: string | null };
export default function Information({ page, title, guidelines, privacyDraft }: Props) {
    return <><Head title={title} /><header className="mx-auto flex max-w-4xl justify-between px-4 py-6"><Link href="/" className="font-bold">TaskMate</Link><nav className="flex gap-4"><Link href={login()}>Log in</Link><Link href={register()}>Register</Link></nav></header><main className="mx-auto max-w-4xl px-4 pb-16"><h1 className="text-3xl font-bold">{title}</h1>
        {page === 'how-it-works' && <div className="mt-6 space-y-6">
            <p className="max-w-3xl text-muted-foreground">TaskMate's main purpose is to help CMU students find individual side jobs with clear work, budget, and workplace details.</p>
            <ol className="space-y-4">
                <li className="rounded-lg border p-4"><h2 className="font-semibold">1. Create an account and verify it</h2><p className="mt-1 text-sm">An administrator reviews the information you submit. Students and faculty/staff use CMU institutional email addresses. Task access begins after account approval.</p></li>
                <li className="rounded-lg border p-4"><h2 className="font-semibold">2. Post a task for review</h2><p className="mt-1 text-sm">Eligible verified posters describe the work, budget, and work mode. Onsite and hybrid tasks include the place of work. New tasks are reviewed before students can see them.</p></li>
                <li className="rounded-lg border p-4"><h2 className="font-semibold">3. Students review and apply</h2><p className="mt-1 text-sm">Verified students can browse approved tasks, check the disclosed conditions, and send one application message. Only students can apply.</p></li>
                <li className="rounded-lg border p-4"><h2 className="font-semibold">4. Agree before work begins</h2><p className="mt-1 text-sm">The poster proposes deliverables, terms, dates, and an amount. Both people review and confirm the same version before work begins.</p></li>
                <li className="rounded-lg border p-4"><h2 className="font-semibold">5. Record completion and payment status</h2><p className="mt-1 text-sm">The parties record completion and payment status in TaskMate. Payment is made directly between them.</p></li>
                <li className="rounded-lg border p-4"><h2 className="font-semibold">6. Share feedback or report a concern</h2><p className="mt-1 text-sm">After a completed task, the parties may leave contextual ratings. Use the report tools for serious problems; ratings are not a substitute for reports.</p></li>
            </ol>
            <p className="rounded-lg border bg-muted/30 p-4 text-sm">Students may post when student posting is enabled. Faculty/staff and approved organization representatives may post. Faculty/staff and organizations cannot apply.</p>
        </div>}
        {page === 'task-guidelines' && <><p className="mt-4 rounded border border-amber-500 bg-amber-50 p-4 text-amber-950">These guidelines are provisional and must be reviewed by the team and university.</p><ul className="mt-6 list-disc space-y-3 pl-6">{guidelines.map((rule) => <li key={rule}>{rule}</li>)}</ul></>}
        {page === 'safety-and-limits' && <div className="mt-6 space-y-4"><p>Verification, screening, and ratings help inform decisions but do not guarantee a person’s future behavior or the safety of a task.</p><p>Review the disclosed work location and conditions. Do not agree to anything that makes you uncomfortable. Use the report tools to raise concerns.</p><p>{PAYMENT_BOUNDARY_NOTICE}</p></div>}
        {page === 'privacy' && <><p className="mt-4 rounded border border-amber-500 bg-amber-50 p-4 text-amber-950">DRAFT PRIVACY NOTICE — not approved. Retention, deletion, consent, and protected-storage rules are still to be decided.</p><pre className="mt-5 whitespace-pre-wrap break-words rounded border p-4 text-sm">{privacyDraft}</pre></>}
    </main></>;
}
