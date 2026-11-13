<?php

namespace App\Services;

use App\Models\ContentFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OffPlatformScanner
{
    /** @return list<array{category:string,label:string}> */
    public function scan(string $text): array
    {
        $matches = [];
        foreach ((array) config('taskmate.offplatform.patterns', []) as $category => $patterns) {
            foreach ($patterns as $item) {
                $pattern = $item['pattern'] ?? '';
                if ($pattern !== '' && @preg_match($pattern, $text) === 1) {
                    $matches[] = ['category' => $category, 'label' => $item['label'] ?? $category];
                }
            }
        }
        return $matches;
    }

    public function flag(Model $subject, User $author, string $field, string $text): array
    {
        $groups = [];
        foreach ($this->scan($text) as $match) $groups[$match['category']][] = $match['label'];
        $created = [];
        foreach ($groups as $category => $labels) {
            $relation = $subject->morphMany(ContentFlag::class, 'flaggable');
            $flag = $relation->where('category', $category)->first();
            if (! $flag) {
                $flag = new ContentFlag;
                $flag->category = $category;
                $flag->source_user_id = $author->id;
                $flag->matched = array_values(array_unique($labels));
                $flag->field = $field;
                $flag->status = 'open';
                $flag->created_at = now();
                $relation->save($flag);
            }
            $created[] = $flag;
        }
        return $created;
    }
}
