<?php

namespace App\Models;

use Database\Factories\ReportMessageSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

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

    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function message(): BelongsTo { return $this->belongsTo(ConversationMessage::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
}
