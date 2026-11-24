<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read string|null $final_amount
 * @property-write string|float|int|null $final_amount
 * @property-read Carbon|null $start_date
 * @property-write Carbon|CarbonImmutable|string|null $start_date
 * @property-read Carbon|null $due_date
 * @property-write Carbon|CarbonImmutable|string|null $due_date
 * @property-read int|null $duration_days
 * @property-write int|string|null $duration_days
 * @property-read int|null $duration_value
 * @property-write int|string|null $duration_value
 * @property-read string|null $duration_unit
 * @property-write string|null $duration_unit
 * @property-read Carbon|null $created_at
 * @property-write Carbon|CarbonImmutable|string|null $created_at

 * @property-read int|null $proposed_by
 * @property-write int|null $proposed_by
 * @property-read int|null $version_number
 * @property-write int|null $version_number
 * @property-read string $work_mode
 * @property-write string $work_mode*/
class AgreementVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'version_number', 'proposed_by', 'deliverables', 'terms', 'final_amount', 'start_date',
        'due_date', 'duration_days', 'duration_value', 'duration_unit', 'work_mode', 'workplace_name', 'workplace_address', 'payment_arrangement', 'comment',
    ];

    protected function casts(): array
    {
        return ['final_amount' => 'decimal:2', 'start_date' => 'date', 'due_date' => 'date', 'duration_days' => 'integer', 'duration_value' => 'integer', 'created_at' => 'datetime'];
    }

    public function durationLabel(): ?string
    {
        $value = $this->duration_value ?? $this->duration_days;
        $unit = $this->duration_unit ?? ($this->duration_days !== null ? 'days' : null);

        if ($value === null || $unit === null) {
            return null;
        }

        $label = match ($unit) {
            'minutes' => 'minute',
            'hours' => 'hour',
            default => 'day',
        };

        return $value.' '.$label.($value === 1 ? '' : 's');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Agreement versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Agreement versions cannot be deleted.'));
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /** @return HasMany<AgreementConfirmation, $this> */
    public function confirmations(): HasMany
    {
        return $this->hasMany(AgreementConfirmation::class);
    }

    /** @return MorphMany<ContentFlag, $this> */
    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
