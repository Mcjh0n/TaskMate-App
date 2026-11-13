<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'skill_requirements',
        'complexity',
        'budget',
        'work_mode',
        'workplace_name',
        'workplace_address',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'estimated_duration_hours' => 'decimal:1',
            'budget' => 'decimal:2',
            'deadline' => 'date',
            'guidelines_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
