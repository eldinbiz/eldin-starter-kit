<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center gap-3 mb-3">
        <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs shrink-0">
            @if ($request->approvable instanceof \App\Contracts\Approvable)
                {{ strtoupper(substr($request->approvable->getApprovalTitle(), 0, 2)) }}
            @else
                PR{{ $request->id }}
            @endif
        </span>
        <div class="min-w-0">
            <a href="{{ route('approvals.show', $request->id) }}" class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate block hover:text-brand-500">
                @if ($request->approvable instanceof \App\Contracts\Approvable)
                    {{ $request->approvable->getApprovalTitle() }}
                @else
                    Approval Request #{{ $request->id }}
                @endif
            </a>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Submitted by you</p>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-3">
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
    </div>

    @php
        $pendingSteps = $request->steps->where('state', \App\Enums\StepState::Pending);
        $firstPending = $pendingSteps->first();
    @endphp
    @if ($firstPending)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            Current step: {{ $firstPending->display_label }} ({{ $firstPending->group_name }})
        </p>
    @endif

    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">Submitted @formatDate($request->submitted_at)</span>
        <a href="{{ route('approvals.show', $request->id) }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            View
        </a>
    </div>
</div>
