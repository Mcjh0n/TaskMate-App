import { Head } from '@inertiajs/react';
import { AgreementDetails } from '@/components/agreement-details';

export default function ShowAgreement(props: { agreement: any; viewerId: number; notice: string; termsMaxLength: number; status?: string | null }) {
    return <><Head title={`Agreement: ${props.agreement.task.title}`} /><div className="print:hidden">{props.status && <p role="status" className="mx-auto mt-4 max-w-4xl rounded-lg border p-3 text-sm">{props.status}</p>}</div><AgreementDetails {...props} /></>;
}
