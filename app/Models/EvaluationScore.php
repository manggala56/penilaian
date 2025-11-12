<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends Model
{
    protected $fillable = [
        'evaluation_id',
        'aspect_id',
        'score',
        'comment'
    ];

    protected $casts = [
        'score' => 'decimal:2'
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(Aspect::class);
    }
}
