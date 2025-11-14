<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Juri extends Model
{
    protected $table = 'juri';

    protected $fillable = [
        'user_id',
        'category_id',
        'is_active',
        'expertise',
        'max_evaluations'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Tambahkan appends untuk accessor
    protected $appends = ['current_evaluations_count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function evaluations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Evaluation::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id'
        );
    }

    public function getCurrentEvaluationsCountAttribute(): int
    {
        if ($this->relationLoaded('evaluations')) {
            return $this->evaluations->count();
        }

        return $this->evaluations()->count();
    }

    public function canEvaluateMore(): bool
    {
        if ($this->max_evaluations === null) {
            return true;
        }

        return $this->current_evaluations_count < $this->max_evaluations;
    }
}
