<?php

namespace App\Models;

use Database\Factories\ReportEvidenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportEvidence extends Model
{
    /** @use HasFactory<ReportEvidenceFactory> */
    use HasFactory;

    public $timestamps = false;
    protected $guarded = ['id', 'report_id', 'uploaded_by', 'stored_path', 'created_at'];
    protected function casts(): array { return ['size_bytes' => 'integer', 'created_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Report evidence is insert-only.'));
        static::deleting(fn () => throw new LogicException('Report evidence cannot be deleted.'));
    }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
