<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

class AgreementVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'version_number', 'proposed_by', 'deliverables', 'terms', 'final_amount', 'start_date',
        'due_date', 'duration_days', 'work_mode', 'workplace_name', 'workplace_address', 'payment_arrangement', 'comment',
    ];

    protected function casts(): array
    {
        return ['final_amount' => 'decimal:2', 'start_date' => 'date', 'due_date' => 'date', 'duration_days' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Agreement versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Agreement versions cannot be deleted.'));
    }

    public function agreement(): BelongsTo { return $this->belongsTo(Agreement::class); }
    public function proposer(): BelongsTo { return $this->belongsTo(User::class, 'proposed_by'); }
    public function confirmations(): HasMany { return $this->hasMany(AgreementConfirmation::class); }
    public function contentFlags(): MorphMany { return $this->morphMany(ContentFlag::class, 'flaggable'); }
}
