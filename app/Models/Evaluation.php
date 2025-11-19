<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Evaluation extends Model
{
    protected $fillable = [
        'participant_id',
        'user_id',
        'evaluation_date',
        'notes',
        'final_score',
        'competition_stage_id'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'final_score' => 'decimal:2'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evaluation) {
            if (Auth::check() && !$evaluation->user_id) {
                $evaluation->user_id = Auth::id();
            }
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function calculateFinalScore(): float
    {
        $totalScore = 0;

        foreach ($this->scores as $score) {
            $aspectWeight = $score->aspect->weight / 100;
            $normalizedScore = ($score->score / $score->aspect->max_score) * 100;
            $totalScore += $normalizedScore * $aspectWeight;
        }

        return round($totalScore, 2);
    }
    public function competitionStage(): BelongsTo
    {
        return $this->belongsTo(CompetitionStage::class, 'competition_stage_id');
    }
}
