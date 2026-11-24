<?php

namespace App\Models;

use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Carbon\CarbonImmutable;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $reviewed_at
 * @property-write Carbon|CarbonImmutable|string|null $reviewed_at
 * @property-read Carbon|null $escalated_at
 * @property-write Carbon|CarbonImmutable|string|null $escalated_at

 * @property-read ReportType $type
 * @property-write ReportType|string $type
 * @property-read ReportStatus $status
 * @property-write ReportStatus|string $status
 * @property-read ReportOutcome|null $outcome
 * @property-write ReportOutcome|string|null $outcome

 * @property-read int|null $escalated_by
 * @property-write int|null $escalated_by
 * @property-read int|null $reported_user_id
 * @property-write int|null $reported_user_id
 * @property-read int|null $reporter_id
 * @property-write int|null $reporter_id
 * @property-read int|null $reviewed_by
 * @property-write int|null $reviewed_by
 * @property-read int|null $task_id
 * @property-write int|null $task_id*/
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = ['description'];

    public function isOpenForReview(): bool
    {
        return in_array($this->status, [ReportStatus::Open, ReportStatus::UnderReview], true);
    }

    protected function casts(): array
    {
        return ['type' => ReportType::class, 'status' => ReportStatus::class, 'outcome' => ReportOutcome::class, 'reviewed_at' => 'datetime', 'escalated_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<ReportStatement, $this> */
    public function statements(): HasMany
    {
        return $this->hasMany(ReportStatement::class)->orderBy('id');
    }

    /** @return HasMany<ReportEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(ReportEvidence::class)->orderBy('id');
    }

    /** @return HasMany<ReportMessageSnapshot, $this> */
    public function messageSnapshots(): HasMany
    {
        return $this->hasMany(ReportMessageSnapshot::class)->orderBy('id');
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<UserSanction, $this> */
    public function sanctions(): HasMany
    {
        return $this->hasMany(UserSanction::class);
    }
}
