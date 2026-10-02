<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgram extends Model
{
    protected $fillable = [
        'title', 'nc_level', 'description', 'duration_hours', 'requirements',
        'schedule_note', 'status', 'source_reference', 'last_verified_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['last_verified_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
