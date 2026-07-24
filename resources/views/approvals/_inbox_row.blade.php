<tr>
    <td class="py-3.5">
        <a href="{{ route('approvals.show', $step->request->id) }}" class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400">
            @if ($step->request->approvable instanceof \App\Contracts\Approvable)
                {{ $step->request->approvable->getApprovalTitle() }}
            @else
                Approval Request #{{ $step->request->id }}
            @endif
        </a>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->request->requestor->name }}</p>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->display_label }} ({{ $step->group_name }})</p>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->request->submitted_at?->format('M d, Y') }}</p>
    </td>
    <td class="py-3.5 text-right">
        <a href="{{ route('approvals.show', $step->request->id) }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
            Review
        </a>
    </td>
</tr>
