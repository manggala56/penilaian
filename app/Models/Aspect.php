<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aspect extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'weight',
        'max_score',
        'order'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'integer'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function evaluationScores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
