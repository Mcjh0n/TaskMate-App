<?php

namespace App\Models;

use Database\Factories\TaskCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCompletion extends Model
{
    /** @use HasFactory<TaskCompletionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'agreement_id', 'delivered_at', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function agreement(): BelongsTo { return $this->belongsTo(Agreement::class); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
