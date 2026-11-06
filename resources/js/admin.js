import { Chart, registerables } from 'chart.js';

/*
 * Admin-section script — loaded once, on every page behind layouts.admin
 * (docs/06-admin-panel-guide.md §2, "Alpine init for this area + Chart.js
 * dashboard wiring + the photo-queue keyboard shortcuts"). Alpine itself is
 * already global (`window.Alpine`, started by resources/js/app.js, which
 * every page loads); this file's own job is the parts Alpine doesn't do:
 * turning `<x-admin.chart>`'s data-attributes into real Chart.js instances.
 *
 * Deliberately not an Alpine component — a dashboard chart has no
 * interactive state of its own once drawn, so there is nothing here for
 * Alpine's reactivity to buy.
 */

Chart.register(...registerables);

/**
 * `<x-admin.chart>` never ships raw rows — every series here is already the
 * server-aggregated `{date|label, value}` shape DashboardMetrics produces.
 * This only decides how to plot it, never what it means.
 */
function renderCharts() {
    Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
    Chart.defaults.color = '#8A7D91';

    document.querySelectorAll('canvas[data-chart-series]').forEach((canvas) => {
        const type = canvas.dataset.chartType || 'line';
        const xKey = canvas.dataset.chartXKey || 'date';
        const series = JSON.parse(canvas.dataset.chartSeries || '[]');
        const isLine = type === 'line';

        // Soft brand gradient under a line; a vertical brand gradient on bars.
        const ctx = canvas.getContext('2d');
        const height = canvas.parentElement?.clientHeight || 220;
        const fill = ctx.createLinearGradient(0, 0, 0, height);
        if (isLine) {
            fill.addColorStop(0, 'rgba(214, 36, 110, 0.28)');
            fill.addColorStop(1, 'rgba(214, 36, 110, 0)');
        } else {
            fill.addColorStop(0, '#E0245E');
            fill.addColorStop(1, '#8E1866');
        }

        new Chart(canvas, {
            type: isLine ? 'line' : type,
            data: {
                labels: series.map((point) => point[xKey]),
                datasets: [{
                    data: series.map((point) => Number(point.value)),
                    borderColor: '#D6246E',
                    backgroundColor: fill,
                    fill: isLine,
                    tension: 0.4,
                    borderWidth: 2.5,
                    borderRadius: isLine ? 0 : 8,
                    borderSkipped: false,
                    maxBarThickness: 34,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#D6246E',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#150A1B',
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: false,
                        titleFont: { weight: '600' },
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 8 } },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: 'rgba(120, 90, 130, 0.10)' },
                        ticks: { maxTicksLimit: 5 },
                    },
                },
            },
        });
    });
}


/*
 * ---------------------------------------------------------------------------
 * Photo queue — the keyboard layer (docs/06-admin-panel-guide.md §6)
 * ---------------------------------------------------------------------------
 *
 * Layered on top of the plain grid, exactly like chat.js sits on top of the
 * plain message form: with this script blocked, every card still has its own
 * Approve button and its own reason picker, and the page works end to end.
 * This only makes forty decisions a minute possible.
 *
 * The one design decision worth reading before changing anything here:
 * **the advance is optimistic, the write is not.** §6 asks for an undo
 * window; §7 forbids optimistic updates on anything irreversible — and a
 * photo decision *is* irreversible through this UI, because ModeratePhoto
 * refuses a photo that is no longer pending. There is no un-approve route to
 * call, so "undo" cannot mean a compensating request. It means the POST has
 * not been sent yet. The moderator moves on immediately; the decision sits in
 * `pending` for `data-undo-seconds` and only then goes to the server.
 *
 * The cost of holding it is that a closed tab could swallow a decision, which
 * is what the `pagehide` beacon exists to prevent — and why the flush posts
 * `_token` as a form field rather than a header, since sendBeacon cannot set
 * one.
 */
