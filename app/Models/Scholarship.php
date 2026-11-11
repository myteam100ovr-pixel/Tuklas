<?php

namespace App\Models;

use Database\Factories\ScholarshipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarship extends Model
{
    /** @use HasFactory<ScholarshipFactory> */
    use HasFactory;

    protected $fillable = ['title', 'provider', 'description', 'eligibility', 'requirements', 'assessment_type', 'assessment_details', 'source_url', 'application_url', 'deadline', 'status', 'verified_at', 'published_by', 'is_demo'];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'verified_at' => 'datetime', 'is_demo' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('verified_at')->where(fn (Builder $query): Builder => $query->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }
}
