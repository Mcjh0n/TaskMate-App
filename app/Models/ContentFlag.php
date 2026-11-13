<?php

namespace App\Models;

use App\Enums\ContentFlagStatus;
use Database\Factories\ContentFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class ContentFlag extends Model
{
    /** @use HasFactory<ContentFlagFactory> */
    use HasFactory;

    public const UPDATED_AT = null;
    protected $guarded = ['*'];
    protected function casts(): array { return ['matched' => 'array', 'status' => ContentFlagStatus::class, 'reviewed_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Content flags cannot be deleted.'));
        static::updating(function (ContentFlag $flag): void {
            if (array_diff(array_keys($flag->getDirty()), ['status', 'reviewed_by', 'reviewed_at', 'review_note'])) throw new LogicException('Content flag details are immutable.');
        });
    }
    public function flaggable(): MorphTo { return $this->morphTo(); }
    public function sourceUser(): BelongsTo { return $this->belongsTo(User::class, 'source_user_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function adminActions(): MorphMany { return $this->morphMany(AdminAction::class, 'subject'); }
}
