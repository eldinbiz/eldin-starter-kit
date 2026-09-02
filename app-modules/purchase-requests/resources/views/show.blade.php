@extends('layouts.app')

@section('title', $purchaseRequest->title)

@section('content')
<div x-data="{ pageName: 'Purchase Request Detail' }">
    @include('partials.breadcrumb')
</div>

<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session('error'))
        <x-ui::alert type="error">
            {{ session('error') }}
        </x-ui::alert>
    @endif

    @if (session('success'))
        <x-ui::alert type="success">
            {{ session('success') }}
        </x-ui::alert>
    @endif

    {{-- PR Header Card --}}
    <x-ui::card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <x-ui::badge variant="brand">
                        {{ $purchaseRequest->pr_number }}
                    </x-ui::badge>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $purchaseRequest->title }}
                    </h2>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Requested by <span class="font-medium text-gray-800 dark:text-gray-200">{{ $purchaseRequest->requester?->name }}</span> on @formatDate($purchaseRequest->created_at)
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if ($purchaseRequest->status === 'draft')
                    <x-ui::badge variant="neutral">Draft</x-ui::badge>
                @elseif ($purchaseRequest->status === 'pending_approval')
                    <x-ui::badge variant="warning">Pending Approval</x-ui::badge>
                @elseif ($purchaseRequest->status === 'approved')
                    <x-ui::badge variant="success">Approved</x-ui::badge>
                @elseif ($purchaseRequest->status === 'rejected')
                    <x-ui::badge variant="danger">Rejected</x-ui::badge>
                @elseif ($purchaseRequest->status === 'canceled')
                    <x-ui::badge variant="neutral">Canceled</x-ui::badge>
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
    </x-ui::card>

    {{-- Workflow Progress Table --}}
    @if ($latestRequest)
        <x-ui::card>
            <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Approval Workflow Status</h3>
            @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
        </x-ui::card>
    @endif
</div>
@endsection
