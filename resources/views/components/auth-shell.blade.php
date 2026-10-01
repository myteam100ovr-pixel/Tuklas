@props(['mode', 'title', 'description'])

<div class="auth-page auth-page--{{ $mode }}">
    <main class="auth-layout">
        <section class="auth-story" data-auth-carousel aria-roledescription="carousel" aria-label="What Tuklas can help you do">
            <a class="auth-brand" href="{{ url('/') }}" aria-label="Tuklas home">
                <span class="auth-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M16 2.5 19.6 12.4 29.5 16l-9.9 3.6L16 29.5l-3.6-9.9L2.5 16l9.9-3.6L16 2.5Z" fill="currentColor" />
                        <circle cx="24.7" cy="7.3" r="2.3" fill="#FFD92E" />
                    </svg>
                </span>
                <span>tuklas</span>
            </a>

            <div class="auth-story-copy">
                <p class="auth-eyebrow"><span></span> EXPLORE WHAT TUKLAS CAN DO</p>
                <div class="auth-feature-slides" aria-live="off">
                    <article class="auth-feature-slide is-active" data-feature-slide aria-roledescription="slide" aria-label="1 of 5: Career guidance" aria-hidden="false">
                        <p class="auth-feature-count">01 <span>/ 05</span> · CAREER GUIDANCE</p>
                        <h1>Find a path that fits you.</h1>
                        <p class="auth-story-text">Take a skills assessment and explore career and TESDA Lingayen training suggestions shaped by your interests and strengths.</p>
                    </article>
                    <article class="auth-feature-slide" data-feature-slide aria-roledescription="slide" aria-label="2 of 5: Resume and certificate scan" aria-hidden="true">
                        <p class="auth-feature-count">02 <span>/ 05</span> · AI DOCUMENT SCAN</p>
                        <h2>Turn documents into a starting point.</h2>
                        <p class="auth-story-text">Gemini can suggest skills, schooling, and credentials from a resume or certificate. You review every suggestion before saving.</p>
                    </article>
                    <article class="auth-feature-slide" data-feature-slide aria-roledescription="slide" aria-label="3 of 5: Your profile" aria-hidden="true">
                        <p class="auth-feature-count">03 <span>/ 05</span> · YOUR PROFILE</p>
                        <h2>Keep your strengths in one place.</h2>
                        <p class="auth-story-text">Add your interests, skills, education, credentials, and livelihood interests, then update them as you grow.</p>
                    </article>
                    <article class="auth-feature-slide" data-feature-slide aria-roledescription="slide" aria-label="4 of 5: Local training opportunities" aria-hidden="true">
                        <p class="auth-feature-count">04 <span>/ 05</span> · LOCAL TRAINING</p>
                        <h2>Explore training offered nearby.</h2>
                        <p class="auth-story-text">See TVET programs offered by TESDA Lingayen and maintained by authorized staff.</p>
                    </article>
                    <article class="auth-feature-slide" data-feature-slide aria-roledescription="slide" aria-label="5 of 5: Local guidance" aria-hidden="true">
                        <p class="auth-feature-count">05 <span>/ 05</span> · MADE FOR PANGASINAN</p>
                        <h2>Get guidance built around your community.</h2>
                        <p class="auth-story-text">Tuklas brings youth, PESO Bugallon, and TESDA Lingayen together, with features tailored to each role.</p>
                    </article>
                </div>
            </div>

            <div class="auth-illustration" aria-hidden="true">
                <svg viewBox="0 0 640 460" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M66 321C148 274 186 332 265 257C334 192 373 222 449 143C494 97 528 95 576 72" stroke="white" stroke-opacity=".3" stroke-width="2" stroke-dasharray="7 9" />
                    <circle cx="111" cy="293" r="8" fill="#FFD92E" />
                    <circle cx="277" cy="246" r="7" fill="#F55BA8" />
                    <circle cx="465" cy="133" r="8" fill="#FFD92E" />
                    <circle cx="568" cy="77" r="7" fill="#F55BA8" />
                    <g transform="rotate(-8 207 245)">
                        <rect x="111" y="142" width="190" height="212" rx="20" fill="#FFE782" />
                        <rect x="129" y="161" width="38" height="38" rx="12" fill="#F55BA8" />
                        <path d="M140 180h16M148 172v16" stroke="white" stroke-width="3" stroke-linecap="round" />
                        <rect x="180" y="166" width="91" height="9" rx="4.5" fill="#4510B9" fill-opacity=".8" />
                        <rect x="180" y="183" width="62" height="6" rx="3" fill="#4510B9" fill-opacity=".35" />
                        <rect x="130" y="218" width="151" height="1" fill="#4510B9" fill-opacity=".15" />
                        <circle cx="145" cy="244" r="7" fill="#5B17FF" />
                        <rect x="162" y="240" width="96" height="7" rx="3.5" fill="#4510B9" fill-opacity=".55" />
                        <circle cx="145" cy="274" r="7" fill="#F55BA8" />
                        <rect x="162" y="270" width="76" height="7" rx="3.5" fill="#4510B9" fill-opacity=".4" />
                        <circle cx="145" cy="304" r="7" fill="#5B17FF" />
                        <rect x="162" y="300" width="104" height="7" rx="3.5" fill="#4510B9" fill-opacity=".4" />
                    </g>
                    <g transform="rotate(7 416 291)">
                        <rect x="337" y="226" width="176" height="133" rx="18" fill="white" />
                        <rect x="355" y="245" width="35" height="35" rx="11" fill="#EDE6FF" />
                        <path d="m365 263 6 6 12-14" stroke="#5B17FF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        <rect x="401" y="249" width="87" height="8" rx="4" fill="#4510B9" fill-opacity=".75" />
                        <rect x="401" y="265" width="61" height="6" rx="3" fill="#4510B9" fill-opacity=".24" />
                        <rect x="355" y="298" width="140" height="1" fill="#4510B9" fill-opacity=".12" />
                        <rect x="355" y="314" width="83" height="22" rx="11" fill="#FCE5F1" />
                        <rect x="366" y="322" width="61" height="6" rx="3" fill="#F55BA8" />
                    </g>
                    <path d="M76 118c13-29 18-36 29-39-11-4-17-12-20-29-4 17-10 25-23 29 13 4 19 12 14 39Z" fill="#FFD92E" />
                    <path d="M531 365c8-18 12-24 20-27-9-3-13-9-16-22-3 13-7 19-17 22 10 3 14 9 13 27Z" fill="#F55BA8" />
                    <circle cx="335" cy="112" r="5" fill="white" fill-opacity=".72" />
                    <circle cx="94" cy="391" r="4" fill="white" fill-opacity=".58" />
                </svg>
            </div>

            <div class="auth-feature-controls" role="group" aria-label="Feature slideshow controls">
                <div class="auth-feature-dots" role="group" aria-label="Choose a Tuklas feature">
                    <button type="button" data-feature-go="0" aria-label="Show career guidance" aria-pressed="true"></button>
                    <button type="button" data-feature-go="1" aria-label="Show AI document scan" aria-pressed="false"></button>
                    <button type="button" data-feature-go="2" aria-label="Show your profile" aria-pressed="false"></button>
                    <button type="button" data-feature-go="3" aria-label="Show local training" aria-pressed="false"></button>
                    <button type="button" data-feature-go="4" aria-label="Show community guidance" aria-pressed="false"></button>
                </div>
                <button class="auth-feature-toggle" type="button" data-feature-toggle aria-pressed="false" aria-label="Pause feature rotation">
                    <span data-toggle-icon aria-hidden="true">Ⅱ</span>
                </button>
            </div>

            <div class="auth-story-foot">
                <span class="auth-foot-dot"></span>
                <p>Thoughtful guidance for young people in Pangasinan.</p>
            </div>
        </section>

        <section class="auth-form-panel" aria-labelledby="auth-title">
            <a class="auth-mobile-brand" href="{{ url('/') }}" aria-label="Tuklas home">✳ <span>tuklas</span></a>
            <div class="auth-form-wrap">
                <a class="auth-back" href="{{ url('/') }}"><span aria-hidden="true">←</span> Back to Tuklas</a>
                <div class="auth-heading">
                    <p class="auth-form-kicker">{{ $mode === 'register' ? 'A GOOD PLACE TO BEGIN' : 'GOOD TO SEE YOU AGAIN' }}</p>
                    <h2 id="auth-title">{{ $title }}</h2>
                    <p>{{ $description }}</p>
                </div>

                {{ $slot }}

                <p class="auth-switch">
                    @if ($mode === 'register')
                        Already have an account? <a href="{{ route('login') }}">Log in</a>
                    @else
                        New to Tuklas? <a href="{{ route('register') }}">Create an account</a>
                    @endif
                </p>
            </div>
            <p class="auth-legal">Tuklas offers guidance and information, not guarantees of employment, admission, or training availability.</p>
        </section>
    </main>
</div>
