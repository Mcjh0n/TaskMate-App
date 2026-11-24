<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TaskCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $delivered_at
 * @property-write Carbon|CarbonImmutable|string|null $delivered_at
 * @property-read Carbon|null $completed_at
 * @property-write Carbon|CarbonImmutable|string|null $completed_at

 * @property-read int|null $completed_by
 * @property-write int|null $completed_by*/
class TaskCompletion extends Model
{
    /** @use HasFactory<TaskCompletionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'agreement_id', 'delivered_at', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
