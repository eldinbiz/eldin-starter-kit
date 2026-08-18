@props(['datatable', 'deferred' => false])

@if($deferred)
    <div
        x-data="{
            loading: true,
            hasLoaded: false,
            currentUrl: '{{ request()->fullUrl() }}',
            init() {
                this.fetchTable(this.currentUrl, false);
            },
            fetchTable(url, pushState = true) {
                this.loading = true;
                fetch(url, {
                    headers: {
                        'X-DataTable-Partial': 'true',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Network error');
                    return res.text();
                })
                .then(html => {
                    this.$refs.tableContent.innerHTML = html;
                    this.currentUrl = url;
                    if (pushState && window.location.href !== url) {
                        window.history.pushState(null, '', url);
                    }
                    this.hasLoaded = true;
                    this.loading = false;
                })
                .catch(err => {
                    console.error('DataTable fetch error:', err);
                    this.loading = false;
                });
            },
            handleContainerClick(e) {
                const link = e.target.closest('a');
                if (!link || !this.$el.contains(link)) return;

                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:')) return;

                // Intercept sort headers, filter chips, pagination, and clear-filter links
                const isSort = link.closest('th') !== null;
                const isPagination = link.closest('.pagination') !== null || link.closest('nav[role=\'navigation\']') !== null;
                const isFilterAction = href.includes('filter_') || href.includes('clear') || href.includes('sort=') || href.includes('page=') || href.includes('per_page=');
                const isRecordAction = href.includes('/edit') || href.includes('/impersonate') || link.hasAttribute('download') || link.hasAttribute('target');

                if ((isSort || isPagination || isFilterAction) && !isRecordAction) {
                    e.preventDefault();
                    this.fetchTable(href, true);
                }
            },
            handleFilterSubmit(e) {
                const form = e.target.closest('form');
                if (!form || !this.$el.contains(form)) return;

                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value !== '') {
                        params.append(key, value);
                    }
                }
                const cleanUrl = form.action.split('?')[0];
                const targetUrl = cleanUrl + (params.toString() ? '?' + params.toString() : '');
                this.fetchTable(targetUrl, true);
            }
        }"
        @popstate.window="fetchTable(window.location.href, false)"
        @click="handleContainerClick($event)"
        @submit="handleFilterSubmit($event)"
        @per-page-change="fetchTable($event.detail, true)"
    >
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filters Panel --}}
        <x-data-table.filters :datatable="$datatable" />

        {{-- Initial Skeleton Placeholder --}}
        <div x-show="loading && !hasLoaded" x-cloak>
            <x-data-table.skeleton :datatable="$datatable" />
        </div>

        {{-- Dynamic Content Area --}}
        <div
            x-ref="tableContent"
            x-show="hasLoaded"
            :class="{ 'opacity-50 pointer-events-none transition-opacity duration-200': loading }"
        >
            {{-- Fallback for JavaScript-disabled environments --}}
            <noscript>
                @include('components.data-table.partial', ['datatable' => $datatable])
            </noscript>
        </div>
    </div>
@else
    {{-- Synchronous SSR Rendering (Default / Backward Compatible) --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters Panel --}}
    <x-data-table.filters :datatable="$datatable" />

    {{-- Partial Content (Chips, Table, Mobile Cards, Pagination) --}}
    @include('components.data-table.partial', ['datatable' => $datatable])
@endif
