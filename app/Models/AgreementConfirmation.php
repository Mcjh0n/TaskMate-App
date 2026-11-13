<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AgreementConfirmation extends Model
{
    public $timestamps = false;
    protected $fillable = [];
    protected function casts(): array { return ['confirmed_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Agreement confirmations are insert-only.'));
        static::deleting(fn () => throw new LogicException('Agreement confirmations cannot be deleted.'));
    }
    public function version(): BelongsTo { return $this->belongsTo(AgreementVersion::class, 'agreement_version_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
