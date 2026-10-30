<?php

namespace App\Models;

use Database\Factories\TrainingAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends Model
{
    /** @use HasFactory<TrainingAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['training_program_id', 'user_id'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
