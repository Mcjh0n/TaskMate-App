import { Alert, AlertDescription } from '@/components/ui/alert';
import { SAFETY_NOTICE } from '@/constants/notices';

export function SafetyNotice() {
    return <Alert><AlertDescription>{SAFETY_NOTICE}</AlertDescription></Alert>;
}
