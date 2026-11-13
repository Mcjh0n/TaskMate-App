<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Agreement extends Model
{
    protected $fillable = ['cancellation_reason'];

    protected function casts(): array
    {
        return [
            'status' => AgreementStatus::class,
            'locked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo { return $this->belongsTo(Task::class); }
    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function poster(): BelongsTo { return $this->belongsTo(User::class, 'poster_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function currentVersion(): BelongsTo { return $this->belongsTo(AgreementVersion::class, 'current_version_id'); }
    public function versions(): HasMany { return $this->hasMany(AgreementVersion::class)->orderBy('version_number'); }
    public function confirmations(): HasManyThrough { return $this->hasManyThrough(AgreementConfirmation::class, AgreementVersion::class); }
    public function messages(): HasMany { return $this->hasMany(AgreementMessage::class)->orderBy('id'); }
}
