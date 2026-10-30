<x-app-layout>
    @php
        $saved = $profile?->assessment_preferences ?? [];
        $selectedSkills = old('skills', session()->hasOldInput() ? [] : ($profile?->skills ?? []));
        $selectedInterests = old('interests', session()->hasOldInput() ? [] : ($profile?->interests ?? []));
        $preferenceLabels = ['priority' => 'What matters most right now?', 'learning_mode' => 'How do you prefer to learn?', 'schedule' => 'When could you attend training?', 'weekly_hours' => 'How much time can you set aside each week?', 'work_environment' => 'Where do you see yourself working?'];
    @endphp
    <div class="assessment-page">
        <x-workspace-heading eyebrow="YOUR DIRECTION · YOUR CHOICE" title="Skills self-assessment" description="Start with what you enjoy, what you have tried, and where you want to go. Build a learning plan that fits your life." />
        <div class="assessment-intro">
            <div><span class="assessment-kicker">A LITTLE REFLECTION. A CLEARER NEXT STEP.</span><h2>Your experience is only the starting point.</h2><p>Your goals matter too. These answers help Tuklas personalize future career conversations and resume or certificate scans.</p></div>
            <div class="assessment-intro-meta"><strong>5 areas to explore</strong><span>About 5–8 minutes</span><span>Update your answers anytime</span></div>
        </div>
        <div class="assessment-layout">
            <form method="POST" action="{{ route('youth.assessment.store') }}" class="assessment-form">
                @csrf
                @if (session('status'))<p class="assessment-notice" role="status">{{ session('status') }}</p>@endif
                @if ($errors->any())<div class="assessment-errors" role="alert"><strong>Check your answers below.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <section class="assessment-section" id="assessment-skills" aria-labelledby="assessment-skills-title">
                    <header class="assessment-section-heading"><span class="assessment-step">01</span><div><p>WHAT YOU BRING</p><h2 id="assessment-skills-title">Recognize your strengths</h2></div><span class="assessment-section-tag">Select any</span></header>
                    <p class="assessment-description">Which tasks have you practiced at school, at work, at home, or in your community? Choose only what you can describe with an example. Starting with no experience is okay.</p>
                    <div class="assessment-options">
                        @foreach ($skills as $skill)<label class="assessment-option"><input type="checkbox" name="skills[]" value="{{ $skill }}" @checked(in_array($skill, $selectedSkills, true))><span>{{ $skill }}</span></label>@endforeach
                    </div>
                    <label class="assessment-field" for="experience-example"><span>A moment you put a skill to use <small>Optional</small></span><textarea id="experience-example" name="experience_example" rows="3" maxlength="500" placeholder="For example: I helped manage orders for our family store, or built a class project with a team.">{{ old('experience_example', $saved['experience_example'] ?? '') }}</textarea></label>
                </section>
                <section class="assessment-section" id="assessment-interests" aria-labelledby="assessment-interests-title">
                    <header class="assessment-section-heading"><span class="assessment-step">02</span><div><p>WHAT DRAWS YOU IN</p><h2 id="assessment-interests-title">Follow your interests</h2></div><span class="assessment-section-tag">Choose at least one</span></header>
                    <p class="assessment-description">What would you enjoy learning or doing? Choose at least one field. You do not need experience in it yet.</p>
                    <div class="assessment-options">
                        @foreach ($interests as $interest)<label class="assessment-option"><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $selectedInterests, true))><span>{{ $interest }}</span></label>@endforeach
                    </div>
                    <label class="assessment-field" for="desired-skills"><span>What would you like to learn next? <small>Optional</small></span><textarea id="desired-skills" name="desired_skills" rows="3" maxlength="500" placeholder="A skill, subject, or activity you want to try — even if you are still exploring.">{{ old('desired_skills', $saved['desired_skills'] ?? '') }}</textarea></label>
                </section>
                <section class="assessment-section" id="assessment-goals" aria-labelledby="assessment-goals-title">
                    <header class="assessment-section-heading"><span class="assessment-step">03</span><div><p>WHERE YOU WANT TO GO</p><h2 id="assessment-goals-title">Set your direction</h2></div></header>
                    <p class="assessment-description">Tell us what you want, rather than choosing a path just because it matches your past experience.</p>
                    <label class="assessment-field" for="assessment-priority"><span>{{ $preferenceLabels['priority'] }} <small>Optional</small></span><select id="assessment-priority" name="priority"><option value="">Choose your main priority</option>@foreach ($preferenceOptions['priority'] as $option)<option value="{{ $option }}" @selected(old('priority', $saved['priority'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                    <label class="assessment-field" for="career-goal"><span>Your goal, in your own words <small>Optional</small></span><textarea id="career-goal" name="career_goal" rows="3" maxlength="500" placeholder="For example: I want to become a baker, start a small business, or explore a career in technology.">{{ old('career_goal', $saved['career_goal'] ?? '') }}</textarea></label>
                    <label class="assessment-field" for="assessment-work-environment"><span>{{ $preferenceLabels['work_environment'] }} <small>Optional</small></span><select id="assessment-work-environment" name="work_environment"><option value="">Choose a preferred setting</option>@foreach ($preferenceOptions['work_environment'] as $option)<option value="{{ $option }}" @selected(old('work_environment', $saved['work_environment'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                </section>
                <section class="assessment-section" id="assessment-learning" aria-labelledby="assessment-learning-title">
                    <header class="assessment-section-heading"><span class="assessment-step">04</span><div><p>AT YOUR OWN PACE</p><h2 id="assessment-learning-title">Make learning fit your life</h2></div></header>
                    <p class="assessment-description">Your availability and preferred format help shape practical next steps. You can leave an answer open while you decide.</p>
                    <div class="assessment-fields">
                        @foreach (['learning_mode', 'schedule', 'weekly_hours'] as $key)
                            <label class="assessment-field" for="assessment-{{ $key }}"><span>{{ $preferenceLabels[$key] }} <small>Optional</small></span><select id="assessment-{{ $key }}" name="{{ $key }}"><option value="">Choose a preference</option>@foreach ($preferenceOptions[$key] as $option)<option value="{{ $option }}" @selected(old($key, $saved[$key] ?? '') === $option)>{{ $option }}</option>@endforeach</select></label>
                        @endforeach
                    </div>
                </section>
                <section class="assessment-section" id="assessment-support" aria-labelledby="assessment-support-title">
                    <header class="assessment-section-heading"><span class="assessment-step">05</span><div><p>A LITTLE SUPPORT GOES FAR</p><h2 id="assessment-support-title">Tell us what would help</h2></div><span class="assessment-section-tag">Optional</span></header>
                    <p class="assessment-description">Choose any support that would make training easier to pursue. These preferences guide suggestions; they do not guarantee assistance or available slots.</p>
                    <div class="assessment-options">
                        @foreach ($preferenceOptions['support_needs'] as $option)<label class="assessment-option"><input type="checkbox" name="support_needs[]" value="{{ $option }}" @checked(in_array($option, old('support_needs', session()->hasOldInput() ? [] : ($saved['support_needs'] ?? [])), true))><span>{{ $option }}</span></label>@endforeach
                    </div>
                </section>
                <div class="assessment-submit"><div><strong>Ready for a more personal starting point?</strong><p>You can return and change your preferences anytime.</p></div><button class="btn btn-primary" type="submit">Save my assessment <span aria-hidden="true">↗</span></button></div>
            </form>
            <aside class="assessment-guide">
                <span class="assessment-kicker">YOUR NEXT CHAPTER</span><h2>A plan built around you.</h2>
                <nav aria-label="Assessment sections"><a href="#assessment-skills">01 · Your strengths</a><a href="#assessment-interests">02 · Your interests</a><a href="#assessment-goals">03 · Your direction</a><a href="#assessment-learning">04 · Your availability</a><a href="#assessment-support">05 · Your support needs</a></nav>
                <div class="assessment-guide-note"><strong>How your answers are used</strong><p>Your next AI scan and career chat can consider these preferences alongside your resume or certificate. Previously saved scan results stay as they are.</p><p>For those features, your preferences may be sent to Google Gemini. Avoid adding sensitive personal details.</p></div>
                <div class="assessment-guide-note"><strong>Reflection, not a score</strong><p>This is a self-assessment, not a competency test or TESDA certification. Existing profile skills and credentials are kept.</p></div>
                <a class="assessment-next" href="{{ route('scanner.index') }}">Scan your experience ↗</a><a class="assessment-next" href="{{ route('tesda.index') }}">Explore TESDA training ↗</a>
            </aside>
        </div>
    </div>
</x-app-layout>
