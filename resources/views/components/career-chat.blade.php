<section class="career-chat" data-career-chat data-chat-url="{{ route('career-chat.store') }}" data-gemini-ready="{{ filled(config('services.google.gemini_api_key')) ? 'true' : 'false' }}" aria-labelledby="career-chat-title">
    <header class="career-chat__heading">
        <p>Google Gemini</p>
        <h2 id="career-chat-title">How can I help you?</h2>
    </header>
    <div class="career-chat__messages" data-chat-messages aria-live="polite" aria-relevant="additions">
        <p class="career-chat__message">Hey! What’s on your mind? Ask me about jobs, TESDA, skills, or anything else.</p>
    </div>
    <div class="career-chat__suggestions" aria-label="Suggested questions">
        <button type="button" data-chat-prompt="Can you help me find current job openings near Bugallon, Pangasinan?">Find jobs near me</button>
        <button type="button" data-chat-prompt="I want to work in AI. What skills should I develop, and which TESDA NC or online TESDA course should I consider first?">AI skills + TESDA plan</button>
        <button type="button" data-chat-prompt="I’m not sure what career fits me. Can we talk it through?">Talk about careers</button>
        <button type="button" data-chat-prompt="What’s one practical next step I can take this week?">Plan a next step</button>
    </div>
    <form class="career-chat__form" data-chat-form>
        <label class="sr-only" for="career-chat-message">Your message</label>
        <input class="career-chat__input" id="career-chat-message" data-chat-input type="text" maxlength="2000" placeholder="Message Tuklas..." autocomplete="off" required>
        <button class="career-chat__send" data-chat-send type="submit" aria-label="Send message" title="Send message" @disabled(! filled(config('services.google.gemini_api_key')))>
            <span aria-hidden="true">↗</span>
        </button>
    </form>
    <p class="career-chat__status" data-chat-status role="status" aria-live="polite"></p>
    <p class="career-chat__privacy">Messages are sent to Google Gemini. For career questions, limited skills and job matches from your latest resume or certificate scan may also be shared to personalize suggestions.</p>
</section>
