<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'version_number', 'proposed_by', 'deliverables', 'terms', 'final_amount', 'start_date',
        'due_date', 'work_mode', 'workplace_name', 'workplace_address', 'payment_arrangement', 'comment',
    ];

    protected function casts(): array
    {
        return ['final_amount' => 'decimal:2', 'start_date' => 'date', 'due_date' => 'date', 'created_at' => 'datetime'];
    }

    public function agreement(): BelongsTo { return $this->belongsTo(Agreement::class); }
    public function proposer(): BelongsTo { return $this->belongsTo(User::class, 'proposed_by'); }
    public function confirmations(): HasMany { return $this->hasMany(AgreementConfirmation::class); }
}
