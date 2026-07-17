@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                All Users
                @if($isPlatformAdmin)
                    <span class="font-normal text-gray-500"> &mdash; Platform View</span>
                @elseif(!empty($activeCompany))
                    <span class="font-normal text-gray-500"> &mdash; {{ $activeCompany->name }}</span>
                @endif
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $isPlatformAdmin ? 'Manage users across all companies.' : 'Manage administrators, managers, and regular users.' }}
            </p>
        </div>
        <div>
            <a
                href="{{ route('admin.users.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 3.33331C10.4602 3.33331 10.8333 3.70641 10.8333 4.16665V9.16665H15.8333C16.2936 9.16665 16.6667 9.53974 16.6667 9.99998C16.6667 10.4602 16.2936 10.8333 15.8333 10.8333H10.8333V15.8333C10.8333 16.2936 10.4602 16.6666 10 16.6666C9.53976 16.6666 9.16667 16.2936 9.16667 15.8333V10.8333H4.16667C3.70643 10.8333 3.33333 10.4602 3.33333 9.99998C3.33333 9.53974 3.70643 9.16665 4.16667 9.16665H9.16667V4.16665C9.16667 3.70641 9.53976 3.33331 10 3.33331Z" fill="currentColor"/>
                </svg>
                Create User
            </a>
        </div>
    </div>

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

    <!-- Advanced Filters -->
    <div x-data="{ filtersOpen: {{ $search || ! empty($filterRoles) || $filterCompanyId || $filterDateFrom || $filterDateTo ? 'true' : 'false' }} }" class="mb-6">
        <button
            @click="filtersOpen = ! filtersOpen"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
        >
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M2.29004 5.90393H17.7067" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17.7075 14.0961H2.29085" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            Advanced Filters
            <svg class="fill-current transition-transform" :class="filtersOpen ? 'rotate-180' : ''" width="16" height="16" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
        </button>

        <div x-show="filtersOpen" x-transition class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
            <form method="GET" action="{{ route('admin.users.index') }}">
                {{-- Preserve sort/direction so filters don't reset sorting --}}
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Search --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Name or Email</label>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search users..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        >
                    </div>

                    {{-- Role Filter --}}
                    <div x-data="{ open: false }" class="relative">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Role</label>
                        <button
                            type="button"
                            @click="open = ! open"
                            class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-left text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <span x-text="open ? 'Select roles...' : '{{ !empty($filterRoles) ? count($filterRoles).' selected' : 'All roles' }}'"></span>
                        </button>
                        <div x-show="open" @click.outside="open = false" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                            @foreach($allRoles as $role)
                                <label class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                    <input type="checkbox" name="filter_roles[]" value="{{ $role->name }}" {{ in_array($role->name, $filterRoles) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                    <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($role->name) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Company Filter (only when not scoped to active company) --}}
                    @if($allCompanies->isNotEmpty())
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Company</label>
                            <select
                                name="filter_company_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            >
                                <option value="">All companies</option>
                                @foreach($allCompanies as $company)
                                    <option value="{{ $company->id }}" {{ $filterCompanyId === $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Date From --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Joined From</label>
                        <input
                            type="date"
                            name="filter_date_from"
                            value="{{ $filterDateFrom }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Joined To</label>
                        <input
                            type="date"
                            name="filter_date_to"
                            value="{{ $filterDateTo }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                    >
                        Apply Filters
                    </button>
                    <a
                        href="{{ route('admin.users.index', ['sort' => $sort, 'direction' => $direction]) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                    >
                        Clear All
                    </a>
                </div>
            </form>
        </div>

        {{-- Active Filter Chips --}}
        @php
            $hasActiveFilters = $search || ! empty($filterRoles) || $filterCompanyId || $filterDateFrom || $filterDateTo;
        @endphp
        @if($hasActiveFilters)
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Active filters:</span>
                @if($search)
                    <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                        "{{ $search }}" ✕
                    </a>
                @endif
                @foreach($filterRoles as $fr)
                    @php
                        $remainingRoles = array_values(array_diff($filterRoles, [$fr]));
                        $url = request()->fullUrlWithQuery(['filter_roles' => $remainingRoles ?: null, 'page' => null]);
                    @endphp
                    <a href="{{ $url }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                        {{ ucfirst($fr) }} ✕
                    </a>
                @endforeach
                @if($filterCompanyId)
                    @php $filterCompany = $allCompanies->firstWhere('id', $filterCompanyId); @endphp
                    <a href="{{ request()->fullUrlWithQuery(['filter_company_id' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                        {{ $filterCompany?->name ?? 'Company #'.$filterCompanyId }} ✕
                    </a>
                @endif
                @if($filterDateFrom || $filterDateTo)
                    <a href="{{ request()->fullUrlWithQuery(['filter_date_from' => null, 'filter_date_to' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                        {{ $filterDateFrom ?: '...' }} → {{ $filterDateTo ?: '...' }} ✕
                    </a>
                @endif
                <a href="{{ route('admin.users.index', ['sort' => $sort, 'direction' => $direction]) }}" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline">
                    Clear all filters
                </a>
            </div>
        @endif
    </div>

    @if($users->isEmpty())
        <div class="py-10 text-center">
            <p class="text-gray-500 dark:text-gray-400">No users match your filters.</p>
            <a href="{{ route('admin.users.index') }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
                Clear filters
            </a>
        </div>
    @else
        <!-- Desktop Table View -->
        <div class="hidden md:block w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-gray-100 border-y dark:border-gray-800">
                        <th class="py-3 text-left">
                            <a
                                href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}"
                                class="inline-flex items-center gap-1 font-medium text-gray-500 text-theme-xs dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                User
                                @if($sort === 'name')
                                    @if($direction === 'asc')
                                        <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 8l-6 6h12z"/></svg>
                                    @else
                                        <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
                                    @endif
                                @endif
                            </a>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Company & Role</p>
                        </th>
                        <th class="py-3 text-left">
                            <a
                                href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}"
                                class="inline-flex items-center gap-1 font-medium text-gray-500 text-theme-xs dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                Joined Date
                                @if($sort === 'created_at')
                                    @if($direction === 'asc')
                                        <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 8l-6 6h12z"/></svg>
                                    @else
                                        <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
                                    @endif
                                @endif
                            </a>
                        </th>
                        <th class="py-3 text-right">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($users as $user)
                        <tr>
                            <td class="py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5">
                                @if(isset($userCompanyRoleMap[$user->id]) && $userCompanyRoleMap[$user->id]->isNotEmpty())
                                    @foreach($userCompanyRoleMap[$user->id] as $companyData)
                                        <div class="{{ !$loop->first ? 'mt-2' : '' }}">
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($companyData['roles'] as $role)
                                                    @if($role->name === 'admin')
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#f2f7ff] px-2 py-0.5 text-xs font-medium text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]">
                                                            Admin
                                                        </span>
                                                    @elseif($role->name === 'manager')
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500">
                                                            Manager
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                            User
                                                        </span>
                                                    @endif
                                                @endforeach
                                                @if($companyData['company_name'])
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                                        {{ $companyData['company_name'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-400 text-theme-sm dark:text-gray-500">&mdash;</span>
                                @endif
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $user->created_at->format('M d, Y') }}</p>
                            </td>
                            <td class="py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.users.edit', $user->id) }}"
                                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        <!-- Edit Icon -->
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                        </svg>
                                    </a>

                                    @if($user->id !== Auth::id())
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                                            >
                                                <!-- Trash Icon -->
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="block md:hidden">
            <div class="grid grid-cols-1 gap-4">
                @foreach($users as $user)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <!-- User Info -->
                        <div class="flex items-center gap-3 mb-3">
                            <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                            </div>
                        </div>

                        <!-- Roles & Companies -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            @if(isset($userCompanyRoleMap[$user->id]) && $userCompanyRoleMap[$user->id]->isNotEmpty())
                                @foreach($userCompanyRoleMap[$user->id] as $companyData)
                                    @foreach($companyData['roles'] as $role)
                                        @if($role->name === 'admin')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-[#f2f7ff] px-2 py-0.5 text-xs font-medium text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]">
                                                Admin
                                            </span>
                                        @elseif($role->name === 'manager')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500">
                                                Manager
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                User
                                            </span>
                                        @endif
                                    @endforeach
                                    @if($companyData['company_name'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                            {{ $companyData['company_name'] }}
                                        </span>
                                    @endif
                                @endforeach
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                            @endif
                        </div>

                        <!-- Footer: Joined Date + Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Joined {{ $user->created_at->format('M d, Y') }}</span>
                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('admin.users.edit', $user->id) }}"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                                >
                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </a>
                                @if($user->id !== Auth::id())
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <span>Show</span>
                <select
                    onchange="window.location.href = this.value"
                    class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    @foreach([5, 10, 20, 50, 100] as $option)
                        <option
                            value="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => null]) }}"
                            {{ (int) request('per_page', 5) === $option ? 'selected' : '' }}
                        >{{ $option }}</option>
                    @endforeach
                </select>
                <span>entries</span>
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
