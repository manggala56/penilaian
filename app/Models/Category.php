<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'competition_id',
        'name',
        'description',
        'max_participants',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function aspects(): HasMany
    {
        return $this->hasMany(Aspect::class)->orderBy('order');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function activeAspects(): HasMany
    {
        return $this->aspects()->where('weight', '>', 0);
    }

    public function getTotalWeightAttribute(): float
    {
        return $this->aspects()->sum('weight');
    }
}
