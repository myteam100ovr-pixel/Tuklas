<?php

namespace App\Models;

use Database\Factories\JobOpportunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobOpportunity extends Model
{
    /** @use HasFactory<JobOpportunityFactory> */
    use HasFactory;

    protected $fillable = ['employer_id', 'title', 'location', 'description', 'requirements', 'application_url', 'closes_at', 'status', 'posted_by'];

    protected function casts(): array
    {
        return ['closes_at' => 'date'];
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
        return $this->belongsToMany(User::class, 'job_recommendations')->withTimestamps();
    }
}
