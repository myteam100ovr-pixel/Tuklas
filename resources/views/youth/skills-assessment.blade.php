<x-app-layout>
    @php
        $saved = $profile?->assessment_preferences ?? [];
        $selectedSkills = old('skills', session()->hasOldInput() ? [] : ($profile?->assessmentSkills() ?? []));
        $selectedInterests = old('interests', session()->hasOldInput() ? [] : ($profile?->interests ?? []));
        $noPriorExperience = old('no_prior_experience', session()->hasOldInput() ? false : ($saved['no_prior_experience'] ?? false));
        $preferenceLabels = ['priority' => 'What matters most right now?', 'learning_mode' => 'How do you prefer to learn?', 'schedule' => 'When could you attend training?', 'weekly_hours' => 'How much time can you set aside each week?', 'work_environment' => 'Where do you see yourself working?'];
    @endphp
    <div class="assessment-page" data-assessment-walkthrough data-auto-open="{{ $assessmentRequired ? 'true' : 'false' }}">
        <dialog class="assessment-walkthrough" data-walkthrough-dialog aria-labelledby="assessment-walkthrough-title">
            <div class="assessment-walkthrough__top"><span data-walkthrough-progress>{{ __('Step 1 of 4') }}</span><button type="button" data-walkthrough-skip class="assessment-walkthrough__close" aria-label="{{ __('Close walkthrough') }}">×</button></div>
            <p class="assessment-kicker">{{ __('YOUR FIRST TIME HERE?') }}</p>
            <h2 id="assessment-walkthrough-title" data-walkthrough-title>{{ __('Start with a few choices about yourself.') }}</h2>
            <p data-walkthrough-copy>{{ __('This is not a test. Your answers help Tuklas suggest directions that fit what you like and what you have tried.') }}</p>
            <div class="assessment-walkthrough__actions"><button type="button" class="assessment-walkthrough__skip" data-walkthrough-skip>{{ __('Skip walkthrough') }}</button><div><button type="button" data-walkthrough-back hidden>{{ __('Back') }}</button><button type="button" class="btn btn-primary" data-walkthrough-next>{{ __('Next') }}</button></div></div>
        </dialog>
        <x-workspace-heading eyebrow="YOUR DIRECTION · YOUR CHOICE" title="{{ __('Skills self-assessment') }}" description="Start with what you enjoy, what you have tried, and where you want to go. Build a learning plan that fits your life." />
        @if ($assessmentRequired)<p class="assessment-required-banner" role="status">{{ __('This is your first step. Save the assessment to unlock your Career Seeker workspace, including jobs, courses, messages, and personalized recommendations.') }}</p>@endif
        <div class="assessment-intro">
            <div><span class="assessment-kicker">{{ __('A LITTLE REFLECTION. A CLEARER NEXT STEP.') }}</span><h2>{{ __('Your experience is only the starting point.') }}</h2><p>{{ __('You can begin without a resume or NC certificate. Your interests help choose a direction, and skills you have practiced can support those preferences.') }}</p></div>
            <div class="assessment-intro-meta"><strong>{{ __('5 areas to explore') }}</strong><span>{{ __('About 5–8 minutes') }}</span><span>{{ __('Update your answers anytime') }}</span></div>
        </div>
        <button type="button" class="assessment-walkthrough-link" data-walkthrough-open>{{ __('Take the step-by-step walkthrough') }}</button>
        @if (session('status'))<p class="assessment-notice" role="status">{{ session('status') }}</p>@endif
        @if ($assessmentComplete && $profile?->assessmentSkills() === null)<p class="assessment-notice" role="status">{{ __('Review the skills below and select only tasks you have personally practiced. Earlier profile skills may include document-scan findings, so they are not counted in your assessment match until you confirm them here.') }}</p>@endif
        <section class="assessment-recommendations" aria-labelledby="starter-recommendations-title">
            <span class="assessment-kicker">{{ $assessmentComplete ? 'YOUR FIRST MATCHES' : 'YOUR NEXT STEP' }}</span>
            <h2 id="starter-recommendations-title">{{ $assessmentComplete ? 'Recommendations shaped around you.' : 'Your recommendations will start here.' }}</h2>
            @if ($assessmentComplete)
                <p>{{ __('These suggestions use published Tuklas listings and your selected interests and self-reported skills. Skill overlaps are based on the listed skills for each career path; they are not qualifications or hiring probabilities.') }}</p>
                <div class="assessment-recommendation-grid">
                    @foreach (['work' => ['Work opportunities', 'No current verified job matches yet.'], 'careers' => ['Career paths', 'No published career paths match yet.'], 'training' => ['Training programs', 'No published training programs match yet.']] as $key => [$title, $empty])
                        <section class="assessment-recommendation-card" aria-labelledby="recommendation-{{ $key }}-title">
                            <h3 id="recommendation-{{ $key }}-title">{{ $title }}</h3>
                            @forelse ($starterRecommendations[$key] ?? [] as $recommendation)
                                <article class="assessment-recommendation-item">
                                    <strong>{{ $recommendation['title'] }}</strong>
                                    @if ($recommendation['detail'] !== '')<p>{{ $recommendation['detail'] }}</p>@endif
                                    @if ($key === 'careers' && ($recommendation['skill_match']['status'] ?? null) === 'ready')
                                        <p><strong>{{ $recommendation['skill_match']['percent'] }}% skills overlap · {{ $recommendation['skill_match']['matched'] }} of {{ $recommendation['skill_match']['total'] }} path skills selected.</strong></p>
                                        <p>This reflects your self-assessment choices, not verified qualifications or hiring probability.</p>
                                    @elseif ($key === 'careers' && ($recommendation['skill_match']['status'] ?? null) === 'requirements_missing')
                                        <p>PESO has not listed enough skills for a percentage match yet.</p>
                                    @endif
                                    <a href="{{ $recommendation['url'] }}">{{ __('Explore this option') }} <span aria-hidden="true">↗</span></a>
                                </article>
                            @empty
                                <p class="assessment-recommendation-empty">{{ $empty }} Explore the full catalog for other options.</p>
                                @if ($key === 'work')<a href="{{ route('opportunities.index') }}">{{ __('Browse job opportunities') }} <span aria-hidden="true">↗</span></a>@elseif ($key === 'careers')<a href="{{ route('careers.index') }}">{{ __('Browse career paths') }} <span aria-hidden="true">↗</span></a>@else<a href="{{ route('tesda.index') }}">{{ __('Browse TESDA programs') }} <span aria-hidden="true">↗</span></a>@endif
                            @endforelse
                        </section>
                    @endforeach
                </div>
            @else
                <p>{{ __('Complete the choices below and save to see personalized job, career path, and training suggestions here. You can begin with no prior experience, resume, or NC certificate.') }}</p>
            @endif
        </section>
        <div class="assessment-layout">
            <form method="POST" action="{{ route('youth.assessment.store') }}" class="assessment-form">
                @csrf
                @if ($errors->any())<div class="assessment-errors" role="alert"><strong>{{ __('Check your answers below.') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <section class="assessment-section" id="assessment-skills" aria-labelledby="assessment-skills-title">
                    <header class="assessment-section-heading"><span class="assessment-step">01</span><div><p>{{ __('WHAT YOU BRING') }}</p><h2 id="assessment-skills-title">{{ __('Recognize your strengths') }}</h2></div><span class="assessment-section-tag">{{ __('Choose skills or no prior experience') }}</span></header>
                    <p class="assessment-description">Select up to 16 tasks you have practiced at school, at work, at home, or in your community. If none fit yet, choose “{{ $noPriorExperienceOption }}.” This helps us avoid treating experience you do not have as a match.</p>
                    <div class="assessment-options" data-assessment-skills>
                        @foreach ($skills as $skill)<label class="assessment-option"><input type="checkbox" name="skills[]" value="{{ $skill }}" @checked(in_array($skill, $selectedSkills, true))><span>{{ $skill }}</span></label>@endforeach
                        <label class="assessment-option"><input type="checkbox" name="no_prior_experience" value="1" data-no-prior-experience @checked((bool) $noPriorExperience)><span>{{ $noPriorExperienceOption }}</span></label>
                    </div>
                    <label class="assessment-field" for="experience-example"><span>{{ __('A moment you put a skill to use') }} <small>{{ __('Optional') }}</small></span><textarea id="experience-example" name="experience_example" rows="3" maxlength="500" placeholder="{{ __('For example: I helped manage orders for our family store, or built a class project with a team.') }}">{{ old('experience_example', $saved['experience_example'] ?? '') }}</textarea></label>
                </section>
                <section class="assessment-section" id="assessment-interests" aria-labelledby="assessment-interests-title">
                    <header class="assessment-section-heading"><span class="assessment-step">02</span><div><p>{{ __('WHAT DRAWS YOU IN') }}</p><h2 id="assessment-interests-title">{{ __('Follow your interests') }}</h2></div><span class="assessment-section-tag">{{ __('Choose at least one') }}</span></header>
                    <p class="assessment-description">{{ __('Choose at least one and up to 12 fields you would enjoy learning about. You do not need experience in them yet.') }}</p>
                    <div class="assessment-options" data-assessment-interests>
                        @foreach ($interests as $interest)<label class="assessment-option"><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $selectedInterests, true))><span>{{ $interest }}</span></label>@endforeach
                    </div>
                    <label class="assessment-field" for="desired-skills"><span>{{ __('What would you like to learn next?') }} <small>{{ __('Optional') }}</small></span><textarea id="desired-skills" name="desired_skills" rows="3" maxlength="500" placeholder="{{ __('A skill, subject, or activity you want to try — even if you are still exploring.') }}">{{ old('desired_skills', $saved['desired_skills'] ?? '') }}</textarea></label>
                </section>
                <section class="assessment-section" id="assessment-goals" aria-labelledby="assessment-goals-title">
                    <header class="assessment-section-heading"><span class="assessment-step">03</span><div><p>{{ __('WHERE YOU WANT TO GO') }}</p><h2 id="assessment-goals-title">{{ __('Set your direction') }}</h2></div></header>
                    <p class="assessment-description">{{ __('Tell us what you want, rather than choosing a path just because it matches your past experience.') }}</p>
                    <label class="assessment-field" for="assessment-priority"><span>{{ $preferenceLabels['priority'] }} <small>{{ __('Required') }}</small></span><select id="assessment-priority" name="priority" required><option value="" disabled @selected(old('priority', $saved['priority'] ?? '') === '')>{{ __('Choose one option') }}</option>@foreach ($preferenceOptions['priority'] as $option)<option value="{{ $option }}" @selected(old('priority', $saved['priority'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                    <label class="assessment-field" for="career-goal"><span>{{ __('Your goal, in your own words') }} <small>{{ __('Optional') }}</small></span><textarea id="career-goal" name="career_goal" rows="3" maxlength="500" placeholder="{{ __('For example: I want to become a baker, start a small business, or explore a career in technology.') }}">{{ old('career_goal', $saved['career_goal'] ?? '') }}</textarea></label>
                    <label class="assessment-field" for="assessment-work-environment"><span>{{ $preferenceLabels['work_environment'] }} <small>{{ __('Required') }}</small></span><select id="assessment-work-environment" name="work_environment" required><option value="" disabled @selected(old('work_environment', $saved['work_environment'] ?? '') === '')>{{ __('Choose one option') }}</option>@foreach ($preferenceOptions['work_environment'] as $option)<option value="{{ $option }}" @selected(old('work_environment', $saved['work_environment'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                </section>
                <section class="assessment-section" id="assessment-learning" aria-labelledby="assessment-learning-title">
                    <header class="assessment-section-heading"><span class="assessment-step">04</span><div><p>{{ __('AT YOUR OWN PACE') }}</p><h2 id="assessment-learning-title">{{ __('Make learning fit your life') }}</h2></div></header>
                    <p class="assessment-description">{{ __('Choose an answer for each. “Not sure yet” and flexible options are available if you are still deciding.') }}</p>
                    <div class="assessment-fields">
                        @foreach (['learning_mode', 'schedule', 'weekly_hours'] as $key)
                            <label class="assessment-field" for="assessment-{{ $key }}"><span>{{ $preferenceLabels[$key] }} <small>{{ __('Required') }}</small></span><select id="assessment-{{ $key }}" name="{{ $key }}" required><option value="" disabled @selected(old($key, $saved[$key] ?? '') === '')>{{ __('Choose one option') }}</option>@foreach ($preferenceOptions[$key] as $option)<option value="{{ $option }}" @selected(old($key, $saved[$key] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                        @endforeach
                    </div>
                </section>
                <section class="assessment-section" id="assessment-support" aria-labelledby="assessment-support-title">
                    <header class="assessment-section-heading"><span class="assessment-step">05</span><div><p>{{ __('A LITTLE SUPPORT GOES FAR') }}</p><h2 id="assessment-support-title">{{ __('Tell us what would help') }}</h2></div><span class="assessment-section-tag">{{ __('Choose at least one') }}</span></header>
                    <p class="assessment-description">{{ __('Choose any support that would make training easier to pursue, or choose “No additional support needed right now.” These preferences guide suggestions; they do not guarantee assistance or available slots.') }}</p>
                    <div class="assessment-options" data-assessment-support>
                        @foreach ($preferenceOptions['support_needs'] as $option)<label class="assessment-option"><input type="checkbox" name="support_needs[]" value="{{ $option }}" @if ($option === 'No additional support needed right now') data-no-additional-support @endif @checked(in_array($option, old('support_needs', session()->hasOldInput() ? [] : ($saved['support_needs'] ?? [])), true))><span>{{ $option }}</span></label>@endforeach
                    </div>
                </section>
                <div class="assessment-submit"><div><strong>{{ __('Ready for a more personal starting point?') }}</strong><p>{{ __('You can return and change your preferences anytime.') }}</p></div><button class="btn btn-primary" type="submit">{{ __('Save my assessment') }} <span aria-hidden="true">↗</span></button></div>
            </form>
            <aside class="assessment-guide">
                <span class="assessment-kicker">{{ __('YOUR NEXT CHAPTER') }}</span><h2>{{ __('A plan built around you.') }}</h2>
                <nav aria-label="{{ __('Assessment sections') }}"><a href="#assessment-skills">01 · Your strengths</a><a href="#assessment-interests">02 · Your interests</a><a href="#assessment-goals">03 · Your direction</a><a href="#assessment-learning">04 · Your availability</a><a href="#assessment-support">05 · Your support needs</a></nav>
                <div class="assessment-guide-note"><strong>{{ __('How your answers are used') }}</strong><p>{{ __('Saving your interests, skills choice, and preferences immediately shows job, career, and training suggestions above this form. A resume or NC certificate is not required. Document scans can add supporting evidence later.') }}</p><p>{{ __('For AI features, your answers may be sent to Google Gemini. Avoid adding sensitive personal details.') }}</p></div>
                <div class="assessment-guide-note"><strong>{{ __('Reflection, not a score') }}</strong><p>{{ __('This is a self-assessment, not a competency test or TESDA certification. Existing profile skills and credentials are kept.') }}</p></div>
                <a class="assessment-next" href="{{ route('scanner.index') }}">Scan your experience ↗</a><a class="assessment-next" href="{{ route('tesda.index') }}">Explore TESDA training ↗</a>
            </aside>
        </div>
    </div>
</x-app-layout>
