<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tuklas') }} | Career and training guidance for Bugallon youth</title>
    <meta name="description" content="Tuklas helps Filipino youth aged 15 to 30 in Bugallon, Pangasinan explore careers and TESDA Lingayen training opportunities.">
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>

<header class="site-header" id="top">
    <a href="{{ route('home') }}" class="brand" aria-label="Tuklas home">tuklas</a>

    <nav class="nav-pill" id="site-nav" aria-label="Primary">
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="#roles">Who it's for</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
    </nav>

    <div class="header-actions">
        @auth
            <a class="btn btn-dark" href="{{ route('dashboard') }}">Dashboard</a>
        @else
            <a class="link" href="{{ route('login') }}">Log in</a>
            <a class="btn btn-dark" href="{{ route('register') }}">Create account</a>
        @endauth
    </div>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
        <span></span><span></span>
    </button>
</header>

<main>
    {{-- HERO --}}
    <section class="hero wrap">
        <h1 class="display reveal">Find a path that<br>fits your future.</h1>
        <p class="lead reveal" style="--d:.1s">
            Tuklas guides Filipino youth aged 15 to 30 in Bugallon, Pangasinan toward careers and
            TESDA Lingayen training opportunities, based on your interests, skills, and schooling.
        </p>
        <div class="hero-cta reveal" style="--d:.2s">
            @auth
                <a class="btn btn-violet" href="{{ route('dashboard') }}">Go to your dashboard</a>
            @else
                <a class="btn btn-violet" href="{{ route('register') }}">Create an account</a>
                <a class="btn btn-outline" href="{{ route('login') }}">Log in</a>
            @endauth
        </div>

        <div class="hero-stack" aria-hidden="true">
            <div class="sheet sheet-yellow"></div>
                <div class="sheet sheet-pink"></div>
                <div class="sheet sheet-violet">
                    <svg viewBox="0 0 1000 460" preserveAspectRatio="xMidYMid slice" focusable="false">
                        <defs>
                            <linearGradient id="portrait" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#c6c4ff"/>
                                <stop offset="1" stop-color="#7582f0"/>
                            </linearGradient>
                            <linearGradient id="path" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#ffe86a"/>
                                <stop offset="1" stop-color="#ff8b55"/>
                            </linearGradient>
                            <filter id="card-shadow" x="-30%" y="-30%" width="160%" height="160%">
                                <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#1e075c" flood-opacity=".38"/>
                            </filter>
                        </defs>
                        <g class="art-orbits" fill="none" stroke="#fff" stroke-opacity=".32">
                            <circle cx="270" cy="235" r="94"/>
                            <circle cx="270" cy="235" r="142"/>
                            <circle cx="270" cy="235" r="190"/>
                            <path d="M65 383C230 290 330 410 483 305s254-65 441-183" stroke-dasharray="2 12" stroke-linecap="round"/>
                            <path d="M593 420c-41-91 46-139 137-109 78 26 97 87 209 64"/>
                        </g>
                        <g class="art-profile" filter="url(#card-shadow)">
                            <circle cx="270" cy="235" r="76" fill="url(#portrait)"/>
                            <path d="M212 264c15-29 41-43 62-43 24 0 48 14 62 43a76 76 0 0 1-124 0Z" fill="#4530a9" opacity=".82"/>
                            <circle cx="270" cy="205" r="25" fill="#ffd98a"/>
                            <path d="M238 208c0-34 17-53 39-53 18 0 30 12 34 31-17-9-43-3-73 22Z" fill="#272060"/>
                            <path d="M223 278c26-17 66-18 95 0" fill="none" stroke="#fff" stroke-opacity=".65" stroke-width="3" stroke-linecap="round"/>
                        </g>
                        <g class="art-route">
                            <path d="M432 442c79-83 89-143 159-172 47-20 79-7 115-63 30-46 44-94 91-129" fill="none" stroke="#230a6e" stroke-width="48" stroke-linecap="round"/>
                            <path d="M432 429c79-83 89-143 159-172 47-20 79-7 115-63 30-46 44-94 91-129" fill="none" stroke="url(#path)" stroke-width="29" stroke-linecap="round"/>
                            <path d="M432 429c79-83 89-143 159-172 47-20 79-7 115-63 30-46 44-94 91-129" fill="none" stroke="#fff8ce" stroke-opacity=".8" stroke-width="3" stroke-dasharray="3 12" stroke-linecap="round"/>
                        </g>
                        <g class="art-next" filter="url(#card-shadow)" transform="rotate(8 758 243)">
                            <rect x="638" y="174" width="241" height="152" rx="22" fill="#fff"/>
                            <circle cx="677" cy="215" r="16" fill="#ffd92e"/>
                            <path d="m670 215 6 6 10-13" fill="none" stroke="#4d16ce" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="707" y="204" width="130" height="9" rx="4.5" fill="#ded9ef"/>
                            <rect x="707" y="221" width="92" height="7" rx="3.5" fill="#eeeaf5"/>
                            <rect x="662" y="254" width="191" height="45" rx="12" fill="#f3efff"/>
                            <path d="m681 277 15 8 17-18 19 10 16-16" fill="none" stroke="#f45ca9" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="748" cy="261" r="5" fill="#4d16ce"/>
                            <path d="m813 268 8 8-8 8" fill="none" stroke="#4d16ce" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        <g class="art-badge" filter="url(#card-shadow)" transform="rotate(-12 891 118)">
                            <rect x="839" y="62" width="107" height="107" rx="28" fill="#f45ca9"/>
                            <path d="m892 139-28-26c-20-20 8-45 28-22 20-23 48 2 28 22l-28 26Z" fill="#4d16ce"/>
                        </g>
                        <circle class="art-spark" cx="116" cy="113" r="13" fill="#ffd92e"/>
                        <path d="m549 100 9 19 20 3-15 13 4 20-18-10-18 10 4-20-15-13 20-3 9-19Z" fill="#f45ca9"/>
                    </svg>
                </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section class="section wrap" id="features">
        <h2 class="section-title reveal">Guidance built from<br>what you tell us</h2>
        <div class="grid-2">
            <article class="panel reveal">
                <h3>What you share</h3>
                <div class="bars" aria-hidden="true">
                    <div class="bar"><i style="--w:88%"></i><span>Interests</span></div>
                    <div class="bar"><i style="--w:74%"></i><span>Skills</span></div>
                    <div class="bar"><i style="--w:62%"></i><span>Educational attainment</span></div>
                    <div class="bar"><i style="--w:48%"></i><span>Credentials</span></div>
                    <div class="bar"><i style="--w:36%"></i><span>Livelihood interests</span></div>
                </div>
                <p class="caption">Illustration only. Bar lengths are not real data.</p>
            </article>

            <article class="panel reveal" style="--d:.1s">
                <h3>What you get back</h3>
                <div class="tiles">
                    <div class="tile tile-yellow"><span>Explore</span><strong>Careers</strong></div>
                    <div class="tile tile-pink"><span>Explore</span><strong>Trainings</strong></div>
                </div>
                <p class="caption">Suggested careers and TESDA Lingayen programs, each with the reasons behind the suggestion.</p>
            </article>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="section wrap" id="how">
        <h2 class="section-title reveal">Three steps to your<br>next move</h2>
        <div class="steps">
            <article class="step reveal">
                <h3>Build your profile</h3>
                <div class="step-card c-orange"><small>Step</small><b>01</b></div>
                <p>Share your interests, skills, education, credentials, and livelihood interests.</p>
            </article>
            <article class="step reveal" style="--d:.1s">
                <h3>Take the skills assessment</h3>
                <div class="step-card c-pink"><small>Step</small><b>02</b></div>
                <p>Answer a short set of questions so suggestions reflect you, not a generic list.</p>
            </article>
            <article class="step reveal" style="--d:.2s">
                <h3>Explore careers and trainings</h3>
                <div class="step-card c-violet"><small>Step</small><b>03</b></div>
                <p>See suggested careers and TESDA Lingayen programs so you can decide what to pursue.</p>
            </article>
        </div>
    </section>

    {{-- STATEMENT --}}
    <section class="section wrap">
        <p class="statement reveal">
            Tuklas turns what you tell us into guidance on careers <span class="orb orb-a"></span>
            and TESDA Lingayen trainings <span class="orb orb-b"></span> to help you decide,
            never to decide for you.
        </p>
    </section>

    {{-- SCOPE FACTS --}}
    <section class="section wrap" id="about">
        <div class="facts">
            <article class="fact reveal">
                <div class="fact-art"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#4a12c9"/><circle cx="62" cy="40" r="22" fill="#f45ca9"/><circle cx="30" cy="70" r="12" fill="#ffd92e"/></svg></div>
                <div class="fact-main"><span class="tag" style="background:var(--orange)">Ages</span><div class="fact-big">15–30</div></div>
                <div class="fact-side"><p>Tuklas is for Filipino youth aged 15 to 30. Users aged 15 to 17 need guardian consent before getting recommendations.</p></div>
            </article>
            <article class="fact reveal">
                <div class="fact-art"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#6f84e8"/><rect x="24" y="24" width="52" height="52" rx="14" fill="#4a12c9" transform="rotate(12 50 50)"/><circle cx="50" cy="50" r="10" fill="#ffd92e"/></svg></div>
                <div class="fact-main"><span class="tag" style="background:var(--pink)">Where</span><div class="fact-big">Bugallon</div></div>
                <div class="fact-side"><p>Built for, and evaluated with, the Municipality of Bugallon, Pangasinan.</p></div>
            </article>
            <article class="fact reveal">
                <div class="fact-art"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#f45ca9"/><circle cx="50" cy="50" r="30" fill="none" stroke="#fff" stroke-width="3"/><circle cx="50" cy="50" r="14" fill="#4a12c9"/></svg></div>
                <div class="fact-main"><span class="tag" style="background:var(--violet)">Lingayen only</span><div class="fact-big">TESDA</div></div>
                <div class="fact-side"><p>Only TVET programs offered by TESDA Lingayen are listed. No programs from other centers or providers.</p></div>
            </article>
        </div>
    </section>

    {{-- ROLES --}}
    <section class="section wrap" id="roles">
        <h2 class="section-title reveal">Made for the people<br>who use it</h2>
        <div class="roles">
            <article class="role reveal">
                <h3>Youth</h3>
                <p>Create and manage your profile, complete the skills assessment, and view career and training recommendations.</p>
            </article>
            <article class="role reveal" style="--d:.1s">
                <h3>PESO Bugallon</h3>
                <p>Oversees the platform: manages users, career information, skills information, and training opportunities.</p>
            </article>
            <article class="role reveal" style="--d:.2s">
                <h3>TESDA Lingayen</h3>
                <p>Manages and publishes its own training information and opportunities.</p>
            </article>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="section wrap" id="faq">
        <h2 class="section-title reveal">Frequently asked<br>questions</h2>
        <div class="faq reveal">
            <details>
                <summary>Who can use Tuklas?</summary>
                <p>Filipino youth aged 15 to 30 in the Municipality of Bugallon, Pangasinan. If you are 15 to 17, a guardian's consent is required.</p>
            </details>
            <details>
                <summary>Which trainings does Tuklas show?</summary>
                <p>Only TVET programs offered by TESDA Lingayen, as entered and maintained by authorized staff. If a program is not listed, it is not in the system.</p>
            </details>
            <details>
                <summary>Does Tuklas guarantee a job or a training slot?</summary>
                <p>No. Suggestions are guidance to help you decide. Employment, admission, and slot availability are decided by employers and TESDA Lingayen.</p>
            </details>
            <details>
                <summary>How is my personal information handled?</summary>
                <p>Tuklas asks only for information needed to give guidance, and sensitive processing happens on the server. A privacy notice is shown when you register.</p>
            </details>
            <details>
                <summary>Can I use Tuklas on my phone?</summary>
                <p>Yes, in your phone's browser. A dedicated mobile app is planned.</p>
            </details>
        </div>
    </section>

    {{-- CTA --}}
    <section class="wrap">
        <div class="cta reveal">
            <div>
                <h2>Ready to find<br>your next step?</h2>
                @auth
                    <a class="btn btn-light" href="{{ route('dashboard') }}">Go to your dashboard</a>
                @else
                    <a class="btn btn-light" href="{{ route('register') }}">Create an account</a>
                @endauth
            </div>
            <svg viewBox="0 0 320 240" aria-hidden="true" focusable="false">
                <circle cx="200" cy="120" r="90" fill="#4a12c9"/>
                <circle cx="200" cy="120" r="56" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="3"/>
                <path d="M40 200 C 100 120, 160 190, 230 90 S 290 60, 305 40" fill="none" stroke="#ffd92e" stroke-width="4" stroke-linecap="round" stroke-dasharray="2 12"/>
                <circle cx="230" cy="90" r="14" fill="#f45ca9"/>
                <circle cx="60" cy="60" r="12" fill="#ffd92e"/>
            </svg>
        </div>
    </section>
