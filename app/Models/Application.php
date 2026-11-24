<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/** @property ApplicationStatus $status @property Carbon|null $responded_at
 * @property-read string|null $proposed_amount
 * @property-write string|float|int|null $proposed_amount

 * @property-read int|null $applicant_id
 * @property-write int|null $applicant_id
 * @property-read int|null $task_id
 * @property-write int|null $task_id*/
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = ['message', 'proposed_amount'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'proposed_amount' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    /** @return HasOne<Agreement, $this> */
    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class);
    }

    /** @return HasOne<Conversation, $this> */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /** @return MorphMany<ContentFlag, $this> */
    public function contentFlags(): MorphMany
    {
        return $this->morphMany(ContentFlag::class, 'flaggable');
    }
}
