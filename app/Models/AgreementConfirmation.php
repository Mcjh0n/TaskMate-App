<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $confirmed_at
 * @property-write Carbon|CarbonImmutable|string|null $confirmed_at

 * @property-read int|null $user_id
 * @property-write int|null $user_id*/
class AgreementConfirmation extends Model
{
    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Agreement confirmations are insert-only.'));
        static::deleting(fn () => throw new LogicException('Agreement confirmations cannot be deleted.'));
    }

    /** @return BelongsTo<AgreementVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AgreementVersion::class, 'agreement_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
