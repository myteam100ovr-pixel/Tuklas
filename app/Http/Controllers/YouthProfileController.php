<?php

namespace App\Http\Controllers;

use App\Models\YouthProfile;
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

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $minor = $this->isMinor($request->input('date_of_birth'));

        $data = $request->validate([
            'date_of_birth' => ['required', 'date', function ($attribute, $value, $fail) {
                $age = Carbon::parse($value)->age;
                if ($age < 15 || $age > 30) {
                    $fail('Tuklas is for youth aged 15 to 30.');
                }
            }],
            'barangay' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'educational_attainment' => ['nullable', Rule::in(YouthProfile::EDUCATION)],
            'employment_status' => ['nullable', Rule::in(YouthProfile::EMPLOYMENT)],
            'livelihood_interests' => ['nullable', 'string', 'max:1000'],
            'guardian_name' => [Rule::requiredIf($minor), 'nullable', 'string', 'max:120'],
            'guardian_relationship' => [Rule::requiredIf($minor), 'nullable', 'string', 'max:60'],
            'guardian_contact' => [Rule::requiredIf($minor), 'nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
        ], [
            'guardian_name.required' => 'A guardian is needed for users under 18.',
            'guardian_relationship.required' => 'Tell us how your guardian is related to you.',
            'guardian_contact.required' => 'Add a contact number for your guardian.',
        ]);

        $user->forceFill(['date_of_birth' => $data['date_of_birth']])->save();
        unset($data['date_of_birth']);

        if (! $minor) {
            $data['guardian_name'] = $data['guardian_relationship'] = $data['guardian_contact'] = null;
        }

        $user->youthProfile()->updateOrCreate([], $data);

        if ($request->expectsJson()) {
            return $this->show($request);
        }

        return redirect()->route('profile.show')->with('status', 'youth-details-saved');
    }

    private function isMinor(?string $dob): bool
    {
        try {
            return $dob !== null && Carbon::parse($dob)->age < 18;
        } catch (\Throwable) {
            return false;
        }
    }
}
