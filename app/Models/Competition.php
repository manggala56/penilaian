<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Competition extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'active_stage_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }
    public function stages(): HasMany
    {
        return $this->hasMany(CompetitionStage::class)->orderBy('stage_order');
    }

    public function activeStage(): BelongsTo
    {
        return $this->belongsTo(CompetitionStage::class, 'active_stage_id');
    }
}
