@extends('layouts.auth')

@section('title', '403 Forbidden')

@section('content')
<div class="text-center bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-theme-lg border border-gray-100 dark:border-gray-800 p-8 sm:p-10">
    <div class="mb-6 flex justify-center">
        {{-- Shield Warning Icon --}}
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 text-error-500">
            <svg class="fill-current" width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L4 5V11C4 16.52 7.38 20.62 12 22C16.62 20.62 20 16.52 20 11V5L12 2ZM18 11C18 15.47 15.34 18.92 12 20.06C8.66 18.92 6 15.47 6 11V6.3L12 4.05L18 6.3V11ZM11 7H13V13H11V7ZM11 15H13V17H11V15Z" />
            </svg>
        </span>
    </div>

    <h1 class="mb-2 text-title-md font-bold text-gray-800 dark:text-white">
        Access Denied
    </h1>
    
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
        You do not have permission to access this resource. If you possess another role with sufficient access, you can switch it using the selector on the dashboard.
    </p>

    <div>
        <a
            href="{{ url('/') }}"
            class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
        >
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
