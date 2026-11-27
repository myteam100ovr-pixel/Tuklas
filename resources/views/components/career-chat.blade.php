<section class="career-chat" data-career-chat data-chat-url="{{ route('career-chat.store') }}" data-history-url="{{ route('career-chat.index') }}" data-gemini-ready="{{ filled(config('services.google.gemini_api_key')) ? 'true' : 'false' }}" aria-labelledby="career-chat-title">
    @if (auth()->user()?->hasRole(\App\Enums\Role::Youth) && ! auth()->user()?->youthProfile?->hasCompletedSkillsAssessment())
        <header class="career-chat__heading"><div><p>{{ __('YOUR NEXT STEP') }}</p><h2 id="career-chat-title">{{ __('Personalized AI guidance') }}</h2><small>{{ __('Tell Tuklas what you enjoy and what skills you have practiced first.') }}</small></div></header>
        <p class="career-chat__privacy">{{ __('Complete your skills assessment to unlock AI career guidance. You can start without a resume or certificate.') }}</p>
        <a class="career-chat__assessment-link" href="{{ route('youth.assessment.index') }}">{{ __('Take the assessment') }} ↗</a>
    @else
    <header class="career-chat__heading">
        <div><p>{{ __('TUKLASBOT · POWERED BY GEMINI') }}</p><h2 id="career-chat-title">{{ __('Ask TuklasBot') }}</h2><small>{{ __('Talk things through, explore ideas, or find useful sources.') }}</small></div>
        <button class="career-chat__expand" type="button" data-chat-expand aria-label="{{ __('Expand chat') }}" aria-expanded="false" title="{{ __('Expand chat') }}">⤢</button>
    </header>
    <div class="career-chat__messages" data-chat-messages aria-live="polite" aria-relevant="additions">
        <p class="career-chat__message">{{ __('Hi, I’m TuklasBot. What would you like to talk about? I can help you explore a question and find sources when current information matters.') }}</p>
    </div>
    <div class="career-chat__suggestions" aria-label="{{ __('Suggested questions') }}">
        <button type="button" data-chat-prompt="{{ __('Can you help me find current job openings near Bugallon, Pangasinan?') }}">{{ __('Find jobs near me') }}</button>
        <button type="button" data-chat-prompt="{{ __('I want to work in AI. What skills should I develop, and which TESDA NC or online TESDA course should I consider first?') }}">{{ __('AI skills + TESDA plan') }}</button>
        <button type="button" data-chat-prompt="{{ __('I’m not sure what career fits me. Can we talk it through?') }}">{{ __('Talk about careers') }}</button>
        <button type="button" data-chat-prompt="{{ __('What’s one practical next step I can take this week?') }}">{{ __('Plan a next step') }}</button>
    </div>
    <form class="career-chat__form" data-chat-form>
        <label class="sr-only" for="career-chat-message">{{ __('Your message') }}</label>
        <input class="career-chat__input" id="career-chat-message" data-chat-input type="text" maxlength="2000" placeholder="{{ __('Message TuklasBot...') }}" autocomplete="off" required>
        <button class="career-chat__send" data-chat-send type="submit" aria-label="{{ __('Send message') }}" title="{{ __('Send message') }}" @disabled(! filled(config('services.google.gemini_api_key')))>
            <span aria-hidden="true">↗</span>
        </button>
    </form>
    <p class="career-chat__status" data-chat-status role="status" aria-live="polite"></p>
    <p class="career-chat__privacy">{{ __('Chat history is saved to your account. Messages are sent to Google Gemini. For career questions, limited skills and job matches from your latest resume or certificate scan may also be shared to personalize suggestions. Search links appear when live sources are available.') }}</p>
    @endif
</section>
