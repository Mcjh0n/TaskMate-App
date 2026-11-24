<?php

namespace App\Services;

use App\Models\AgreementMessage;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\ContentFlag;
use App\Models\ConversationMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OffPlatformScanner
{
    public function __construct(private readonly TaskMateNotifications $alerts) {}

    /** @return list<array{category:string,label:string}> */
    public function scan(string $text): array
    {
        if (! config('taskmate.offplatform.enabled', false)) {
            return [];
        }

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

    /**
     * @return list<ContentFlag>
     */
    public function flag(Model $subject, User $author, string $field, string $text): array
    {
        $groups = [];
        foreach ($this->scan($text) as $match) {
            $groups[$match['category']][] = $match['label'];
        }
        $created = [];
        foreach ($groups as $category => $labels) {
            $relation = $subject->morphMany(ContentFlag::class, 'flaggable');
            $flag = $relation->where('category', $category)->first();
            $wasCreated = false;
            if (! $flag) {
                $flag = new ContentFlag;
                $flag->category = $category;
                $flag->source_user_id = $author->id;
                $flag->matched = array_values(array_unique($labels));
                $flag->field = $field;
                $flag->status = 'open';
                $flag->created_at = now();
                $relation->save($flag);
                $wasCreated = true;
            }
            if ($wasCreated) {
                $related = ['flag_id' => $flag->id];
                $related['task_id'] = match (true) {
                    $subject instanceof Task => $subject->id,
                    $subject instanceof Application => $subject->task_id,
                    $subject instanceof AgreementVersion => $subject->agreement()->value('task_id'),
                    $subject instanceof AgreementMessage => $subject->agreement()->value('task_id'),
                    $subject instanceof ConversationMessage => $subject->conversation()->value('task_id'),
                    default => 0,
                };
                if ($related['task_id'] === 0) {
                    unset($related['task_id']);
                }
                $this->alerts->admins('flag.new', route('admin.flags.show', $flag->id, false), $related, actorId: $author->id);
            }
            $created[] = $flag;
        }

        return $created;
    }
}
