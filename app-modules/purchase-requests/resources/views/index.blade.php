@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Purchase Requests</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Manage procurement purchase requests and approval pipelines</p>
    </div>

    @livewire('purchase-requests.purchase-request-table')
</div>
@endsection
