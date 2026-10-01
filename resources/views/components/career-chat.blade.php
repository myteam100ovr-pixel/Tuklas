<section class="career-chat" data-career-chat data-chat-url="{{ route('career-chat.store') }}" data-gemini-ready="{{ filled(config('services.google.gemini_api_key')) ? 'true' : 'false' }}" aria-labelledby="career-chat-title">
    <header class="career-chat__heading">
        <p>Google Gemini</p>
        <h2 id="career-chat-title">How can I help you?</h2>
        <small>{{ config('services.google.gemini_model') }}</small>
    </header>
    <div class="career-chat__messages" data-chat-messages aria-live="polite" aria-relevant="additions">
        <p class="career-chat__message">Ask about careers, skills training, or a practical next step.</p>
    </div>
    <div class="career-chat__suggestions" aria-label="Suggested questions">
        <button type="button" data-chat-prompt="Help me explore careers that match my interests.">Explore careers</button>
        <button type="button" data-chat-prompt="How can I find skills training that fits my goals?">Find skills training</button>
        <button type="button" data-chat-prompt="Help me make a practical plan for my next step.">Plan a next step</button>
        <button type="button" data-chat-prompt="What skills should I build for the kind of work I want?">Build my skills</button>
    </div>
    <form class="career-chat__form" data-chat-form>
        <label class="sr-only" for="career-chat-message">Your message</label>
        <input class="career-chat__input" id="career-chat-message" data-chat-input type="text" maxlength="2000" placeholder="Ask something..." autocomplete="off" required>
        <button class="career-chat__send" data-chat-send type="submit" aria-label="Send message" title="Send message" @disabled(! filled(config('services.google.gemini_api_key')))>
            <span aria-hidden="true">↗</span>
        </button>
    </form>
    <p class="career-chat__status" data-chat-status role="status" aria-live="polite"></p>
    <p class="career-chat__privacy">Messages are sent to Google Gemini. Avoid sharing private information.</p>
</section>