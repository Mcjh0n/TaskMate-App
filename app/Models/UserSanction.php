<?php

namespace App\Models;

use App\Enums\SanctionType;
use Database\Factories\UserSanctionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UserSanction extends Model
{
    /** @use HasFactory<UserSanctionFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array { return ['type' => SanctionType::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'acknowledged_at' => 'datetime', 'lifted_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::updating(function (UserSanction $sanction): void {
            if (array_diff(array_keys($sanction->getDirty()), ['acknowledged_at', 'lifted_at', 'lifted_by', 'lift_note', 'updated_at'])) {
                throw new LogicException('Sanction details are immutable; only acknowledgement and lifting may be recorded.');
            }
        });
        static::deleting(fn () => throw new LogicException('Sanction records cannot be deleted.'));
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function flag(): BelongsTo { return $this->belongsTo(ContentFlag::class, 'flag_id'); }
    public function liftedBy(): BelongsTo { return $this->belongsTo(User::class, 'lifted_by'); }
}
