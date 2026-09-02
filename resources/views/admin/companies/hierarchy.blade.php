@extends('layouts.app')

@section('title', 'Company Hierarchy')

@push('styles')
<style>
    .org-chart-container {
        height: 500px;
    }
    .org-chart-container > svg {
        width: 100%;
        height: 100%;
    }
    .org-chart-container .node-rect {
        rx: 12;
        ry: 12;
    }
    .org-chart-container [data-search-dimmable] {
        transition: opacity 0.3s ease;
    }
</style>
@endpush

@section('content')
<div x-data="companyHierarchy()" x-init="init()" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Company Hierarchy</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage the organizational structure of companies — drag to reparent, toggle to enable/disable.
            </p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <div class="relative">
                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400">
                    <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" />
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Search companies..."
                    x-on:input.debounce.300ms="doSearch()"
                    class="w-64 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
            </div>
            {{-- Reset View --}}
            <button @click="resetView()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
                </svg>
                Reset View
            </button>
            @if($canManage)
            <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Company
            </a>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    <div x-show="flashMessage" x-cloak x-transition class="rounded-lg px-4 py-3 text-sm" :class="flashType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
        <span x-text="flashMessage"></span>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Hierarchy Tree Canvas --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        {{-- Loading State --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm text-gray-500">Loading company hierarchy...</span>
            </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && isEmpty" x-cloak class="flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No companies found</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Create your first company to build the hierarchy.</p>
        </div>

        {{-- D3 Org Chart Container --}}
        <div x-show="!loading && !isEmpty" x-cloak id="org-chart-container" class="org-chart-container overflow-auto"></div>
    </div>
</div>

{{-- Company Hierarchy Initialization --}}
<script>
function companyHierarchy() {
    return {
        chart: null,
        companies: [],
        searchQuery: '',
        loading: true,
        isEmpty: false,
        flatData: [],
        flashMessage: '',
        flashType: 'success',
        canManage: {{ $canManage ? 'true' : 'false' }},

        async init() {
            window.__companyTree = this;
            try {
                const resp = await fetch('{{ route("admin.companies.tree") }}');
                const data = await resp.json();
                // Handle both flat and nested formats
                if (Array.isArray(data.companies)) {
                    this.companies = data.companies;
                } else if (data.name && data.children) {
                    this.companies = [];
                    this.extractFlat(data, null);
                } else {
                    this.companies = [];
                }
                if (this.companies.length === 0) {
                    this.isEmpty = true;
                    this.loading = false;
                } else {
                    this.isEmpty = false;
                    this.flatData = this.companies.map(c => ({
                        id: c.id.toString(),
                        parentId: c.parent_id ? c.parent_id.toString() : '',
                        name: c.name,
                        slug: c.slug || '',
                        type: c.type || '',
                        type_label: c.type_label || '',
                        is_active: c.is_active !== undefined ? c.is_active : true,
                        users_count: c.users_count || 0,
                        logo_url: c.logo_url || null,
                    }));
                    this.loading = false;
                    await this.$nextTick();
                    this.initChart();
                }
            } catch (e) {
                console.error('Failed to load company tree:', e);
                this.isEmpty = true;
                this.loading = false;
                this.showFlash('Failed to load company hierarchy.', 'error');
            }
        },

        extractFlat(node, parentId) {
            this.companies.push({
                id: node.id,
                name: node.name,
                slug: node.slug || '',
                type: node.type || '',
                type_label: node.title || node.type_label || '',
                parent_id: parentId,
                is_active: node.is_active !== undefined ? node.is_active : true,
                users_count: node.users_count || 0,
                logo_url: node.logo_url || null,
            });
            if (node.children) {
                node.children.forEach(c => this.extractFlat(c, node.id));
            }
        },

        prepareChartData() {
            const data = this.flatData.map(d => ({ ...d }));
            const rootNodes = data.filter(d => !d.parentId || d.parentId === '');
            if (rootNodes.length > 1) {
                const syntheticRootId = '__company-root__';
                data.forEach(d => {
                    if (!d.parentId || d.parentId === '') {
                        d.parentId = syntheticRootId;
                    }
                });
                data.push({
                    id: syntheticRootId,
                    parentId: '',
                    name: 'Company Structure',
                    slug: '',
                    type: '',
                    type_label: '',
                    is_active: true,
                    users_count: 0,
                    logo_url: null,
                    isStructure: true,
                });
            }
            return data;
        },

        initChart() {
            if (typeof d3 === 'undefined' || typeof d3.OrgChart === 'undefined') {
                setTimeout(() => this.initChart(), 50);
                return;
            }

            const self = this;

            this.chart = new d3.OrgChart()
                .container('#org-chart-container')
                .data(this.prepareChartData())
                .nodeWidth(() => 240)
                .nodeHeight(() => 140)
                .svgHeight(() => 500)
                .childrenMargin(() => 50)
                .compactMarginBetween(() => 30)
                .compactMarginPair(() => 80)
                .neighbourMargin(() => 20)
                .siblingsMargin(() => 20)
                .nodeContent((d) => self.renderCompanyHtml(d))
                .nodeButtonWidth(() => 0)
                .nodeButtonHeight(() => 0)
                .buttonContent(() => '')
                .linkUpdate(function(d) {
                    d3.select(this).attr('stroke', '#000');
                })
                .render();
            this.chart.expandAll();
        },

        renderCompanyHtml(d) {
            const data = d.data || d;

            // Structure root node
            if (data.isStructure) {
                return `
                <div data-company-id="${data.id}" data-search-dimmable style="
                    width:100%;height:100%;
                    display:flex;align-items:center;justify-content:center;
                    background:linear-gradient(135deg,#1e40af,#3b82f6);
                    border:none;
                    border-radius:12px;
                    padding:14px 16px;
                    box-shadow:0 4px 12px rgba(59,130,246,0.3);
                    font-family:system-ui,-apple-system,sans-serif;
                    box-sizing:border-box;
                    color:#fff;
                ">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="width:24px;height:24px;flex-shrink:0;opacity:0.9;">
                            <path d="M3 21h18M3 10h18M7 3v4M17 3v4M5 3v4M19 3v4M5 10v11M19 10v11M9 10v11M15 10v11M3 21V10l9-7 9 7v11H3z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <div style="flex:1;min-width:0;">
                            <div data-search-name style="font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escHtml(data.name)}</div>
                            <div style="font-size:11px;opacity:0.8;margin-top:2px;">All Company Structure registered in the system</div>
                        </div>
                    </div>
                </div>`;
            }

            // Company node
            const typeColors = {
                'holding': { bg: '#dbeafe', text: '#1e40af' },
                'sub-holding': { bg: '#fef3c7', text: '#92400e' },
                'operating': { bg: '#d1fae5', text: '#065f46' },
            };
            const typeColor = typeColors[data.type] || { bg: '#f3f4f6', text: '#374151' };
            const activeClass = data.is_active ? '' : ' opacity-40';
            const disabledBadge = !data.is_active
                ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:500;color:#6b7280;background:#f3f4f6;padding:2px 6px;border-radius:9999px;margin-top:4px;"><span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:#9ca3af;"></span>Disabled</span>`
                : '';
            const toggleBtnColor = data.is_active
                ? 'background:#fef2f2;color:#dc2626;'
                : 'background:#f0fdf4;color:#16a34a;';
            const iconHtml = data.logo_url
                ? `<img src="${this.escHtml(data.logo_url)}" alt="" style="width:36px;height:36px;border-radius:8px;object-fit:contain;background:#f3f4f6;flex-shrink:0;" />`
                : `<div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${typeColor.bg};color:${typeColor.text};">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6 2.75C4.75736 2.75 3.75 3.75736 3.75 5V19C3.75 19.4142 4.08579 19.75 4.5 19.75H19.5C19.9142 19.75 20.25 19.4142 20.25 19V5C20.25 3.75736 19.2426 2.75 18 2.75H6ZM5.25 5C5.25 4.58579 5.58579 4.25 6 4.25H18C18.4142 4.25 18.75 4.58579 18.75 5V18.25H5.25V5ZM8.75 8C8.75 7.58579 9.08579 7.25 9.5 7.25H10.5C10.9142 7.25 11.25 7.58579 11.25 8V8.5C11.25 8.9142 10.9142 9.25 10.5 9.25H9.5C9.08579 9.25 8.75 8.9142 8.75 8.5V8Z" fill="currentColor"/></svg>
                </div>`;

            return `
            <div data-company-id="${data.id}" data-search-dimmable style="
                width:100%;height:100%;
                background:#fff;
                border:1px solid #e5e7eb;
                border-radius:12px;
                padding:12px;
                box-shadow:0 1px 2px rgba(0,0,0,0.05);
                font-family:system-ui,-apple-system,sans-serif;
                box-sizing:border-box;
                ${!data.is_active ? 'opacity:0.5;' : ''}
            ">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    ${iconHtml}
                    <div style="flex:1;min-width:0;">
                        <div data-search-name style="font-size:13px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escHtml(data.name)}</div>
                        <div data-search-slug style="font-size:11px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;">${this.escHtml(data.slug)}</div>
                        <span style="display:inline-block;font-size:10px;font-weight:500;padding:1px 6px;border-radius:9999px;background:${typeColor.bg};color:${typeColor.text};margin-top:4px;">${this.escHtml(data.type_label)}</span>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;flex-wrap:wrap;">
                            <span style="font-size:10px;color:#9ca3af;">${data.users_count} users</span>
                            ${disabledBadge}
                        </div>
                    </div>
                </div>
                ${this.canManage ? `
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;display:flex;gap:5px;">
                    <button onclick="event.stopPropagation();event.preventDefault();window.__companyTree.toggleCompanyActive(${data.id});return false;"
                        style="flex:1;padding:3px 5px;font-size:10px;font-weight:500;border-radius:6px;border:none;cursor:pointer;${toggleBtnColor}">${data.is_active ? 'Disable' : 'Enable'}</button>
                    <a href="/admin/companies/${data.id}/edit" onclick="event.stopPropagation();"
                        style="flex:1;padding:3px 5px;font-size:10px;font-weight:500;border-radius:6px;border:none;background:#f3f4f6;color:#374151;cursor:pointer;text-align:center;text-decoration:none;">Edit</a>
                    <a href="/admin/companies/create?parent_id=${data.id}" onclick="event.stopPropagation();"
                        style="flex:1;padding:3px 5px;font-size:10px;font-weight:500;border-radius:6px;border:none;background:#ede9fe;color:#7c3aed;cursor:pointer;text-align:center;text-decoration:none;">+Child</a>
                </div>` : ''}
            </div>`;
        },

        escHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        },

        // ---- Search ----
        doSearch() {
            const container = document.getElementById('org-chart-container');
            if (!container) return;
            const q = (this.searchQuery || '').toLowerCase().trim();
            const cards = container.querySelectorAll('[data-search-dimmable]');
            cards.forEach(card => {
                const nameEl = card.querySelector('[data-search-name]');
                const slugEl = card.querySelector('[data-search-slug]');
                const name = (nameEl?.textContent || '').toLowerCase();
                const slug = (slugEl?.textContent || '').toLowerCase();
                const matches = !q || name.includes(q) || slug.includes(q);
                card.style.opacity = matches ? '1' : '0.2';
            });
        },

        // ---- Reset View ----
        resetView() {
            if (!this.chart) return;
            const container = document.getElementById('org-chart-container');
            container.innerHTML = '';
            this.initChart();
        },

        // ---- Toggle Company Active ----
        async toggleCompanyActive(companyId) {
            if (!this.canManage) return;
            try {
                const resp = await fetch(`/admin/companies/${companyId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await resp.json();
                if (data.success) {
                    // Update local cached data
                    const idx = this.companies.findIndex(c => c.id === companyId);
                    if (idx !== -1) this.companies[idx].is_active = data.is_active;
                    const flatIdx = this.flatData.findIndex(c => c.id == companyId);
                    if (flatIdx !== -1) this.flatData[flatIdx].is_active = data.is_active;
                    // Re-render chart
                    this.chart.data(this.prepareChartData()).render();
                    this.chart.expandAll();
                    this.showFlash(data.is_active ? 'Company enabled.' : 'Company disabled.', 'success');
                } else if (data.error) {
                    this.showFlash(data.error, 'error');
                }
            } catch (e) {
                this.showFlash('Failed to toggle company status.', 'error');
            }
        },

        showFlash(msg, type) {
            this.flashMessage = msg; this.flashType = type;
            setTimeout(() => { this.flashMessage = ''; }, 4000);
        },
    };
}
</script>
@endsection
