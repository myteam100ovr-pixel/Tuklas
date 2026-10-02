<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tuklas</title>

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>

{{-- Header --}}
<header class="site-header">
    <a href="{{ route('home') }}" class="logo" aria-label="Tuklas home">tuklas</a>

    <nav class="nav-pill" aria-label="Main">
        <a href="#features">Features</a>
        <a href="#pathways">Pathways</a>
        <a href="#ai-scan">AI Scan</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
    </nav>

    <div class="header-actions">
        <a href="{{ Route::has('login') ? route('login') : url('/login') }}">Log in</a>
        <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn-get">Get started</a>
    </div>
</header>

<main>

{{-- Hero --}}
<section class="hero" id="top">
    <div class="hero-stage">
        <div class="hero-layer hero-layer-yellow" aria-hidden="true"></div>
        <div class="hero-layer hero-layer-pink" aria-hidden="true"></div>
        <div class="hero-copy">
            <span class="hero-badge"><span aria-hidden="true">&#10022;</span> Powered by Google Gemini AI</span>
            <h1>AI-Powered Career Path and<br>Skills Development for Youth in Pangasinan</h1>
            <p>Piloting in Bugallon, with TESDA Lingayen trainings.</p>
            <div class="hero-actions">
                <a class="hero-primary" href="{{ Route::has('register') ? route('register') : url('/register') }}">Get started</a>
                <a class="hero-secondary" href="#pathways">See how it works</a>
            </div>
        </div>
        <div class="hero-card">
            <span class="ring r1"></span><span class="ring r2"></span>
            <span class="ring r3"></span><span class="ring r4"></span>
            <span class="small-ring"></span>
            <svg class="wave" viewBox="0 0 1000 160" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 150 C150 90 300 60 450 80 S720 100 800 60 S950 -10 1000 -20" fill="none" stroke="rgba(255,255,255,.3)" stroke-width="1.5"/>
            </svg>
            <div class="orb"></div>
            <div class="pin"></div>
            <div class="plane"></div>
            <div class="cream-square"></div>
            <div class="blue-bar"></div>
            <div class="hero-bubble" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="hero-sphere"><i></i><i></i><svg viewBox="0 0 220 100" aria-hidden="true"><path d="M8 8 C42 54 88 94 150 78 C174 72 194 58 212 48" /></svg></div>
            <div class="hero-triangle" aria-hidden="true"><i></i></div>
            <div class="hero-meter" aria-hidden="true"><i></i></div>
            <div class="hero-phone"></div>
            <span class="dot d1"></span><span class="dot d2"></span>
        </div>
    </div>
</section>

