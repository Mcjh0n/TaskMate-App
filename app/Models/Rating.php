<?php

namespace App\Models;

use App\Enums\RatingDirection;
use Carbon\CarbonImmutable;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read bool $is_hidden
 * @property-write bool|int|string $is_hidden
 * @property-read Carbon|null $hidden_at
 * @property-write Carbon|CarbonImmutable|string|null $hidden_at

 * @property-read RatingDirection $direction
 * @property-write RatingDirection|string $direction

 * @property-read int|null $hidden_by
 * @property-write int|null $hidden_by
 * @property-read int|null $ratee_id
 * @property-write int|null $ratee_id
 * @property-read int|null $rater_id
 * @property-write int|null $rater_id
 * @property-read int|null $task_id
 * @property-write int|null $task_id
 */
class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    protected $fillable = ['overall_experience', 'comment'];

    protected function casts(): array
    {
        return ['direction' => RatingDirection::class, 'overall_experience' => 'integer', 'is_hidden' => 'boolean', 'hidden_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Rating $rating): void {
            $changed = array_keys($rating->getDirty());
            if (array_diff($changed, ['is_hidden', 'hidden_by', 'hidden_at', 'hidden_reason'])) {
                throw new LogicException('Submitted ratings are immutable.');
            }
            if ($changed && ! auth()->user()?->isAdmin()) {
                throw new LogicException('Only an administrator may moderate a rating.');
            }
        });
        static::deleting(fn () => throw new LogicException('Submitted ratings cannot be deleted.'));
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /** @return BelongsTo<User, $this> */
    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    /** @return HasMany<RatingScore, $this> Historical dimension scores. */
    public function scores(): HasMany
    {
        return $this->hasMany(RatingScore::class);
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }
}
