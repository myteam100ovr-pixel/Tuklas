<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'Tuklas') }} | AI-powered career path and skills development for youth in Pangasinan</title>
    <meta name="description" content="AI-powered career path and skills development for youth in Pangasinan. Piloting in Bugallon with TESDA Lingayen trainings, powered by Google Gemini AI.">
    <script>document.documentElement.classList.add('js')</script>
    @include('partials.theme-boot')
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>

{{-- ============ HEADER ============ --}}
<header class="hdr" id="hdr">
    <a href="{{ route('home') }}" class="logo" aria-label="Tuklas home">tuklas</a>

    <nav class="pill" id="site-nav" aria-label="Primary">
        <a href="#features">Features</a>
        <a href="#pathways">Pathways</a>
        <a href="{{ route('scanner.index') }}">Scanner</a>
        <a href="{{ route('tesda.index') }}">TESDA</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
    </nav>

    <div class="hdr-actions">
        <button type="button" class="theme-btn" data-theme-toggle aria-label="Switch between light and dark mode" title="Light / dark mode">
            <svg class="t-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="t-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
        </button>
        @auth
            <a class="btn btn-black" href="{{ route('dashboard') }}">Dashboard</a>
        @else
            <a class="login" href="{{ route('login') }}">Log in</a>
            <a class="btn btn-black" href="{{ route('register') }}">Get started</a>
        @endauth
    </div>

    <button class="burger" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu"><i></i><i></i></button>
</header>

<main>

