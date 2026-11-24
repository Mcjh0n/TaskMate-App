<?php

namespace App\Models;

use App\Enums\ContentFlagStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ContentFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $reviewed_at
 * @property-write Carbon|CarbonImmutable|string|null $reviewed_at

 * @property-read ContentFlagStatus $status
 * @property-write ContentFlagStatus|string $status

 * @property-read list<string> $matched
 * @property-write list<string> $matched
 * @property-read int|null $reviewed_by
 * @property-write int|null $reviewed_by
 * @property-read int|null $source_user_id
 * @property-write int|null $source_user_id
 * @property-read string $category
 * @property-write string $category
 */
class ContentFlag extends Model
{
    /** @use HasFactory<ContentFlagFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['matched' => 'array', 'status' => ContentFlagStatus::class, 'reviewed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Content flags cannot be deleted.'));
        static::updating(function (ContentFlag $flag): void {
            if (array_diff(array_keys($flag->getDirty()), ['status', 'reviewed_by', 'reviewed_at', 'review_note'])) {
                throw new LogicException('Content flag details are immutable.');
            }
        });
    }

    /** @return MorphTo<Model, $this> */
    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }
}
