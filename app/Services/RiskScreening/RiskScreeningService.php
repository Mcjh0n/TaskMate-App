<?php

namespace App\Services\RiskScreening;

use App\Enums\RiskLevel;
use App\Models\Task;

class RiskScreeningService
{
    public function screen(Task $task): RiskAssessment
    {
        $rules = config('taskmate.risk_rules', []);
        $points = $rules['points'] ?? [];
        $factors = [];
        $categoryLabel = config('taskmate.categories.'.$task->category, $task->category);

        $this->addFactor($factors, 'category_base', 'Task category', $points['category_base'][$task->category] ?? $points['category_base']['default'] ?? 0, "Category: {$categoryLabel}.");
        $this->addChoiceFactor($factors, 'physical_demand', 'Physical demand', $task->physical_demand, 'taskmate.risk_questions.physical_demand', $points['physical_demand'] ?? []);
        $this->addChoiceFactor($factors, 'academic_involvement', 'Academic involvement', $task->academic_involvement, 'taskmate.risk_questions.academic_involvement', $points['academic_involvement'] ?? []);
        $this->addFactor($factors, 'hazard_exposure', 'Hazard exposure', $points['hazard_exposure'][$task->hazard_exposure ? 'yes' : 'no'] ?? 0, $task->hazard_exposure ? 'The task reports a hazard exposure.' : 'The task reports no hazard exposure.');
        $this->addFactor($factors, 'private_meeting_required', 'Private meeting', $points['private_meeting_required'][$task->private_meeting_required ? 'yes' : 'no'] ?? 0, $task->private_meeting_required ? 'A one-on-one meeting in a private or isolated place is required.' : 'A private or isolated one-on-one meeting is not required.');

        if ($task->work_mode === 'remote') {
            $this->addFactor($factors, 'workplace_setting', 'Workplace setting', 0, 'This task is remote, so no workplace setting applies.');
        } else {
            $this->addChoiceFactor($factors, 'workplace_setting', 'Workplace setting', $task->workplace_setting, 'taskmate.risk_questions.workplace_setting', $points['workplace_setting'] ?? []);
        }

        $keywordHits = $this->findKeywordHits($task);
        $keywordRaw = count($keywordHits) * (int) ($points['keyword_per_hit'] ?? 0);
        $keywordPoints = min($keywordRaw, max(0, (int) ($rules['keyword_max_points'] ?? 0)));
        $this->addFactor($factors, 'keywords', 'Keyword indicators', $keywordPoints, $keywordHits === []
            ? 'No configured keyword indicators were found.'
            : count($keywordHits).' configured keyword indicator(s) were found; keyword points are capped by the provisional rules.');

        $score = array_sum(array_column($factors, 'points'));
        $nonKeywordScore = array_sum(array_column(array_filter($factors, fn (array $factor) => $factor['key'] !== 'keywords'), 'points'));
        $hardTriggers = $this->findHardTriggers($task, $rules['hard_triggers'] ?? []);
        $hasProhibitedKeyword = collect($keywordHits)->contains(fn (array $hit) => $hit['type'] === 'prohibited');

        $moderateFrom = (int) ($rules['thresholds']['moderate_from'] ?? PHP_INT_MAX);
        $highFrom = (int) ($rules['thresholds']['high_from'] ?? PHP_INT_MAX);
        $level = $score >= $highFrom ? RiskLevel::High : ($score >= $moderateFrom ? RiskLevel::Moderate : RiskLevel::Low);

        // Keyword evidence can raise a task to moderate, but cannot be the sole reason for a high rating.
        if ($nonKeywordScore === 0 && $keywordPoints > 0 && $level === RiskLevel::High) {
            $level = RiskLevel::Moderate;
        }
        if ($hasProhibitedKeyword && $level === RiskLevel::Low) {
            $level = RiskLevel::Moderate;
        }
        if ($hardTriggers !== []) {
            $level = RiskLevel::High;
        }

        $outcome = $this->outcome($task, $level, $hardTriggers, $keywordHits);

        return new RiskAssessment($level, $score, $factors, $keywordHits, $hardTriggers, $outcome);
    }

