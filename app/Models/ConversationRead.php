<?php

namespace App\Models;

use Database\Factories\ConversationReadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationRead extends Model
{
    /** @use HasFactory<ConversationReadFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class, 'last_read_message_id');
    }
}
