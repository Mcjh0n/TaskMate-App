<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementMessage extends Model
{
    public $timestamps = false;
    protected $fillable = ['body'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(Agreement::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
