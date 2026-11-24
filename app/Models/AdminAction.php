<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property-read Carbon|null $created_at
 * @property-write Carbon|CarbonImmutable|string|null $created_at

 * @property-read int|null $admin_id
 * @property-write int|null $admin_id
 * @property-read array<string, mixed>|null $metadata
 * @property-write array<string, mixed>|null $metadata*/
#[Fillable(['admin_id', 'action', 'note', 'metadata'])]
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'admin_actions';

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Admin action records are insert-only.'));
        static::deleting(fn () => throw new LogicException('Admin action records cannot be deleted.'));
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