{{-- ============ 1. HERO (copy collapses, stack straightens, card goes full-bleed) ============ --}}
<section class="hero-wrap" id="hero">
    <div class="hero-sticky">
        <div class="hero-copy">
            <div class="hero-copy-in">
                <p class="gem hl"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="gg1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4f8bff"/><stop offset=".55" stop-color="#7b5cff"/><stop offset="1" stop-color="#f55ba8"/></linearGradient></defs><path d="M12 1.5C12 8 16 12 22.5 12C16 12 12 16 12 22.5C12 16 8 12 1.5 12C8 12 12 8 12 1.5Z" fill="url(#gg1)"/></svg>Powered by Google Gemini AI</p>
                <h1 class="display">
                    <span class="hl">AI-Powered Career Path and</span>
                    <span class="hl">Skills Development for Youth in Pangasinan</span>
                </h1>
                <p class="sub hl">Piloting in Bugallon, with TESDA Lingayen trainings.</p>
                <div class="mobile-artwork" aria-hidden="true">
                    <svg viewBox="0 0 390 255" preserveAspectRatio="none">
                        <rect width="390" height="255" fill="#4510b9" />
                        <g fill="none" stroke="#fff" stroke-opacity=".24">
                            <circle cx="289" cy="89" r="34" />
                            <circle cx="289" cy="89" r="63" />
                            <circle cx="289" cy="89" r="92" />
                            <path d="M42 56 C125 15 222 142 430 51" stroke-width="1.5" />
                        </g>
                        <circle cx="66" cy="71" r="8" fill="#ffd92e" />
                        <circle cx="189" cy="122" r="5" fill="#fff" />
                        <path d="M289 124 C254 91 260 47 289 47 C318 47 324 91 289 124Z" fill="#f55ba8" />
                        <circle cx="289" cy="73" r="8" fill="#4510b9" />
                        <path d="M179 171 207 96 218 180Z" fill="#ffd92e" />
                    </svg>
                    <span class="mobile-art-caption">Your future has<br>more than one path.</span>
                    <span class="mobile-art-compass">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15.6 8.4-2.4 5.2-5.2 2.4 2.4-5.2 5.2-2.4Z" fill="#4510b9"/><circle cx="12" cy="12" r="10" fill="none" stroke="#ffd92e" stroke-width="2"/></svg>
                    </span>
                </div>
                <div class="hero-cta">
                    @auth
                        <a class="btn btn-violet" href="{{ route('dashboard') }}">Go to dashboard</a>
                    @else
                        <a class="btn btn-violet" href="{{ route('register') }}">Get started</a>
                        <a class="btn btn-line" href="{{ route('login') }}">Log in</a>
                        <a class="mobile-resource-link" href="{{ route('tesda.index') }}"><span aria-hidden="true">&#8599;</span> TESDA resources</a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="stack" aria-hidden="true">
            <div class="sheet sheet-y"></div>
            <div class="sheet sheet-p"></div>
            <div class="card">
                <div class="art">
                    <svg viewBox="0 0 1600 3200" preserveAspectRatio="xMidYMin slice" focusable="false">
                        <defs>
                            <linearGradient id="gPin" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ff8ac8"/><stop offset="1" stop-color="#f0479a"/></linearGradient>
                            <linearGradient id="gSide" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff9a3c"/><stop offset="1" stop-color="#e2461c"/></linearGradient>
                            <radialGradient id="gSphere" cx=".35" cy=".3" r=".9"><stop offset="0" stop-color="#f1f0ff"/><stop offset=".55" stop-color="#8f96ea"/><stop offset="1" stop-color="#4f45c9"/></radialGradient>
                            <radialGradient id="gSphere2" cx=".3" cy=".25" r="1"><stop offset="0" stop-color="#e9f0c8"/><stop offset=".5" stop-color="#8f9ee8"/><stop offset="1" stop-color="#5a4bd0"/></radialGradient>
                            <linearGradient id="gPencil" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff3a8"/><stop offset=".5" stop-color="#ffd92e"/><stop offset="1" stop-color="#f2a01a"/></linearGradient>
                        </defs>
                        <rect width="1600" height="3200" fill="#4510b9"/>

                        {{-- top: orbit + map pin --}}
                        <g fill="none" stroke="#fff" stroke-opacity=".28" stroke-width="3">
                            <circle cx="470" cy="330" r="150"/><circle cx="470" cy="330" r="230"/>
                            <path d="M40 620 C 420 520, 760 700, 1100 520 S 1500 360, 1580 300"/>
                            <circle cx="150" cy="860" r="34"/>
                        </g>
                        <circle cx="470" cy="330" r="112" fill="url(#gSphere)"/>
                        <circle cx="440" cy="300" r="14" fill="#4f45c9" opacity=".55"/>
                        <g transform="translate(1060 270) rotate(10)">
                            <path d="M0 110 C-100 -20, 10 -150, 110 -60 C210 -150, 320 -20, 220 110 L110 260 Z" fill="url(#gSide)" transform="translate(-20 16)"/>
                            <path d="M0 110 C-100 -20, 10 -150, 110 -60 C210 -150, 320 -20, 220 110 L110 260 Z" fill="url(#gPin)"/>
                            <circle cx="110" cy="70" r="62" fill="#4510b9"/>
                        </g>

                        {{-- middle: compass needle, chat bubble, blocks --}}
                        <g transform="translate(720 1150) rotate(38)">
                            <polygon points="0,-520 90,0 -90,0" fill="url(#gPencil)"/>
                            <polygon points="0,520 90,0 -90,0" fill="#e2461c"/>
                            <polygon points="0,-520 30,-380 -30,-380" fill="#fff3a8"/>
                            <circle cx="0" cy="0" r="46" fill="#4510b9" stroke="#fff" stroke-opacity=".55" stroke-width="8"/>
                        </g>
                        <g fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="3"><circle cx="720" cy="1150" r="360"/><circle cx="720" cy="1150" r="480"/></g>
                        <g transform="translate(150 1180)">
                            <rect width="360" height="220" rx="34" fill="#372692"/>
                            <path d="M60 220 L40 300 L140 220 Z" fill="#372692"/>
                            <circle cx="110" cy="110" r="16" fill="none" stroke="#a9a2ff" stroke-width="5"/><circle cx="170" cy="110" r="16" fill="none" stroke="#a9a2ff" stroke-width="5"/><circle cx="230" cy="110" r="16" fill="none" stroke="#a9a2ff" stroke-width="5"/>
                        </g>
                        <g transform="translate(1180 1010) rotate(-6)">
                            <rect width="230" height="230" rx="24" fill="#4a5fb8"/><rect x="230" y="-60" width="170" height="170" rx="20" fill="#fff3c2"/>
                        </g>
                        <rect x="1140" y="1560" width="300" height="470" rx="46" fill="#5b2ee0" opacity=".75"/>
                        <rect x="1190" y="1620" width="200" height="90" rx="14" fill="#3a1f9c"/>

                        {{-- bottom: globe, tent, radar --}}
                        <circle cx="520" cy="2450" r="470" fill="url(#gSphere2)"/>
                        <path d="M240 2390 Q 330 2540, 470 2470" fill="none" stroke="#111" stroke-width="7" stroke-linecap="round"/>
                        <ellipse cx="330" cy="2330" rx="10" ry="26" fill="#111"/><ellipse cx="450" cy="2330" rx="10" ry="26" fill="#111"/>
                        <g transform="translate(1030 2380)">
                            <polygon points="150,0 300,330 0,330" fill="#f0479a"/><polygon points="150,110 220,300 80,300" fill="#ffb02e" opacity=".75"/>
                            <rect x="-20" y="330" width="340" height="150" rx="16" fill="#3a1f9c"/>
                            <circle cx="150" cy="405" r="48" fill="none" stroke="#a9a2ff" stroke-width="12"/><path d="M150 357 A48 48 0 0 1 198 405" fill="none" stroke="#ffd92e" stroke-width="12"/>
                        </g>
                        <circle cx="1400" cy="2260" r="26" fill="#fff3c2"/><circle cx="1350" cy="2330" r="12" fill="#ffd92e"/>
                        <g fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="3"><path d="M40 3020 C 500 2930, 900 3060, 1580 2960"/></g>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ 2. ANALYTICS-STYLE PINNED BLOCK (3 states, falling chips, counters) ============ --}}