function bindPhotoQueue() {
    const grid = document.querySelector('[data-photo-queue]');

    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('[data-photo-card]'));

    if (cards.length === 0) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const undoMs = Math.max(0, Number(grid.dataset.undoSeconds ?? 6)) * 1000;

    // Keyed by photo id. A card is in here between the keypress and the POST,
    // and that is the whole undo window.
    const pending = new Map();

    let cursor = 0;

    // --- Chrome ------------------------------------------------------------

    document.getElementById('photo-queue-hints')?.removeAttribute('hidden');

    // Built here rather than in Blade: it carries no server data, and the
    // no-JS page must not render an empty region for something that never
    // appears. (Contrast chat.js, which re-fetches server markup precisely
    // because *that* content is server data.)
    const toasts = document.createElement('div');
    toasts.className = 'fixed bottom-4 right-4 z-50 flex w-72 flex-col gap-2';
    toasts.setAttribute('aria-live', 'polite');
    document.body.appendChild(toasts);

    function toast(message, { action = null, tone = 'info' } = {}) {
        const el = document.createElement('div');

        // `border-danger` is a Tailwind class, not a CSS variable, because the
        // palette's semantic colours are the same in both themes — only the
        // surface/text/border neutrals swap, and those are the ones that live
        // in `:root` custom properties. See resources/css/app.css.
        el.className = `rounded-md border px-3 py-2 text-sm shadow-lg${tone === 'error' ? ' border-danger' : ''}`;
        el.style.backgroundColor = 'var(--bg-surface)';
        el.style.color = 'var(--text-primary)';

        if (tone !== 'error') {
            el.style.borderColor = 'var(--border-default)';
        }

        const text = document.createElement('span');
        text.textContent = message;
        el.appendChild(text);

        if (action) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ml-2 font-medium text-primary-600 underline';
            button.textContent = action.label;
            button.addEventListener('click', () => {
                action.run();
                el.remove();
            });
            el.appendChild(button);
        }

        toasts.appendChild(el);

        return el;
    }

    // --- Cursor ------------------------------------------------------------

    function isLive(card) {
        return !card.dataset.photoState;
    }

    function focusCard(index, { scroll = true } = {}) {
        cards.forEach((card) => card.classList.remove('ring-2', 'ring-primary-500'));

        const card = cards[index];

        if (!card) {
            return;
        }

        cursor = index;
        card.classList.add('ring-2', 'ring-primary-500');

        if (scroll) {
            card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        prefetchFrom(index);
    }

    /**
     * The *original*, not the thumbnail — the grid already has every thumbnail
     * inline, and the file a moderator actually opens to decide (a watermark,
     * a second face) is the full-size one. Two ahead: enough to cover a fast
     * run down the queue without pulling forty full-size images for a
     * moderator who stops at the third.
     */
    function prefetchFrom(index) {
        for (let i = index + 1; i <= index + 2; i++) {
            const url = cards[i]?.dataset.prefetchUrl;

            if (url && !cards[i].dataset.prefetched) {
                cards[i].dataset.prefetched = '1';
                new Image().src = url;
            }
        }
    }

    function advance(from) {
        for (let i = from + 1; i < cards.length; i++) {
            if (isLive(cards[i])) {
                focusCard(i);

                return;
            }
        }

        // Nothing live after the cursor — fall back to anything still live
        // before it, so a moderator who skipped around does not get stranded.
        for (let i = 0; i < cards.length; i++) {
            if (isLive(cards[i])) {
                focusCard(i);

                return;
            }
        }

        cards.forEach((card) => card.classList.remove('ring-2', 'ring-primary-500'));
        toast('Page cleared. Reload for the next batch.', {
            action: { label: 'Reload', run: () => window.location.reload() },
        });
    }

    function step(delta) {
        for (let i = cursor + delta; i >= 0 && i < cards.length; i += delta) {
            if (isLive(cards[i])) {
                focusCard(i);

                return;
            }
        }
    }

    // --- Decisions ---------------------------------------------------------

    function mark(card, state, note) {
        card.dataset.photoState = state;
        card.classList.remove('ring-2', 'ring-primary-500');
        card.classList.add('opacity-50');

        let badge = card.querySelector('[data-photo-badge]');

        if (!badge) {
            badge = document.createElement('p');
            badge.dataset.photoBadge = '';
            badge.className = 'px-3 pb-3 text-xs font-medium';
            card.appendChild(badge);
        }

        badge.textContent = note;
    }

    function unmark(card) {
        delete card.dataset.photoState;
        card.classList.remove('opacity-50');
        card.querySelector('[data-photo-badge]')?.remove();
    }

    function decide(card, decision) {
        const id = card.dataset.photoId;
        const label = card.dataset.photoLabel || 'photo';
        let reason = null;

        if (decision === 'reject') {
            const select = card.querySelector('[data-reject-reason]');
            reason = select?.value ?? '';

            // The reason is mandatory server-side (RejectPhotoRequest), so
            // there is nothing to send yet — put the moderator in the picker
            // instead of firing a request that would 422.
            if (!reason) {
                select?.focus();
                toast('Pick a rejection reason first.', { tone: 'error' });

                return;
            }
        }

        mark(card, decision, decision === 'approve' ? 'Approving…' : 'Rejecting…');
        advance(cards.indexOf(card));

        const entry = {
            card,
            decision,
            reason,
            url: decision === 'approve' ? card.dataset.approveUrl : card.dataset.rejectUrl,
            timer: null,
            toast: null,
        };

        entry.toast = toast(
            `${decision === 'approve' ? 'Approved' : 'Rejected'} ${label}.`,
            { action: { label: 'Undo', run: () => undo(id) } },
        );

        entry.timer = window.setTimeout(() => send(id), undoMs);
        pending.set(id, entry);
    }

    function undo(id) {
        const entry = pending.get(id);

        if (!entry) {
            return;
        }

        window.clearTimeout(entry.timer);
        entry.toast?.remove();
        pending.delete(id);
        unmark(entry.card);
        focusCard(cards.indexOf(entry.card));
    }

    async function send(id) {
        const entry = pending.get(id);

        if (!entry) {
            return;
        }

        pending.delete(id);
        entry.toast?.remove();

        const body = new URLSearchParams();

        if (entry.reason) {
            body.set('reason', entry.reason);
        }

        let response;

        try {
            response = await fetch(entry.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body,
            });
        } catch {
            // Network gone. The decision was never written, so restore the
            // card rather than leaving a dimmed one that lies about it.
            unmark(entry.card);
            toast('Could not reach the server — that photo is still pending.', { tone: 'error' });

            return;
        }

        if (!response.ok) {
            unmark(entry.card);

            // 419 after the 30-minute session idle is the realistic one here,
            // and no amount of retrying fixes it — say so and stop.
            toast(
                response.status === 419
                    ? 'Your session expired. Reload and sign in again — nothing was saved.'
                    : 'That decision was refused. Reload to see the current queue.',
                { tone: 'error', action: { label: 'Reload', run: () => window.location.reload() } },
            );

            return;
        }

        const payload = await response.json().catch(() => null);

        mark(
            entry.card,
            entry.decision,
            payload?.data?.stale
                ? 'Already reviewed by someone else'
                : entry.decision === 'approve' ? 'Approved' : 'Rejected',
        );
    }

    /*
     * Picking a reason moves the cursor onto that card and drops focus out of
     * the select. Both halves matter: without the blur the very next `R` is
     * swallowed as select type-ahead (the handler below deliberately ignores
     * keys typed into a form control), and without the cursor move an `R`
     * would reject whichever card the cursor happened to be on rather than
     * the one whose reason was just chosen.
     */
    grid.addEventListener('change', (event) => {
        const select = event.target.closest?.('[data-reject-reason]');

        if (!select) {
            return;
        }

        const index = cards.indexOf(select.closest('[data-photo-card]'));

        if (index !== -1) {
            focusCard(index, { scroll: false });
        }

        select.blur();
    });

    // --- Keys --------------------------------------------------------------

    document.addEventListener('keydown', (event) => {
        if (event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        // Typing in the reason picker, the filter box or the bulk bar is not
        // a shortcut. Without this, choosing "Abusive behaviour" by typing
        // would approve four photos on the way.
        const tag = event.target?.tagName;

        if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA' || event.target?.isContentEditable) {
            return;
        }

        const card = cards[cursor];
        const key = event.key.toLowerCase();

        if (key === 'arrowright' || key === 'arrowleft') {
            event.preventDefault();
            step(key === 'arrowright' ? 1 : -1);

            return;
        }

        if (key === 'u') {
            event.preventDefault();

            // Most recent first — undo means "take back what I just did".
            const last = Array.from(pending.keys()).pop();

            if (last) {
                undo(last);
            }

            return;
        }

        if (key === 'escape') {
            event.preventDefault();
            Array.from(pending.keys()).forEach(undo);

            return;
        }

        if ((key === 'a' || key === 'r') && card && isLive(card)) {
            event.preventDefault();
            decide(card, key === 'a' ? 'approve' : 'reject');
        }
    });

    /*
     * The undo window's one real hazard: a moderator who presses A and closes
     * the tab within six seconds would otherwise lose the decision silently.
     * `pagehide` (not `beforeunload` — bfcache and mobile Safari skip that
     * one) flushes whatever is still held.
     *
     * FormData, and `_token` as a *field*, because sendBeacon cannot set an
     * X-CSRF-TOKEN header. Laravel's VerifyCsrfToken reads the field, so the
     * request authenticates the same way the plain <form> POST does — which
     * is exactly what this is, minus the navigation.
     */
    window.addEventListener('pagehide', () => {
        pending.forEach((entry, id) => {
            window.clearTimeout(entry.timer);

            const body = new FormData();
            body.set('_token', csrf);

            if (entry.reason) {
                body.set('reason', entry.reason);
            }

            navigator.sendBeacon?.(entry.url, body);
            pending.delete(id);
        });
    });

    focusCard(0, { scroll: false });
}

