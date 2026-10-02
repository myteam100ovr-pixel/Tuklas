const scanner = document.querySelector('[data-document-scanner]');

if (scanner) {
    const input = scanner.querySelector('[data-document-input]');
    const chooseButton = scanner.querySelector('[data-document-choose]');
    const consent = scanner.querySelector('[data-document-consent]');
    const submitButton = scanner.querySelector('[data-document-submit]');
    const selectedList = scanner.querySelector('[data-document-selected]');
    const progress = scanner.querySelector('[data-document-progress]');
    const progressLabel = scanner.querySelector('[data-progress-label]');
    const progressValue = scanner.querySelector('[data-progress-value]');
    const progressBar = scanner.querySelector('[data-progress-bar]');
    const status = scanner.querySelector('[data-document-status]');
    const results = scanner.querySelector('[data-document-results]');
    const latestAnalysis = scanner.querySelector('[data-latest-analysis]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const geminiReady = scanner.dataset.geminiReady === 'true';
    const profileSyncEnabled = scanner.dataset.profileSync === 'true';
    const maxFileSize = 10 * 1024 * 1024;
    let selectedFiles = [];
    let isScanning = false;

    const formatSize = (bytes) => {
        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const setStatus = (message, kind = '') => {
        status.textContent = message;
        status.dataset.kind = kind;
    };

    const updateSubmitState = () => {
        submitButton.disabled = !geminiReady || selectedFiles.length === 0 || !consent.checked || isScanning;
    };

    const renderLatestAnalysis = (documentData) => {
        const summary = documentData.analysis?.summary;

        if (!summary) {
            return;
        }

        const heading = document.createElement('p');
        heading.className = 'document-scanner__latest-file';
        heading.textContent = documentData.original_name;

        const copy = document.createElement('p');
        copy.className = 'document-scanner__latest-copy';
        copy.textContent = summary;

        const content = document.createElement('div');
        content.className = 'document-scanner__latest-content';
        content.append(heading, copy);

        if (profileSyncEnabled) {
            const profileNote = document.createElement('p');
            profileNote.className = 'document-scanner__latest-note';
            profileNote.textContent = 'Skills and qualifications found here are saved to your Tuklas profile and shown on Overview.';
            content.append(profileNote);
        }

        latestAnalysis.replaceChildren(latestAnalysis.querySelector('.document-scanner__latest-heading'), content);
    };

    const setProgress = (percent, label, scanning = false) => {
        progress.hidden = false;
        progress.classList.toggle('is-scanning', scanning);
        progressLabel.textContent = label;

        if (scanning) {
            progressValue.hidden = true;
            progressBar.removeAttribute('aria-valuenow');
            return;
        }

        progressValue.hidden = false;
        progressValue.textContent = `${percent}%`;
        progressBar.setAttribute('aria-valuenow', String(percent));
        progressBar.style.setProperty('--upload-progress', `${percent}%`);
    };

    const renderSelected = () => {
        selectedList.replaceChildren();

        selectedFiles.forEach((file) => {
            const item = document.createElement('div');
            item.className = 'document-scanner__file';

            const name = document.createElement('strong');
            name.textContent = file.name;

            const size = document.createElement('small');
            size.textContent = formatSize(file.size);

            item.append(name, size);
            selectedList.append(item);
        });

        if (selectedFiles.length > 0) {
            setStatus(`${selectedFiles.length} file${selectedFiles.length === 1 ? '' : 's'} ready to scan.`);
        }

        updateSubmitState();
    };

    const renderDocument = (documentData, prepend = true) => {
        const empty = results.querySelector('.document-scanner__empty');
        empty?.remove();

        const card = document.createElement('article');
        card.className = 'document-scanner__result';

        const heading = document.createElement('div');
        heading.className = 'document-scanner__result-heading';

        const title = document.createElement('strong');
        title.textContent = documentData.original_name;

        const badge = document.createElement('span');
        badge.className = `document-scanner__badge is-${documentData.status}`;
        badge.textContent = documentData.status === 'completed' ? 'Scanned' : 'Scan failed';
        heading.append(title, badge);

        const type = document.createElement('small');
        type.className = 'document-scanner__result-type';
        type.textContent = {
            resume: 'Resume',
            certificate: 'Certificate',
            certification: 'Certificate',
            document: 'Career document',
        }[documentData.document_type] ?? 'Career document';
        card.append(heading, type);

        const summary = documentData.analysis?.summary;

        if (summary) {
            const copy = document.createElement('p');
            copy.className = 'document-scanner__analysis';
            copy.textContent = summary;
            card.append(copy);
        } else if (documentData.failure_message) {
            const failure = document.createElement('p');
            failure.className = 'document-scanner__analysis document-scanner__analysis--error';
            failure.textContent = documentData.failure_message;
            card.append(failure);
        }

        if (prepend) {
            results.prepend(card);
        } else {
            results.append(card);
        }
    };

    const setFiles = (files) => {
        const received = Array.from(files);

        if (received.length > 1) {
            selectedFiles = [];
            renderSelected();
            setStatus('Choose one document per scan.', 'error');
            input.value = '';
            return;
        }

        const invalid = received.find((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase();
            return file.size > maxFileSize || !['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(extension);
        });

        if (invalid) {
            selectedFiles = [];
            renderSelected();
            setStatus(`${invalid.name} is too large or uses an unsupported format.`, 'error');
            input.value = '';
            return;
        }

        selectedFiles = received;
        renderSelected();
    };

    const uploadFile = (file) => new Promise((resolve) => {
        const request = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('file', file, file.name);

        request.open('POST', scanner.dataset.uploadUrl);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-CSRF-TOKEN', csrfToken);

        request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                setProgress(0, `Uploading ${file.name}`);
                return;
            }

            const percent = Math.min(100, Math.round((event.loaded / Math.max(1, file.size)) * 100));
            setProgress(percent, `Uploading ${file.name}`);
        });

        request.upload.addEventListener('load', () => {
            setProgress(0, `Gemini is analyzing ${file.name}`, true);
        });

        request.addEventListener('load', () => {
            let response = {};

            try {
                response = JSON.parse(request.responseText);
            } catch (error) {
                response = {};
            }

            if (request.status >= 200 && request.status < 300 && response.document) {
                renderDocument(response.document);
                renderLatestAnalysis(response.document);
                resolve(true);
                return;
            }

            if (response.document) {
                renderDocument(response.document);
            }

            setStatus(response.message ?? `Could not scan ${file.name}.`, 'error');
            resolve(false);
        });

        request.addEventListener('error', () => {
            setStatus(`The upload for ${file.name} was interrupted. Check your connection and try again.`, 'error');
            resolve(false);
        });

        request.addEventListener('abort', () => {
            setStatus(`The upload for ${file.name} was cancelled.`, 'error');
            resolve(false);
        });

        request.send(formData);
    });

    const scanDocuments = async () => {
        if (isScanning || selectedFiles.length === 0 || !consent.checked) {
            return;
        }

        isScanning = true;
        updateSubmitState();
        input.disabled = true;
        chooseButton.disabled = true;
        consent.disabled = true;
        progress.hidden = false;
        const succeeded = await uploadFile(selectedFiles[0]);

        if (succeeded) {
            setProgress(100, 'Scan complete');
            setStatus('Document scanned. Review the insights.', 'success');
        }
        selectedFiles = [];
        input.value = '';
        renderSelected();
        input.disabled = false;
        chooseButton.disabled = false;
        consent.checked = false;
        consent.disabled = false;
        isScanning = false;
        updateSubmitState();
    };

    const loadHistory = async () => {
        try {
            const response = await fetch(scanner.dataset.historyUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            results.replaceChildren();

            if (payload.documents.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'document-scanner__empty';
                empty.textContent = 'Your completed scans will appear here.';
                results.append(empty);
                return;
            }

            payload.documents.forEach((documentData) => renderDocument(documentData, false));
            const latestCompleted = payload.documents.find((documentData) => documentData.status === 'completed' && documentData.analysis?.summary);

            if (latestCompleted) {
                renderLatestAnalysis(latestCompleted);
            }
        } catch (error) {
            // The scan form remains usable when prior history cannot be loaded.
        }
    };

    chooseButton.addEventListener('click', () => input.click());
    input.addEventListener('change', () => setFiles(input.files));
    consent.addEventListener('change', updateSubmitState);
    submitButton.addEventListener('click', scanDocuments);

    chooseButton.addEventListener('dragover', (event) => {
        event.preventDefault();
        chooseButton.classList.add('is-dragging');
    });

    chooseButton.addEventListener('dragleave', () => chooseButton.classList.remove('is-dragging'));
    chooseButton.addEventListener('drop', (event) => {
        event.preventDefault();
        chooseButton.classList.remove('is-dragging');
        setFiles(event.dataTransfer.files);
    });

    if (!geminiReady) {
        setStatus('Gemini scanning is unavailable until the server API key is configured.', 'error');
    }

    loadHistory();
}
