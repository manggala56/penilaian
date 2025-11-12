<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active'
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
}
