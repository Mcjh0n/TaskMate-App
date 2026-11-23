<?php

namespace App\Models;

use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = ['description'];

    protected function casts(): array
    {
        return ['type' => ReportType::class, 'status' => ReportStatus::class, 'outcome' => ReportOutcome::class, 'reviewed_at' => 'datetime', 'escalated_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(ReportStatement::class)->orderBy('id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ReportEvidence::class)->orderBy('id');
    }

    public function messageSnapshots(): HasMany
    {
        return $this->hasMany(ReportMessageSnapshot::class)->orderBy('id');
    }

    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(UserSanction::class);
    }
}
