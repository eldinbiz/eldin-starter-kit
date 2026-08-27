@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div x-data="{ pageName: 'Audit Trail' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">System Audit Trail</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">Track change history, security provenance, and user activities across the platform.</p>
    </div>

    @livewire('admin.audit-trail.audit-trail-viewer')
</div>
@endsection