<section class="an" id="features">
    <div class="an-run">
        <div class="an-sticky">
            <p class="mobile-eyebrow">A path that starts with you</p>
            <h2 class="title">Guidance built around you</h2>

            <div class="an-grid">
                {{-- LEFT CARD: 3 crossfading states --}}
                <div class="lc">
                    <div class="lc-views">
                        <article class="lc-view is-on" data-i="0">
                            <h3>Interests and Skills</h3>
                            <p class="mobile-guidance-copy">Share your interests and skills to help shape career and training guidance. Suggestions are guidance, not guarantees of jobs, admission, or training slots.</p>
                            <div class="lc-panel chips" aria-hidden="true">
                                <span class="chip" style="--x:30%;--y:34%;--r:-4deg">Electronics</span>
                                <span class="chip" style="--x:6%;--y:44%;--r:12deg">Cooking</span>
                                <span class="chip" style="--x:50%;--y:38%;--r:-10deg">Farming</span>
                                <span class="chip" style="--x:22%;--y:52%;--r:4deg">Computers</span>
                                <span class="chip" style="--x:58%;--y:50%;--r:16deg">Sewing</span>
                                <span class="chip" style="--x:2%;--y:60%;--r:-18deg">Driving</span>
                                <span class="chip" style="--x:34%;--y:64%;--r:-6deg">Drawing</span>
                                <span class="chip" style="--x:62%;--y:64%;--r:-14deg">Baking</span>
                                <span class="chip" style="--x:10%;--y:76%;--r:8deg">Selling</span>
                                <span class="chip" style="--x:40%;--y:78%;--r:2deg">Caregiving</span>
                                <span class="chip" style="--x:66%;--y:78%;--r:10deg">Welding</span>
                            </div>
                        </article>

                        <article class="lc-view" data-i="1">
                            <h3>Profile Overview</h3>
                            <div class="lc-panel bars" aria-hidden="true">
                                <div class="hb" style="--w:71%"><i><b>71 %</b></i><span>Interests</span></div>
                                <div class="hb" style="--w:54%"><i><b>54 %</b></i><span>Skills</span></div>
                                <div class="hb" style="--w:36%"><i><b>36 %</b></i><span>Schooling</span></div>
                                <div class="hb" style="--w:37%"><i><b>37 %</b></i><span>Credentials</span></div>
                                <div class="hb" style="--w:28%"><i><b>28 %</b></i><span>Livelihood</span></div>
                                <div class="hb" style="--w:18%"><i><b>18 %</b></i><span>Other info</span></div>
                            </div>
                        </article>

                        <article class="lc-view" data-i="2">
                            <h3>Assessment Progress</h3>
                            <div class="lc-panel curve" aria-hidden="true">
                                <div class="toggles"><span class="tg on">&#8645; by skill</span><span class="tg">&#8645; by interest</span></div>
                                <svg viewBox="0 0 400 170" preserveAspectRatio="none">
                                    <path class="cv" vector-effect="non-scaling-stroke" d="M0 90 C 25 20, 55 20, 80 95 S 125 160, 150 110 S 215 20, 250 60 S 320 120, 400 40" fill="none" stroke="#ffd92e" stroke-width="4" stroke-linecap="round"/>
                                    <line x1="215" y1="40" x2="215" y2="150" stroke="#111" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke"/>
                                </svg>
                                <i class="dot yl" style="left:53.75%;top:calc(2.6rem + (100% - 4.8rem) * .235)"></i>
                                <span class="tip">Step 3 of 5</span>
                                <div class="axis"><i>Wk 1</i><i>Wk 2</i><i>Wk 3</i><i>Wk 4</i><i>Wk 5</i><i>Wk 6</i></div>
                            </div>
                        </article>
                    </div>

                    <div class="seg" aria-hidden="true">
                        <i class="s1"><b></b></i><i class="s2"><b></b></i><i class="s3"><b></b></i>
                    </div>
                </div>

                {{-- RIGHT CARD: counters + chart --}}
                <div class="rc">
                    <h3>Your Guidance Preview</h3>
                    <div class="rc-body">
                        <div class="tiles">
                            <div class="tile t-y"><small>Profile complete</small><strong><span data-count="a">+24</span>%</strong></div>
                            <div class="tile t-p"><small>Assessment done</small><strong><span data-count="b">+46</span>%</strong></div>
                        </div>
                        <div class="chart-col">
                            <div class="tabs" role="tablist" aria-label="Profile area">
                                <span class="tab-thumb"></span>
                                <button role="tab" aria-selected="false" type="button">Overall</button>
                                <button role="tab" aria-selected="true" class="on" type="button">Skills</button>
                                <button role="tab" aria-selected="false" type="button">Interests</button>
                                <button role="tab" aria-selected="false" type="button">Schooling</button>
                                <button role="tab" aria-selected="false" type="button">Credentials</button>
                                <button role="tab" aria-selected="false" type="button">Livelihood</button>
                            </div>
                            <div class="line">
                                <div class="gl"></div><div class="gl"></div><div class="gl"></div>
                                <svg viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true">
                                    <polyline points="0,170 40,138 95,86" fill="none" stroke="#f9a3cc" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                                    <polyline points="95,86 235,182 335,96 395,20" fill="none" stroke="#f55ba8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                                    <line x1="95" y1="86" x2="95" y2="200" stroke="#f55ba8" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke"/>
                                    <line x1="335" y1="96" x2="335" y2="200" stroke="#f55ba8" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke"/>
                                </svg>
                                <i class="dot pk" style="left:23.75%;top:43%"></i><i class="dot pk" style="left:83.75%;top:48%"></i>
                                <span class="mk m1"><span data-count="m1">+34</span>%</span>
                                <span class="mk m2"><span data-count="m2">+54</span>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="sample-note">Sample preview with example numbers. It is not real user data.</p>
        </div>
    </div>
