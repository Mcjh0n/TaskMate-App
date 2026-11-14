import { StarRating } from '@/components/star-rating';
import { RATINGS_NOTICE } from '@/constants/notices';

export type RatingSummaryData = { count: number; average: number | null; dimensions: Record<string, { label: string; average: number }> };
export const ratingNotice = RATINGS_NOTICE;

export function RatingSummary({ summary }: { summary: RatingSummaryData }) {
    return <section className="space-y-2 rounded-lg border p-3 text-sm" aria-label="Rating summary">
        <p className="font-medium">{summary.average === null ? `${summary.count} visible rating${summary.count === 1 ? '' : 's'}` : <><StarRating score={Math.round(summary.average)} label="Average rating" /> <span>{summary.average} average · {summary.count} ratings</span></>}</p>
        {Object.entries(summary.dimensions).map(([key, dimension]) => <p key={key} className="flex items-center justify-between gap-3 text-muted-foreground"><span>{dimension.label}</span><StarRating score={Math.round(dimension.average)} label={`${dimension.label} average`} /></p>)}
        <p className="border-t pt-2 text-xs text-muted-foreground">{ratingNotice}</p>
    </section>;
}
