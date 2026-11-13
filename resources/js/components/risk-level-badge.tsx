import { Badge } from '@/components/ui/badge';

const styles: Record<string, string> = {
    low: 'border-green-300 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100',
    moderate: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100',
    high: 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100',
};

export function RiskLevelBadge({ level }: { level: string | null }) {
    return <Badge variant="outline" className={level ? styles[level] : ''}>{level ? `${level[0].toUpperCase()}${level.slice(1)} risk` : 'Not screened'}</Badge>;
}
