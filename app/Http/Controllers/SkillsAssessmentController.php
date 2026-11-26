<?php

namespace App\Http\Controllers;

use App\Services\StarterCareerRecommendations;
use App\Support\ActivityNotifier;
use App\Support\CareerSkillCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkillsAssessmentController extends Controller
{
    private const INTERESTS = [
        'Information technology', 'Hospitality and tourism', 'Food and beverage',
        'Cooking and baking', 'Electronics', 'Agriculture', 'Entrepreneurship',
        'Construction', 'Health and caregiving', 'Arts and design', 'Automotive',
        'Business and finance', 'Logistics', 'Education and teaching',
        'Community and public service', 'Beauty and wellness', 'Sports and fitness',
        'Environment and sustainability', 'Manufacturing and production',
        'Transportation and delivery', 'Events and customer experience',
        'Office and administration', 'Science and laboratory work',
        'Creative media and communication', 'Marine and fisheries',
        'Security and safety', 'Fashion and garment making',
        'Retail and customer service', 'Animal care', 'Plumbing and building services',
        'Childcare and family support',
    ];

    private const PREFERENCES = [
        'priority' => ['Find my first job', 'Improve my current skills', 'Change career direction', 'Start a small business', 'Prepare for TESDA assessment', 'Explore my options', 'I am still exploring'],
        'learning_mode' => ['Hands-on / face-to-face', 'Online at my own pace', 'Blended online and in-person', 'Open to any format'],
        'schedule' => ['Weekday mornings', 'Weekday afternoons', 'Evenings', 'Weekends', 'Flexible schedule', 'Not sure yet'],
        'weekly_hours' => ['Less than 5 hours', '5–10 hours', '11–20 hours', 'More than 20 hours', 'Not sure yet'],
        'work_environment' => ['Office or digital work', 'Hands-on workshop', 'Outdoors or in the field', 'Working directly with people', 'Remote work', 'Moving between locations', 'Open to different settings'],
        'support_needs' => ['Free or low-cost training', 'Scholarship information', 'Nearby training providers', 'Flexible schedule', 'Access to a device or internet', 'Help preparing requirements', 'Help choosing a career direction', 'No additional support needed right now'],
    ];

    public function index(Request $request, StarterCareerRecommendations $recommendations): View|JsonResponse
    {
        $profile = $request->user()->youthProfile;
        $assessmentComplete = $profile?->hasCompletedSkillsAssessment() ?? false;
        $data = [
            'skills' => CareerSkillCatalog::OPTIONS,
            'noPriorExperienceOption' => CareerSkillCatalog::NO_PRIOR_EXPERIENCE,
            'interests' => self::INTERESTS,
            'profile' => $profile,
            'preferenceOptions' => self::PREFERENCES,
            'assessmentComplete' => $assessmentComplete,
            'assessmentSkillsRecorded' => $profile?->assessmentSkills() !== null,
            'starterRecommendations' => $assessmentComplete ? $recommendations->for($request->user()) : [],
        ];

        return $request->routeIs('mobile.*')
            ? response()->json($data)
            : view('youth.skills-assessment', [
                ...$data,
                'assessmentRequired' => ! $assessmentComplete,
            ]);
    }

    public function store(Request $request, ActivityNotifier $activity, StarterCareerRecommendations $recommendations): RedirectResponse|JsonResponse
    {
        $rules = [
            'skills' => $request->boolean('no_prior_experience')
                ? ['nullable', 'array', 'max:16']
                : ['required', 'array', 'min:1', 'max:16'],
            'skills.*' => ['string', 'distinct', Rule::in(CareerSkillCatalog::OPTIONS)],
            'no_prior_experience' => ['nullable', 'boolean'],
            'interests' => ['required', 'array', 'min:1', 'max:12'],
            'interests.*' => ['string', 'distinct', Rule::in(self::INTERESTS)],
            'career_goal' => ['nullable', 'string', 'max:500'],
            'experience_example' => ['nullable', 'string', 'max:500'],
            'desired_skills' => ['nullable', 'string', 'max:500'],
        ];
        foreach (self::PREFERENCES as $key => $options) {
            if ($key === 'support_needs') {
                $rules[$key] = ['required', 'array', 'min:1', 'max:8'];
                $rules[$key.'.*'] = ['string', 'distinct', Rule::in($options)];
            } else {
                $rules[$key] = ['required', 'string', Rule::in($options)];
            }
        }
        $validated = $request->validate($rules, [
            'skills.required' => 'Select a practiced skill or choose the no prior experience option.',
            'skills.min' => 'Select a practiced skill or choose the no prior experience option.',
            'interests.min' => 'Choose at least one interest to personalize your recommendations.',
            'support_needs.required' => 'Choose a support preference, including “No additional support needed right now.”',
            'support_needs.min' => 'Choose a support preference, including “No additional support needed right now.”',
        ]);

        $profile = $request->user()->youthProfile()->firstOrNew();
        $assessmentSkills = array_values($validated['skills'] ?? []);
        $profile->skills = array_values(array_unique(array_merge($profile->skills ?? [], $assessmentSkills)));
        $profile->interests = array_values($validated['interests']);
        $preferences = collect($validated)->except(['skills', 'interests'])->all();
        $preferences['no_prior_experience'] = $assessmentSkills === [] && $request->boolean('no_prior_experience');
        $preferences['assessment_skills'] = array_values(array_unique($assessmentSkills));
        $profile->assessment_preferences = $preferences;
        $profile->save();
        $activity->record($request->user(), 'updated their skills assessment.');
        $starterRecommendations = $recommendations->for($request->user()->fresh());

        if ($request->routeIs('mobile.*')) {
            return response()->json([
                'message' => 'Your assessment is saved. Here are your first job, career, and training suggestions based on your interests and existing skills.',
                'starter_recommendations' => $starterRecommendations,
            ]);
        }

        return redirect()->route('youth.assessment.index')->with('status', 'Your assessment is saved. Your job, career, and training suggestions are ready above.');
    }
}
