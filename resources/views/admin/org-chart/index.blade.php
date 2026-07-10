@extends('layouts.app')

@section('title', 'Organization Chart')

@section('content')
<div x-data="orgChart()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Organization Chart</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $activeCompany->name ?? $company->name ?? 'Company' }} — Reporting Structure
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
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Search users..."
                    class="w-64 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500"
                />
            </div>
            <!-- Expand All / Collapse All -->
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
        </div>
    </div>

    <!-- Org Chart Canvas -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm text-gray-500">Loading organization chart...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && rootNodes.length === 0" x-cloak class="flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No organization structure found</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Assign users to this company and set reporting lines to build the chart.</p>
        </div>

        <!-- Tree View -->
        <div x-show="!loading && rootNodes.length > 0" x-cloak class="overflow-x-auto">
            <div class="min-w-max py-4">
                <template x-for="node in rootNodes" :key="node.id">
                    <div x-data>
                        <!-- RECURSIVE ORG NODE TEMPLATE -->
                        <div class="flex flex-col items-center">
                            <!-- Node Card -->
                            <div
                                class="relative flex flex-col items-center"
                                :class="{ 'opacity-30': searchQuery && searchQuery.trim() !== '' && !$parent.isSearchMatch(node) }"
                            >
                                <div
                                    class="w-56 rounded-xl border shadow-xs bg-white dark:bg-gray-800 p-3.5 transition-all duration-200 cursor-grab active:cursor-grabbing"
                                    :class="{
                                        'border-brand-500 ring-2 ring-brand-200 dark:ring-brand-800': $parent.isTracing(node.id),
                                        'border-yellow-400 ring-2 ring-yellow-200 dark:ring-yellow-800': searchQuery && searchQuery.trim() !== '' && $parent.isSearchMatch(node),
                                        'border-gray-200 dark:border-gray-700': !$parent.isTracing(node.id) && (!searchQuery || searchQuery.trim() === '' || !$parent.isSearchMatch(node)),
                                    }"
                                    :draggable="true"
                                    @dragstart="$parent.dragStart($event, node.id)"
                                    @dragover.prevent="$parent.dragOver($event)"
                                    @drop="$parent.drop($event, node.id)"
                                >
                                    <div class="flex items-start gap-3">
                                        <!-- Avatar / Initials -->
                                        <div
                                            class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                                            :class="node.status === 'active' ? 'bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                            x-text="node.initials"
                                        ></div>
                                        <!-- Info -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" x-text="node.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="node.job_title"></p>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <span
                                                    class="inline-flex h-1.5 w-1.5 rounded-full"
                                                    :class="node.status === 'active' ? 'bg-green-500' : (node.status === 'on_leave' ? 'bg-amber-500' : 'bg-gray-400')"
                                                ></span>
                                                <span class="text-[11px] text-gray-400" x-text="node.status === 'active' ? 'Active' : (node.status === 'on_leave' ? 'On Leave' : 'Inactive')"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1.5 mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <button
                                            @click.stop="node.collapsed = !node.collapsed"
                                            class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            x-show="node.children && node.children.length > 0"
                                        >
                                            <svg class="fill-current transition-transform duration-200" :class="node.collapsed ? '' : 'rotate-90'" width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.47 4.47a.75.75 0 011.06 0l7 7a.75.75 0 010 1.06l-7 7a.75.75 0 01-1.06-1.06L14.94 12 8.47 5.53a.75.75 0 010-1.06z"/>
                                            </svg>
                                            <span x-text="node.children.length"></span>
                                        </button>
                                        <button
                                            @click.stop="$parent.triggerTrace(node.id)"
                                            class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors"
                                        >
                                            <svg class="fill-current" width="10" height="10" viewBox="0 0 24 24" fill="none">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                            </svg>
                                            Trace
                                        </button>
                                    </div>
                                </div>

                                <!-- Tree Connector Line (down from node to children) -->
                                <div x-show="node.children && node.children.length > 0 && !node.collapsed" class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>

                                <!-- Children Container -->
                                <div x-show="node.children && node.children.length > 0 && !node.collapsed" class="relative">
                                    <!-- Horizontal connector bar -->
                                    <div class="absolute top-0 left-1/2 -translate-x-1/2 h-px bg-gray-300 dark:bg-gray-600" :style="'width: ' + ((node.children.length - 1) * 16) + 'rem; max-width: calc(100% - 8rem);'"></div>
                                    <!-- Children Row -->
                                    <div class="flex items-start gap-4 pt-6">
                                        <template x-for="child in node.children" :key="child.id">
                                            <div>
                                                <!-- Recursive Child Node (simplified - same structure) -->
                                                <div class="flex flex-col items-center">
                                                    <div
                                                        class="w-56 rounded-xl border shadow-xs bg-white dark:bg-gray-800 p-3.5 transition-all duration-200 cursor-grab active:cursor-grabbing"
                                                        :class="{
                                                            'border-brand-500 ring-2 ring-brand-200 dark:ring-brand-800': $data.isTracing(child.id),
                                                            'border-yellow-400 ring-2 ring-yellow-200 dark:ring-yellow-800': searchQuery && searchQuery.trim() !== '' && $data.isSearchMatch(child),
                                                            'border-gray-200 dark:border-gray-700': !$data.isTracing(child.id) && (!searchQuery || searchQuery.trim() === '' || !$data.isSearchMatch(child)),
                                                            'opacity-30': searchQuery && searchQuery.trim() !== '' && !$data.isSearchMatch(child),
                                                        }"
                                                        :draggable="true"
                                                        @dragstart.stop="$data.dragStart($event, child.id)"
                                                        @dragover.prevent="$data.dragOver($event)"
                                                        @drop.stop="$data.drop($event, child.id)"
                                                    >
                                                        <div class="flex items-start gap-3">
                                                            <div
                                                                class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                                                                :class="child.status === 'active' ? 'bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                                                x-text="child.initials"
                                                            ></div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" x-text="child.name"></p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="child.job_title"></p>
                                                                <div class="flex items-center gap-2 mt-1.5">
                                                                    <span
                                                                        class="inline-flex h-1.5 w-1.5 rounded-full"
                                                                        :class="child.status === 'active' ? 'bg-green-500' : (child.status === 'on_leave' ? 'bg-amber-500' : 'bg-gray-400')"
                                                                    ></span>
                                                                    <span class="text-[11px] text-gray-400" x-text="child.status === 'active' ? 'Active' : (child.status === 'on_leave' ? 'On Leave' : 'Inactive')"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 mt-3 pt-2 border-t border-gray-100 dark:border-gray-700" x-show="child.children && child.children.length > 0">
                                                            <button
                                                                @click.stop="child.collapsed = !child.collapsed"
                                                                class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                            >
                                                                <svg class="fill-current transition-transform duration-200" :class="child.collapsed ? '' : 'rotate-90'" width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.47 4.47a.75.75 0 011.06 0l7 7a.75.75 0 010 1.06l-7 7a.75.75 0 01-1.06-1.06L14.94 12 8.47 5.53a.75.75 0 010-1.06z"/>
                                                                </svg>
                                                                <span x-text="child.children.length"></span>
                                                            </button>
                                                            <button
                                                                @click.stop="$data.triggerTrace(child.id)"
                                                                class="flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors"
                                                            >
                                                                <svg class="fill-current" width="10" height="10" viewBox="0 0 24 24" fill="none">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                                                </svg>
                                                                Trace
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Trace Panel (Slide-in info about selected trace) -->
    <div x-show="traceIds.length > 0" x-cloak class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                Reporting Chain: <span x-text="traceChain.map(u => u.name).join(' → ')"></span>
            </h4>
            <button @click="clearTrace()" class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Org Chart Alpine.js Logic -->
