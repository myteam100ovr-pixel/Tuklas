<?php

namespace App\Models;

use Database\Factories\TrainerQualificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerQualification extends Model
{
    /** @use HasFactory<TrainerQualificationFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'qualification_id', 'title', 'nc_level'];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
