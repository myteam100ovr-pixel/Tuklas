<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkillsAssessmentController extends Controller
{
    private const SKILLS = ['Communication', 'Customer service', 'Computer literacy', 'Food preparation', 'Basic electronics', 'Sewing', 'Gardening', 'Bookkeeping'];

    private const INTERESTS = ['Information technology', 'Hospitality and tourism', 'Food and beverage', 'Electronics', 'Agriculture', 'Entrepreneurship', 'Construction'];

    public function index(Request $request): View
    {
        return view('youth.skills-assessment', [
            'skills' => self::SKILLS,
            'interests' => self::INTERESTS,
            'profile' => $request->user()->youthProfile,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'skills' => ['sometimes', 'array', 'max:8'],
            'skills.*' => ['string', 'distinct', Rule::in(self::SKILLS)],
            'interests' => ['required', 'array', 'min:1', 'max:7'],
            'interests.*' => ['string', 'distinct', Rule::in(self::INTERESTS)],
        ]);

        $profile = $request->user()->youthProfile()->firstOrNew();
        $profile->skills = array_values(array_unique(array_merge($profile->skills ?? [], $validated['skills'] ?? [])));
        $profile->interests = array_values(array_unique(array_merge($profile->interests ?? [], $validated['interests'])));
        $profile->save();

        return redirect()->route('youth.assessment.index')->with('status', 'Your self-assessment was saved to your profile. Scan a resume or certificate for evidence-based TESDA recommendations, or explore the training catalog below.');
    }
}
