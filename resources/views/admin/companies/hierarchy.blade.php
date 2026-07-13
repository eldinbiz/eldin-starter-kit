@extends('layouts.app')

@section('title', 'Company Hierarchy')

@section('content')
<div x-data="companyHierarchy()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Company Hierarchy</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage the organizational structure of companies — drag to reparent, toggle to enable/disable.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Search -->
            <div class="relative">
                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400">
                    <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" />
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Search companies..."
                    class="w-64 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
            </div>
            <button @click="expandAll()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Expand All
            </button>
            <button @click="collapseAll()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Collapse All
            </button>
            <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Company
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
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

    <!-- Hierarchy Tree Canvas -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm text-gray-500">Loading company hierarchy...</span>
            </div>
        </div>

        <div x-show="!loading && rootNodes.length === 0" x-cloak class="flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No companies found</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Create your first company to build the hierarchy.</p>
        </div>

        <div x-show="!loading && rootNodes.length > 0" x-cloak class="overflow-x-auto">
            <div class="min-w-max py-4 flex items-start gap-8">
                <template x-for="node in filteredRootNodes" :key="`${node.id}-${treeVersion}`">
                    <div x-data="{ node: node }" x-init="node = node">
                        <div x-html="renderSubtree(node)"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function companyHierarchy() {
    return {
        allCompanies: [],
        rootNodes: [],
        searchQuery: '',
        loading: true,
        flashMessage: '',
        flashType: 'success',
        treeVersion: 0,

        buildTree(parentId) {
            return this.allCompanies
                .filter(c => c.parent_id === parentId)
                .map(c => ({ ...c, collapsed: false, children: this.buildTree(c.id) }));
        },

        async init() {
            window.__companyTree = this;
            try {
                const resp = await fetch('{{ route("admin.companies.tree") }}');
                const data = await resp.json();
                // Handle both flat (old) and nested (new) data formats
                if (Array.isArray(data.companies)) {
                    this.allCompanies = data.companies;
                } else if (data.name && data.children) {
                    // Nested format from new tree endpoint
                    this.extractFlat(data, null);
                } else {
                    this.allCompanies = [];
                }
                this.rootNodes = this.buildTree(null);
            } catch (e) {
                console.error('Failed to load company tree:', e);
                this.showFlash('Failed to load company hierarchy.', 'error');
            } finally {
                this.loading = false;
            }
        },

        extractFlat(node, parentId) {
            this.allCompanies.push({
                id: node.id,
                name: node.name,
                slug: node.slug || '',
                type: node.type || '',
                type_label: node.title || node.type_label || '',
                parent_id: parentId,
                is_active: node.is_active !== undefined ? node.is_active : true,
                users_count: node.users_count || 0,
            });
            if (node.children) {
                node.children.forEach(c => this.extractFlat(c, node.id));
            }
        },

        get filteredRootNodes() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return this.rootNodes;
            return this.rootNodes.filter(n => this.treeHasMatch(n));
        },

        treeHasMatch(node) {
            if (this.isSearchMatch(node)) return true;
            if (node.children && node.children.length) return node.children.some(c => this.treeHasMatch(c));
            return false;
        },

        isSearchMatch(node) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const q = this.searchQuery.toLowerCase();
            return node.name.toLowerCase().includes(q) ||
                   node.slug.toLowerCase().includes(q) ||
                   (node.type_label && node.type_label.toLowerCase().includes(q));
        },

        isSearchHighlight(node) {
            return this.searchQuery && this.searchQuery.trim() !== '' && this.isSearchMatch(node);
        },

        visibleChildren(node) {
            if (!node.children || !node.children.length) return [];
            if (!this.searchQuery || this.searchQuery.trim() === '') return node.children;
            return node.children.filter(c => this.treeHasMatch(c));
        },

        typeIconBg(type) {
            return {
                'holding': 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
                'sub-holding': 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
                'operating': 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400',
            }[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        },

        typeBadgeBg(type) {
            return {
                'holding': 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
                'sub-holding': 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
                'operating': 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
            }[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
        },

        expandAll() { this.setAllCollapsed(this.rootNodes, false); },
        collapseAll() { this.setAllCollapsed(this.rootNodes, true); },

        setAllCollapsed(nodes, collapsed) {
            nodes.forEach(n => {
                n.collapsed = collapsed;
                if (n.children && n.children.length) this.setAllCollapsed(n.children, collapsed);
            });
        },

        dragStart(event, companyId) {
            event.dataTransfer.setData('text/plain', companyId.toString());
            event.dataTransfer.effectAllowed = 'move';
        },
        dragOver(event) { event.dataTransfer.dropEffect = 'move'; },
        async drop(event, targetCompanyId) {
            const draggedId = parseInt(event.dataTransfer.getData('text/plain'));
            if (!draggedId || draggedId === targetCompanyId) return;
            const draggedNode = this.findNodeById(this.rootNodes, draggedId);
            const targetNode = this.findNodeById(this.rootNodes, targetCompanyId);
            if (draggedNode && targetNode && this.isDescendantOf(targetNode, draggedId)) {
                this.showFlash('Cannot move a company under one of its own subsidiaries.', 'error');
                return;
            }
            await this.reparent(draggedId, targetCompanyId);
        },

        isDescendantOf(node, ancestorId) {
            if (node.parent_id === ancestorId) return true;
            const parent = this.findNodeById(this.rootNodes, node.parent_id);
            if (parent) return this.isDescendantOf(parent, ancestorId);
            return false;
        },

        findNodeById(nodes, id) {
            for (const n of nodes) {
                if (n.id == id) return n;
                if (n.children && n.children.length) {
                    const found = this.findNodeById(n.children, id);
                    if (found) return found;
                }
            }
            return null;
        },

        async reparent(companyId, newParentId) {
            try {
                const resp = await fetch('{{ route("admin.companies.reparent") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ company_id: companyId, new_parent_id: newParentId }),
                });
                const data = await resp.json();
                if (data.success) { await this.reloadTree(); this.treeVersion++; this.showFlash('Company moved successfully.', 'success'); }
                else if (data.error) this.showFlash(data.error, 'error');
            } catch (e) { this.showFlash('Failed to move company.', 'error'); }
        },

        async toggleCompanyActive(companyId) {
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
                    const idx = this.allCompanies.findIndex(c => c.id === companyId);
                    if (idx !== -1) {
                        this.allCompanies[idx].is_active = data.is_active;
                    }
                    // Rebuild tree from updated local data, preserving collapse state
                    const oldCollapsed = {};
                    this.collectCollapsed(this.rootNodes, oldCollapsed);
                    this.rootNodes = this.buildTree(null);
                    this.restoreCollapsed(this.rootNodes, oldCollapsed);
                    // Force all x-html directives to re-render by bumping the version
                    this.treeVersion++;
                    this.showFlash(data.is_active ? 'Company enabled.' : 'Company disabled.', 'success');
                }
                else if (data.error) this.showFlash(data.error, 'error');
            } catch (e) { this.showFlash('Failed to toggle company status.', 'error'); }
        },

        async reloadTree() {
            try {
                const resp = await fetch('{{ route("admin.companies.tree") }}');
                const data = await resp.json();
                if (Array.isArray(data.companies)) {
                    this.allCompanies = data.companies;
                } else if (data.name && data.children) {
                    this.allCompanies = [];
                    this.extractFlat(data, null);
                } else {
                    this.allCompanies = [];
                }
                const oldCollapsed = {};
                this.collectCollapsed(this.rootNodes, oldCollapsed);
                this.rootNodes = this.buildTree(null);
                this.restoreCollapsed(this.rootNodes, oldCollapsed);
                this.treeVersion++;
            } catch (e) { console.error('Failed to reload tree:', e); }
        },

        collectCollapsed(nodes, map) {
            nodes.forEach(n => { if (n.collapsed) map[n.id] = true; if (n.children && n.children.length) this.collectCollapsed(n.children, map); });
        },
        restoreCollapsed(nodes, map) {
            nodes.forEach(n => { if (map[n.id]) n.collapsed = true; if (n.children && n.children.length) this.restoreCollapsed(n.children, map); });
        },

        showFlash(msg, type) {
            this.flashMessage = msg; this.flashType = type;
            setTimeout(() => { this.flashMessage = ''; }, 4000);
        },

        // ---- Recursive HTML Rendering (FULLY recursive at all depths) ----
        renderSubtree(node) {
            const self = this;
            const card = this.renderNodeCard(node, self);
            if (!node.children || !node.children.length || node.collapsed) {
                return `<div class="flex flex-col items-center ${!node.is_active ? 'opacity-40' : ''}">${card}</div>`;
            }
            const visible = self.visibleChildren(node);
            if (!visible.length) {
                return `<div class="flex flex-col items-center ${!node.is_active ? 'opacity-40' : ''}">${card}</div>`;
            }
            const barWidth = Math.max(0, (visible.length - 1) * 16);
            return `
            <div class="flex flex-col items-center ${!node.is_active ? 'opacity-40' : ''}">
                ${card}
                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                <div class="relative">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 h-px bg-gray-300 dark:bg-gray-600" style="width:${barWidth}rem;max-width:calc(100% - 8rem)"></div>
                    <div class="flex items-start gap-4 pt-6">
                        ${visible.map(c => `<div class="flex flex-col items-center">${self.renderSubtree(c)}</div>`).join('')}
                    </div>
                </div>
            </div>`;
        },

        renderNodeCard(node, self) {
            const borderClass = node.is_active
                ? (self.isSearchHighlight(node) ? 'border-yellow-400 ring-2 ring-yellow-200 dark:ring-yellow-800' : 'border-gray-200 dark:border-gray-700')
                : 'border-gray-300 dark:border-gray-600';
            const iconBg = self.typeIconBg(node.type);
            const badgeBg = self.typeBadgeBg(node.type);
            const disabledBadge = !node.is_active
                ? '<span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Disabled</span>'
                : '';
            const toggleClass = node.is_active
                ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40'
                : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40';

            const iconOrLogo = node.logo_url
                ? `<img src="${self.esc(node.logo_url)}" alt="${self.esc(node.name)} Logo" class="h-10 w-10 rounded-lg object-contain bg-gray-100 dark:bg-gray-700 shrink-0" />`
                : `<div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 ${iconBg}">
                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2.75C4.75736 2.75 3.75 3.75736 3.75 5V19C3.75 19.4142 4.08579 19.75 4.5 19.75H19.5C19.9142 19.75 20.25 19.4142 20.25 19V5C20.25 3.75736 19.2426 2.75 18 2.75H6ZM5.25 5C5.25 4.58579 5.58579 4.25 6 4.25H18C18.4142 4.25 18.75 4.58579 18.75 5V18.25H5.25V5ZM8.75 8C8.75 7.58579 9.08579 7.25 9.5 7.25H10.5C10.9142 7.25 11.25 7.58579 11.25 8V8.5C11.25 8.9142 10.9142 9.25 10.5 9.25H9.5C9.08579 9.25 8.75 8.9142 8.75 8.5V8Z"/>
                    </svg>
                </div>`;

            return `
            <div draggable="true" data-company-id="${node.id}"
                 class="w-64 rounded-xl border shadow-xs bg-white dark:bg-gray-800 p-3.5 transition-all duration-200 cursor-grab active:cursor-grabbing ${borderClass}">
                <div class="flex items-start gap-3">
                    ${iconOrLogo}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">${self.esc(node.name)}</p>
                            <span class="inline-flex text-[10px] font-medium px-1.5 py-0.5 rounded-full ${badgeBg}">${self.esc(node.type_label)}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${self.esc(node.slug)}</p>
                        <div class="flex items-center gap-3 mt-1.5">
                            <span class="text-[11px] text-gray-400">${node.users_count} users</span>
                            ${disabledBadge}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    ${node.children && node.children.length > 0
                        ? `<button onclick="event.stopPropagation(); const d=this.closest('[x-data]').__x.$data.node; d.collapsed=!d.collapsed;" class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><span>${node.children.length} subs</span></button>`
                        : '<span class="text-[11px] text-gray-400 px-2 py-1">No subs</span>'}
                    <button onclick="event.stopPropagation(); window.__companyTree.toggleCompanyActive(${node.id});" class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md transition-colors ${toggleClass}">${node.is_active ? 'Disable' : 'Enable'}</button>
                    <a href="/admin/companies/${node.id}/edit" onclick="event.stopPropagation();" class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Edit</a>
                    <a href="/admin/companies/create?parent_id=${node.id}" onclick="event.stopPropagation();" class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/40 transition-colors">+Child</a>
                </div>
            </div>`;
        },

        esc(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        },
    };
}
</script>
@endsection
