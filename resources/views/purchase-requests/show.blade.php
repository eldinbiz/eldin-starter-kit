@extends('layouts.app')

@section('title', $purchaseRequest->title)

@section('content')
<div x-data="{ pageName: 'Purchase Request Detail' }">
    @include('partials.breadcrumb')
</div>

<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session('error'))
        <div class="rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    {{-- PR Header Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        {{ $purchaseRequest->pr_number }}
                    </span>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $purchaseRequest->title }}
                    </h2>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Requested by <span class="font-medium text-gray-800 dark:text-gray-200">{{ $purchaseRequest->requester?->name }}</span> on {{ $purchaseRequest->created_at->format('F d, Y') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if ($purchaseRequest->status === 'draft')
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">Draft</span>
                @elseif ($purchaseRequest->status === 'pending_approval')
                    <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Pending Approval</span>
                @elseif ($purchaseRequest->status === 'approved')
                    <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">Approved</span>
                @elseif ($purchaseRequest->status === 'rejected')
                    <span class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">Rejected</span>
                @elseif ($purchaseRequest->status === 'canceled')
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">Canceled</span>
                @endif

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

        {{-- Details Grid --}}
        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-3 dark:border-gray-800">
            <div>
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Department</span>
                <p class="mt-1 font-semibold text-gray-800 dark:text-gray-200">{{ $purchaseRequest->department }}</p>
            </div>
            <div>
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Total Amount</span>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">${{ number_format((float) $purchaseRequest->total_amount, 2) }}</p>
            </div>
            <div>
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Company Context</span>
                <p class="mt-1 font-semibold text-gray-800 dark:text-gray-200">{{ $purchaseRequest->company?->name }}</p>
            </div>
        </div>

        @if ($purchaseRequest->description)
            <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-800">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Description / Rationale</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $purchaseRequest->description }}</p>
            </div>
        @endif
    </div>

    {{-- Workflow Progress Table --}}
    @if ($latestRequest)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Approval Workflow Status</h3>
            @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
        </div>
    @endif
</div>
@endsection
