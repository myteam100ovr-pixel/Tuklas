<?php

namespace App\Models;

use Database\Factories\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'nc_level', 'description', 'duration_hours', 'duration_days', 'requirements',
        'schedule_note', 'status', 'source_reference', 'last_verified_at', 'created_by', 'training_center_id', 'is_demo',
    ];

    protected function casts(): array
    {
        return ['last_verified_at' => 'datetime', 'is_demo' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(TrainingCenter::class, 'training_center_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
