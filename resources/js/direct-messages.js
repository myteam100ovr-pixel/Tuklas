const thread = document.querySelector('[data-message-thread]');
const form = document.querySelector('[data-message-form]');

if (thread && form) {
    const input = form.querySelector('textarea[name="body"]');
    const sendButton = form.querySelector('button[type="submit"]');
    const status = form.querySelector('[data-message-status]');
    const currentUserId = Number(thread.dataset.senderId);
    const seenIds = new Set([...thread.querySelectorAll('[data-message-id]')].map((item) => Number(item.dataset.messageId)));
    let lastFetchedId = Math.max(0, ...seenIds);
    let polling = false;
    let sending = false;
    let pollTimer;

    const renderMessage = (message, scrollToLatest = false) => {
        const id = Number(message.id);

        if (!Number.isSafeInteger(id) || seenIds.has(id)) return;

        const nearBottom = scrollToLatest || thread.scrollHeight - thread.scrollTop - thread.clientHeight < 100;
        const mine = Number(message.sender_id) === currentUserId;
        const article = document.createElement('article');
        article.dataset.messageId = String(id);
        article.className = `max-w-[85%] rounded-2xl px-4 py-3 ${mine ? 'self-end bg-violet-700 text-white' : 'self-start bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white'}`;

        const body = document.createElement('p');
        body.className = 'whitespace-pre-wrap break-words text-sm';
        body.textContent = message.body;
        article.append(body);

        const date = new Date(message.created_at);
        if (!Number.isNaN(date.getTime())) {
            const time = document.createElement('time');
            time.className = 'mt-2 block text-xs opacity-75';
            time.dateTime = message.created_at;
            time.textContent = new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(date);
            article.append(time);
        }

        thread.querySelector('[data-message-empty]')?.remove();
        const nextMessage = [...thread.querySelectorAll('[data-message-id]')].find((item) => Number(item.dataset.messageId) > id);
        thread.insertBefore(article, nextMessage ?? null);
        seenIds.add(id);

        if (nearBottom) thread.scrollTop = thread.scrollHeight;
    };

    const poll = async () => {
        if (polling || document.hidden) return;
        polling = true;

        try {
            const url = new URL(thread.dataset.updatesUrl, window.location.href);
            url.searchParams.set('after', String(lastFetchedId));
            const response = await fetch(url, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Messages could not sync. Retrying…');
            const payload = await response.json();
            if (!Array.isArray(payload.messages)) throw new Error('Messages could not sync. Retrying…');

            payload.messages.forEach((message) => {
                renderMessage(message);
                lastFetchedId = Math.max(lastFetchedId, Number(message.id) || 0);
            });
            if (!sending && status.dataset.kind === 'sync') {
                status.textContent = '';
                delete status.dataset.kind;
            }
        } catch {
            if (!sending && status.dataset.kind !== 'send') {
                status.textContent = 'Messages could not sync. Retrying…';
                status.dataset.kind = 'sync';
            }
        } finally {
            polling = false;
            clearTimeout(pollTimer);
            pollTimer = setTimeout(poll, 3000);
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const originalText = input.value;
        const body = originalText.trim();
        if (!body || sending) return;

        sending = true;
        sendButton.disabled = true;
        status.textContent = '';
        delete status.dataset.kind;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({ body }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.message) throw new Error(payload.message || 'Message could not be sent. Please try again.');

            renderMessage(payload.message, true);
            if (input.value === originalText) input.value = '';
        } catch (error) {
            status.textContent = error.message || 'Message could not be sent. Please try again.';
            status.dataset.kind = 'send';
        } finally {
            sending = false;
            sendButton.disabled = false;
            input.focus();
        }
    });

    thread.scrollTop = thread.scrollHeight;
    pollTimer = setTimeout(poll, 3000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.addEventListener('focus', poll);
}
