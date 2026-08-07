@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div x-data="{ pageName: 'Audit Trail' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">System Audit Trail</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Track and inspect change history, provenance chains, and user activity.</p>
    </div>

    <div class="w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
        <iframe
            src="/audit-log"
            class="w-full h-[calc(100vh-250px)] min-h-[600px] border-0"
            title="Audit Trail Dashboard"
        ></iframe>
    </div>
</div>
@endsection
