<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Services\SanctionService;
use Carbon\CarbonImmutable;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $closed_at
 * @property-write Carbon|CarbonImmutable|string|null $closed_at

 * @property-read int|null $closed_by
 * @property-write int|null $closed_by
 * @property-read int|null $poster_id
 * @property-write int|null $poster_id
 * @property int $task_id
 * @property int $applicant_id
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    /** @return BelongsTo<User, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<ConversationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('id');
    }

    /** @return HasOne<ConversationMessage, $this> */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ConversationMessage::class)->latestOfMany();
    }

    /** @return HasMany<ConversationRead, $this> */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class);
    }

    /** @return HasMany<ConversationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ConversationEvent::class)->orderBy('id');
    }

    public function isParticipant(User $user): bool
    {
        return ! $user->isAdmin() && in_array($user->id, [$this->poster_id, $this->applicant_id], true);
    }

    /** Returns why sending is blocked, or null when this participant may send now. */
    public function sendingBlockedReason(User $sender): ?string
    {
        if ($sender->isAdmin()) {
            return 'Administrators cannot access or send messages in private conversations.';
        }
        if (! in_array($sender->id, [$this->poster_id, $this->applicant_id], true)) {
            return 'Only the task poster and applicant can use this conversation.';
        }
        if (! $sender->isVerified()) {
            return 'Your account must be verified before you can send messages.';
        }

        $sanctions = app(SanctionService::class);
        if ($sanctions->banned($sender)) {
            return 'Messaging is unavailable while this account is banned.';
        }
        if ($sanctions->suspended($sender)) {
            return 'Messaging is unavailable while this account is suspended. You can still read this conversation.';
        }
        if ($this->closed_at) {
            $closer = $this->closed_by === $sender->id ? 'You closed this conversation.' : 'The other participant closed this conversation.';

            return $closer.' Only the person who closed it can reopen it.';
        }

        $application = $this->application()->first();
        if (! $application) {
            return 'This conversation is unavailable because its application no longer exists.';
        }
        if (in_array($application->status, [ApplicationStatus::Declined, ApplicationStatus::Withdrawn, ApplicationStatus::Cancelled], true)) {
            return match ($application->status) {
                ApplicationStatus::Declined => 'This conversation is read-only because the application was declined.',
                ApplicationStatus::Withdrawn => 'This conversation is read-only because the application was withdrawn.',
                default => 'This conversation is read-only because the agreement or application was cancelled.',
            };
        }

        $task = $this->task()->first();
        if (! $task) {
            return 'This conversation is unavailable because its task no longer exists.';
        }
        if (in_array($task->status, [TaskStatus::Cancelled, TaskStatus::Rejected, TaskStatus::Removed], true)) {
            return match ($task->status) {
                TaskStatus::Cancelled => 'This conversation is read-only because the task was cancelled.',
                TaskStatus::Rejected => 'This conversation is read-only because the task was rejected.',
                default => 'This conversation is read-only because the task was removed.',
            };
        }

        return null;
    }

    public function hasUnreadMessagesFor(User $user): bool
    {
        $read = $this->relationLoaded('reads')
            ? $this->reads->firstWhere('user_id', $user->id)
            : $this->reads()->where('user_id', $user->id)->first();
        $lastRead = $read->last_read_message_id ?? 0;

        return $this->messages()->where('sender_id', '!=', $user->id)->where('id', '>', $lastRead)->exists();
    }

    public static function displayName(User $user): string
    {
        return $user->isOrganization() ? (string) $user->organization_name : $user->name;
    }
}
