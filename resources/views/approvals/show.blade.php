@extends('layouts.app')

@section('title', 'Approval Request')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

@php
    $approvable = $approvalRequest->getApprovable();
    $user = auth()->user();
    $currentSteps = $approvalRequest->currentSteps();
    $isApprover = $currentSteps->contains('approver_id', $user->id);
    $isRequestor = $approvalRequest->requestor_id === $user->id;
@endphp

{{-- Request Info Bar --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                @if ($approvable)
                    {{ $approvable->getApprovalTitle() }}
                @else
                    Approval Request #{{ $approvalRequest->id }}
                @endif
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Requested by {{ $approvalRequest->requestor->name }}
                &middot; {{ $approvalRequest->submitted_at?->format('M d, Y h:i A') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if ($approvalRequest->status === \App\Enums\ApprovalStatus::Pending)
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-3 py-1 text-sm font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                    Pending
                </span>
            @elseif ($approvalRequest->status === \App\Enums\ApprovalStatus::Approved)
                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-sm font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
                    Approved
                </span>
            @elseif ($approvalRequest->status === \App\Enums\ApprovalStatus::Rejected)
                <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-3 py-1 text-sm font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                    Rejected
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    Canceled
                </span>
            @endif

            @if ($approvable)
                <a href="{{ $approvable->getApprovalUrl() }}" target="_blank"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                    View Full &nearr;
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Approvable Preview --}}
@if ($approvable && $preview = $approvable->getApprovalPreview())
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-3">{{ $approvable->getApprovalTitle() }}</h4>
        @include($preview['view'], $preview['data'])
    </div>
@endif

{{-- Workflow Progress --}}
<div class="mb-6">
    @include('approvals._workflow-progress-table', ['approvalRequest' => $approvalRequest])
</div>

{{-- Action Buttons --}}
@if ($approvalRequest->status === \App\Enums\ApprovalStatus::Pending)
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if ($isApprover)
                <p class="text-sm text-gray-500 dark:text-gray-400">You are the current approver.</p>
            @elseif ($isRequestor)
                <p class="text-sm text-gray-500 dark:text-gray-400">This is your request.</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($isApprover)
                {{-- Approve Modal --}}
                <div x-data="{ open: false, notes: '' }">
                    <button @click="open = true"
                        class="inline-flex items-center justify-center gap-1 rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-success-600 shadow-theme-xs">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Approve
                    </button>

                    <div x-show="open" x-cloak x-trap.noscroll="open"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                        <div @click.outside="open = false" class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 mb-3">Approve this step?</p>

                            <form action="{{ route('approvals.approve', $currentSteps->firstWhere('approver_id', $user->id)->id) }}" method="POST">
                                @csrf
                                <textarea name="notes" rows="2" x-model="notes" placeholder="Optional notes..."
                                    class="mb-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500"></textarea>

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600">
                                        Confirm
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Reject Modal --}}
                <div x-data="{ open: false, reason: '' }">
                    <button @click="open = true"
                        class="inline-flex items-center justify-center gap-1 rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-600 shadow-theme-xs">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Reject
                    </button>

                    <div x-show="open" x-cloak x-trap.noscroll="open"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                        <div @click.outside="open = false" class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 mb-3">Reject this request?</p>
                            <p class="text-xs text-error-600 dark:text-error-400 mb-3">This will reject the entire approval request.</p>

                            <form action="{{ route('approvals.reject', $currentSteps->firstWhere('approver_id', $user->id)->id) }}" method="POST">
                                @csrf
                                <textarea name="reason" rows="2" x-model="reason" placeholder="Reason for rejection..."
                                    class="mb-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500" required></textarea>

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="reason.trim() === ''"
                                        :class="reason.trim() === '' ? 'bg-gray-300 cursor-not-allowed dark:bg-gray-700' : 'bg-error-500 hover:bg-error-600 cursor-pointer'"
                                        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors">
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Delegate Modal --}}
                <div x-data="{ open: false, selectedUser: '' }">
                    <button @click="open = true"
                        class="inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                        Delegate
                    </button>

                    <div x-show="open" x-cloak x-trap.noscroll="open"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                        <div @click.outside="open = false" class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 mb-3">Delegate Approval</p>

                            <form action="{{ route('approvals.delegate', $currentSteps->firstWhere('approver_id', $user->id)->id) }}" method="POST">
                                @csrf
                                <select name="approver_id" x-model="selectedUser" required
                                    class="mb-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                    <option value="">Select user...</option>
                                    @foreach ($companyUsers as $companyUser)
                                        @if ($companyUser->id !== $user->id)
                                            <option value="{{ $companyUser->id }}">{{ $companyUser->name }}</option>
                                        @endif
                                    @endforeach
                                </select>

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="selectedUser === ''"
                                        :class="selectedUser === '' ? 'bg-gray-300 cursor-not-allowed dark:bg-gray-700' : 'bg-brand-500 hover:bg-brand-600 cursor-pointer'"
                                        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors">
                                        Delegate
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($isRequestor && $approvalRequest->isCancelable())
                <div x-data="{ confirming: false }">
                    <button @click="confirming = true"
                        class="inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                        Cancel Request
                    </button>

                    <div x-show="confirming" x-cloak x-trap.noscroll="confirming"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                        <div @click.outside="confirming = false" class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 mb-3">Cancel approval request?</p>
                            <p class="text-xs text-gray-500 mb-3">This will cancel the entire request. Pending approvers will be notified.</p>

                            <form action="{{ route('approvals.cancel', $approvalRequest->id) }}" method="POST">
                                @csrf

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="confirming = false"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        No, go back
                                    </button>
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600">
                                        Yes, cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('approvals.inbox') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                Back
            </a>
        </div>
    </div>
@endif
@endsection
