const scanner = document.querySelector('[data-document-scanner]');

if (scanner) {
    const input = scanner.querySelector('[data-document-input]');
    const chooseButton = scanner.querySelector('[data-document-choose]');
    const cameraOpenButton = scanner.querySelector('[data-camera-open]');
    const cameraDialog = scanner.querySelector('[data-camera-dialog]');
    const cameraVideo = scanner.querySelector('[data-camera-video]');
    const cameraStatus = scanner.querySelector('[data-camera-status]');
    const cameraCaptureButton = scanner.querySelector('[data-camera-capture]');
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
    let cameraStream = null;

    const setFileActionIcon = (button, paths) => {
        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('width', '18');
        icon.setAttribute('height', '18');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '1.8');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');
        icon.setAttribute('aria-hidden', 'true');

        paths.forEach((data) => {
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', data);
            icon.append(path);
        });

        button.replaceChildren(icon);
    };

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
        const documentTypeLabels = {
            resume: 'Resume',
            certificate: 'Certificate',
            study_material: 'Study material',
            other: 'Document',
        };
        const documentType = documentTypeLabels[analysis.documentType];
        heading.textContent = `${documentData.original_name ?? 'Latest scan'}${documentType ? ` · ${documentType}` : ''}`;

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

        const keyFacts = Array.isArray(analysis.keyFacts) ? analysis.keyFacts : [];
        if (keyFacts.length > 0) {
            const facts = document.createElement('section');
            facts.className = 'document-scanner__insight';
            const title = document.createElement('h4');
            title.textContent = 'Key details found';
            const list = document.createElement('ul');
            keyFacts.forEach((fact) => {
                if (!fact || typeof fact !== 'object' || !fact.label || !fact.value) return;
                const row = document.createElement('li');
                row.textContent = `${fact.label}: ${fact.value}`;
                if (fact.evidence) {
                    const evidence = document.createElement('small');
                    evidence.className = 'document-scanner__skill-evidence';
                    evidence.textContent = `Document evidence: ${fact.evidence}`;
                    row.append(evidence);
                }
                list.append(row);
            });
            if (list.childElementCount > 0) {
                facts.append(title, list);
                content.append(facts);
            }
        }

        const extractedFields = analysis.extractedFields;
        if (extractedFields && typeof extractedFields === 'object'
            && Object.values(extractedFields).some((value) => value !== null && value !== undefined && value !== '')) {
            const details = document.createElement('section');
            details.className = 'document-scanner__insight';
            const title = document.createElement('h4');
            title.textContent = 'Readable document details';
            details.append(title);
            const list = document.createElement('ul');
            const labels = {
                fullName: 'Name', qualification: 'Qualification', issuingOrganization: 'Issuing organization',
                issueDate: 'Issue date', expiryDate: 'Expiry date', signaturePresent: 'Signature visible',
            };
            Object.entries(labels).forEach(([key, label]) => {
                const value = extractedFields[key];
                const row = document.createElement('li');
                row.textContent = `${label}: ${value === null || value === undefined || value === '' ? 'Not readable or not shown' : value === true ? 'Yes' : value === false ? 'No' : value}`;
                list.append(row);
            });
            details.append(list);
            content.append(details);
        }

        [['identityChecks', 'Identity cross-check'], ['requirementChecks', 'Requirement cross-check']].forEach(([key, label]) => {
            const checks = analysis[key];
            if (!Array.isArray(checks) || checks.length === 0) return;
            const section = document.createElement('section');
            section.className = 'document-scanner__insight';
            const heading = document.createElement('h4');
            heading.textContent = label;
            const list = document.createElement('ul');
            checks.forEach((check) => {
                if (typeof check !== 'object' || check === null) return;
                const row = document.createElement('li');
                const details = [check.status, check.reason, check.evidence].filter(Boolean);
                row.textContent = `${check.field ?? check.requirement ?? 'Check'}: ${details.join(' · ') || 'No readable comparison available'}`;
                list.append(row);
            });
            section.append(heading, list);
            content.append(section);
        });

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

        const recommendedLinks = Array.isArray(analysis.recommended_links)
            ? analysis.recommended_links.filter((item) => typeof item?.web_url === 'string'
                && item.web_url.startsWith('/')
                && !item.web_url.startsWith('//'))
            : [];
        if (recommendedLinks.length > 0) {
            const actions = document.createElement('section');
            actions.className = 'document-scanner__insight';
            const title = document.createElement('h4');
            title.textContent = 'Open in Tuklas';
            actions.append(title);
            const links = document.createElement('div');
            links.className = 'document-scanner__recommended-links';
            recommendedLinks.forEach((item) => {
                const link = document.createElement('a');
                link.className = 'document-scanner__recommended-link';
                link.href = item.web_url;
                link.textContent = item.label || 'Open page';
                links.append(link);
            });
            actions.append(links);
            content.append(actions);
        }

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

        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'document-scanner__file is-selected';

            const name = document.createElement('strong');
            name.textContent = file.name;

            const size = document.createElement('small');
            size.textContent = formatSize(file.size);

            const details = document.createElement('div');
            details.className = 'document-scanner__file-details';
            details.append(name, size);

            const actions = document.createElement('div');
            actions.className = 'document-scanner__file-actions';

            const replaceButton = document.createElement('button');
            replaceButton.type = 'button';
            replaceButton.className = 'document-scanner__file-action document-scanner__file-action--replace';
            replaceButton.setAttribute('aria-label', `Replace ${file.name}`);
            replaceButton.title = `Replace ${file.name}`;
            setFileActionIcon(replaceButton, [
                'M20 7v5h-5',
                'M4 17v-5h5',
                'M5.64 5.64A9 9 0 0 1 20 12',
                'M4 12a9 9 0 0 0 14.36 6.36',
            ]);
            replaceButton.disabled = isScanning;
            replaceButton.addEventListener('click', () => {
                const replacementInput = input.cloneNode();
                replacementInput.multiple = false;
                replacementInput.value = '';
                replacementInput.addEventListener('change', () => {
                    setFiles(replacementInput.files, { replaceIndex: index });
                    replacementInput.remove();
                }, { once: true });
                replacementInput.addEventListener('cancel', () => replacementInput.remove(), { once: true });
                input.insertAdjacentElement('afterend', replacementInput);
                replacementInput.click();
            });

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'document-scanner__file-action document-scanner__file-action--remove';
            removeButton.setAttribute('aria-label', `Remove ${file.name}`);
            removeButton.title = `Remove ${file.name}`;
            setFileActionIcon(removeButton, [
                'M3 6h18',
                'M8 6V4h8v2',
                'M19 6l-1 14H6L5 6',
                'M10 11v5',
                'M14 11v5',
            ]);
            removeButton.disabled = isScanning;
            removeButton.addEventListener('click', () => {
                selectedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
                renderSelected();
                setStatus(`${file.name} removed from the scan list.`);
            });

            actions.append(replaceButton, removeButton);
            item.append(details, actions);
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

    const setFiles = (files, { replace = false, replaceIndex = null } = {}) => {
        const received = Array.from(files);
        if (received.length === 0) return;

        const invalid = received.find((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase();
            return file.size > maxFileSize || !['pdf', 'docx', 'jpg', 'jpeg', 'png', 'webp'].includes(extension);
        });

        if (invalid) {
            setStatus(`${invalid.name} is too large or uses an unsupported format.`, 'error');
            input.value = '';
            return;
        }

        if (replaceIndex !== null && selectedFiles[replaceIndex]) {
            const duplicate = selectedFiles.some((file, index) =>
                index !== replaceIndex && file.name === received[0].name && file.size === received[0].size && file.lastModified === received[0].lastModified,
            );
            if (duplicate) {
                setStatus('That file is already in the scan list.', 'error');
                return;
            }
            selectedFiles = [...selectedFiles];
            selectedFiles[replaceIndex] = received[0];
            renderSelected();
            setStatus(`${received[0].name} is ready to scan.`);
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
        cameraOpenButton.disabled = true;
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
        cameraOpenButton.disabled = false;
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

    const stopCamera = () => {
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        cameraVideo.srcObject = null;
    };

    cameraOpenButton.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Camera capture needs a secure connection and a browser with camera support.', 'error');
            return;
        }

        cameraOpenButton.disabled = true;

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
            cameraVideo.srcObject = cameraStream;
            cameraDialog.showModal();
            await cameraVideo.play();
            cameraStatus.textContent = 'Position the document inside the frame and keep the text in focus.';
        } catch (error) {
            stopCamera();
            cameraOpenButton.disabled = false;
            const message = error.name === 'NotAllowedError'
                ? 'Camera permission was denied. Allow camera access in your browser settings and try again.'
                : 'The camera could not be opened. Check that it is connected and not being used by another app.';
            cameraStatus.textContent = message;
            setStatus(message, 'error');
        }
    });

    cameraCaptureButton.addEventListener('click', () => {
        if (!cameraVideo.videoWidth || !cameraVideo.videoHeight) {
            cameraStatus.textContent = 'The camera is not ready yet. Wait a moment and try again.';
            return;
        }

        const canvas = document.createElement('canvas');
        const scale = Math.min(1, 1800 / Math.max(cameraVideo.videoWidth, cameraVideo.videoHeight));
        canvas.width = Math.round(cameraVideo.videoWidth * scale);
        canvas.height = Math.round(cameraVideo.videoHeight * scale);
        const context = canvas.getContext('2d');

        if (!context) {
            cameraStatus.textContent = 'The photo could not be captured. Please try again.';
            return;
        }

        context.drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);
        cameraCaptureButton.disabled = true;
        canvas.toBlob((blob) => {
            cameraCaptureButton.disabled = false;

            if (!blob) {
                cameraStatus.textContent = 'The photo could not be captured. Please try again.';
                return;
            }

            if (blob.size > maxFileSize) {
                cameraStatus.textContent = 'The photo is larger than 10 MB. Move closer to the document and try again.';
                return;
            }

            const photo = new File([blob], `tuklas-document-${Date.now()}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
            setFiles([photo]);
            cameraDialog.close();
            stopCamera();

            if (selectedFiles.includes(photo)) {
                setStatus('Photo added. You can scan it now or add more documents.');
            }
        }, 'image/jpeg', 0.84);
    });

    scanner.querySelectorAll('[data-camera-close], [data-camera-cancel]').forEach((button) => {
        button.addEventListener('click', () => cameraDialog.close());
    });
    cameraDialog.addEventListener('close', () => {
        stopCamera();
        cameraOpenButton.disabled = false;
    });
    window.addEventListener('pagehide', stopCamera);

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
