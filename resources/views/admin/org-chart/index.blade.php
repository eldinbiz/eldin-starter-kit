@extends('layouts.app')

@section('title', 'Organization Chart')

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
<div x-data="orgChart()" x-init="init()" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Organization Chart</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $activeCompany->name ?? $company->name ?? 'Company' }} — Reporting Structure
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
                <input
                    type="text"
                    x-model="searchQuery"
                    x-on:input.debounce.300ms="doSearch()"
                    data-testid="org-chart-search-input"
                    placeholder="Search users..."
                    class="w-64 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500"
                />
            </div>
            {{-- Reset View --}}
            <button @click="resetView()" data-testid="org-chart-reset-button" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
                </svg>
                Reset View
            </button>
        </div>
    </div>

    {{-- Org Chart Canvas --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        {{-- Loading State --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm text-gray-500">Loading organization chart...</span>
            </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && isEmpty" x-cloak class="flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No organization structure found</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Assign users to this company and set reporting lines to build the chart.</p>
        </div>

        {{-- D3 Org Chart Container --}}
        <div x-show="!loading && !isEmpty" x-cloak id="org-chart-container" class="org-chart-container overflow-auto"></div>
    </div>

    {{-- Reassign Parent Modal (proper centered dialog) --}}
    <div x-show="showEditParent" x-transition.opacity
         data-testid="edit-employee-modal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="closeEditParent()">
        <div x-transition class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-md max-h-[80vh] overflow-y-auto text-gray-800 dark:text-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Employee Details</h3>
                <button @click="closeEditParent()" data-testid="close-edit-parent-button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                Editing: <strong x-text="editParentUserName"></strong>
                <span x-show="editParentUserEmail" class="text-gray-400" x-text="' — ' + editParentUserEmail"></span>
            </p>
            {{-- Job Title --}}
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Job Title</label>
            <input type="text" x-model="editParentJobTitle" data-testid="edit-job-title-input" placeholder="Enter job title..."
                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 px-3 text-sm outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white mb-3" />
            {{-- Status --}}
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select x-model="editParentStatus" data-testid="edit-status-select"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 px-3 text-sm outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white mb-4">
                <option value="active">Active</option>
                <option value="on_leave">On Leave</option>
                <option value="inactive">Inactive</option>
            </select>
            {{-- Change Manager Section --}}
            <template x-if="editParentCurrentManager">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                    Current manager: <strong x-text="editParentCurrentManager"></strong>
                </p>
            </template>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Change manager:</p>
            <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                <template x-for="candidate in parentCandidates" :key="candidate.id">
                    <div @click="selectManager(candidate.id)"
                        :data-testid="'candidate-manager-' + candidate.id"
                        class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 flex items-center justify-between"
                        :class="{ 'bg-brand-50 dark:bg-brand-900/20': editParentNewManager === candidate.id }">
                        <div class="flex items-center gap-2">
                            <span x-text="candidate.name"></span>
                            <span x-show="candidate.isDescendant" data-testid="badge-will-be-promoted" class="text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 rounded-full font-medium">will be promoted</span>
                        </div>
                        <span class="text-xs text-gray-400" x-text="candidate.title || ''"></span>
                    </div>
                </template>
                <template x-if="parentCandidates.length === 0">
                    <div class="px-3 py-4 text-sm text-gray-400 text-center">No candidates available</div>
                </template>
                {{-- Remove Manager option --}}
                <div @click="selectManager(null)"
                    data-testid="remove-manager-button"
                    :class="{ 'bg-red-50 dark:bg-red-900/20': editParentNewManager === null }"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Remove Manager (make root-level)
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button @click="saveEmployee()" data-testid="save-employee-button" class="flex-1 py-2 text-sm font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors">Save Changes</button>
                <button @click="closeEditParent()" data-testid="cancel-employee-button" class="flex-1 py-2 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Org Chart Initialization --}}
<script>
function orgChart() {
    return {
        chart: null,
        users: [],
        companyName: 'Organization',
        searchQuery: '',
        loading: true,
        isEmpty: false,
        flatData: [],
        canManage: {{ $canManage ? 'true' : 'false' }},

        // Edit Parent state
        showEditParent: false,
        editParentUserId: null,
        editParentUserName: '',
        editParentUserEmail: '',
        editParentCurrentManager: '',
        editParentJobTitle: '',
        editParentStatus: 'active',
        editParentNewManager: undefined,
        parentCandidates: [],

        async init() {
            window.__orgChart = this;
            try {
                const resp = await fetch('{{ route("admin.org-chart.tree") }}');
                const data = await resp.json();
                this.users = data.users || [];
                this.companyName = data.company?.name || 'Organization';
                if (this.users.length === 0) {
                    this.isEmpty = true;
                    this.loading = false;
                } else {
                    this.isEmpty = false;
                    this.flatData = this.users.map(u => ({
                        id: u.id.toString(),
                        parentId: u.manager_id ? u.manager_id.toString() : '',
                        name: u.name,
                        title: u.job_title || 'No Title',
                        status: u.status || 'active',
                        initials: u.initials,
                        email: u.email,
                    }));
                    this.loading = false;
                    await this.$nextTick();
                    this.initChart();
                }
            } catch (e) {
                console.error('Failed to load org chart:', e);
                this.isEmpty = true;
                this.loading = false;
            }
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
                .nodeWidth(() => 230)
                .nodeHeight(() => 140)
                .svgHeight(() => 500)
                .childrenMargin(() => 50)
                .compactMarginBetween(() => 30)
                .compactMarginPair(() => 80)
                .neighbourMargin(() => 20)
                .siblingsMargin(() => 20)
                .nodeContent((d) => self.renderNodeHtml(d))
                .nodeButtonWidth(() => 0)
                .nodeButtonHeight(() => 0)
                .buttonContent(() => '')
                .linkUpdate(function(d) {
                    d3.select(this).attr('stroke', '#000');
                })
                .render();
            this.chart.expandAll();
        },

        renderNodeHtml(d) {
            const data = d.data || d;

            // Company node (synthetic root)
            if (data.isCompany) {
                return `
                <div data-user-id="${data.id}" data-search-dimmable style="
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
                            <div data-search-title style="font-size:11px;opacity:0.8;margin-top:2px;">Company Overview</div>
                        </div>
                    </div>
                </div>`;
            }

            // Person node
            const statusDotColor = data.status === 'active' ? '#22c55e' : (data.status === 'on_leave' ? '#f59e0b' : '#9ca3af');
            const statusLabel = data.status === 'active' ? 'Active' : (data.status === 'on_leave' ? 'On Leave' : 'Inactive');
            const avatarBg = data.status === 'active'
                ? 'background:#ede9fe;color:#7c3aed;'
                : 'background:#f3f4f6;color:#6b7280;';

            const editBtnHtml = this.canManage ? `
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;">
                    <button onclick="event.stopPropagation();event.preventDefault();window.__orgChart.openEditParent('${data.id}');return false;"
                        data-testid="edit-node-button-${data.id}"
                        style="width:100%;padding:4px 6px;font-size:10px;font-weight:500;border-radius:6px;border:none;background:#e0e7ff;color:#4338ca;cursor:pointer;">
                        Edit
                    </button>
                </div>` : '';

            return `
            <div data-user-id="${data.id}" data-search-dimmable style="
                width:100%;height:100%;
                background:#fff;
                border:1px solid #e5e7eb;
                border-radius:12px;
                padding:12px;
                box-shadow:0 1px 2px rgba(0,0,0,0.05);
                font-family:system-ui,-apple-system,sans-serif;
                box-sizing:border-box;
            ">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <div style="
                        width:36px;height:36px;
                        border-radius:50%;
                        display:flex;align-items:center;justify-content:center;
                        font-size:13px;font-weight:700;
                        flex-shrink:0;
                        ${avatarBg}
                    ">${this.escHtml(data.initials)}</div>
                    <div style="flex:1;min-width:0;">
                        <div data-search-name style="font-size:13px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escHtml(data.name)}</div>
                        <div data-search-title style="font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escHtml(data.title)}</div>
                        <div data-search-email style="font-size:10px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escHtml(data.email || '')}</div>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:${statusDotColor};"></span>
                            <span style="font-size:10px;color:#9ca3af;">${statusLabel}</span>
                        </div>
                    </div>
                </div>
                ${editBtnHtml}
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
                const titleEl = card.querySelector('[data-search-title]');
                const emailEl = card.querySelector('[data-search-email]');
                const name = (nameEl?.textContent || '').toLowerCase();
                const title = (titleEl?.textContent || '').toLowerCase();
                const email = (emailEl?.textContent || '').toLowerCase();
                const matches = !q || name.includes(q) || title.includes(q) || email.includes(q);
                card.style.opacity = matches ? '1' : '0.2';
            });
        },

        // ---- Prepare Chart Data (wraps multiple roots under synthetic company node) ----
        prepareChartData() {
            const data = this.flatData.map(d => ({ ...d }));
            const rootNodes = data.filter(d => !d.parentId || d.parentId === '');
            if (rootNodes.length > 1) {
                const syntheticRootId = '__org-root__';
                data.forEach(d => {
                    if (!d.parentId || d.parentId === '') {
                        d.parentId = syntheticRootId;
                    }
                });
                const companyName = this.companyName || 'Organization';
                data.push({
                    id: syntheticRootId,
                    parentId: '',
                    name: companyName,
                    title: 'Company',
                    status: 'active',
                    initials: '',
                    isCompany: true,
                });
            }
            return data;
        },

        // ---- Reset View ----
        resetView() {
            if (!this.chart) return;
            const container = document.getElementById('org-chart-container');
            container.innerHTML = '';
            this.initChart();
        },

        // ---- Select Manager (no save) ----
        selectManager(id) {
            this.editParentNewManager = id;
        },

        // ---- Save Employee ----
        async saveEmployee() {
            const userId = this.editParentUserId;
            if (!userId) return;
            try {
                // Use selected manager if explicitly chosen, otherwise keep current
                let managerToSave;
                if (this.editParentNewManager !== undefined) {
                    managerToSave = this.editParentNewManager;
                } else {
                    const user = (this.users || []).find(u => u.id == userId);
                    managerToSave = user ? (user.manager_id || null) : null;
                }
                const resp = await fetch('{{ route("admin.org-chart.reassign") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_id: userId, new_manager_id: managerToSave, job_title: this.editParentJobTitle, status: this.editParentStatus }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.closeEditParent();
                    const treeResp = await fetch('{{ route("admin.org-chart.tree") }}');
                    const treeData = await treeResp.json();
                    this.users = treeData.users || [];
                    this.flatData = this.users.map(u => ({
                        id: u.id.toString(),
                        parentId: u.manager_id ? u.manager_id.toString() : '',
                        name: u.name,
                        title: u.job_title || 'No Title',
                        status: u.status || 'active',
                        initials: u.initials,
                        email: u.email,
                    }));
                    this.chart.data(this.prepareChartData()).render();
                    this.chart.expandAll();
                }
            } catch (e) {
                console.error('Failed to save employee:', e);
            }
        },

        // ---- Edit Parent (Reassign Manager) ----
        openEditParent(userId) {
            console.log('openEditParent called with userId:', userId);
            // Show the modal FIRST, before any data operations that might fail
            this.showEditParent = true;
            this.editParentUserId = userId;
            const users = this.users || [];
            const user = users.find(u => u.id == userId);
            this.editParentUserName = user ? user.name : 'Unknown';
            this.editParentUserEmail = user ? (user.email || '') : '';
            this.editParentJobTitle = user ? (user.job_title || '') : '';
            this.editParentStatus = user ? (user.status || 'active') : 'active';
            // Find current manager
            if (user && user.manager_id) {
                const mgr = users.find(u => u.id == user.manager_id);
                this.editParentCurrentManager = mgr ? mgr.name : 'Unknown';
            } else {
                this.editParentCurrentManager = '';
            }
            this.editParentNewManager = undefined;
            this.filterParentCandidates();
        },

        closeEditParent() {
            this.showEditParent = false;
            this.editParentUserId = null;
            this.editParentJobTitle = '';
            this.editParentStatus = 'active';
            this.editParentNewManager = undefined;
            this.parentCandidates = [];
        },

        filterParentCandidates() {
            const selfId = this.editParentUserId;

            // Collect all descendant IDs of selfId
            const descendantIds = new Set();
            const queue = [selfId];
            while (queue.length > 0) {
                const current = queue.shift();
                this.users.forEach(u => {
                    if (u.manager_id == current && !descendantIds.has(u.id)) {
                        descendantIds.add(u.id);
                        queue.push(u.id);
                    }
                });
            }

            this.parentCandidates = this.users
                .filter(u => u.id != selfId)
                .map(u => ({
                    id: u.id,
                    name: u.name,
                    title: u.job_title || '',
                    isDescendant: descendantIds.has(u.id),
                }));
        },

        async reassignParent(newManagerId) {
            const userId = this.editParentUserId;
            if (!userId) return;
            try {
                const resp = await fetch('{{ route("admin.org-chart.reassign") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_id: userId, new_manager_id: newManagerId, job_title: this.editParentJobTitle, status: this.editParentStatus }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.closeEditParent();
                    // Reload tree
                    const treeResp = await fetch('{{ route("admin.org-chart.tree") }}');
                    const treeData = await treeResp.json();
                    this.users = treeData.users || [];
                    this.flatData = this.users.map(u => ({
                        id: u.id.toString(),
                        parentId: u.manager_id ? u.manager_id.toString() : '',
                        name: u.name,
                        title: u.job_title || 'No Title',
                        status: u.status || 'active',
                        initials: u.initials,
                        email: u.email,
                    }));
                    // Re-render chart (always expanded)
                    this.chart.data(this.prepareChartData()).render();
                    this.chart.expandAll();
                }
            } catch (e) {
                console.error('Failed to reassign manager:', e);
            }
        },
    };
}
</script>
@endsection
