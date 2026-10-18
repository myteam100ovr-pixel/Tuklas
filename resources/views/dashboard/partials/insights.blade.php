            @isset($profileInsights)
                <section class="card profile-insights" aria-labelledby="profile-insights-title">
                    <div class="profile-insights__heading">
                        <div><span class="stat-label">From your profile and latest scan</span><h2 id="profile-insights-title">Your career insights</h2></div>
                        <a href="{{ route('scanner.index') }}">Open AI Scanner</a>
                    </div>
                    <div class="profile-insights__groups">
                        @foreach ([
                            'skills' => 'Skills',
                            'credentials' => 'Qualifications and certificates',
                            'job_roles' => 'Suggested job roles',
                            'tesda_training' => 'AI TESDA recommendations',
                        ] as $key => $label)
                            <section class="profile-insights__group {{ $key === 'tesda_training' ? 'profile-insights__group--tesda' : '' }}">
                                <h3>{{ $label }}</h3>
                                @if (count($profileInsights[$key]) > 0)
                                    <ul>
                                        @foreach ($profileInsights[$key] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($key === 'tesda_training')
                                    <p>{{ $profileInsights['has_scan'] ? 'No TESDA qualification was saved with your latest scan. Check the catalog or scan an updated resume or certificate.' : 'Scan a resume or certificate to get AI TESDA recommendations based on your documented skills.' }}</p>
                                    @if ($profileInsights['has_scan'])
                                        <a href="{{ route('tesda.index') }}">Browse TESDA programs</a>
                                    @else
                                        <a href="{{ route('scanner.index') }}">Open AI Scanner</a>
                                    @endif
                                @else
                                    <p>Nothing saved yet.</p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                    <div class="profile-insights__next-steps">
                        <section class="profile-insights__group profile-insights__group--wide">
                            <h3>AI job matches</h3>
                            @if (count($profileInsights['job_recommendations']) > 0)
                                <ul class="profile-insights__resources">
                                    @foreach ($profileInsights['job_recommendations'] as $jobRecommendation)
                                        <li class="profile-insights__resource">
                                            <h4>{{ $jobRecommendation['title'] }}</h4>
                                            @if (is_string($jobRecommendation['reason']) && filled($jobRecommendation['reason']))
                                                <p>{{ $jobRecommendation['reason'] }}</p>
                                            @endif
                                            @if (is_string($jobRecommendation['evidence']) && filled($jobRecommendation['evidence']))
                                                <p><strong>From your scan:</strong> {{ $jobRecommendation['evidence'] }}</p>
                                            @elseif (is_array($jobRecommendation['evidence']))
                                                <ul>@foreach ($jobRecommendation['evidence'] as $evidence)@if (is_string($evidence))<li>{{ $evidence }}</li>@endif @endforeach</ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p>Use the AI Scanner to analyze a resume or certificate and see which jobs may fit.</p>
                            @endif
                        </section>

                        <section class="profile-insights__group profile-insights__group--wide">
                            <h3>Skills to develop next</h3>
                            @if (count($profileInsights['skill_gaps']) > 0)
                                <ul>
                                    @foreach ($profileInsights['skill_gaps'] as $skillGap)
                                        <li>{{ $skillGap }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p>Scan a resume or certificate to get skills matched to your career goals.</p>
                            @endif
                        </section>

                        <section class="profile-insights__group profile-insights__group--wide">
                            <h3>Free learning and open-source practice</h3>
                            @if (count($profileInsights['learning_recommendations']) > 0)
                                <ul class="profile-insights__resources">
                                    @foreach ($profileInsights['learning_recommendations'] as $learningRecommendation)
                                        @php
                                            $learningTitle = $learningRecommendation['title'] ?? null;
                                            $learningReason = $learningRecommendation['reason'] ?? null;
                                            $learningSite = $learningRecommendation['learningSite'] ?? null;
                                            $directUrl = $learningRecommendation['directUrl'] ?? null;
                                            $isSafeDirectUrl = is_string($directUrl)
                                                && filter_var($directUrl, FILTER_VALIDATE_URL) !== false
                                                && parse_url($directUrl, PHP_URL_SCHEME) === 'https';
                                        @endphp

                                        @if (is_string($learningTitle) && filled($learningTitle))
                                            <li class="profile-insights__resource">
                                                <h4>{{ $learningTitle }}</h4>
                                                @if (is_string($learningSite) && filled($learningSite))
                                                    <p>{{ $learningSite }}</p>
                                                @endif
                                                @if (is_string($learningReason) && filled($learningReason))
                                                    <p>{{ $learningReason }}</p>
                                                @endif
                                                @if ($isSafeDirectUrl)
                                                    <a href="{{ $directUrl }}" target="_blank" rel="noopener noreferrer">Open learning resource</a>
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                <p>Scan a resume or certificate to get free courses and open-source projects matched to your skill gaps.</p>
                            @endif
                        </section>
                    </div>
                </section>
            @endisset
