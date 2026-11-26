<?php

namespace App\Services;

use App\Models\CareerPath;
use App\Models\JobOpportunity;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Eloquent\Builder;

class StarterCareerRecommendations
{
    public function __construct(private CareerPathSkillMatcher $careerSkillMatcher) {}

    private const INTEREST_TERMS = [
        'Information technology' => ['computer', 'information technology', 'software', 'network', 'programming', 'digital'],
        'Hospitality and tourism' => ['hospitality', 'tourism', 'food', 'beverage', 'cookery', 'bread', 'pastry', 'housekeeping'],
        'Food and beverage' => ['food', 'beverage', 'cookery', 'bread', 'pastry', 'baking', 'culinary'],
        'Cooking and baking' => ['food', 'beverage', 'cookery', 'bread', 'pastry', 'baking', 'culinary'],
        'Electronics' => ['electronic', 'electrical', 'mechatronic', 'solar', 'repair'],
        'Agriculture' => ['agriculture', 'organic', 'farm', 'crop', 'livestock', 'gardening'],
        'Entrepreneurship' => ['business', 'entrepreneur', 'bookkeeping', 'sales', 'food', 'bread', 'pastry'],
        'Construction' => ['construction', 'masonry', 'carpentry', 'plumbing', 'welding', 'electrical'],
        'Health and caregiving' => ['health', 'caregiving', 'care', 'medical', 'wellness'],
        'Arts and design' => ['arts', 'design', 'creative', 'graphics', 'dressmaking', 'sewing'],
        'Automotive' => ['automotive', 'motorcycle', 'vehicle', 'engine', 'mechanic', 'servicing'],
        'Business and finance' => ['business', 'finance', 'bookkeeping', 'accounting', 'office', 'sales'],
        'Logistics' => ['logistics', 'warehouse', 'supply', 'driving', 'delivery', 'forklift'],
        'Education and teaching' => ['education', 'teaching', 'trainer', 'training', 'childcare', 'tutor'],
        'Community and public service' => ['community', 'public service', 'government', 'social work', 'community development'],
        'Beauty and wellness' => ['beauty', 'wellness', 'hair', 'skin', 'salon', 'massage'],
        'Sports and fitness' => ['sports', 'fitness', 'physical', 'coaching', 'wellness'],
        'Environment and sustainability' => ['environment', 'sustainability', 'conservation', 'waste', 'organic', 'agriculture'],
        'Manufacturing and production' => ['manufacturing', 'production', 'assembly', 'quality', 'welding', 'electronics'],
        'Transportation and delivery' => ['transportation', 'delivery', 'driving', 'logistics', 'vehicle'],
        'Events and customer experience' => ['events', 'hospitality', 'tourism', 'customer service', 'food', 'beverage'],
        'Office and administration' => ['office', 'administration', 'bookkeeping', 'computer', 'data entry', 'business'],
        'Science and laboratory work' => ['science', 'laboratory', 'medical', 'health', 'research'],
        'Creative media and communication' => ['creative', 'media', 'design', 'graphics', 'writing', 'communication'],
        'Marine and fisheries' => ['marine', 'fisheries', 'fish', 'aquaculture', 'agriculture'],
        'Security and safety' => ['security', 'safety', 'first aid', 'health', 'construction'],
        'Fashion and garment making' => ['fashion', 'dressmaking', 'sewing', 'textile', 'design'],
        'Retail and customer service' => ['retail', 'sales', 'customer service', 'cashier', 'business'],
        'Animal care' => ['animal', 'livestock', 'agriculture', 'caregiving'],
        'Plumbing and building services' => ['plumbing', 'construction', 'carpentry', 'building'],
        'Childcare and family support' => ['childcare', 'child care', 'caregiving', 'education', 'health'],
    ];

