<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Competition extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

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

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($competition) {
            // When competition is soft deleted, delete all categories individually to trigger events
            $competition->categories->each(function ($category) {
                $category->delete();
            });
        });

        static::restored(function ($competition) {
            // When competition is restored, restore all categories individually
            $competition->categories()->withTrashed()->get()->each(function ($category) {
                $category->restore();
            });
        });
    }

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
