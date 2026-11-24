<?php

namespace App\Models;

use App\Enums\PaymentEventType;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read string|null $amount
 * @property-write string|float|int|null $amount
 * @property-read Carbon|null $paid_on
 * @property-write Carbon|CarbonImmutable|string|null $paid_on
 * @property-read Carbon|null $created_at
 * @property-write Carbon|CarbonImmutable|string|null $created_at

 * @property-read PaymentEventType $event
 * @property-write PaymentEventType|string $event

 * @property-read int|null $actor_id
 * @property-write int|null $actor_id*/
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

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