    private function addChoiceFactor(array &$factors, string $key, string $label, ?string $value, string $optionsConfig, array $pointMap): void
    {
        $optionLabel = config($optionsConfig.'.'.$value, $value ?? 'Not provided');
        $this->addFactor($factors, $key, $label, $pointMap[$value] ?? 0, "Selected answer: {$optionLabel}.");
    }

    private function addFactor(array &$factors, string $key, string $label, mixed $points, string $reason): void
    {
        $factors[] = ['key' => $key, 'label' => $label, 'points' => (int) $points, 'reason' => $reason];
    }

    private function findKeywordHits(Task $task): array
    {
        $textFields = [
            'title' => $task->title,
            'description' => $task->description,
            'required_activities' => $task->required_activities,
            'deliverables' => $task->deliverables,
            'skill_requirements' => $task->skill_requirements,
        ];
        $keywords = [
            ...array_map(fn (string $phrase) => ['phrase' => $phrase, 'type' => 'prohibited'], config('taskmate.prohibited_keywords', [])),
            ...array_map(fn (string $phrase) => ['phrase' => $phrase, 'type' => 'risk'], config('taskmate.risk_keywords', [])),
        ];
        $hits = [];

        foreach ($textFields as $field => $text) {
            if (! is_string($text) || $text === '') {
                continue;
            }

            foreach ($keywords as $keyword) {
                $phrase = trim($keyword['phrase']);
                if ($phrase === '') {
                    continue;
                }

                $pattern = '/(?<![\pL\pN_])'.preg_quote($phrase, '/').'(?![\pL\pN_])/iu';
                if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as [$match, $offset]) {
                        $hits[] = [
                            'phrase' => $match,
                            'type' => $keyword['type'],
                            'field' => $field,
                            'start' => $this->unicodeOffset($text, $offset),
                            'length' => mb_strlen($match),
                        ];
                    }
                }
            }
        }

        return $hits;
    }

    private function unicodeOffset(string $text, int $byteOffset): int
    {
        return (int) (strlen(mb_convert_encoding(substr($text, 0, $byteOffset), 'UTF-16LE', 'UTF-8')) / 2);
    }

    private function findHardTriggers(Task $task, array $configuration): array
    {
        $triggers = [];
        $prohibitedCategories = config('taskmate.prohibited_categories', $configuration['prohibited_categories'] ?? []);
        if (in_array($task->category, $prohibitedCategories, true)) {
            $triggers[] = ['key' => 'prohibited_category', 'reason' => 'This category is on the provisional prohibited category list.'];
        }

        $prohibitedAcademicValue = $configuration['academic_involvement'] ?? 'produces_graded_work';
        if ($task->academic_involvement === $prohibitedAcademicValue) {
            $triggers[] = ['key' => 'academic_involvement', 'reason' => 'This task involves producing another person’s graded work.'];
        }

        return $triggers;
    }

    private function outcome(Task $task, RiskLevel $level, array $hardTriggers, array $keywordHits): string
    {
        if ($level === RiskLevel::High || $hardTriggers !== []) {
            return config('taskmate.high_risk_action', 'escalate') === 'block' ? 'blocked' : 'escalated';
        }
        if ($level === RiskLevel::Moderate) {
            return 'needs_review';
        }

        $poster = $task->relationLoaded('poster') ? $task->getRelation('poster') : null;
        $autoApprovalEligible = (bool) config('taskmate.auto_approval_enabled', false)
            && $poster?->isVerified()
            && $task->guidelines_confirmed_at !== null
            && $keywordHits === []
            && ($task->work_mode === 'remote'
                || (! config('taskmate.in_person_requires_admin_review', true)
                    && filled($task->workplace_name)
                    && filled($task->workplace_address)));

        return $autoApprovalEligible ? 'auto_approved' : 'needs_review';
    }
}
