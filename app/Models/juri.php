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
        'max_evaluations',
        'can_judge_all_categories' // tambahkan ini
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_judge_all_categories' => 'boolean' // tambahkan ini
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

    // Scope untuk juri universal
    public function scopeUniversal($query)
    {
        return $query->where('can_judge_all_categories', true);
    }

    // Scope untuk juri spesifik
    public function scopeSpecific($query)
    {
        return $query->where('can_judge_all_categories', false);
    }

    // Method untuk mengecek apakah juri bisa menilai kategori tertentu
    public function canJudgeCategory($categoryId): bool
    {
        return $this->can_judge_all_categories || $this->category_id == $categoryId;
    }

    // Method untuk mendapatkan kategori yang bisa dinilai
    public function getJudgableCategories()
    {
        if ($this->can_judge_all_categories) {
            return Category::all();
        }

        return collect([$this->category]);
    }

    // Accessor untuk menampilkan nama kategori (termasuk "Semua Kategori")
    public function getCategoryNameAttribute()
    {
        if ($this->can_judge_all_categories) {
            return 'Semua Kategori';
        }

        return $this->category ? $this->category->name : 'Tidak ada kategori';
    }
}
