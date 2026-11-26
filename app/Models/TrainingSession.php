<?php

namespace App\Models;

use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    protected $fillable = ['training_program_id', 'training_center_id', 'trainer_id', 'starts_at', 'ends_at', 'training_dates', 'skip_weekends', 'non_working_dates', 'capacity', 'status', 'created_by', 'is_demo'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'training_dates' => 'array', 'skip_weekends' => 'boolean', 'non_working_dates' => 'array', 'capacity' => 'integer', 'is_demo' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(TrainingCenter::class, 'training_center_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }
}
