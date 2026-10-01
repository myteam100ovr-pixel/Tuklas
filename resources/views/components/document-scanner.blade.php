<section class="document-scanner" id="ai-scanner" data-document-scanner data-upload-url="{{ route('document-scans.store') }}" data-history-url="{{ route('document-scans.index') }}" data-gemini-ready="{{ filled(config('services.google.gemini_api_key')) ? 'true' : 'false' }}" aria-labelledby="document-scanner-title">
    <div class="document-scanner__intro">
        <p class="document-scanner__eyebrow"><span aria-hidden="true">✦</span> Google Gemini AI</p>
        <h2 id="document-scanner-title">Make your experience easier to show.</h2>
        <p>Scan a resume or certificate to organize your verified skills and get practical career next steps.</p>
        <div class="document-scanner__privacy"><span aria-hidden="true">↗</span><span>Files are stored in your private account area. Starting a scan sends a copy to Google Gemini for analysis.</span></div>
        @unless (filled(config('services.google.gemini_api_key')))
            <p class="document-scanner__setup" role="status">Gemini is not connected yet. Add <code>GOOGLE_AI_API_KEY</code> to the server environment to enable scans.</p>
        @endunless
    </div>

    <div class="document-scanner__workspace">
        <div class="document-scanner__controls">
            <label class="document-scanner__field" for="document-type">
                <span>Document type</span>
                <select id="document-type" data-document-type>
                    <option value="resume">Resume</option>
                    <option value="certification">Certificate</option>
                </select>
            </label>
            <input class="document-scanner__input" data-document-input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" aria-label="Choose a resume or certificate">
            <button class="document-scanner__dropzone" data-document-choose type="button" aria-describedby="document-file-help">
                <span class="document-scanner__upload-mark" aria-hidden="true">↑</span>
                <span><strong>Choose a document</strong><small id="document-file-help">PDF or image · up to 10 MB · one document at a time</small></span>
            </button>
            <div class="document-scanner__selected" data-document-selected aria-live="polite"></div>
            <label class="document-scanner__consent">
                <input type="checkbox" data-document-consent>
                <span>I agree to send these files to Google Gemini to scan them.</span>
            </label>
            <button class="document-scanner__submit" data-document-submit type="button" disabled>Scan documents <span aria-hidden="true">↗</span></button>
            <div class="document-scanner__progress" data-document-progress hidden>
                <div class="document-scanner__progress-copy"><span data-progress-label>Waiting for upload</span><strong data-progress-value>0%</strong></div>
                <div class="document-scanner__progress-track" role="progressbar" aria-label="Document upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-progress-bar><span></span></div>
            </div>
            <p class="document-scanner__status" data-document-status aria-live="polite"></p>
        </div>

        <div class="document-scanner__results">
            <div class="document-scanner__results-heading"><div><span class="document-scanner__results-label">Your scans</span><h3>Document insights</h3></div><span class="document-scanner__gemini-mark" aria-label="Powered by Google Gemini">✦</span></div>
            <div class="document-scanner__results-list" data-document-results aria-live="polite"><p class="document-scanner__empty">Your completed scans will appear here.</p></div>
        </div>
    </div>
</section>
