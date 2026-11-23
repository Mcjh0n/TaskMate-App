import { Head } from '@inertiajs/react';
import { AgreementDetails } from '@/components/agreement-details';
import { ProblemReportForm } from '@/components/problem-report-form';

export default function ShowAgreement(props: {
    agreement: any;
    completionPayment: import('@/components/completion-payment-panel').CompletionPaymentData;
    viewerId: number;
    actionsAllowed: boolean;
    notice: string;
    termsMaxLength: number;
    status?: string | null;
    problemReport?: {
        action: string;
        types: { value: string; label: string }[];
    };
    offPlatformWarning?: boolean;
}) {
    return (
        <>
            <Head title={`Agreement: ${props.agreement.task.title}`} />
            <div className="print:hidden">
                {props.status && (
                    <p
                        role="status"
                        className="mx-auto mt-4 max-w-4xl rounded-lg border p-3 text-sm"
                    >
                        {props.status}
                    </p>
                )}
                {props.offPlatformWarning && (
                    <p className="mx-auto mt-4 max-w-4xl rounded border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        This message contains a request to move outside TaskMate
                        or skip its steps. Be careful, keep agreements and
                        communications on record, and report anything that makes
                        you uncomfortable.
                    </p>
                )}
                {props.problemReport && (
                    <div className="mx-auto mt-4 max-w-4xl px-4">
                        <ProblemReportForm {...props.problemReport} />
                    </div>
                )}
            </div>
            <AgreementDetails {...props} />
        </>
    );
}
