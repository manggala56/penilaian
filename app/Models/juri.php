<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Many-to-many relationship dengan categories
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'juri_category')
                    ->withTimestamps();
    }

    // Relasi langsung dengan evaluations melalui user_id
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'user_id', 'user_id');
    }

    // Accessor untuk jumlah evaluasi saat ini
    public function getCurrentEvaluationsCountAttribute(): int
    {
        // Gunakan count() langsung pada query untuk menghindari N+1 problem
        return $this->evaluations()->count();
    }

    // Accessor untuk nama kategori yang bisa dinilai
    public function getCategoryNamesAttribute(): string
    {
        if ($this->can_judge_all_categories) {
            return 'Semua Kategori';
        }

        if ($this->relationLoaded('categories')) {
            return $this->categories->isNotEmpty()
                ? $this->categories->pluck('name')->join(', ')
                : 'Tidak ada kategori';
        }

        // Jika categories tidak diload, ambil data secara eager
        return $this->categories()->pluck('name')->join(', ') ?: 'Tidak ada kategori';
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
        // Jika juri universal, bisa menilai semua kategori
        if ($this->can_judge_all_categories) {
            return true;
        }

        // Cek apakah kategori ada dalam daftar kategori juri
        if ($this->relationLoaded('categories')) {
            return $this->categories->contains('id', $categoryId);
        }

        return $this->categories()->where('categories.id', $categoryId)->exists();
    }

    // Method untuk mendapatkan kategori yang bisa dinilai
    public function getJudgableCategories()
    {
        if ($this->can_judge_all_categories) {
            return Category::all();
        }

        return $this->categories;
    }

    // Method untuk sync categories
    public function syncCategories($categoryIds)
    {
        if (!$this->can_judge_all_categories) {
            return $this->categories()->sync($categoryIds);
        }

        return null;
    }

    // Method untuk menambah kategori
    public function addCategory($categoryId)
    {
        if (!$this->can_judge_all_categories) {
            return $this->categories()->syncWithoutDetaching([$categoryId]);
        }

        return null;
    }

    // Method untuk menghapus kategori
    public function removeCategory($categoryId)
    {
        if (!$this->can_judge_all_categories) {
            return $this->categories()->detach($categoryId);
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

    // Method helper untuk load relationships yang umum digunakan
    public function loadCommonRelations()
    {
        return $this->load([
            'user',
            'categories',
            'evaluations'
        ]);
    }

    // Method untuk mendapatkan juri yang tersedia untuk evaluasi baru
    public static function getAvailableJudges($categoryId = null)
    {
        return static::active()
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->canJudgeCategory($categoryId);
            })
            ->with(['user', 'categories'])
            ->get()
            ->filter(function ($juri) {
                return $juri->canEvaluateMore();
            });
    }
}
