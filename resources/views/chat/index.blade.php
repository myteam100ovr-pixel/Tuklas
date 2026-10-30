<x-app-layout>
    <div class="tuklasbot-page">
        <header class="tuklasbot-page__intro">
            <p class="workspace-eyebrow">YOUR CONVERSATION SPACE</p>
            <h1>Meet TuklasBot.</h1>
            <p>Ask about your next step, explore any topic, or find a useful source. Continue the conversation from your dashboard here.</p>
        </header>

        <div class="tuklasbot-page__layout">
            <x-career-chat />

            <aside class="tuklasbot-profile" aria-labelledby="tuklasbot-profile-title">
                <span class="tuklasbot-profile__eyebrow">YOUR PROFILE</span>
                <div class="tuklasbot-profile__avatar" aria-hidden="true">
                    @if (auth()->user()->profile_photo_path)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="">
                    @else
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <h2 id="tuklasbot-profile-title">{{ auth()->user()->name }}</h2>
                <p class="tuklasbot-profile__role">{{ auth()->user()->role->label() }} workspace</p>
                <p class="tuklasbot-profile__note">Your saved chat follows your account. TuklasBot can use limited career context when your question calls for it.</p>
                <a class="tuklasbot-profile__link" href="{{ route('profile.show') }}">View your profile <span aria-hidden="true">↗</span></a>
                <div class="tuklasbot-profile__tips">
                    <h3>Try asking</h3>
                    <p>“Can you find current training options and include official links?”</p>
                    <p>“Help me compare two career paths.”</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
