<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class AgreementMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['body'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Agreement messages are insert-only.'));
        static::deleting(fn () => throw new LogicException('Agreement messages cannot be deleted.'));
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
