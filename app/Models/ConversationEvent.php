<?php

namespace App\Models;

use Database\Factories\ConversationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property-read int|null $user_id
 * @property-write int|null $user_id*/
class ConversationEvent extends Model
{
    /** @use HasFactory<ConversationEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Conversation history is permanent.'));
        static::deleting(fn () => throw new LogicException('Conversation history is permanent.'));
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