</section>

{{-- ============ 3. PATHWAYS (big title + ticker pill + rising staggered cards) ============ --}}
<section class="pw" id="pathways">
    <div class="pw-run">
        <div class="pw-sticky">
            <div class="pw-title">
                <svg class="float-ic" viewBox="0 0 120 120" aria-hidden="true"><g transform="rotate(-18 60 60)"><rect x="12" y="28" width="96" height="64" rx="12" fill="#3a14b0"/><path d="M12 36 L60 68 L108 36" fill="none" stroke="#7d5cff" stroke-width="6" stroke-linejoin="round"/><circle cx="60" cy="68" r="8" fill="#ffd92e"/></g><circle cx="102" cy="34" r="12" fill="#5b17ff" stroke="#fff" stroke-width="3"/></svg>
                <h2>
                    <span class="w">Pathways</span>
                    <span class="tick" aria-hidden="true"><span class="tick-in">tuklas &nbsp; tuklas &nbsp; tuklas &nbsp; tuklas &nbsp; tuklas &nbsp; tuklas</span></span>
                </h2>
            </div>

            <div class="pw-cards">
                <article class="pc c1">
                    <p>Tell Us<br>About You</p>
                    <div class="pc-tile o"><small>Profile</small><b>01</b>
                        <svg viewBox="0 0 60 60" aria-hidden="true"><circle cx="34" cy="24" r="16" fill="#3a14b0"/><path d="M6 58 C10 36, 40 36, 46 58 Z" fill="#ffd92e"/></svg>
                        <em>&rarr;</em></div>
                </article>
                <article class="pc c2">
                    <p>Take the Skills<br>Assessment</p>
                    <div class="pc-tile p"><small>Assessment</small><b>02</b>
                        <svg viewBox="0 0 60 60" aria-hidden="true"><rect x="10" y="6" width="38" height="48" rx="8" fill="#fff" opacity=".9"/><path d="M18 22 l6 6 l12 -12" fill="none" stroke="#4510b9" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/><rect x="18" y="38" width="22" height="5" rx="2.5" fill="#f55ba8"/></svg>
                        <em>&rarr;</em></div>
                </article>
                <article class="pc c3">
                    <p>Explore Careers<br>and Trainings</p>
                    <div class="pc-tile v"><small>Recommendations</small><b>03</b>
                        <svg viewBox="0 0 60 60" aria-hidden="true"><polygon points="30,4 38,24 58,26 42,40 47,58 30,48 13,58 18,40 2,26 22,24" fill="#ffd92e"/></svg>
                        <em>&rarr;</em></div>
                </article>
            </div>
        </div>
    </div>
