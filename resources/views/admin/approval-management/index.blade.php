@extends('layouts.app')

@section('title', 'Manage Approvals')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Manage Approvals</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Company-wide approval oversight. Delegate or skip pending steps as needed.</p>
    </div>

    <x-data-table :datatable="$dataTable" />
</div>
@endsection
