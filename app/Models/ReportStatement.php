<?php

namespace App\Models;

use Database\Factories\ReportStatementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportStatement extends Model
{
    /** @use HasFactory<ReportStatementFactory> */
    use HasFactory;

    public $timestamps = false;
    protected $guarded = ['id', 'report_id', 'user_id', 'role', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Report statements are insert-only.'));
        static::deleting(fn () => throw new LogicException('Report statements cannot be deleted.'));
    }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
