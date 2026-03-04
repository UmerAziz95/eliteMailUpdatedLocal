@props([
    'offcanvasId' => 'configHistoryOffcanvas',
])

@once
    @push('styles')
        <style>
            .config-history-header {
                background: linear-gradient(135deg, #3f3cbb, #615dfa);
                color: #fff;
            }
            .history-loader {
                background: #0f172a;
                border-radius: 10px;
                padding: 0.6rem 0.85rem;
                color: #cbd5e1;
            }
            .history-error,
            .history-empty {
                border-radius: 10px;
            }
            .history-timeline { margin: 0; padding: 0; }
            .history-card {
                background: #111827;
                border: 1px solid #1f2937;
                border-radius: 10px;
                padding: 0.75rem 0.9rem;
                color: #e5e7eb;
            }
            .history-meta {
                color: #94a3b8;
                font-size: 0.85rem;
            }
            .history-chip {
                display: inline-block;
                background: #0b1224;
                border: 1px solid #1f2937;
                border-radius: 6px;
                padding: 0.35rem 0.5rem;
                font-size: 0.85rem;
                color: #cbd5e1;
            }
            .config-history-offcanvas {
                --bs-offcanvas-width: 600px;
                width: min(600px, 95vw);
            }
        </style>
    @endpush
@endonce

<div class="offcanvas offcanvas-end config-history-offcanvas" tabindex="-1" id="{{ $offcanvasId }}" aria-labelledby="{{ $offcanvasId }}Label">
    <div class="offcanvas-header config-history-header">
        <div>
            <h5 class="offcanvas-title mb-1" id="{{ $offcanvasId }}Label">Configuration History</h5>
            <small id="{{ $offcanvasId }}-key">Key: --</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body bg-dark text-light">
        <div class="history-loader d-none align-items-center gap-2 mb-3" id="{{ $offcanvasId }}-loading">
            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
            <span>Loading history...</span>
        </div>
        <div class="alert alert-secondary history-empty d-none" id="{{ $offcanvasId }}-empty">No history recorded yet.</div>
        <div class="alert alert-danger history-error d-none" id="{{ $offcanvasId }}-error"></div>
        <ul class="mb-0 list-unstyled history-timeline position-relative" id="{{ $offcanvasId }}-timeline"></ul>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.ConfigHistoryOffcanvas = (function () {
                const offcanvasId = @json($offcanvasId);
                const offcanvasEl = document.getElementById(offcanvasId);
                const keyLabel = document.getElementById(`${offcanvasId}-key`);
                const loadingEl = document.getElementById(`${offcanvasId}-loading`);
                const emptyEl = document.getElementById(`${offcanvasId}-empty`);
                const errorEl = document.getElementById(`${offcanvasId}-error`);
                const timelineEl = document.getElementById(`${offcanvasId}-timeline`);

                function toggle(el, show, display = 'block') {
                    if (!el) return;
                    el.classList.toggle('d-none', !show);
                    el.style.display = show ? display : 'none';
                }

                function showLoading(show = true) {
                    toggle(loadingEl, show, 'flex');
                }

                function showEmpty(show = true) {
                    toggle(emptyEl, show, 'block');
                }

                function showError(message = '') {
                    if (!errorEl) return;
                    errorEl.textContent = message || '';
                    toggle(errorEl, !!message, 'block');
                }

                function formatValue(value) {
                    if (value === null || value === undefined || value === '') {
                        return '--';
                    }
                    if (typeof value === 'object') {
                        try {
                            return JSON.stringify(value);
                        } catch (e) {
                            return String(value);
                        }
                    }
                    return String(value);
                }

                function formatTimestamp(timestamp) {
                    if (!timestamp) return 'Unknown time';
                    const parsed = new Date(timestamp);
                    return isNaN(parsed.getTime()) ? timestamp : parsed.toLocaleString();
                }

                function renderTimeline(entries) {
                    if (!timelineEl) return;
                    showLoading(false);
                    timelineEl.innerHTML = '';

                    if (!entries || !entries.length) {
                        showEmpty(true);
                        return;
                    }

                    showEmpty(false);

                    entries.forEach((entry) => {
                        const item = document.createElement('li');
                        item.className = 'position-relative mb-4';

                        const card = document.createElement('div');
                        card.className = 'history-card';

                        const header = document.createElement('div');
                        header.className = 'd-flex align-items-start justify-content-between flex-wrap gap-1';

                        const title = document.createElement('div');
                        title.innerHTML = `<div class="fw-bold text-white">Updated to ${formatValue(entry.new_value)}</div>`;

                        const time = document.createElement('div');
                        time.className = 'history-meta';
                        time.textContent = formatTimestamp(entry.changed_at);

                        header.appendChild(title);
                        header.appendChild(time);
                        card.appendChild(header);

                        const meta = document.createElement('div');
                        meta.className = 'history-meta mt-1';
                        meta.textContent = `Changed by ${entry.user_name || (entry.user_id ? 'User #' + entry.user_id : 'Unknown user')}`;
                        card.appendChild(meta);

                        const changes = document.createElement('div');
                        changes.className = 'mt-3';

                        const from = document.createElement('div');
                        from.className = 'history-chip mb-2';
                        from.innerHTML = `<strong>Previous (${entry.previous_type || 'N/A'}):</strong> ${formatValue(entry.previous_value)}`;

                        const to = document.createElement('div');
                        to.className = 'history-chip';
                        to.innerHTML = `<strong>New (${entry.new_type || 'N/A'}):</strong> ${formatValue(entry.new_value)}`;

                        changes.appendChild(from);
                        changes.appendChild(to);

                        if (entry.previous_description !== undefined || entry.new_description !== undefined) {
                            const descLine = document.createElement('div');
                            descLine.className = 'history-chip mt-2 d-block';
                            descLine.innerHTML = `<strong>Description:</strong> ${formatValue(entry.previous_description)} -> ${formatValue(entry.new_description)}`;
                            changes.appendChild(descLine);
                        }

                        card.appendChild(changes);
                        item.appendChild(card);
                        timelineEl.appendChild(item);
                    });
                }

                async function open(key, url) {
                    if (!offcanvasEl || !window.bootstrap) return;

                    keyLabel.textContent = `Key: ${key}`;
                    showError('');
                    showEmpty(false);
                    showLoading(true);
                    if (timelineEl) timelineEl.innerHTML = '';

                    const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
                    offcanvas.show();

                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Request failed with status ${response.status}`);
                        }

                        const data = await response.json();
                        if (data && data.success) {
                            renderTimeline(data.data);
                        } else {
                            renderTimeline([]);
                            showError((data && data.message) || 'Failed to load history.');
                        }
                    } catch (error) {
                        console.error('History load error:', error);
                        renderTimeline([]);
                        showError('Unable to load history. Please try again.');
                    } finally {
                        showLoading(false);
                    }
                }

                return { open };
            })();

            window.viewConfigHistory = function (key) {
                if (!window.ConfigHistoryOffcanvas || typeof window.ConfigHistoryOffcanvas.open !== 'function') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unavailable',
                        text: 'History viewer is not available at the moment.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                const url = '{{ route("admin.configurations.history", ["key" => "__KEY__"]) }}'.replace('__KEY__', encodeURIComponent(key));
                window.ConfigHistoryOffcanvas.open(key, url);
            };
        </script>
    @endpush
@endonce
