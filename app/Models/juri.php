<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Juri extends Model
{
    protected $table = 'juri';

    protected $fillable = [
        'user_id',
        'is_active',
        'expertise',
        'max_evaluations',
        'can_judge_all_categories'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_judge_all_categories' => 'boolean'
    ];

    protected $appends = ['current_evaluations_count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Many-to-many relationship dengan categories
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'juri_category')
                    ->withTimestamps();
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
        return $this->can_judge_all_categories ||
            $this->categories()->where('categories.id', $categoryId)->exists();
    }

    // Method untuk mendapatkan kategori yang bisa dinilai
    public function getJudgableCategories()
    {
        if ($this->can_judge_all_categories) {
            return Category::all();
        }

        return $this->categories;
    }

    // Accessor untuk menampilkan nama kategori (termasuk "Semua Kategori" atau daftar kategori)
    public function getCategoryNameAttribute()
    {
        if ($this->can_judge_all_categories) {
            return 'Semua Kategori';
        }

        if ($this->relationLoaded('categories') && $this->categories->isNotEmpty()) {
            return $this->categories->pluck('name')->join(', ');
        }

        return 'Tidak ada kategori';
    }

    // Method untuk sync categories
    public function syncCategories($categoryIds)
    {
        if (!$this->can_judge_all_categories) {
            return $this->categories()->sync($categoryIds);
        }

        return null;
    }

    // Validasi sebelum save
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($juri) {
            // Jika bisa menilai semua kategori, hapus semua kategori spesifik
            if ($juri->can_judge_all_categories) {
                $juri->categories()->detach();
            }
        });

        static::deleting(function ($juri) {
            $juri->categories()->detach();
        });
    }
}
