<?php

namespace App\Models;

use App\Enums\PaymentEventType;
use Database\Factories\PaymentEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PaymentEvent extends Model
{
    /** @use HasFactory<PaymentEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id', 'agreement_id', 'actor_id', 'event', 'amount', 'paid_on', 'method', 'created_at'];

    protected function casts(): array
    {
        return ['event' => PaymentEventType::class, 'amount' => 'decimal:2', 'paid_on' => 'date', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Payment event records are insert-only.'));
        static::deleting(fn () => throw new LogicException('Payment event records cannot be deleted.'));
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
