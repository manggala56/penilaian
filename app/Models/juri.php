<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

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

    protected $appends = ['current_evaluations_count', 'category_names'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Many-to-many relationship dengan categories - PERBAIKAN: pastikan nama tabel pivot benar
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'juri_category', 'juri_id', 'category_id')
                    ->withTimestamps();
    }

    // Relasi langsung dengan evaluations
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'user_id', 'user_id');
    }

    // Accessor untuk jumlah evaluasi saat ini
    public function getCurrentEvaluationsCountAttribute(): int
    {
        return $this->evaluations()->count();
    }

    // Accessor untuk nama kategori yang bisa dinilai
    public function getCategoryNamesAttribute(): string
    {
        if ($this->can_judge_all_categories) {
            return 'Semua Kategori';
        }

        if ($this->relationLoaded('categories') && $this->categories->isNotEmpty()) {
            return $this->categories->pluck('name')->join(', ');
        }

        return 'Tidak ada kategori';
    }

    public function canEvaluateMore(): bool
    {
        if ($this->max_evaluations === null) {
            return true;
        }

        return $this->current_evaluations_count < $this->max_evaluations;
    }

    // Scope untuk juri aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    // Scope untuk juri yang bisa menilai kategori tertentu
    public function scopeCanJudgeCategory($query, $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('can_judge_all_categories', true)
              ->orWhereHas('categories', function ($q) use ($categoryId) {
                  $q->where('categories.id', $categoryId);
              });
        });
    }

    // Method untuk mengecek apakah juri bisa menilai kategori tertentu
    public function canJudgeCategory($categoryId): bool
    {
        if ($this->can_judge_all_categories) {
            return true;
        }

        if ($this->relationLoaded('categories')) {
            return $this->categories->contains('id', $categoryId);
        }

        return $this->categories()->where('categories.id', $categoryId)->exists();
    }

    // Method untuk mendapatkan kategori yang bisa dinilai
    public function getJudgableCategories(): Collection
    {
        if ($this->can_judge_all_categories) {
            return Category::all();
        }

        return $this->categories;
    }

    // Method untuk sync categories
    public function syncCategories($categoryIds): ?array
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
            if ($juri->can_judge_all_categories) {
                $juri->categories()->detach();
            }
        });

        static::deleting(function ($juri) {
            $juri->categories()->detach();
        });
    }

    // Method untuk mendapatkan nama juri dari relasi user
    public function getNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown';
    }

    // Method untuk mendapatkan email juri dari relasi user
    public function getEmailAttribute(): string
    {
        return $this->user->email ?? 'Unknown';
    }
}
