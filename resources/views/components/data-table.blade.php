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

                // NEVER intercept links inside table body or mobile card records (edit, view, show, etc.)
                if (link.closest('tbody') !== null || link.closest('.grid') !== null) return;

                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:') || link.hasAttribute('download') || link.hasAttribute('target')) return;

                // Only intercept dedicated DataTable navigation links (sort headers, pagination, filter chips, clear buttons)
                const isSort = link.closest('th') !== null;
                const isPagination = link.closest('.pagination') !== null || link.closest('nav[role=\'navigation\']') !== null || link.closest('.pagination-links') !== null;
                const isFilterChip = link.closest('.datatable-filter-chips') !== null;
                const isFilterControl = link.closest('.datatable-filters') !== null;
                const isEmptyStateClear = link.closest('.datatable-empty-state') !== null;

                if (isSort || isPagination || isFilterChip || isFilterControl || isEmptyStateClear) {
                    e.preventDefault();
                    this.fetchTable(href, true);
                }
            },
            handleFilterSubmit(e) {
                const form = e.target.closest('form');
                if (!form || !this.$el.contains(form)) return;

                // NEVER intercept non-GET forms or action forms (POST/DELETE/PUT for delete, disable, restore, etc.)
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                const isFilterForm = form.classList.contains('datatable-filter-form') || form.hasAttribute('data-datatable-filter') || form.closest('.datatable-filters') !== null;

                if (method !== 'GET' || !isFilterForm) {
                    return; // Allow native form submission to controller
                }

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

        {{-- Dynamic Content Area with Spinner Overlay --}}
        <div class="relative">
            {{-- Active Spinner Overlay for Page Changes / Sorting / Filtering --}}
            <div
                x-show="loading && hasLoaded"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-30 flex items-center justify-center rounded-xl bg-white/70 backdrop-blur-[2px] dark:bg-gray-900/70 min-h-[160px]"
            >
                <x-data-table.spinner label="Loading records..." />
            </div>

            <div
                x-ref="tableContent"
                x-show="hasLoaded"
                :class="{ 'opacity-40 pointer-events-none transition-opacity duration-150': loading && hasLoaded }"
            ></div>
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