{{-- Guidance Built Around You --}}
<section class="section guidance" id="features">
    <div class="guidance-content">
    <h2 class="h-xl reveal">Guidance Built<br>Around You</h2>

    <div class="grid reveal">
        <div class="panel">
            <h3 data-left-title>Interests and Skills</h3>
            <div class="card-white" data-views>
                <div class="view is-active">
                    @foreach ([
                        ['Electronics', 100, 78, -2], ['Farming', 154, 92, 4], ['Cooking', 18, 104, 8],
                        ['Computers', 70, 120, 3], ['Sewing', 176, 108, 6], ['Driving', 6, 136, -8],
                        ['Drawing', 100, 144, 2], ['Baking', 190, 142, -4], ['Selling', 22, 168, 3],
                        ['Caregiving', 112, 172, 2], ['Welding', 200, 174, 6],
                    ] as [$label, $x, $y, $r])
                        <span class="chip" style="left: {{ $x * 1.55 }}px; top: {{ ($y * 1.5) - 14 }}px; transform: rotate({{ $r }}deg)">{{ $label }}</span>
                    @endforeach
                </div>

                <div class="view bars"></div>

                <div class="view">
                    <div class="assess-tabs"><span class="on">&#8645; by skill</span><span>&#8645; by interest</span></div>
                    <span class="step-tag">Step 3 of 5</span>
                    <svg class="wave-svg" viewBox="0 0 310 180" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M0 60 C20 10 40 10 60 60 S100 150 130 130 S170 20 190 40 S230 110 260 100 S295 70 310 60" fill="none" stroke="#fbe132" stroke-width="2.5"/>
                        <line x1="170" y1="42" x2="170" y2="120" stroke="#999" stroke-dasharray="2 3"/>
                        <circle cx="170" cy="42" r="2.5" fill="#fbe132"/>
                    </svg>
                </div>
            </div>
            <div class="seg"><span></span><span></span><span></span></div>
        </div>

        <div class="panel">
            <h3>Your Guidance Preview</h3>
            <div class="preview">
                <div class="stat-stack">
                    <div class="stat y"><span>Profile complete</span><strong data-stat="profile" data-value="0">+0%</strong></div>
                    <div class="stat p"><span>Assessment done</span><strong data-stat="assess" data-value="0">+0%</strong></div>
                </div>
                <div>
                    <div class="tabs" role="tablist">
                        @foreach (['Overall', 'Skills', 'Interests', 'Schooling', 'Credentials', 'Livelihood'] as $tab)
                            <button type="button" role="tab" data-tab="{{ $tab }}" @class(['on' => $tab === 'Skills'])>{{ $tab }}</button>
                        @endforeach
                    </div>
                    <div class="chart">
                        <svg viewBox="0 0 310 190" preserveAspectRatio="none" aria-hidden="true">
                            <g stroke="#e4e4e4" stroke-dasharray="2 4"><line x1="0" y1="45" x2="310" y2="45"/><line x1="0" y1="100" x2="310" y2="100"/><line x1="0" y1="140" x2="310" y2="140"/></g>
                            <polyline points="0,155 74,75" fill="none" stroke="#f5a3cd" stroke-width="2"/>
                            <polyline points="74,75 182,166 260,85 306,12" fill="none" stroke="#e8479f" stroke-width="2"/>
                            <g stroke="#e8479f" stroke-dasharray="2 3"><line x1="74" y1="75" x2="74" y2="185"/><line x1="260" y1="85" x2="260" y2="185"/></g>
                            <circle cx="74" cy="75" r="3" fill="#e8479f"/><circle cx="260" cy="85" r="3" fill="#e8479f"/>
                        </svg>
                        <span class="badge" data-badge="a" style="left: 74px; top: 44px">+19%</span>
                        <span class="badge" data-badge="b" style="left: 260px; top: 54px">+39%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p class="note">Sample preview with example numbers. It is not real user data.</p>
    </div>
</section>

{{-- Pathways --}}
<section class="section pathways" id="pathways">
    <div class="pathways-title reveal">
        <span class="mail"></span>
        <span>Pathways</span>
        <span class="ticker"><span class="ticker-track">tuklas  </span></span>
    </div>

    <div class="steps">
        <a href="#" class="step reveal">
            <h4>Tell Us<br>About You</h4>
            <div class="step-box o">
                <small>Profile</small>
                <svg class="ico" width="28" height="30" viewBox="0 0 28 30"><circle cx="14" cy="9" r="8" fill="#4300b9"/><path d="M2 30a12 12 0 0 1 24 0z" fill="#fbe132"/></svg>
                <span class="num">01</span><span class="arrow">&rarr;</span>
            </div>
        </a>
        <a href="#" class="step reveal">
            <h4>Take the Skills<br>Assessment</h4>
            <div class="step-box k">
                <small>Assessment</small>
                <svg class="ico" width="26" height="32" viewBox="0 0 26 32"><rect width="26" height="32" rx="4" fill="#fff"/><path d="M6 10l4 4 9-9" stroke="#4300b9" stroke-width="3" fill="none"/><rect x="6" y="20" width="14" height="3" fill="#e8479f"/></svg>
                <span class="num">02</span><span class="arrow">&rarr;</span>
            </div>
        </a>
        <a href="#" class="step reveal">
            <h4>Explore Careers<br>and Trainings</h4>
            <div class="step-box v">
                <small>Recommendations</small>
                <svg class="ico" width="36" height="34" viewBox="0 0 24 24"><path d="M12 1l3.2 7 7.8.8-5.8 5.2 1.7 7.6L12 17.6 5.1 21.6l1.7-7.6L1 8.8 8.8 8z" fill="#fbe132"/></svg>
                <span class="num">03</span><span class="arrow">&rarr;</span>
            </div>
        </a>
    </div>
</section>

