<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TaskScreeningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $created_at
 * @property-write Carbon|CarbonImmutable|string|null $created_at
 */
class TaskScreening extends Model
{
    /** @use HasFactory<TaskScreeningFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['task_id', 'factors', 'keyword_hits', 'hard_triggers'];

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

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
