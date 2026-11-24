<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RatingScore extends Model
{
    public $timestamps = false;

    protected $fillable = ['dimension', 'score'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Rating scores cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Rating scores cannot be deleted.'));
    }

    /** @return BelongsTo<Rating, $this> */
    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }
}