{{-- Stat rows --}}
<section class="rows">
    <h2 class="rows-title reveal">Tuklas Reads the Interests and Skills You Share <span aria-hidden="true">♥</span> to Offer Career and Training Guidance Made for Bugallon Youth</h2>
    <div class="row reveal">
        <svg class="art" viewBox="0 0 138 138" aria-hidden="true"><rect width="138" height="138" rx="12" fill="#fbe132"/><circle cx="69" cy="69" r="42" fill="#4300b9"/><path d="M69 43v29l19 13" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="big age" data-range-counter="5,11,15,30">5–11</div>
        <p>Tuklas is designed for youth in Bugallon, Pangasinan, with guardian consent built into sign-up for users aged 15 to 17.</p>
        <span class="tag vi">Youth</span>
    </div>
    <div class="row reveal">
        <svg class="art" viewBox="0 0 138 138"><rect width="138" height="138" rx="12" fill="#6f7de3"/><path d="M69 22c26 18 36 46 0 94-36-48-26-76 0-94z" fill="none" stroke="#4300b9" stroke-width="12"/><circle cx="69" cy="66" r="12" fill="#fbe132"/></svg>
        <div class="big" data-count-counter="3">2</div>
        <p>Youth, PESO Bugallon as Super Administrator, and TESDA Lingayen as Trainer. Each role only sees the features it needs.</p>
        <span class="tag pk">Roles</span>
    </div>
    <div class="row reveal">
        <svg class="art" viewBox="0 0 138 138"><rect width="138" height="138" rx="10" fill="#3a05a8"/><rect x="24" y="36" width="88" height="16" rx="8" fill="#e8479f"/><rect x="24" y="64" width="88" height="16" rx="8" fill="#fbe132"/><rect x="24" y="92" width="52" height="16" rx="8" fill="#8f86ee"/></svg>
        <div class="big" data-count-counter="5">4</div>
        <p>Interests, skills, educational attainment, credentials, and livelihood interests shape what Tuklas suggests, along with other details you choose to provide.</p>
        <span class="tag vi">Inputs</span>
    </div>
</section>

{{-- AI Scan --}}
<section class="section" id="ai-scan" style="padding-bottom: 124px">
    <div class="scan reveal">
        <div>
            <span class="gem"><b>&#10022;</b> Powered by Google Gemini AI</span>
            <h2>Scan Your Resume<br>and Certifications</h2>
            <p class="lead">Upload a resume or a certificate and Google Gemini reads it to suggest skills, schooling, and credentials for your profile.</p>
            <ul class="dots">
                <li>You review and confirm every detail before it is saved.</li>
                <li>Uploading is optional. You can type everything in yourself.</li>
                <li>Files are sent to Google Gemini to be read, so upload only what you are comfortable sharing.</li>
            </ul>
        </div>
        <div class="scan-right">
            <div class="doc" aria-hidden="true">
                <i class="short"></i><i class="long"></i><i class="mid"></i><i class="gap"></i>
                <i class="short"></i><i class="long"></i><i class="mid"></i>
            </div>
            <div class="opts">
                <div class="opt"><small>Skills</small>Computer basics, customer service</div>
                <div class="opt"><small>Schooling</small>Senior high school graduate</div>
                <div class="opt"><small>Credentials</small>Certificate of completion</div>
                <div class="opt next"><small>Next</small>Review and confirm</div>
            </div>
            <p class="note" style="grid-column: 1">Illustration only. Not a real document.</p>
        </div>
    </div>
</section>

{{-- See How Tuklas Helps --}}
<section class="section helps">
    <h2 class="h-xl reveal">See How<br>Tuklas Helps</h2>
    <div class="masonry" style="margin-top: 40px">
        <div class="col">
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Share your interests, skills, schooling, credentials, and livelihood interests once, and update them when your situation changes.</p><div class="who">Youth users<small>Profile</small></div></div>
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Oversee users and keep career, skills, and training information organized in one place.</p><div class="who">PESO Bugallon<small>Super Administrator</small></div></div>
        </div>
        <div class="col">
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Complete the skills assessment and see suggested careers and TESDA Lingayen programs, with the reasons behind each suggestion.</p><div class="who">Youth users<small>Recommendations</small></div></div>
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Manage and publish the training information and opportunities that TESDA Lingayen offers.</p><div class="who">TESDA Lingayen<small>Trainer</small></div></div>
        </div>
        <div class="col">
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Suggestions are guidance to help you decide. They are not guarantees of a job, admission, or a training slot.</p><div class="who">Every user<small>Guidance only</small></div></div>
            <div class="mcard reveal"><span class="star">&#10022;</span><p>Personal information is protected, and users aged 15 to 17 have guardian consent built into sign-up.</p><div class="who">Data privacy<small>Built in</small></div></div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section faq" id="faq">
    <h2 class="h-xl reveal">Frequently Asked<br>Questions</h2>
    <div class="faq-list">
        @foreach ([
            ['Who can use Tuklas?', 'Youth users aged 15 to 30 can create a profile. PESO Bugallon manages the system as Super Administrator and TESDA Lingayen manages training information as Trainer.'],
            ['Which trainings does Tuklas show?', 'Tuklas shows programs published by TESDA Lingayen, along with the reasons each program fits your profile.'],
            ['Does Tuklas guarantee a job or a training slot?', 'No. Tuklas offers guidance to help you decide. A suggestion is not a guarantee of a job, admission, or a training slot.'],
            ['How does the resume and certificate scan work?', 'Uploading is optional. Google Gemini reads a resume or certificate and suggests profile details for you to review and confirm before they are saved.'],
            ['Is there a mobile app available?', 'Tuklas is available through its website. A dedicated mobile app is not yet available.'],
            ['How does Tuklas protect my personal information?', 'Personal information is protected. Users aged 15 to 17 have guardian consent built into sign-up.'],
        ] as [$q, $a])
            <div class="faq-item reveal">
                <button type="button" class="faq-q" aria-expanded="false">
                    <span>{{ $q }}</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M2 4l4 4 4-4"/></svg>
                </button>
                <div class="faq-a"><p>{{ $a }}</p></div>
            </div>
        @endforeach
    </div>
