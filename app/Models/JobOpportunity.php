<?php

namespace App\Models;

use Database\Factories\JobOpportunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpportunity extends Model
{
    /** @use HasFactory<JobOpportunityFactory> */
    use HasFactory;

    protected $fillable = ['employer_id', 'employer_name', 'employer_verified', 'title', 'compensation', 'location', 'description', 'requirements', 'application_url', 'source_url', 'source_checked_at', 'closes_at', 'status', 'posted_by', 'is_demo'];

    protected function casts(): array
    {
        return ['closes_at' => 'date', 'source_checked_at' => 'date', 'employer_verified' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(fn (Builder $query): Builder => $query->whereNull('closes_at')->orWhereDate('closes_at', '>=', today()));
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function recommendedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'job_recommendations')->withPivot(['hired_at', 'hired_by', 'not_selected_at', 'not_selected_by', 'not_selected_reason'])->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
