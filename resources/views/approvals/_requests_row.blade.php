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
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">@formatDate($request->submitted_at)</p>
    </td>
    <td class="py-3.5 text-right">
        <a href="{{ route('approvals.show', $request->id) }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            View
        </a>
    </td>
</tr>
