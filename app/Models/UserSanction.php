<?php

namespace App\Models;

use App\Enums\SanctionType;
use Carbon\CarbonImmutable;
use Database\Factories\UserSanctionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $starts_at
 * @property-write Carbon|CarbonImmutable|string|null $starts_at
 * @property-read Carbon|null $ends_at
 * @property-write Carbon|CarbonImmutable|string|null $ends_at
 * @property-read Carbon|null $acknowledged_at
 * @property-write Carbon|CarbonImmutable|string|null $acknowledged_at
 * @property-read Carbon|null $lifted_at
 * @property-write Carbon|CarbonImmutable|string|null $lifted_at

 * @property-read SanctionType $type
 * @property-write SanctionType|string $type

 * @property-read int|null $issued_by
 * @property-write int|null $issued_by
 * @property-read int|null $lifted_by
 * @property-write int|null $lifted_by
 * @property-read int|null $user_id
 * @property-write int|null $user_id*/
class UserSanction extends Model
{
    /** @use HasFactory<UserSanctionFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['type' => SanctionType::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'acknowledged_at' => 'datetime', 'lifted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (UserSanction $sanction): void {
            if (array_diff(array_keys($sanction->getDirty()), ['acknowledged_at', 'lifted_at', 'lifted_by', 'lift_note', 'updated_at'])) {
                throw new LogicException('Sanction details are immutable; only acknowledgement and lifting may be recorded.');
            }
        });
        static::deleting(fn () => throw new LogicException('Sanction records cannot be deleted.'));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<ContentFlag, $this> */
    public function flag(): BelongsTo
    {
        return $this->belongsTo(ContentFlag::class, 'flag_id');
    }

    /** @return BelongsTo<User, $this> */
    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }
}