<script>
function orgChart() {
    return {
        allUsers: [],
        rootNodes: [],
        searchQuery: '',
        traceIds: [],
        traceChain: [],
        loading: true,

        async init() {
            try {
                const resp = await fetch('{{ route("admin.org-chart.tree") }}');
                const data = await resp.json();
                this.allUsers = data.users || [];
                this.rootNodes = this.buildTree(null);
            } catch (e) {
                console.error('Failed to load org chart:', e);
            } finally {
                this.loading = false;
            }
        },

        buildTree(managerId) {
            return this.allUsers
                .filter(u => u.manager_id === managerId)
                .map(u => ({
                    ...u,
                    collapsed: false,
                    children: this.buildTree(u.id),
                }));
        },

        getChildren(userId) {
            return this.allUsers
                .filter(u => u.manager_id === userId)
                .map(u => ({
                    ...u,
                    collapsed: false,
                    children: this.getChildren(u.id),
                }));
        },

        expandAll() {
            this.setAllNodesCollapsed(this.rootNodes, false);
        },

        collapseAll() {
            this.setAllNodesCollapsed(this.rootNodes, true);
        },

        setAllNodesCollapsed(nodes, collapsed) {
            nodes.forEach(n => {
                n.collapsed = collapsed;
                if (n.children) {
                    this.setAllNodesCollapsed(n.children, collapsed);
                }
            });
        },

        async triggerTrace(userId) {
            try {
                const resp = await fetch(`{{ url('/admin/org-chart/trace') }}/${userId}`);
                const data = await resp.json();
                this.traceChain = data.chain || [];
                this.traceIds = this.traceChain.map(u => u.id);
            } catch (e) {
                console.error('Failed to trace:', e);
            }
        },

        clearTrace() {
            this.traceIds = [];
            this.traceChain = [];
        },

        isTracing(userId) {
            return this.traceIds.includes(userId);
        },

        isSearchMatch(user) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const q = this.searchQuery.toLowerCase();
            return user.name.toLowerCase().includes(q) ||
                   (user.job_title && user.job_title.toLowerCase().includes(q));
        },

        hasSearchMatches(node) {
            if (this.isSearchMatch(node)) return true;
            if (node.children && node.children.length > 0) {
                return node.children.some(c => this.hasSearchMatches(c));
            }
            return false;
        },

        dragStart(event, userId) {
            event.dataTransfer.setData('text/plain', userId.toString());
            event.dataTransfer.effectAllowed = 'move';
        },

        dragOver(event) {
            event.dataTransfer.dropEffect = 'move';
        },

        async drop(event, targetUserId) {
            const draggedUserId = parseInt(event.dataTransfer.getData('text/plain'));
            if (draggedUserId && draggedUserId !== targetUserId) {
                await this.reassignManager(draggedUserId, targetUserId);
            }
        },

        async reassignManager(userId, newManagerId) {
            try {
                const resp = await fetch('{{ route("admin.org-chart.reassign") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_id: userId, new_manager_id: newManagerId }),
                });
                const data = await resp.json();
                if (data.success) {
                    // Reload the tree
                    const treeResp = await fetch('{{ route("admin.org-chart.tree") }}');
                    const treeData = await treeResp.json();
                    this.allUsers = treeData.users || [];
                    this.rootNodes = this.buildTree(null);
                }
            } catch (e) {
                console.error('Failed to reassign:', e);
            }
        }
    };
}
</script>
@endsection
