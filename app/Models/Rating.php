<?php

namespace App\Models;

use App\Enums\RatingDirection;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    protected $fillable = ['overall_experience', 'comment'];

    protected function casts(): array
    {
        return ['direction' => RatingDirection::class, 'is_hidden' => 'boolean', 'hidden_at' => 'datetime'];
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

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RatingScore::class);
    }

    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }
}
