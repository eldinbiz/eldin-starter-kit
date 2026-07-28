@php use App\Models\PurchaseRequest; @endphp
@extends('layouts.app')
@section('title', $purchaseRequest->getApprovalTitle())
@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">@include('partials.breadcrumb')</div>

{{-- Flash Messages --}}
@if (session('error'))
    <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
        {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
        {{ session('success') }}
    </div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Purchase Request #{{ $purchaseRequest->id }}: {{ $purchaseRequest->title }}
            </h3>
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span>Requested by <strong>{{ $purchaseRequest->requester->name }}</strong></span>
                <span>&middot;</span>
                <span>{{ $purchaseRequest->created_at->format('M d, Y') }}</span>
            </div>
        </div>
        <div>
            @if ($purchaseRequest->status === 'draft')
                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Draft</span>
            @elseif ($purchaseRequest->status === 'pending_approval')
                <span class="inline-flex rounded-full bg-warning-100 px-3 py-1 text-sm font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Pending Approval</span>
            @elseif ($purchaseRequest->status === 'approved')
                <span class="inline-flex rounded-full bg-success-100 px-3 py-1 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">Approved</span>
            @elseif ($purchaseRequest->status === 'rejected')
                <span class="inline-flex rounded-full bg-error-100 px-3 py-1 text-sm font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
            @elseif ($purchaseRequest->status === 'canceled')
                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Canceled</span>
            @endif
        </div>
    </div>

    {{-- Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Title</p>
            <p class="text-sm text-gray-800 dark:text-white/90 font-medium">{{ $purchaseRequest->title }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Amount</p>
            <p class="text-sm text-gray-800 dark:text-white/90 font-medium">{{ number_format((float) $purchaseRequest->amount, 2) }}</p>
        </div>
        @if ($purchaseRequest->description)
            <div class="rounded-lg border border-gray-200 p-4 md:col-span-2 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Goods to Purchase</p>
                <p class="text-sm text-gray-800 dark:text-white/90 whitespace-pre-wrap">{{ $purchaseRequest->description }}</p>
            </div>
        @endif
    </div>

    {{-- Approval Progress --}}
    @if ($latestRequest)
        @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
    @endif

    {{-- Submit for Approval (only if draft and owned by current user) --}}
    @if ($purchaseRequest->status === 'draft' && $purchaseRequest->requester_id === auth()->id())
        <div class="border-t border-gray-200 pt-4 mt-6 dark:border-gray-700">
            @include('approvals._submit-modal', [
                'approvable' => $purchaseRequest,
                'submitRoute' => route('purchase-requests.submit-for-approval', $purchaseRequest->id),
                'templates' => $templates,
                'previewRoute' => route('purchase-requests.preview-workflow', $purchaseRequest->id),
            ])
        </div>
    @endif

    {{-- Back link --}}
    <div class="border-t border-gray-200 pt-4 mt-6 dark:border-gray-700">
        <a href="{{ route('purchase-requests.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            &larr; Back to Purchase Requests
        </a>
    </div>
</div>
@endsection
