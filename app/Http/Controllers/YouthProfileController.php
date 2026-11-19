<?php

namespace App\Http\Controllers;

use App\Models\YouthProfile;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class YouthProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('youthProfile');
        $profile = $user->youthProfile;

        return response()->json([
            'user' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'middle_initial' => $user->middle_initial,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'date_of_birth' => $user->date_of_birth?->toDateString(),
            ],
            'profile' => [
                'barangay' => $profile?->barangay,
                'contact_number' => $profile?->contact_number,
                'educational_attainment' => $profile?->educational_attainment,
                'employment_status' => $profile?->employment_status,
                'livelihood_interests' => $profile?->livelihood_interests,
                'interests' => $profile?->interests ?? [],
                'skills' => $profile?->skills ?? [],
                'credentials' => $profile?->credentials ?? [],
                'guardian_name' => $profile?->guardian_name,
                'guardian_relationship' => $profile?->guardian_relationship,
                'guardian_contact' => $profile?->guardian_contact,
            ],
        ]);
    }

    public function update(Request $request, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'date_of_birth' => ['bail', 'required', 'date', function ($attribute, $value, $fail) {
                $age = Carbon::parse($value)->age;
                if ($age < 18 || $age > 30) {
                    $fail('Tuklas is for Career Seekers aged 18 to 30.');
                }
            }],
            'barangay' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'educational_attainment' => ['nullable', Rule::in(YouthProfile::EDUCATION)],
            'employment_status' => ['nullable', Rule::in(YouthProfile::EMPLOYMENT)],
            'livelihood_interests' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill(['date_of_birth' => $data['date_of_birth']])->save();
        unset($data['date_of_birth']);

        $data['guardian_name'] = $data['guardian_relationship'] = $data['guardian_contact'] = null;

        $user->youthProfile()->updateOrCreate([], $data);
        $activity->record($user, 'updated their youth profile.');

        if ($request->expectsJson()) {
            return $this->show($request);
        }

        return redirect()->route('profile.show')->with('status', 'youth-details-saved');
    }
}