/*
 * ---------------------------------------------------------------------------
 * Image fields — the upload half of <x-admin.image-field>
 * ---------------------------------------------------------------------------
 *
 * The field is a plain text input holding a path, and stays one: this only
 * adds a way to *produce* a path without leaving the form. With the script
 * blocked, the field still accepts a path copied from the media library page,
 * which is why the picker below is rendered `hidden` in Blade and unhidden
 * here rather than the other way round.
 *
 * The file goes to `admin.media.store` on its own rather than riding along
 * with the surrounding form's submit. Two reasons, both practical: the
 * surrounding form has a dozen other fields and a validation failure would
 * otherwise discard the uploaded bytes and ask for the file again, and the
 * upload lands in the shared library where the next banner can reuse it
 * instead of being an attachment to this one row.
 */
function bindImageFields() {
    document.querySelectorAll('[data-image-field]').forEach((field) => {
        const input = field.querySelector('[data-upload-input]');
        const trigger = field.querySelector('[data-upload-trigger]');
        const path = field.querySelector('[data-path-input]');
        const preview = field.querySelector('[data-image-preview]');
        const status = field.querySelector('[data-upload-status]');

        if (!input || !trigger || !path) {
            return;
        }

        trigger.removeAttribute('hidden');
        status?.removeAttribute('hidden');

        function say(message, isError = false) {
            if (!status) {
                return;
            }

            status.textContent = message;
            status.style.color = isError ? '' : 'var(--text-muted)';
            status.classList.toggle('text-danger', isError);
        }

        input.addEventListener('change', async () => {
            const file = input.files?.[0];

            if (!file) {
                return;
            }

            const body = new FormData();
            body.set('file', file);
            body.set('collection', field.dataset.collection || 'general');

            say('Uploading…');

            let response;

            try {
                response = await fetch(field.dataset.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body,
                });
            } catch {
                say('Upload failed — the file is not on the server. Nothing else on this form was lost.', true);

                return;
            }

            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                // 422 carries Laravel's own field messages, which are the ones
                // worth showing (wrong type, too large). Anything else gets the
                // status code's own meaning rather than a guess.
                const firstError = payload?.errors && Object.values(payload.errors)[0]?.[0];

                say(
                    firstError
                        ?? (response.status === 419
                            ? 'Your session expired. Open this form again in a new tab before uploading.'
                            : 'The server refused that file.'),
                    true,
                );

                // So picking the *same* file again still fires a change event.
                input.value = '';

                return;
            }

            path.value = payload?.data?.path ?? '';

            if (preview && payload?.data?.url) {
                preview.src = payload.data.url;
                preview.removeAttribute('hidden');
            }

            say(`${payload?.data?.original_name ?? 'Uploaded'}${payload?.data?.dimensions ? ` · ${payload.data.dimensions}` : ''}. Save the form to keep it.`);
            input.value = '';
        });
    });
}

function boot() {
    renderCharts();
    bindPhotoQueue();
    bindImageFields();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