</section>

{{-- ============ 4. STATEMENT (masked line reveal) ============ --}}
<section class="stmt" id="about">
    <p class="stmt-p">
        <span class="ln"><span>Tuklas Reads the Interests and</span></span>
        <span class="ln"><span>Skills You Share <svg class="ico" viewBox="0 0 60 60" aria-hidden="true"><defs><linearGradient id="iH" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ff8ac8"/><stop offset="1" stop-color="#f0479a"/></linearGradient></defs><path d="M6 24 C2 8, 24 2, 30 16 C36 2, 58 8, 54 24 L30 56 Z" fill="url(#iH)"/><circle cx="30" cy="24" r="9" fill="#4510b9"/></svg> to Offer</span></span>
        <span class="ln"><span>Career and Training Guidance</span></span>
        <span class="ln"><span>Made for Bugallon <svg class="ico" viewBox="0 0 60 60" aria-hidden="true"><defs><linearGradient id="iS" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd92e"/><stop offset=".55" stop-color="#f55ba8"/><stop offset="1" stop-color="#6d4bea"/></linearGradient></defs><circle cx="30" cy="30" r="28" fill="url(#iS)"/><path d="M6 34 H54 M9 42 H51 M14 50 H46" stroke="#fff" stroke-opacity=".55" stroke-width="3"/></svg> Youth</span></span>
    </p>
</section>

