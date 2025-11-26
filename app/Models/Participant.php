<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'email',
        'phone',
        'institution',
        'innovation_title',
        'innovation_description',
        'documents',
        'is_approved',
        'current_stage_order'
    ];

    protected $casts = [
        'documents' => 'array',
        'is_approved' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function getFinalScoreAttribute(): ?float
    {
        $evaluations = $this->evaluations()->whereNotNull('final_score')->get();

        if ($evaluations->isEmpty()) {
            return null;
        }

        return $evaluations->avg('final_score');
    }

    public function hasBeenEvaluatedBy($userId): bool
    {
        return $this->evaluations()->where('user_id', $userId)->exists();
    }
}
