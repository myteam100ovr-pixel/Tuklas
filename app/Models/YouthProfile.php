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
        'livelihood_interests', 'interests', 'skills', 'credentials', 'assessment_preferences',
        'guardian_name', 'guardian_relationship', 'guardian_contact',
    ];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'skills' => 'array',
            'credentials' => 'array',
            'assessment_preferences' => 'array',
            'scan_consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, array<int, string>> */
    public function careerPreferences(): array
    {
        $preferences = [];
        foreach (['interests' => $this->interests, ...($this->assessment_preferences ?? [])] as $key => $value) {
            $values = is_array($value) ? $value : [$value];
            $clean = collect($values)->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): string => mb_substr(trim($item), 0, 500))->take(12)->values()->all();
            if ($clean !== []) {
                $preferences['user_preference_'.$key] = $clean;
            }
        }

        return $preferences;
    }
}
