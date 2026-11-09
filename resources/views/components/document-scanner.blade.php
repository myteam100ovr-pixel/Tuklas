<section class="document-scanner" id="ai-scanner" data-document-scanner data-profile-sync="{{ auth()->user()->hasRole(\App\Enums\Role::Youth) ? 'true' : 'false' }}" data-upload-url="{{ route('document-scans.store') }}" data-history-url="{{ route('document-scans.index') }}" data-gemini-ready="{{ filled(config('services.google.gemini_api_key')) ? 'true' : 'false' }}" aria-labelledby="document-scanner-title">
    <div class="document-scanner__workspace">
        <div class="document-scanner__controls">
            <header class="scan-panel-heading"><span>01 / INPUT</span><h3>Upload a document</h3><p>Start with one clear resume or certificate.</p></header>
            <input class="document-scanner__input" data-document-input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" aria-label="Choose a document">
            <button class="document-scanner__dropzone" data-document-choose type="button" aria-describedby="document-file-help">
                <span class="document-scanner__upload-mark" aria-hidden="true">↑</span>
                <span><strong>Drop your document here</strong><small>or click to browse your files</small><small id="document-file-help">PDF, JPG, PNG or WebP · up to 10 MB</small></span>
            </button>
            <div class="document-scanner__selected" data-document-selected aria-live="polite"></div>
            <label class="document-scanner__goal" for="scan-goal">Where would you like to go next? <small>Optional</small></label>
            <textarea class="input" id="scan-goal" data-document-goal maxlength="1000" rows="2" placeholder="For example: Find training related to my certificate."></textarea>
            <label class="document-scanner__consent">
                <input type="checkbox" data-document-consent>
                <span>I agree to send these files to Google Gemini to scan them.</span>
            </label>
            <button class="document-scanner__submit" data-document-submit type="button" disabled>Discover my next steps <span aria-hidden="true">↗</span></button>
            <div class="document-scanner__progress" data-document-progress hidden>
                <div class="document-scanner__progress-copy"><span data-progress-label>Waiting for upload</span><strong data-progress-value>0%</strong></div>
                <div class="document-scanner__progress-track" role="progressbar" aria-label="Document processing progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar><span></span></div>
            </div>
            <p class="document-scanner__status" data-document-status aria-live="polite"></p>
        </div>

        <div class="document-scanner__results">
            <div class="document-scanner__results-heading"><div><span class="document-scanner__results-label">YOUR LIBRARY</span><h3>Recent scans</h3></div><span class="document-scanner__gemini-mark" aria-label="Powered by Google Gemini">✦</span></div>
            <p class="scan-history-caption">Your saved documents and scan summaries.</p>
            <div class="document-scanner__results-list" data-document-results aria-live="polite"><p class="document-scanner__empty">Your library starts with one document. Completed scans will appear here.</p></div>
        </div>
    </div>

    <div class="document-scanner__intro">
        <p class="document-scanner__eyebrow"><span aria-hidden="true">✦</span> Powered by Google Gemini</p>
        <h2 id="document-scanner-title">Turn your documents into a next step.</h2>
        <p>Upload one readable resume or certificate. We’ll identify documented skills and suggest careers and training to explore.</p>
        <details class="scan-data-note">
        <summary>Before you scan: your data and AI guidance</summary>
        <div class="document-scanner__privacy"><span aria-hidden="true">↗</span><span>Your file is temporarily stored and sent to Google Gemini, then deleted after processing. Only accepted resume and certificate scan results stay in your account.</span></div>
        <div class="document-scanner__privacy" role="note"><span aria-hidden="true">!</span><span>Gemini is an AI and may misread a document or suggest an unsuitable career or TESDA program. Skill percentages show documented skills from a listed set of role skills, not your chance of being hired. Check the details and confirm training and assessment requirements with TESDA or the provider.</span></div>
        <a href="{{ route('policy.show') }}">Read the privacy notice ↗</a>
        </details>
        @unless (filled(config('services.google.gemini_api_key')))
            <p class="document-scanner__setup" role="status">Scanning is currently unavailable. Ask your administrator to connect the AI service.</p>
        @endunless
    </div>

    <section class="document-scanner__latest" id="scan-report" data-latest-analysis aria-live="polite" aria-labelledby="document-latest-title">
        <div class="document-scanner__latest-heading">
            <div>
                <span class="document-scanner__results-label">02 / YOUR DIRECTION</span>
                <h3 id="document-latest-title">Your career report</h3>
            </div>
            <span class="document-scanner__gemini-mark" aria-label="Powered by Google Gemini">✦</span>
        </div>
        <p class="document-scanner__latest-empty">Your report will bring together career ideas, skills to build, and learning options. Upload a document above to begin.</p>
    </section>
</section>
