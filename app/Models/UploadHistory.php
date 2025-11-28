<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadHistory extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'participant_id',
        'email',
        'ip_address',
        'user_agent',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
