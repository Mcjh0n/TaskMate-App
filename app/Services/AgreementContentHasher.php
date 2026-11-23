<?php

namespace App\Services;

use App\Models\AgreementVersion;

class AgreementContentHasher
{
    /** Hash only the terms both parties are confirming, in a stable key order. */
    public function hash(array|AgreementVersion $version): string
    {
        $fields = $version instanceof AgreementVersion ? [
            'deliverables' => $version->deliverables,
            'terms' => $version->terms,
            'final_amount' => number_format((float) $version->final_amount, 2, '.', ''),
            'start_date' => $version->start_date?->toDateString(),
            'due_date' => $version->due_date?->toDateString(),
            'work_mode' => $version->work_mode,
            'workplace_name' => $version->workplace_name,
            'workplace_address' => $version->workplace_address,
            'payment_arrangement' => $version->payment_arrangement,
        ] : $version;

        // Preserve old immutable hashes: duration was not part of earlier versions.
        if ($version instanceof AgreementVersion && $version->duration_days !== null) {
            $fields['duration_days'] = $version->duration_days;
        }

        ksort($fields);

        return hash('sha256', json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
