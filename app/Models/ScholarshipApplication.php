<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipApplication extends Model
{
    protected $fillable = [
        'scholarship_id',
        'user_id',
        'application_message',
        'documents',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $hidden = ['documents'];

    protected function casts(): array
    {
        return ['documents' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
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
