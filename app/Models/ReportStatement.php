<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReportStatementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $created_at
 * @property-write Carbon|CarbonImmutable|string|null $created_at

 * @property-read int|null $user_id
 * @property-write int|null $user_id*/
class ReportStatement extends Model
{
    /** @use HasFactory<ReportStatementFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id', 'report_id', 'user_id', 'role', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Report statements are insert-only.'));
        static::deleting(fn () => throw new LogicException('Report statements cannot be deleted.'));
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
