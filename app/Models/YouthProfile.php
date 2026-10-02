<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouthProfile extends Model
{
    public const EDUCATION = [
        'Elementary graduate',
        'Junior high school undergraduate',
        'Junior high school graduate',
        'Senior high school undergraduate',
        'Senior high school graduate',
        'Vocational or TVET graduate',
        'College undergraduate',
        'College graduate',
        'Postgraduate',
    ];

    public const EMPLOYMENT = ['Student', 'Employed', 'Self-employed', 'Unemployed', 'Out of school'];

    protected $fillable = [
        'barangay', 'contact_number', 'educational_attainment', 'employment_status',
        'livelihood_interests', 'interests', 'skills', 'credentials',
        'guardian_name', 'guardian_relationship', 'guardian_contact',
    ];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'skills' => 'array',
            'credentials' => 'array',
            'scan_consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
