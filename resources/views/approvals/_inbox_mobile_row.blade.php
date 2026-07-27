<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center gap-3 mb-3">
        <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs shrink-0">
            @if ($step->request->approvable instanceof \App\Contracts\Approvable)
                {{ strtoupper(substr($step->request->approvable->getApprovalTitle(), 0, 2)) }}
            @else
                PR{{ $step->request->id }}
            @endif
        </span>
        <div class="min-w-0">
            <a href="{{ route('approvals.show', $step->request->id) }}" class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate block hover:text-brand-500">
                @if ($step->request->approvable instanceof \App\Contracts\Approvable)
                    {{ $step->request->approvable->getApprovalTitle() }}
                @else
                    Approval Request #{{ $step->request->id }}
                @endif
            </a>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">From {{ $step->request->requestor->name }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-3">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $step->display_label }} ({{ $step->group_name }})
        </p>
    </div>

    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">Submitted {{ $step->request->submitted_at?->format('M d, Y') }}</span>
        <a href="{{ route('approvals.show', $step->request->id) }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
            Review
        </a>
    </div>
</div>