    /** @return array{work: array<int, array<string, mixed>>, careers: array<int, array<string, mixed>>, training: array<int, array<string, mixed>>} */
    public function for(User $user): array
    {
        $profile = $user->youthProfile;
        $terms = collect($profile?->interests ?? [])
            ->flatMap(fn (string $interest): array => self::INTEREST_TERMS[$interest] ?? [$interest])
            ->merge($this->skillTerms($profile))
            ->merge($this->skillTermsFromAssessment($profile))
            ->map(fn (string $term): string => mb_strtolower(trim($term)))
            ->filter(fn (string $term): bool => $term !== '')
            ->unique()->values()->all();

        $careerTerms = collect($profile?->interests ?? [])
            ->flatMap(fn (string $interest): array => self::INTEREST_TERMS[$interest] ?? [$interest])
            ->merge($this->skillTermsFromAssessment($profile))
            ->map(fn (string $term): string => mb_strtolower(trim($term)))
            ->filter(fn (string $term): bool => $term !== '')
            ->unique()->values()->all();

        if ($terms === []) {
            return ['work' => [], 'careers' => [], 'training' => []];
        }

        $trainingPrograms = TrainingProgram::published()->where('is_demo', false)
            ->with('center:id,name,is_active,is_demo')
            ->whereHas('center', fn (Builder $center): Builder => $center->where('is_active', true)->where('is_demo', false))
            ->where(fn (Builder $query): Builder => $this->matchTerms($query, $terms, ['title', 'description', 'requirements']))
            ->tap(fn (Builder $query): Builder => $this->orderByRelevance($query, $terms, ['title', 'description', 'requirements']))
            ->orderBy('title')->limit(3)
            ->get(['id', 'title', 'description', 'requirements', 'nc_level', 'duration_days', 'duration_hours', 'training_center_id']);

        $trainingRecommendations = $trainingPrograms->map(fn (TrainingProgram $program): array => [
            'title' => $program->title,
            'detail' => collect([
                $program->center?->name,
                $program->duration_days ? $program->duration_days.' days' : null,
                $program->nc_level,
                $this->matchReason($profile, implode(' ', [$program->title, $program->description, $program->requirements])),
            ])->filter()->implode(' · '),
            'url' => route('tesda.index'),
        ])->all();

        if ($trainingRecommendations === []) {
            $trainingRecommendations = collect((new TesdaRegisteredProgramCatalog)->otherRegisteredProviders()['records'])
                ->filter(fn (array $record): bool => collect($terms)->contains(fn (string $term): bool => str_contains(mb_strtolower($record['program']), $term)))
                ->unique(fn (array $record): string => mb_strtolower($record['program'].'|'.$record['provider'].'|'.$record['address']))
                ->sortByDesc(fn (array $record): int => $this->relevanceScore($terms, ['program' => $record['program']]))
                ->take(3)
                ->map(fn (array $record): array => [
                    'title' => $record['program'],
                    'detail' => $record['provider'].' · '.$record['address'].' · '.$this->matchReason($profile, $record['program']).' Confirm current schedule and slots with the provider.',
                    'url' => route('tesda.index'),
                ])->values()->all();
        }

        return [
            'work' => JobOpportunity::published()
                ->where(fn (Builder $query): Builder => $query->where('employer_verified', true)
                    ->orWhereHas('employer', fn (Builder $employer): Builder => $employer->where('is_verified', true)))
                ->where(fn (Builder $query): Builder => $this->matchTerms($query, $terms, ['title', 'description', 'requirements']))
                ->tap(fn (Builder $query): Builder => $this->orderByRelevance($query, $terms, ['title', 'description', 'requirements']))
                ->orderBy('title')->limit(3)->get(['id', 'title', 'employer_name', 'location', 'description', 'requirements'])
                ->map(fn (JobOpportunity $job): array => [
                    'title' => $job->title,
                    'detail' => trim(collect([$job->employer_name, $job->location, $this->matchReason($profile, implode(' ', [$job->title, $job->description, $job->requirements]))])->filter()->implode(' · ')),
                    'url' => route('opportunities.index', ['q' => $job->title]),
                ])->all(),
            'careers' => CareerPath::published()
                ->when($careerTerms !== [], fn (Builder $query): Builder => $query->where(fn (Builder $matches): Builder => $this->matchTerms($matches, $careerTerms, ['title', 'description'])))
                ->when($careerTerms === [], fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
                ->orderBy('title')->limit(100)->get(['title', 'description', 'education_level', 'required_skills'])
                ->map(function (CareerPath $career) use ($profile): array {
                    return [
                        'title' => $career->title,
                        'detail' => trim(collect([$career->education_level, $this->matchReason($profile, $career->title.' '.$career->description)])->filter()->implode(' · ')),
                        'url' => route('careers.index'),
                        'skill_match' => $this->careerSkillMatcher->match($career, $profile),
                    ];
                })
                ->sortByDesc(fn (array $career): int => $career['skill_match']['percent'] ?? -1)
                ->take(3)->values()->all(),
            'training' => $trainingRecommendations,
        ];
    }

    /** @param array<int, string> $terms @param array<int, string> $fields */
    private function matchTerms(Builder $query, array $terms, array $fields): Builder
    {
        return $query->where(function (Builder $matches) use ($terms, $fields): void {
            foreach ($terms as $term) {
                foreach ($fields as $field) {
                    $matches->orWhere($field, 'like', '%'.$term.'%');
                }
            }
        });
    }

    /** @param array<int, string> $terms @param array<int, string> $fields */
    private function orderByRelevance(Builder $query, array $terms, array $fields): Builder
    {
        $scoreParts = [];
        $bindings = [];

        foreach ($terms as $term) {
            foreach ($fields as $field) {
                $weight = $field === 'title' ? 3 : 1;
                $scoreParts[] = 'CASE WHEN LOWER('.$field.') LIKE ? THEN '.$weight.' ELSE 0 END';
                $bindings[] = '%'.mb_strtolower($term).'%';
            }
        }

        if ($scoreParts !== []) {
            $query->orderByRaw('('.implode(' + ', $scoreParts).') DESC', $bindings);
        }

        return $query;
    }

    /** @param array<int, string> $terms @param array<string, string> $fields */
    private function relevanceScore(array $terms, array $fields): int
    {
        $score = 0;
        foreach ($terms as $term) {
            foreach ($fields as $field => $value) {
                if (str_contains(mb_strtolower($value), mb_strtolower($term))) {
                    $score += $field === 'title' || $field === 'program' ? 3 : 1;
                }
            }
        }

        return $score;
    }

    private function matchReason(?YouthProfile $profile, string $text): string
    {
        $normalizedText = mb_strtolower($text);
        foreach ($profile?->interests ?? [] as $interest) {
            $terms = self::INTEREST_TERMS[$interest] ?? [$interest];
            if (collect($terms)->contains(fn (string $term): bool => str_contains($normalizedText, mb_strtolower($term)))) {
                return 'Matches your '.$interest.' interest.';
            }
        }

        foreach ($profile?->skills ?? [] as $skill) {
            if (str_contains($normalizedText, mb_strtolower($this->skillTerm($skill)))) {
                return 'Connects with your '.$skill.' skill.';
            }
        }

        foreach ($profile?->assessmentSkills() ?? [] as $skill) {
            if (str_contains($normalizedText, mb_strtolower($this->skillTerm($skill)))) {
                return 'Connects with your self-reported '.$skill.' assessment skill.';
            }
        }

        return 'Based on your saved interests and skills.';
    }

    /** @return array<int, string> */
    private function skillTerms(?YouthProfile $profile): array
    {
        return collect($profile?->skills ?? [])->map(fn (string $skill): string => $this->skillTerm($skill))->all();
    }

    /** @return array<int, string> */
    private function skillTermsFromAssessment(?YouthProfile $profile): array
    {
        return collect($profile?->assessmentSkills() ?? [])->map(fn (string $skill): string => $this->skillTerm($skill))->all();
    }

    private function skillTerm(string $skill): string
    {
        return match ($skill) {
            'Communication', 'Public speaking' => 'communication',
            'Customer service' => 'customer service',
            'Computer literacy' => 'computer',
            'Food preparation', 'Food safety and hygiene' => 'food',
            'Basic electronics' => 'electronic',
            'Sewing' => 'dressmaking',
            'Gardening', 'Crop production' => 'agriculture',
            'Bookkeeping', 'Financial record keeping' => 'bookkeeping',
            'Teamwork' => 'teamwork',
            'Problem solving', 'Troubleshooting' => 'problem solving',
            'Writing' => 'writing',
            'Design and creativity', 'Social media content' => 'design',
            'Sales' => 'sales',
            'Repair and maintenance' => 'repair',
            'Caregiving', 'Child care', 'Animal care' => 'care',
            'Planning and organization', 'Event coordination' => 'planning',
            'Time management' => 'schedule',
            'Basic mathematics and measurement' => 'measurement',
            'Reading and following procedures' => 'procedure',
            'Cleaning and sanitation' => 'sanitation',
            'Inventory tracking' => 'inventory',
            'Cash handling' => 'cash',
            'Data entry', 'Typing' => 'data entry',
            'Carpentry' => 'carpentry',
            'Welding' => 'welding',
            'Plumbing' => 'plumbing',
            'Driving and navigation' => 'driving',
            'First aid' => 'first aid',
            default => $skill,
        };
    }
}
