@php
    $u = auth()->user();
    $yp = $u->youthProfile;
@endphp

<section class="card tk-sec">
    <div class="tk-sec-title">
        <h3>Career Seeker details</h3>
        <p>Tell us about your education and interests. Tuklas uses these to suggest careers and TESDA training. Suggestions are guidance; they do not guarantee a job, admission, or a training place.</p>
    </div>

    <form method="POST" action="{{ route('youth.profile.update') }}" class="tk-sec-form">
        @csrf
        @method('PUT')

        <div class="field">
            <label class="label" for="date_of_birth">{{ __('Date of birth') }}</label>
            <input class="input" id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $u->date_of_birth?->toDateString()) }}" min="{{ now()->subYears(31)->addDay()->toDateString() }}" max="{{ now()->subYears(18)->toDateString() }}" required>
            <span class="hint">{{ __('Career Seekers must be 18 to 30 years old.') }}</span>
            @error('date_of_birth')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label class="label" for="barangay">{{ __('Barangay') }}</label>
                <input class="input" id="barangay" name="barangay" type="text" maxlength="100" value="{{ old('barangay', $yp?->barangay) }}" placeholder="Barangay in Bugallon">
                @error('barangay')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label class="label" for="contact_number">{{ __('Contact number') }}</label>
                <input class="input" id="contact_number" name="contact_number" type="tel" maxlength="30" value="{{ old('contact_number', $yp?->contact_number) }}">
                @error('contact_number')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label class="label" for="educational_attainment">Educational attainment</label>
                <select class="input" id="educational_attainment" name="educational_attainment">
                    <option value="">Select one</option>
                    @foreach (\App\Models\YouthProfile::EDUCATION as $option)
                        <option value="{{ $option }}" @selected(old('educational_attainment', $yp?->educational_attainment) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('educational_attainment')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label class="label" for="employment_status">Current status</label>
                <select class="input" id="employment_status" name="employment_status">
                    <option value="">Select one</option>
                    @foreach (\App\Models\YouthProfile::EMPLOYMENT as $option)
                        <option value="{{ $option }}" @selected(old('employment_status', $yp?->employment_status) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('employment_status')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="field">
            <label class="label" for="livelihood_interests">Interests and livelihood interests</label>
            <textarea class="input" id="livelihood_interests" name="livelihood_interests" rows="3" maxlength="1000" placeholder="For example: cooking, farming, electronics, selling online">{{ old('livelihood_interests', $yp?->livelihood_interests) }}</textarea>
            @error('livelihood_interests')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="tk-sec-actions">
            <button type="submit" class="btn btn-primary">Save details</button>
        </div>
    </form>
</section>
