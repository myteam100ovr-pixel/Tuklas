const scanner = document.querySelector('[data-document-scanner]');

if (scanner) {
    const input = scanner.querySelector('[data-document-input]');
    const chooseButton = scanner.querySelector('[data-document-choose]');
    const consent = scanner.querySelector('[data-document-consent]');
    const submitButton = scanner.querySelector('[data-document-submit]');
    const cancelButton = scanner.querySelector('[data-document-cancel]');
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
    const maxFiles = 5;
    let selectedFiles = [];
    let isScanning = false;
    let cancelRequested = false;
    let activeRequest = null;
    let reusedRecentScan = false;
    let historyRequestId = 0;

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

    const showToast = (message, kind) => {
        let toast = document.querySelector('[data-scanner-toast]');
        if (!toast) {
            toast = document.createElement('div');
            toast.dataset.scannerToast = '';
            toast.className = 'document-scanner-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.append(toast);
        }
        toast.textContent = message;
        toast.dataset.kind = kind;
        toast.hidden = false;
        clearTimeout(toast.dismissTimer);
        toast.dismissTimer = setTimeout(() => {
            toast.hidden = true;
        }, 5000);
    };

    const reportOutcome = (outcome, fileName) => {
        const eventId = globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        fetch(scanner.dataset.outcomeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ event_id: eventId, status: outcome, file_name: fileName }),
        }).catch(() => {});
    };

    const updateSubmitState = () => {
        submitButton.disabled = !geminiReady || selectedFiles.length === 0 || !consent.checked || isScanning;
    };

    const renderLatestAnalysis = (documentData) => {
        const analysis = documentData.analysis ?? {};
        const summary = analysis.summary;

        if (!summary && !documentData.failure_message) {
            return;
        }

        const heading = document.createElement('p');
        heading.className = 'document-scanner__latest-file';
        heading.textContent = documentData.original_name ?? 'Latest scan';

        const content = document.createElement('div');
        content.className = 'document-scanner__latest-content';
        content.append(heading);

        if (summary) {
            const copy = document.createElement('p');
            copy.className = 'document-scanner__latest-copy';
            copy.textContent = summary;
            content.append(copy);
        } else if (documentData.failure_message) {
            const failure = document.createElement('p');
            failure.className = 'document-scanner__analysis document-scanner__analysis--error';
            failure.textContent = documentData.failure_message;
            content.append(failure);
        }

        const sections = [
            ['jobRecommendations', 'Job recommendations'],
            ['careerMatches', 'Career matches'],
            ['skillGaps', 'Skills to build'],
            ['learningRecommendations', 'Free learning and practice'],
            ['tesdaRecommendations', 'TESDA training to consider'],
            ['nextActions', 'Next steps'],
        ];

        sections.forEach(([key, title]) => {
            const items = analysis[key];
            if (!Array.isArray(items) || items.length === 0) {
                return;
            }

            const section = document.createElement('section');
            section.className = 'document-scanner__insight';
            const sectionTitle = document.createElement('h4');
            sectionTitle.textContent = title;
            section.append(sectionTitle);

            const list = document.createElement('ul');
            items.forEach((item) => {
                const row = document.createElement('li');
                const name = typeof item === 'string'
                    ? item
                    : (item.title ?? item.name ?? 'Recommendation');
                const detail = typeof item === 'object' && item !== null
                    ? [item.match, item.reason, item.description].filter(Boolean).join(' · ')
                    : '';
                row.textContent = detail ? `${name} — ${detail}` : name;
                if (['careerMatches', 'jobRecommendations'].includes(key) && typeof item === 'object' && item !== null) {
                    const coverage = item.skillCoverage;
                    const explanation = document.createElement('small');
                    explanation.className = 'document-scanner__skill-coverage';
                    explanation.textContent = coverage && Number.isInteger(coverage.percent)
                        ? `${coverage.percent}% of listed role skills documented · ${coverage.matched}/${coverage.total} skills`
                        : 'Rescan to calculate documented skill coverage.';
                    row.append(explanation);
                    if (Array.isArray(item.evidencedSkills) && item.evidencedSkills.length > 0) {
                        const evidenced = document.createElement('small');
                        evidenced.className = 'document-scanner__skill-evidence';
                        evidenced.textContent = `From scan: ${item.evidencedSkills.join(', ')}`;
                        row.append(evidenced);
                    }
                    if (Array.isArray(item.skillsToDevelop) && item.skillsToDevelop.length > 0) {
                        const missing = document.createElement('small');
                        missing.className = 'document-scanner__skill-evidence';
                        missing.textContent = `To build or document: ${item.skillsToDevelop.join(', ')}`;
                        row.append(missing);
                    }
                }
                list.append(row);

                const url = typeof item === 'object' && item !== null
                    ? (item.directUrl ?? item.url)
                    : null;
                if (typeof url === 'string' && /^https:\/\//i.test(url)) {
                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = 'Open resource';
                    row.append(' ', link);
                }
            });
            section.append(list);
            content.append(section);
        });

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
        const classifiedType = documentData.analysis?.documentType;
        type.textContent = {
            resume: 'Resume',
            certificate: 'Certificate',
            certification: 'Certificate',
            study_material: 'Study material',
            other: 'Document',
            document: 'Document',
        }[classifiedType] ?? ({
            resume: 'Resume',
            certificate: 'Certificate',
            certification: 'Certificate',
            document: 'Document',
        }[documentData.document_type] ?? 'Document');
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

    const setFiles = (files, { replace = false } = {}) => {
        const received = Array.from(files);

        const invalid = received.find((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase();
            return file.size > maxFileSize || !['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(extension);
        });

        if (invalid) {
            setStatus(`${invalid.name} is too large or uses an unsupported format.`, 'error');
            input.value = '';
            return;
        }

        const combined = replace ? received : [...selectedFiles, ...received];
        selectedFiles = combined.filter((file, index, files) =>
            files.findIndex((candidate) => candidate.name === file.name && candidate.size === file.size && candidate.lastModified === file.lastModified) === index,
        );
        let exceededLimit = false;
        if (selectedFiles.length > maxFiles) {
            selectedFiles = selectedFiles.slice(0, maxFiles);
            exceededLimit = true;
        }
        renderSelected();
        if (exceededLimit) setStatus(`You can scan up to ${maxFiles} files at a time. Extra files were not added.`, 'error');
    };

    const uploadFile = (file) => new Promise((resolve) => {
        const request = new XMLHttpRequest();
        activeRequest = request;
        let settled = false;
        const finish = (succeeded, outcome = null) => {
            if (settled) return;
            settled = true;
            if (activeRequest === request) activeRequest = null;
            if (outcome) reportOutcome(outcome, file.name);
            resolve(succeeded);
        };
        const formData = new FormData();
        formData.append('file', file, file.name);
        formData.append('goal', scanner.querySelector('[data-document-goal]')?.value.trim() ?? '');

        request.open('POST', scanner.dataset.uploadUrl);
        request.timeout = 260000;
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-CSRF-TOKEN', csrfToken);

        request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                setProgress(0, `Uploading ${file.name}`, true);
                return;
            }

            const percent = Math.min(100, Math.round((event.loaded / Math.max(1, event.total)) * 100));
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
                setStatus('The server returned an unreadable response. Please try scanning again.', 'error');
                finish(false, 'error');
                return;
            }

            if (request.status >= 200 && request.status < 300 && response.document) {
                reusedRecentScan = reusedRecentScan || response.duplicate === true;
                renderDocument(response.document);
                renderLatestAnalysis(response.document);
                finish(true);
                return;
            }

            if (response.document) {
                renderDocument(response.document);
            }

            setStatus(response.message ?? `Could not scan ${file.name}.`, 'error');
            finish(false, [403, 422].includes(request.status) ? 'denied' : 'error');
        });

        request.addEventListener('error', () => {
            setStatus(`The upload for ${file.name} was interrupted. Check your connection and try again.`, 'error');
            finish(false, 'error');
        });

        request.addEventListener('abort', () => {
            setStatus(`The upload for ${file.name} was cancelled.`, 'error');
            finish(false);
        });

        request.addEventListener('timeout', () => {
            setStatus(`AI scanning ${file.name} took too long. Please try again.`, 'error');
            finish(false, 'error');
        });

        request.send(formData);
    });

    const scanDocuments = async () => {
        if (isScanning || selectedFiles.length === 0 || !consent.checked) {
            return;
        }

        isScanning = true;
        cancelRequested = false;
        historyRequestId += 1;
        updateSubmitState();
        input.disabled = true;
        chooseButton.disabled = true;
        consent.disabled = true;
        cancelButton.hidden = false;
        progress.hidden = false;
        const filesToScan = [...selectedFiles];
        reusedRecentScan = false;
        const failedFiles = [];
        let completed = 0;
        for (const file of filesToScan) {
            if (cancelRequested) {
                failedFiles.push(...filesToScan.slice(completed + failedFiles.length));
                break;
            }
            setProgress(Math.round((completed / filesToScan.length) * 100), `Scanning ${completed + 1} of ${filesToScan.length}: ${file.name}`, true);
            const succeeded = await uploadFile(file);
            if (succeeded) {
                completed += 1;
            } else {
                failedFiles.push(file);
                if (cancelRequested) {
                    failedFiles.push(...filesToScan.slice(completed + failedFiles.length));
                    break;
                }
            }
        }
        selectedFiles = failedFiles;
        input.value = '';
        renderSelected();
        input.disabled = false;
        chooseButton.disabled = false;
        consent.checked = false;
        consent.disabled = false;
        cancelButton.hidden = true;
        isScanning = false;
        updateSubmitState();
        await loadHistory();
        if (cancelRequested && failedFiles.length > 0) {
            progress.hidden = true;
            const message = `${completed} completed. The remaining scan was canceled; selected files are still available to retry.`;
            setStatus(message, 'error');
            reportOutcome('canceled', `${filesToScan.length} selected documents`);
            showToast('Document scan canceled.', 'canceled');
        } else if (failedFiles.length > 0) {
            progress.hidden = true;
            const message = `${completed} of ${filesToScan.length} files scanned. ${failedFiles.length} failed; keep them selected and try again.`;
            setStatus(message, 'error');
            showToast(`${failedFiles.length} scan${failedFiles.length === 1 ? '' : 's'} failed. Check Notifications for details.`, 'error');
        } else {
            setProgress(100, `${completed} ${completed === 1 ? 'document' : 'documents'} scanned`);
            setStatus(`${completed} ${completed === 1 ? 'document' : 'documents'} scanned. Review your insights.`, 'success');
            showToast(
                reusedRecentScan
                    ? 'This exact file was scanned recently; its saved result was reused without creating a duplicate.'
                    : `${completed} document${completed === 1 ? '' : 's'} scanned successfully.`,
                'success',
            );
        }
    };

    const loadHistory = async () => {
        if (isScanning) {
            return;
        }

        const requestId = ++historyRequestId;

        try {
            const response = await fetch(scanner.dataset.historyUrl, { headers: { Accept: 'application/json' } });
            if (isScanning || requestId !== historyRequestId) {
                return;
            }

            if (!response.ok) {
                setStatus('Could not load previous scans. You can still submit a new document.', 'error');
                return;
            }

            const payload = await response.json();
            if (isScanning || requestId !== historyRequestId) {
                return;
            }

            if (!Array.isArray(payload.documents)) {
                setStatus('The scan history response was invalid. You can still submit a new document.', 'error');
                return;
            }
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
            if (!isScanning) {
                setStatus('Could not load previous scans. Check your connection and try again.', 'error');
            }
        }
    };

    window.addEventListener('tuklas:document-scans-sync', (event) => {
        if (event.detail?.resource === 'document-scans') {
            loadHistory();
        }
    });

    chooseButton.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        setFiles(input.files);
        input.value = '';
    });
    consent.addEventListener('change', updateSubmitState);
    submitButton.addEventListener('click', scanDocuments);
    cancelButton.addEventListener('click', () => {
        if (!isScanning || cancelRequested) return;
        cancelRequested = true;
        activeRequest?.abort();
    });

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
