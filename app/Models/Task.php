<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $poster_id
 * @property TaskStatus $status
 * @property Carbon|null $deadline
 * @property-read Carbon|null $guidelines_confirmed_at
 * @property-write Carbon|CarbonImmutable|string|null $guidelines_confirmed_at
 * @property-read Carbon|null $reviewed_at
 * @property-write Carbon|CarbonImmutable|string|null $reviewed_at

 * @property-read string|null $estimated_duration_hours
 * @property-write string|float|int|null $estimated_duration_hours
 * @property-read int|null $estimated_duration_days
 * @property-write int|string|null $estimated_duration_days
 * @property-read int|null $estimated_duration_value
 * @property-write int|string|null $estimated_duration_value
 * @property-read string|null $estimated_duration_unit
 * @property-write string|null $estimated_duration_unit
 * @property-read string|null $budget
 * @property-write string|float|int|null $budget
 * @property-read string|null $budget_min
 * @property-write string|float|int|null $budget_min
 * @property-read string|null $budget_max
 * @property-write string|float|int|null $budget_max
 * @property-read bool $hazard_exposure
 * @property-write bool|int|string $hazard_exposure
 * @property-read bool $private_meeting_required
 * @property-write bool|int|string $private_meeting_required
 * @property-read Carbon|null $screened_at
 * @property-write Carbon|CarbonImmutable|string|null $screened_at
 * @property-read Carbon|null $removed_at
 * @property-write Carbon|CarbonImmutable|string|null $removed_at
 * @property-read int|null $removed_by
 * @property-write int|null $removed_by
 * @property-read int|null $reviewed_by
 * @property-write int|null $reviewed_by
 * @property-read string $category
 * @property-write string $category
 * @property-read string $work_mode
 * @property-write string $work_mode
 * @property-read string|null $complexity Historical task label.
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'description',
        'required_activities',
        'deliverables',
        'estimated_duration_hours',
        'estimated_duration_days',
        'estimated_duration_value',
        'estimated_duration_unit',
        'skill_requirements',
        'budget',
        'budget_min',
        'budget_max',
        'work_mode',
        'workplace_name',
        'workplace_address',
        'deadline',
        'physical_demand',
        'academic_involvement',
        'hazard_exposure',
        'hazard_description',
        'private_meeting_required',
        'workplace_setting',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'estimated_duration_hours' => 'decimal:1',
            'estimated_duration_days' => 'integer',
            'estimated_duration_value' => 'integer',
            'budget' => 'decimal:2',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'deadline' => 'date',
            'hazard_exposure' => 'boolean',
            'private_meeting_required' => 'boolean',
            'guidelines_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'screened_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    public function budgetDisplay(): string
    {
        $format = fn ($amount): string => '₱'.number_format((float) $amount, 2, '.', ',');

        if ($this->budget !== null) {
            return $format($this->budget);
        }

        if ($this->budget_min !== null && $this->budget_max !== null) {
            return $format($this->budget_min).'–'.$format($this->budget_max);
        }

        return 'Budget not provided';
    }

    public function durationLabel(): ?string
    {
        $value = $this->estimated_duration_value ?? $this->estimated_duration_days;
        $unit = $this->estimated_duration_unit ?? ($this->estimated_duration_days !== null ? 'days' : null);

        if ($value === null || $unit === null) {
            if ($this->estimated_duration_hours !== null) {
                $hours = (float) $this->estimated_duration_hours;

                return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.').' '.($hours === 1.0 ? 'hour' : 'hours');
            }

            return null;
        }

        $label = match ($unit) {
            'minutes' => 'minute',
            'hours' => 'hour',
            default => 'day',
        };

        return $value.' '.$label.($value === 1 ? '' : 's');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    /** @return HasMany<TaskScreening, $this> */
    public function screenings(): HasMany
    {
        return $this->hasMany(TaskScreening::class)->latest('id');
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** @return HasOne<Agreement, $this> */
    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany();
    }

    /** @return HasMany<Agreement, $this> */
    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function hasActiveAgreement(): bool
    {
        return $this->agreements()->whereIn('status', [AgreementStatus::Draft->value, AgreementStatus::Negotiating->value, AgreementStatus::Agreed->value])->exists();
    }

    /** @return HasOneThrough<TaskCompletion, Agreement, $this> */
    public function completion(): HasOneThrough
    {
        return $this->hasOneThrough(TaskCompletion::class, Agreement::class);
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    /** @return HasMany<Rating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** @return MorphMany<ContentFlag, $this> */
    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
