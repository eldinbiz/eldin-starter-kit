@extends('layouts.app')

@section('title', 'My Approval Requests')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">My Requests</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Approval requests you have submitted.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if ($approvalRequests->isEmpty())
        <div class="py-10 text-center">
            <p class="text-gray-500 dark:text-gray-400">You haven't submitted any approval requests yet.</p>
        </div>
    @else
        <div class="w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-gray-100 border-y dark:border-gray-800">
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Request</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Current Step</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Submitted</p>
                        </th>
                        <th class="py-3 text-right">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($approvalRequests as $request)
                        <tr>
                            <td class="py-3.5">
                                <a href="{{ route('approvals.show', $request->id) }}" class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                    @if ($request->approvable instanceof \App\Contracts\Approvable)
                                        {{ $request->approvable->getApprovalTitle() }}
                                    @else
                                        Approval Request #{{ $request->id }}
                                    @endif
                                </a>
                            </td>
                            <td class="py-3.5">
                                @if ($request->status === \App\Enums\ApprovalStatus::Pending)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                                        Pending
                                    </span>
                                @elseif ($request->status === \App\Enums\ApprovalStatus::Approved)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
                                        Approved
                                    </span>
                                @elseif ($request->status === \App\Enums\ApprovalStatus::Rejected)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        Canceled
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5">
                                @php
                                    $pendingSteps = $request->steps->where('state', \App\Enums\StepState::Pending);
                                    $firstPending = $pendingSteps->first();
                                @endphp
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    @if ($firstPending)
                                        {{ $firstPending->display_label }} ({{ $firstPending->group_name }})
                                    @elseif ($request->status === \App\Enums\ApprovalStatus::Approved)
                                        All approved
                                    @else
                                        &mdash;
                                    @endif
                                </p>
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $request->submitted_at?->format('M d, Y') }}</p>
                            </td>
                            <td class="py-3.5 text-right">
                                <a href="{{ route('approvals.show', $request->id) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $approvalRequests->links() }}
        </div>
    @endif
</div>
@endsection
