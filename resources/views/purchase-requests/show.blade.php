@extends('layouts.app')

@section('title', 'Purchase Request #' . $purchaseRequest->id)

@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $purchaseRequest->title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Requested by {{ $purchaseRequest->requester->name }}
                &middot; {{ $purchaseRequest->created_at->format('M d, Y') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if ($purchaseRequest->status === 'draft')
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Draft</span>
            @elseif ($purchaseRequest->status === 'pending_approval')
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-3 py-1 text-sm font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">Pending Approval</span>
            @elseif ($purchaseRequest->status === 'approved')
                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-sm font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">Approved</span>
            @elseif ($purchaseRequest->status === 'rejected')
                <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-3 py-1 text-sm font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
            @endif
        </div>
    </div>

    {{-- Details --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-xs text-gray-400 dark:text-gray-500">Amount</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white/90">${{ number_format($purchaseRequest->amount, 2) }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 dark:text-gray-500">Status</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}</p>
        </div>
        @if ($purchaseRequest->description)
            <div class="col-span-2">
                <p class="text-xs text-gray-400 dark:text-gray-500">Description</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $purchaseRequest->description }}</p>
            </div>
        @endif
    </div>

    {{-- Approval Progress --}}
    @if ($latestRequest)
        <div class="mb-6">
            @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
            <div class="mt-1 text-right">
                <a href="{{ route('approvals.show', $latestRequest->id) }}"
                    class="text-sm font-medium text-brand-500 hover:text-brand-600">
                    View Full Approval Progress →
                </a>
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
        <a href="{{ route('purchase-requests.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            Back
        </a>

        @if ($purchaseRequest->status === 'draft' && $purchaseRequest->requester_id === auth()->id())
            @include('approvals._submit-modal', [
                'approvable' => $purchaseRequest,
                'submitRoute' => route('purchase-requests.submit-for-approval', $purchaseRequest->id),
                'templates' => $templates,
                'previewRoute' => route('purchase-requests.preview-workflow', $purchaseRequest->id),
            ])
        @endif
    </div>
</div>
@endsection
