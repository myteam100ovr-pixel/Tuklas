<?php

namespace App\Http\Controllers;

use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkillsAssessmentController extends Controller
{
    private const SKILLS = ['Communication', 'Customer service', 'Computer literacy', 'Food preparation', 'Basic electronics', 'Sewing', 'Gardening', 'Bookkeeping', 'Teamwork', 'Problem solving', 'Writing', 'Design and creativity', 'Sales', 'Repair and maintenance', 'Caregiving', 'Planning and organization'];

    private const INTERESTS = ['Information technology', 'Hospitality and tourism', 'Food and beverage', 'Electronics', 'Agriculture', 'Entrepreneurship', 'Construction', 'Health and caregiving', 'Arts and design', 'Automotive', 'Business and finance', 'Logistics'];

    private const PREFERENCES = [
        'priority' => ['Find my first job', 'Improve my current skills', 'Change career direction', 'Start a small business', 'Prepare for TESDA assessment', 'Explore my options'],
        'learning_mode' => ['Hands-on / face-to-face', 'Online at my own pace', 'Blended online and in-person', 'Open to any format'],
        'schedule' => ['Weekdays', 'Evenings', 'Weekends', 'Flexible / not sure yet'],
        'weekly_hours' => ['Less than 5 hours', '5–10 hours', '11–20 hours', 'More than 20 hours', 'Not sure yet'],
        'work_environment' => ['Office or digital work', 'Hands-on workshop', 'Outdoors or in the field', 'Working directly with people', 'Remote work', 'Open to different settings'],
        'support_needs' => ['Free or low-cost training', 'Scholarship information', 'Nearby training providers', 'Flexible schedule', 'Access to a device or internet', 'Help preparing requirements'],
    ];

    public function index(Request $request): View|JsonResponse
    {
        $data = [
            'skills' => self::SKILLS,
            'interests' => self::INTERESTS,
            'profile' => $request->user()->youthProfile,
            'preferenceOptions' => self::PREFERENCES,
        ];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('youth.skills-assessment', $data);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $rules = [
            'skills' => ['sometimes', 'array', 'max:16'],
            'skills.*' => ['string', 'distinct', Rule::in(self::SKILLS)],
            'interests' => ['required', 'array', 'min:1', 'max:12'],
            'interests.*' => ['string', 'distinct', Rule::in(self::INTERESTS)],
            'career_goal' => ['nullable', 'string', 'max:500'],
            'experience_example' => ['nullable', 'string', 'max:500'],
            'desired_skills' => ['nullable', 'string', 'max:500'],
        ];
        foreach (self::PREFERENCES as $key => $options) {
            if ($key === 'support_needs') {
                $rules[$key] = ['sometimes', 'array', 'max:6'];
                $rules[$key.'.*'] = ['string', 'distinct', Rule::in($options)];
            } else {
                $rules[$key] = ['nullable', 'string', Rule::in($options)];
            }
        }
        $validated = $request->validate($rules);

        $profile = $request->user()->youthProfile()->firstOrNew();
        $profile->skills = array_values(array_unique(array_merge($profile->skills ?? [], $validated['skills'] ?? [])));
        $profile->interests = array_values($validated['interests']);
        $profile->assessment_preferences = collect($validated)->except(['skills', 'interests'])->all();
        $profile->save();
        $activity->record($request->user(), 'updated their skills assessment.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Your skills and preferences are saved. Your next AI scan and career chat will consider your goals, interests, and training preferences.']);
        }

        return redirect()->route('youth.assessment.index')->with('status', 'Your skills and preferences are saved. Your next AI scan and career chat will consider your goals, interests, and training preferences.');
    }
}
