<?php

namespace App\Models;

use Database\Factories\TaskScreeningFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TaskScreening extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<TaskScreeningFactory> */
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'task_id', 'risk_level', 'risk_score', 'factors', 'keyword_hits', 'hard_triggers', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'factors' => 'array',
            'keyword_hits' => 'array',
            'hard_triggers' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Task screening history is immutable.'));
        static::deleting(fn () => throw new LogicException('Task screening history is immutable.'));
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