</section>

{{-- Built with --}}
<section class="stack" id="contact">
    <p class="label">BUILT WITH</p>
    <div class="marquee">
        <div class="marquee-track">
            @foreach ([
                ['Jetstream', 'laravel', '#f55247'], ['Livewire', 'livewire', '#4e56a6'], ['Blade', 'blade', '#e8479f'], ['JavaScript', 'javascript', '#d9c900'],
                ['Alpine.js', 'alpinejs', '#77b9c8'], ['Vite', 'vite', '#7b2ff7'], ['Tailwind CSS', 'tailwind', '#38bdb0'], ['MySQL', 'mysql', '#5d87a8'],
                ['Resend', 'resend', '#111111'], ['Google Sign-In', 'google', '#4285f4'], ['Facebook Login', 'facebook', '#1877f2'], ['Laravel', 'laravel', '#f55247'], ['PHP', 'php', '#777bb3'],
            ] as [$name, $brand, $color])
                <span class="pill-chip" style="--c: {{ $color }}"><i class="ic" data-brand="{{ $brand }}" aria-hidden="true"></i>{{ $name }}</span>
            @endforeach
        </div>
    </div>

    <p class="label" style="margin-top: 36px">WHAT THE SYSTEM DOES</p>
    <div class="marquee rev">
        <div class="marquee-track">
            @foreach ([
                ['API token management', '#6c63c9'], ['Account deletion', '#e8643a'], ['Account registration', '#e8479f'],
                ['Google and Facebook sign-in', '#7b2ff7'], ['Email verification', '#ea743a'], ['Password reset', '#d9b800'],
                ['Profile settings', '#e8479f'], ['Role-based account access', '#7b2ff7'], ['Two-factor authentication', '#ea743a'],
            ] as [$name, $color])
                <span class="pill-chip sm" style="--c: {{ $color }}"><i class="ic" aria-hidden="true">{{ $loop->iteration === 1 ? '⌑' : ($loop->iteration === 2 ? '▤' : ($loop->iteration === 3 ? '♙' : ($loop->iteration === 4 ? '↗' : ($loop->iteration === 5 ? '✉' : ($loop->iteration === 6 ? '♙' : ($loop->iteration === 7 ? '♙' : ($loop->iteration === 8 ? '⬡' : '▣'))))))) }}</i>{{ $name }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta reveal">
    <div>
        <h2>Take the First Step<br>Toward Your Next Career.</h2>
        <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn-ghost">Get started</a>
    </div>
    <span class="c2"></span><span class="c1"></span><span class="c3"></span>
    <div class="cta-art"><span class="needle"></span></div>
</section>

<footer class="site-footer">
    <div class="footer-main">
        <a href="{{ route('home') }}" class="footer-logo" aria-label="Tuklas home">tuklas</a>
        <p>Career and skills guidance for youth in Bugallon, Pangasinan.</p>
        <nav aria-label="Footer">
            <a href="#features">Features</a>
            <a href="#pathways">Pathways</a>
            <a href="#ai-scan">AI Scan</a>
            <a href="#faq">FAQ</a>
            <a href="{{ Route::has('login') ? route('login') : url('/login') }}">Log in</a>
            <a href="{{ Route::has('register') ? route('register') : url('/register') }}">Get started</a>
        </nav>
    </div>
    <div class="footer-bottom">
        <span>© {{ now()->year }} Tuklas</span>
        <span>Piloting in Bugallon, with TESDA Lingayen trainings.</span>
    </div>
</footer>

</main>
</body>
</html>
