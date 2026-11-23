<?php

namespace App\Models;

use Database\Factories\ConversationMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class ConversationMessage extends Model
{
    /** @use HasFactory<ConversationMessageFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['body'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Conversation messages are permanent and cannot be edited.'));
        static::deleting(fn () => throw new LogicException('Conversation messages are permanent and cannot be deleted.'));
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
