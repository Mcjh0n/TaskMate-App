<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AgreementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $locked_at
 * @property-write Carbon|CarbonImmutable|string|null $locked_at
 * @property-read Carbon|null $cancelled_at
 * @property-write Carbon|CarbonImmutable|string|null $cancelled_at

 * @property-read AgreementStatus $status
 * @property-write AgreementStatus|string $status

 * @property-read int|null $cancelled_by
 * @property-write int|null $cancelled_by
 * @property-read int|null $poster_id
 * @property-write int|null $poster_id
 * @property int $task_id
 * @property int $student_id
 */
class Agreement extends Model
{
    /** @use HasFactory<AgreementFactory> */
    use HasFactory;

    protected $fillable = ['cancellation_reason'];

    protected function casts(): array
    {
        return [
            'status' => AgreementStatus::class,
            'locked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<AgreementVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(AgreementVersion::class, 'current_version_id');
    }

    /** @return HasMany<AgreementVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(AgreementVersion::class)->orderBy('version_number');
    }

    /** @return HasManyThrough<AgreementConfirmation, AgreementVersion, $this> */
    public function confirmations(): HasManyThrough
    {
        return $this->hasManyThrough(AgreementConfirmation::class, AgreementVersion::class);
    }

    /** @return HasMany<AgreementMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AgreementMessage::class)->orderBy('id');
    }

    /** @return HasOne<TaskCompletion, $this> */
    public function completion(): HasOne
    {
        return $this->hasOne(TaskCompletion::class);
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderBy('id');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @return HasMany<Rating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    public function paymentStatus(): PaymentStatus
    {
        $latest = $this->paymentEvents()->reorder()->latest('id')->first();

        return match ($latest?->event) {
            PaymentEventType::MarkedPaid => PaymentStatus::MarkedPaid,
            PaymentEventType::ConfirmedReceived => PaymentStatus::ConfirmedReceived,
            PaymentEventType::ReportedNotReceived => PaymentStatus::NotReceived,
            default => PaymentStatus::Unpaid,
        };
    }

    public function paymentIsSettled(): bool
    {
        $latest = $this->paymentEvents()->reorder()->latest('id')->first();

        return $latest?->event === PaymentEventType::ConfirmedReceived
            && $latest->amount !== null
            && (float) $latest->amount === (float) $this->currentVersion->final_amount;
    }

    public function latestPaymentEvent(): ?PaymentEvent
    {
        return $this->paymentEvents()->reorder()->with('actor:id,name')->latest('id')->first();
    }
}
