<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReportMessageSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $sent_at
 * @property-write Carbon|CarbonImmutable|string|null $sent_at
 * @property-read bool $is_reported_message
 * @property-write bool|int|string $is_reported_message

 * @property-read int|null $sender_id
 * @property-write int|null $sender_id*/
class ReportMessageSnapshot extends Model
{
    /** @use HasFactory<ReportMessageSnapshotFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'is_reported_message' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Reported message snapshots are permanent.'));
        static::deleting(fn () => throw new LogicException('Reported message snapshots are permanent.'));
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<ConversationMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
