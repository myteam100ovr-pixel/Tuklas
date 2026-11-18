<?php

namespace App\Models;

use Database\Factories\ScholarshipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    /** @use HasFactory<ScholarshipFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'provider', 'description', 'eligibility', 'requirements', 'assessment_type', 'assessment_details',
        'source_url', 'application_url', 'deadline', 'status', 'verified_at', 'published_by', 'is_demo',
        'training_center_id', 'training_program_id', 'slot_limit', 'center_approval_status', 'center_approved_by', 'center_approved_at', 'center_approval_notes',
        'trainer_approval_status', 'trainer_approved_by', 'trainer_approved_at', 'trainer_approval_notes', 'submitted_for_approval_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'verified_at' => 'datetime',
            'center_approved_at' => 'datetime',
            'trainer_approved_at' => 'datetime',
            'submitted_for_approval_at' => 'datetime',
            'is_demo' => 'boolean',
            'slot_limit' => 'integer',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(TrainingCenter::class, 'training_center_id');
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function centerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'center_approved_by');
    }

    public function trainerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_approved_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('verified_at')->where(fn (Builder $query): Builder => $query->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }
}
