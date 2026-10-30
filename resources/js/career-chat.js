const chat = document.querySelector('[data-career-chat]');

if (chat) {
    const form = chat.querySelector('[data-chat-form]');
    const input = chat.querySelector('[data-chat-input]');
    const sendButton = chat.querySelector('[data-chat-send]');
    const transcript = chat.querySelector('[data-chat-messages]');
    const status = chat.querySelector('[data-chat-status]');
    const expandButton = chat.querySelector('[data-chat-expand]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const geminiReady = chat.dataset.geminiReady === 'true';
    let isSending = false;

    const appendMessage = (role, text, sources = []) => {
        const message = document.createElement('p');
        message.className = `career-chat__message${role === 'user' ? ' is-user' : ''}`;
        message.textContent = text;

        if (role !== 'user' && Array.isArray(sources) && sources.length > 0) {
            const sourceList = document.createElement('span');
            sourceList.className = 'career-chat__sources';
            sourceList.append(document.createTextNode('Sources: '));

            sources.forEach((source, index) => {
                try {
                    const url = new URL(source.url);

                    if (!['http:', 'https:'].includes(url.protocol)) {
                        return;
                    }

                    if (index > 0) {
                        sourceList.append(document.createTextNode(' · '));
                    }

                    const link = document.createElement('a');
                    link.href = url.href;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = source.title || url.hostname;
                    sourceList.append(link);
                } catch {
                    // Ignore malformed source URLs from the provider response.
                }
            });

            message.append(document.createElement('br'), sourceList);
        }

        transcript.append(message);
        transcript.scrollTop = transcript.scrollHeight;
        return message;
    };

    const loadHistory = async () => {
        try {
            const response = await fetch(chat.dataset.historyUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Could not load your saved chat.');
            const payload = await response.json();
            if (Array.isArray(payload.messages) && payload.messages.length > 0) {
                transcript.replaceChildren();
                payload.messages.forEach((item) => appendMessage(item.role, item.text, item.sources));
            }
        } catch {
            status.textContent = 'Saved chat history could not be loaded.';
            status.dataset.kind = 'error';
        }
    };

    expandButton.addEventListener('click', () => {
        const expanded = chat.classList.toggle('is-expanded');
        expandButton.setAttribute('aria-expanded', String(expanded));
        expandButton.setAttribute('aria-label', expanded ? 'Collapse chat' : 'Expand chat');
        expandButton.title = expanded ? 'Collapse chat' : 'Expand chat';
        expandButton.textContent = expanded ? '⤡' : '⤢';
        if (expanded) input.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && chat.classList.contains('is-expanded')) expandButton.click();
    });

    const sendMessage = async (text) => {
        const message = text.trim();

        if (!message || isSending || !geminiReady) {
            return;
        }

        const pendingMessage = appendMessage('user', message);
        input.value = '';
        isSending = true;
        input.disabled = true;
        sendButton.disabled = true;
        status.textContent = 'Thinking...';
        delete status.dataset.kind;

        try {
            const response = await fetch(chat.dataset.chatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ messages: [{ role: 'user', text: message }] }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || typeof payload.reply !== 'string') {
                throw new Error(payload.message ?? 'Gemini could not reply right now. Please try again.');
            }

            appendMessage('model', payload.reply, payload.sources);
            status.textContent = '';
        } catch (error) {
            pendingMessage.remove();
            input.value = message;
            status.textContent = error.message;
            status.dataset.kind = 'error';
        } finally {
            isSending = false;
            input.disabled = false;
            sendButton.disabled = !geminiReady;
            input.focus();
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    chat.querySelectorAll('[data-chat-prompt]').forEach((button) => {
        button.addEventListener('click', () => sendMessage(button.dataset.chatPrompt));
    });

    loadHistory();

    if (!geminiReady) {
        input.disabled = true;
        status.textContent = 'Gemini chat is unavailable until the server API key is configured.';
        status.dataset.kind = 'error';
    }
}
