<?php

namespace App\Models;

use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'job_opportunity_id',
        'user_id',
        'qualification_summary',
        'application_message',
        'status',
        'decision_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobOpportunity::class, 'job_opportunity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
