<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'evaluation_id',
        'user_name',
        'participant_name',
        'action',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
