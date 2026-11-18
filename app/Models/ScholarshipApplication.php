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
        'center_approval_status',
        'center_approved_by',
        'center_approved_at',
        'center_approval_notes',
        'trainer_approval_status',
        'trainer_approved_by',
        'trainer_approved_at',
        'trainer_approval_notes',
        'cancelled_at',
    ];

    protected $hidden = ['documents'];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'reviewed_at' => 'datetime',
            'center_approved_at' => 'datetime',
            'trainer_approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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

    public function centerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'center_approved_by');
    }

    public function trainerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_approved_by');
    }
}
