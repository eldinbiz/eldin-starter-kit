{{-- shadcn Sidebar Navigation --}}
<aside
    :class="{
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full lg:translate-x-0': !mobileSidebarOpen,
        'lg:w-64': !sidebarCollapsed,
        'lg:w-18': sidebarCollapsed
    }"
    class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-zinc-200 bg-white duration-200 ease-in-out dark:border-zinc-800 dark:bg-zinc-950 lg:static transition-[width,transform]"
>
    {{-- Sidebar Top: Brand & Workspace --}}
    <div class="flex h-14 items-center justify-between border-b border-zinc-200 px-3.5 dark:border-zinc-800">
        <a href="{{ url('/') }}" wire:navigate.hover class="flex items-center gap-2.5 overflow-hidden">
            @php $sidebarLogo = $activeCompanyLogo ?: $appLogo; @endphp
            @if (!empty($sidebarLogo))
                <img src="{{ $sidebarLogo }}" alt="{{ config('app.name', 'Scaffolding App') }}" class="size-7 shrink-0 object-contain" />
            @else
                <div class="flex size-7 shrink-0 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                    </svg>
                </div>
            @endif

            <div x-show="!sidebarCollapsed" x-cloak class="flex flex-col overflow-hidden text-left">
                <span class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ config('app.name', 'Scaffolding') }}
                </span>
                @if(isset($activeCompany) && $activeCompany)
                    <span class="truncate text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                        {{ $activeCompany->name }}
                    </span>
                @endif
            </div>
        </a>

        {{-- Mobile Close Button --}}
        <button
            @click="mobileSidebarOpen = false"
            class="flex size-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 lg:hidden"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation Links Container --}}
    <div class="flex-1 overflow-y-auto px-3 py-3 custom-scrollbar">
        <nav class="space-y-6">
            {{-- Section 1: Main Overview --}}
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed" x-cloak class="px-2.5 py-1 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    Overview
                </div>

                {{-- Dashboard --}}
                <a
                    href="{{ url('/') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('/') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Dashboard"
                >
                    <svg class="size-4 shrink-0 {{ Request::is('/') ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 group-hover:text-zinc-900 dark:text-zinc-400 dark:group-hover:text-zinc-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Dashboard</span>
                </a>

                {{-- Purchase Requests --}}
                <a
                    href="{{ route('purchase-requests.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('purchase-requests*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Purchase Requests"
                >
                    <svg class="size-4 shrink-0 {{ Request::is('purchase-requests*') ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 group-hover:text-zinc-900 dark:text-zinc-400 dark:group-hover:text-zinc-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Purchase Requests</span>
                </a>
            </div>

            {{-- Section 2: Approvals --}}
            @feature('approvals')
            @canany(['view-approval-inbox', 'view-approval-history', 'view-approval-requests', 'manage-approvals', 'manage-approval-workflows'])
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed" x-cloak class="px-2.5 py-1 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    Approvals
                </div>

                @can('view-approval-inbox')
                <a
                    href="{{ route('approvals.inbox') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('approvals/inbox*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Inbox"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Inbox</span>
                </a>
                @endcan

                @can('view-approval-history')
                <a
                    href="{{ route('approvals.history') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('approvals/history*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="History"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">History</span>
                </a>
                @endcan

                @can('view-approval-requests')
                <a
                    href="{{ route('approvals.requests') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('approvals/requests*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="My Requests"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">My Requests</span>
                </a>
                @endcan

                @can('manage-approvals')
                <a
                    href="{{ route('admin.approval-management.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/approval-management*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Manage Approvals"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Management</span>
                </a>
                @endcan

                @can('manage-approval-workflows')
                <a
                    href="{{ route('admin.approval-workflows.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/approval-workflows*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Workflow Templates"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="8" height="8" x="3" y="3" rx="2"/><path d="M7 11v4a2 2 0 0 0 2 2h4"/><rect width="8" height="8" x="13" y="13" rx="2"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Workflows</span>
                </a>
                @endcan
            </div>
            @endcanany
            @endfeature

            {{-- Section 3: Administration --}}
            @canany(['manage-users', 'manage-roles', 'manage-companies', 'view-companies', 'manage-org-chart', 'view-org-chart'])
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed" x-cloak class="px-2.5 py-1 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    Administration
                </div>

                @can('manage-users')
                <a
                    href="{{ route('admin.users.index') }}"
                    wire:navigate.hover
                    data-testid="nav-user-management"
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/users*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="User Management"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Users</span>
                </a>
                @endcan

                @can('manage-roles')
                <a
                    href="{{ route('admin.roles.index') }}"
                    wire:navigate.hover
                    data-testid="nav-user-roles"
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/roles*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="User Roles"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Roles</span>
                </a>
                @endcan

                @canany(['manage-companies', 'view-companies'])
                <a
                    href="{{ route('admin.companies.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/companies*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Companies"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Companies</span>
                </a>
                @endcanany

                @canany(['manage-org-chart', 'view-org-chart'])
                <a
                    href="{{ route('admin.org-chart.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/org-chart*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Org Structure"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="6" height="6" x="9" y="2" rx="1"/><rect width="6" height="6" x="2" y="16" rx="1"/><rect width="6" height="6" x="16" y="16" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Org Structure</span>
                </a>
                @endcanany
            </div>
            @endcanany

            {{-- Section 4: System --}}
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed" x-cloak class="px-2.5 py-1 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    System
                </div>

                @can('manage-system-settings')
                <a
                    href="{{ route('admin.settings.general') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ (Request::is('admin/settings*') && !Request::is('admin/settings/auth-providers*')) ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="System Settings"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Settings</span>
                </a>
                @endcan

                @can('manage-auth-providers')
                <a
                    href="{{ route('admin.settings.auth-providers.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/settings/auth-providers*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Auth Providers"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Auth (SSO)</span>
                </a>
                @endcan

                @feature('api_access')
                @can('manage-api-tokens')
                <a
                    href="{{ route('admin.api-tokens.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/api-tokens*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="API Tokens"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">API Tokens</span>
                </a>

                <a
                    href="{{ route('admin.api-docs.index') }}"
                    wire:navigate.hover
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('admin/api-docs*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="API Explorer"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">API Explorer</span>
                </a>
                @endcan
                @endfeature

                @feature('audit_logs')
                @can('view-audit-logs')
                <a
                    href="{{ route('audit-log.dashboard') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="sidebarCollapsed ? 'justify-center px-2' : 'px-2.5'"
                    class="group flex items-center gap-2.5 rounded-md py-1.5 text-sm transition-colors {{ Request::is('audit-log*') ? 'bg-zinc-100 text-zinc-900 font-semibold dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200' }}"
                    title="Audit Trail"
                >
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Audit Trail</span>
                </a>
                @endcan
                @endfeature
            </div>
        </nav>
    </div>

    {{-- Sidebar Footer: Collapse Toggle --}}
    <div class="hidden border-t border-zinc-200 p-2.5 dark:border-zinc-800 lg:block">
        <button
            @click="sidebarCollapsed = !sidebarCollapsed"
            :title="sidebarCollapsed ? 'Expand sidebar (⌘B)' : 'Collapse sidebar (⌘B)'"
            class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors"
            :class="sidebarCollapsed ? 'justify-center' : ''"
        >
            <svg class="size-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 15-3-3 3-3"/>
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak class="text-xs">Collapse Sidebar</span>
        </button>
    </div>
</aside>
