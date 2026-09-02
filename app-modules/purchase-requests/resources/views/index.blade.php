@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">
    @include('partials.breadcrumb')
</div>

<x-ui::card>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Purchase Requests</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Manage procurement purchase requests and approval pipelines</p>
    </div>

    @livewire('purchase-requests.purchase-request-table')
</x-ui::card>
@endsection
