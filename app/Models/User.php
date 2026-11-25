<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\UserType;
use App\Enums\VerificationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property-write Carbon|CarbonImmutable|string|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams

 * @property-read Carbon|null $verified_at
 * @property-write Carbon|CarbonImmutable|string|null $verified_at
 * @property-read bool $is_admin
 * @property-write bool|int|string $is_admin

 * @property-read UserType $user_type
 * @property-write UserType|string $user_type
 * @property-read VerificationStatus $verification_status
 * @property-write VerificationStatus|string $verification_status
 * @property int|null $verified_by
 */
#[Fillable(['name', 'email', 'password', 'user_type', 'id_number', 'affiliation', 'organization_name', 'position', 'supporting_info'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    public function hasVerifiedEmail(): bool
    {
        return ! config('taskmate.email_verification_enabled', true)
            || $this->email_verified_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    public function isStudent(): bool
    {
        return $this->user_type === UserType::Student;
    }

    public function isFacultyStaff(): bool
    {
        return $this->user_type === UserType::FacultyStaff;
    }

    public function isOrganization(): bool
    {
        return $this->user_type === UserType::Organization;
    }

    public function canPost(): bool
    {
        if ($this->isAdmin() || ! $this->isVerified()) {
            return false;
        }

        return $this->isFacultyStaff()
            || $this->isOrganization()
            || ($this->isStudent() && (bool) config('taskmate.students_can_post', true));
    }

    public function canApply(): bool
    {
        return ! $this->isAdmin() && $this->isStudent() && $this->isVerified();
    }

    public function canBrowse(): bool
    {
        return ! $this->isAdmin() && $this->isStudent();
    }

    /** @return MorphMany<AdminAction, $this> */
    public function adminActions(): MorphMany
    {
        return $this->morphMany(AdminAction::class, 'subject');
    }

    /** @return HasMany<AdminAction, $this> */
    public function adminActionsPerformed(): HasMany
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'verified_by');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'poster_id');
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'applicant_id');
    }

    /** @return HasMany<Conversation, $this> */
    public function postedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'poster_id');
    }

    /** @return HasMany<Conversation, $this> */
    public function applicantConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'applicant_id');
    }

    /** @return HasMany<Agreement, $this> */
    public function postedAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'poster_id');
    }

    /** @return HasMany<Agreement, $this> */
    public function studentAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'student_id');
    }

    /** @return HasMany<Report, $this> */
    public function filedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /** @return HasMany<Report, $this> */
    public function receivedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    /** @return HasMany<Rating, $this> */
    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    /** @return HasMany<Rating, $this> */
    /** @return HasMany<Rating, $this> */
    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'ratee_id');
    }

    /** @return HasMany<UserSanction, $this> */
    public function sanctions(): HasMany
    {
        return $this->hasMany(UserSanction::class);
    }

    /** @return HasMany<ContentFlag, $this> */
    public function contentFlags(): HasMany
    {
        return $this->hasMany(ContentFlag::class, 'source_user_id');
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'verification_status' => VerificationStatus::class,
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }
}