</main>

<footer class="footer wrap" id="contact">
    <div class="footer-main">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="brand">tuklas</a>
            <p>Career and training guidance for youth in Bugallon, Pangasinan.</p>
        </div>

        <nav class="footer-column" aria-label="Explore Tuklas">
            <h2>Explore</h2>
            <a href="#features">Guidance</a>
            <a href="#how">How it works</a>
            <a href="#roles">Who it's for</a>
            <a href="#faq">Frequently asked questions</a>
        </nav>

        <nav class="footer-column" aria-label="Your account">
            <h2>Your account</h2>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('register') }}">Create an account</a>
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </nav>

        <nav class="footer-column" aria-label="Policies">
            <h2>Policies</h2>
            @if (Route::has('policy.show'))
                <a href="{{ route('policy.show') }}">Privacy policy</a>
            @endif
            @if (Route::has('terms.show'))
                <a href="{{ route('terms.show') }}">Terms of service</a>
            @endif
            <a href="#contact">Contact PESO Bugallon</a>
        </nav>
    </div>

    <div class="footer-bottom">
        <small>&copy; {{ date('Y') }} Tuklas. For questions, contact PESO Bugallon.</small>
        <small>Recommendations are guidance, not guarantees of employment, admission, or training availability.</small>
    </div>
</footer>

</body>
</html>
