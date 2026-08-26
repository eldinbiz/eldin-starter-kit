@extends('layouts.app')

@section('title', $title ?? 'System Configuration')

@section('content')
<div x-data="{ pageName: 'System Configuration' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            {{ $heading ?? 'System Configuration & Settings' }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $subheading ?? 'Manage platform-wide defaults, mail transport, email templates, custom variables, and tenant preferences.' }}
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Nav Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Settings Tabs">
            @can('manage-system-settings')
            <a
                href="{{ route('admin.settings.general') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.general*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                ⚙️ General & Branding
            </a>

            <a
                href="{{ route('admin.settings.mail') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.mail*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                ✉️ Mail & SMTP
            </a>

            <a
                href="{{ route('admin.settings.email-templates.index') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.email-templates*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                📄 Email Templates
            </a>

            <a
                href="{{ route('admin.settings.custom-variables') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.custom-variables*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                🎛️ Custom Variables
            </a>
            @endcan

            @can('manage-platform')
            <a
                href="{{ route('admin.settings.features') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.features*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                🧩 Feature Toggles
            </a>
            @endcan

            @can('manage-company-settings')
            <a
                href="{{ route('admin.settings.company') }}"
                wire:navigate
                wire:navigate.hover
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors {{ Request::routeIs('admin.settings.company*') ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                🏢 Company Preferences
            </a>
            @endcan
        </nav>
    </div>

    @yield('settings-content')
</div>
@endsection
