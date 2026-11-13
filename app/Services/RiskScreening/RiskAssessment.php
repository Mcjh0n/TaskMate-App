<?php

namespace App\Services\RiskScreening;

use App\Enums\RiskLevel;

final readonly class RiskAssessment
{
    /** @param array<int, array{key: string, label: string, points: int, reason: string}> $factors
     *  @param array<int, array{phrase: string, type: string, field: string, start: int, length: int}> $keywordHits
     *  @param array<int, array{key: string, reason: string}> $hardTriggers
     */
    public function __construct(
        public RiskLevel $level,
        public int $score,
        public array $factors,
        public array $keywordHits,
        public array $hardTriggers,
        public string $outcome,
    ) {}
}
