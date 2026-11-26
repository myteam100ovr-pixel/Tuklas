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

    public function hasCompletedSkillsAssessment(): bool
    {
        return is_array($this->interests) && $this->interests !== [] && is_array($this->assessment_preferences);
    }

    /** @return array<int, string>|null */
    public function assessmentSkills(): ?array
    {
        $preferences = $this->assessment_preferences ?? [];
        if (! array_key_exists('assessment_skills', $preferences) || ! is_array($preferences['assessment_skills'])) {
            return null;
        }

        return collect($preferences['assessment_skills'])
            ->filter(fn (mixed $skill): bool => is_string($skill) && trim($skill) !== '')
            ->map(fn (string $skill): string => trim($skill))
            ->unique()->values()->all();
    }

    /** @return array<string, array<int, string>> */
    public function careerPreferences(): array
    {
        $preferences = [];
        $assessmentPreferences = collect($this->assessment_preferences ?? [])->except('assessment_skills')->all();
        foreach (['interests' => $this->interests, ...$assessmentPreferences] as $key => $value) {
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
