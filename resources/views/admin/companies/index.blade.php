@extends('layouts.app')

@section('title', 'Companies')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Companies</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all companies in the system.</p>
        </div>
        <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
            <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Add Company
        </a>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Slug</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Parent</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Users</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    <tr class="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">
                            {{ $company->name }}
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $company->slug }}</td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                            {{ $company->parent?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $company->users_count }}</td>
                        <td class="px-6 py-4">
                            @if($company->is_active)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-full">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded-full">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.companies.edit', $company) }}" class="text-gray-400 hover:text-brand-500 transition-colors">
                                    <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" onsubmit="return confirm('Delete this company?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-error-500 transition-colors">
                                        <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                            No companies found. Create your first company to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