{{-- ============ 5. STAT ROWS (count-up on scroll) ============ --}}
<section class="stats" aria-label="Tuklas at a glance">
    <article class="row">
        <div class="tileimg"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#4510b9"/><path d="M-10 84 C 30 50, 60 90, 110 40 L110 110 L-10 110Z" fill="#3a14b0"/><circle cx="66" cy="36" r="14" fill="#8f96ea"/><rect x="30" y="46" width="28" height="30" rx="6" fill="#f47c33"/><path d="M36 60 l6 6 l10 -12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/></svg></div>
        <div class="numwrap"><span class="tag t-o">Ages</span><div class="num" data-to="15,30" data-sep="–">15–30</div></div>
        <p class="desc">Tuklas is for Filipino youth aged 15 to 30 in Bugallon, Pangasinan. Guardian consent is part of sign-up for users aged 15 to 17.</p>
    </article>
    <article class="row">
        <div class="tileimg"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#6f84e8"/><path d="M50 14 C 20 26, 24 62, 50 86 C 76 62, 80 26, 50 14Z" fill="none" stroke="#3a14b0" stroke-width="9"/><circle cx="50" cy="48" r="10" fill="#ffd92e"/></svg></div>
        <div class="numwrap"><span class="tag t-p">Roles</span><div class="num" data-to="3">3</div></div>
        <p class="desc">Youth, PESO Bugallon as Super Administrator, and TESDA Lingayen as Trainer. Each role only sees the features it needs.</p>
    </article>
    <article class="row">
        <div class="tileimg"><svg viewBox="0 0 100 100" aria-hidden="true"><rect width="100" height="100" fill="#3a14b0"/><rect x="18" y="22" width="64" height="12" rx="6" fill="#f55ba8"/><rect x="18" y="44" width="64" height="12" rx="6" fill="#ffd92e"/><rect x="18" y="66" width="40" height="12" rx="6" fill="#8f96ea"/></svg></div>
        <div class="numwrap"><span class="tag t-v">Inputs</span><div class="num" data-to="5">5</div></div>
        <p class="desc">Interests, skills, educational attainment, credentials, and livelihood interests shape what Tuklas suggests, along with other details you choose to provide.</p>
    </article>
</section>

{{-- ============ 5b. GEMINI RESUME + CERTIFICATE SCAN ============ --}}
<section class="scan" id="scan">
    <div class="scan-card">
        <div class="scan-copy">
            <p class="gem"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="gg2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4f8bff"/><stop offset=".55" stop-color="#7b5cff"/><stop offset="1" stop-color="#f55ba8"/></linearGradient></defs><path d="M12 1.5C12 8 16 12 22.5 12C16 12 12 16 12 22.5C12 16 8 12 1.5 12C8 12 12 8 12 1.5Z" fill="url(#gg2)"/></svg>Powered by Google Gemini AI</p>
            <h2>Scan Your Resume<br>and Certifications</h2>
            <p class="scan-lead">Upload a resume or a certificate and Google Gemini reads it to suggest skills, schooling, and credentials for your profile.</p>
            <ul class="scan-points">
                <li>You review and confirm every detail before it is saved.</li>
                <li>Uploading is optional. You can type everything in yourself.</li>
                <li>Files are sent to Google Gemini to be read, so upload only what you are comfortable sharing.</li>
            </ul>
        </div>

        <div class="scan-demo" aria-hidden="true">
            <div class="doc">
                <div class="dl dh" style="--w:46%"></div>
                <div class="dl" style="--w:72%"></div>
                <div class="dl" style="--w:58%"></div>
                <div class="dl dh sp" style="--w:34%"></div>
                <div class="dl" style="--w:90%"></div>
                <div class="dl" style="--w:82%"></div>
                <div class="dl" style="--w:66%"></div>
                <div class="dl dh sp" style="--w:40%"></div>
                <div class="dl" style="--w:88%"></div>
                <div class="dl" style="--w:54%"></div>
                <div class="beam"></div>
            </div>
            <div class="found">
                <div class="fd"><small>Skills</small><span>Computer basics, customer service</span></div>
                <div class="fd"><small>Schooling</small><span>Senior high school graduate</span></div>
                <div class="fd"><small>Credentials</small><span>Certificate of completion</span></div>
                <div class="fd ok"><small>Next</small><span>Review and confirm</span></div>
            </div>
            <p class="scan-cap">Illustration only. Not a real document.</p>
        </div>
    </div>
