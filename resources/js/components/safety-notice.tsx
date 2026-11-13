import { Alert, AlertDescription } from '@/components/ui/alert';

export function SafetyNotice() {
    return (
        <Alert>
            <AlertDescription>
                Verification helps confirm who someone is, but it does not
                guarantee a person&apos;s future behavior or the safety of any
                task.
            </AlertDescription>
        </Alert>
    );
}
