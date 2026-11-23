<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
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
 * @property Carbon|null $guidelines_confirmed_at
 * @property Carbon|null $reviewed_at
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
        'skill_requirements',
        'complexity',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(TaskScreening::class)->latest('id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany();
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function hasActiveAgreement(): bool
    {
        return $this->agreements()->whereIn('status', [AgreementStatus::Draft->value, AgreementStatus::Negotiating->value, AgreementStatus::Agreed->value])->exists();
    }

    public function completion(): HasOneThrough
    {
        return $this->hasOneThrough(TaskCompletion::class, Agreement::class);
    }

    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