</section>

{{-- ============ 6. STAGGERED PARALLAX COLUMNS ============ --}}
<section class="how" id="how">
    <h2 class="title">See How<br>Tuklas Helps</h2>
    <div class="cols">
        <div class="col c-a" data-speed="70">
            <article class="qc"><i class="qm">&#10022;</i><p>Share your interests, skills, schooling, credentials, and livelihood interests once, and update them when your situation changes.</p><footer><b>Youth users</b><span>Profile</span></footer></article>
            <article class="qc"><i class="qm">&#10022;</i><p>Oversee users and keep career, skills, and training information organized in one place.</p><footer><b>PESO Bugallon</b><span>Super Administrator</span></footer></article>
        </div>
        <div class="col c-b" data-speed="130">
            <article class="qc"><i class="qm">&#10022;</i><p>Complete the skills assessment and see suggested careers and TESDA Lingayen programs, with the reasons behind each suggestion.</p><footer><b>Youth users</b><span>Recommendations</span></footer></article>
            <article class="qc"><i class="qm">&#10022;</i><p>Manage and publish the training information and opportunities that TESDA Lingayen offers.</p><footer><b>TESDA Lingayen</b><span>Trainer</span></footer></article>
        </div>
        <div class="col c-c" data-speed="95">
            <article class="qc"><i class="qm">&#10022;</i><p>Suggestions are guidance to help you decide. They are not guarantees of a job, admission, or a training slot.</p><footer><b>Every user</b><span>Guidance only</span></footer></article>
            <article class="qc"><i class="qm">&#10022;</i><p>Personal information is protected, and users aged 15 to 17 have guardian consent built into sign-up.</p><footer><b>Data privacy</b><span>Built in</span></footer></article>
        </div>
    </div>
</section>

{{-- ============ 7. FAQ ============ --}}
<section class="faq-s" id="faq">
    <h2 class="title">Frequently Asked<br>Questions</h2>
    <div class="faq">
        <div class="q"><button type="button" aria-expanded="false"><span>Who can use Tuklas?</span><i></i></button><div class="a"><p>Filipino youth aged 15 to 30 in the Municipality of Bugallon, Pangasinan. If you are 15 to 17, a guardian's consent is required.</p></div></div>
        <div class="q"><button type="button" aria-expanded="false"><span>Which trainings does Tuklas show?</span><i></i></button><div class="a"><p>Only TVET programs offered by TESDA Lingayen, entered and maintained by authorized staff. If a program is not listed, it is not in the system.</p></div></div>
        <div class="q"><button type="button" aria-expanded="false"><span>Does Tuklas guarantee a job or a training slot?</span><i></i></button><div class="a"><p>No. Suggestions are guidance to help you decide. Employment, admission, and slot availability are decided by employers and TESDA Lingayen.</p></div></div>
        <div class="q"><button type="button" aria-expanded="false"><span>How does the resume and certificate scan work?</span><i></i></button><div class="a"><p>You can upload a resume or certificate for Google Gemini to summarize supported skills and qualifications and suggest job roles and published TESDA training to explore. The uploaded file is deleted after processing. Your result appears in scan history and does not change your profile automatically. Uploading is optional.</p></div></div>
        <div class="q"><button type="button" aria-expanded="false"><span>Is there a mobile app available?</span><i></i></button><div class="a"><p>Tuklas works in your phone's browser today. A dedicated mobile app is planned.</p></div></div>
        <div class="q"><button type="button" aria-expanded="false"><span>How does Tuklas protect my personal information?</span><i></i></button><div class="a"><p>Tuklas asks only for information needed to give guidance, and processing happens on the server. Files you choose to scan are sent to Google Gemini to be read. A privacy notice is shown when you register.</p></div></div>
    </div>
</section>

{{-- ============ 7b. TECH STACK + SYSTEM FUNCTIONS (two looping lines) ============ --}}
<section class="tech" aria-label="Technology and functions of Tuklas">
    <h2 class="tech-h">Built With</h2>
    <div class="marq">
        <div class="marq-track">
            <ul class="marq-set">
                @include('partials.tech-logos')
            </ul>
        </div>
    </div>

    <h2 class="tech-h tech-h2">What the System Does</h2>
    <div class="marq rev">
        <div class="marq-track">
            <ul class="marq-set">
                @include('partials.function-chips')
            </ul>
        </div>
    </div>
</section>

{{-- ============ 8. CTA BANNER + FOOTER ============ --}}
<section class="cta-s">
    <div class="cta">
        <div class="cta-copy">
            <h2>Take the First Step<br>Toward Your Next Career.</h2>
            @auth
                <a class="btn btn-ghost" href="{{ route('dashboard') }}">Go to dashboard</a>
            @else
                <a class="btn btn-ghost" href="{{ route('register') }}">Get started</a>
            @endauth
        </div>
        <svg class="cta-art" viewBox="0 0 400 400" aria-hidden="true" focusable="false">
            <circle cx="120" cy="60" r="34" fill="#4510b9"/><circle cx="60" cy="28" r="12" fill="#ffd92e"/>
            <g transform="translate(210 210) rotate(14)"><rect x="-120" y="-130" width="240" height="260" rx="44" fill="#c9cdf2"/><rect x="-96" y="-104" width="192" height="208" rx="30" fill="#5b3fe0"/>
            <polygon points="0,-70 34,10 -34,10" fill="#ffd92e"/><polygon points="0,70 34,10 -34,10" fill="#f0479a"/><circle cx="0" cy="10" r="12" fill="#3a14b0"/></g>
            <path d="M40 420 C 60 300, 150 330, 190 420" fill="none" stroke="#4510b9" stroke-width="34" stroke-linecap="round"/>
        </svg>
    </div>
</section>
</main>

<footer class="foot" id="contact">
    <div class="foot-top">
        <div class="foot-brand">
            <a href="{{ route('home') }}" class="foot-logo" aria-label="Tuklas home">tuklas</a>
            <p>AI-powered career path and skills development for youth in Pangasinan, piloting in Bugallon.</p>
            <p class="gem sm"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="gg3" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4f8bff"/><stop offset=".55" stop-color="#7b5cff"/><stop offset="1" stop-color="#f55ba8"/></linearGradient></defs><path d="M12 1.5C12 8 16 12 22.5 12C16 12 12 16 12 22.5C12 16 8 12 1.5 12C8 12 12 8 12 1.5Z" fill="url(#gg3)"/></svg>Powered by Google Gemini AI</p>
        </div>

        <nav class="foot-col" aria-label="Explore">
            <h3>Explore</h3>
            <a href="#features">Features</a>
            <a href="#pathways">Pathways</a>
            <a href="{{ route('scanner.index') }}">Scanner</a>
            <a href="{{ route('tesda.index') }}">TESDA</a>
            <a href="#how">How it helps</a>
            <a href="#faq">FAQ</a>
        </nav>

        <nav class="foot-col" aria-label="Account">
            <h3>Get Started</h3>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('register') }}">Create an account</a>
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </nav>

        <div class="foot-col">
            <h3>About the Study</h3>
            <span>Municipality of Bugallon, Pangasinan</span>
            <span>TESDA Lingayen programs only</span>
            <span>Youth aged 15 to 30</span>
        </div>

        <div class="foot-col">
            <h3>Contact</h3>
            <span>Questions about Tuklas? Reach out to PESO Bugallon.</span>
        </div>
    </div>

    <p class="foot-note">Recommendations are guidance to support informed decisions, not guarantees of employment, admission, or training availability.</p>

    <div class="foot-word" aria-hidden="true">tuklas</div>

    <div class="foot-bottom">
        <span>&copy; {{ date('Y') }} Tuklas</span>
        <span>A capstone project of Tour de Force, Universidad de Dagupan</span>
        <a class="totop" href="#top">Back to top &uarr;</a>
    </div>
</footer>

</body>
</html>
