@props([
    'paginator',
])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
        {{-- Records Info --}}
        <div class="text-xs text-gray-500 dark:text-gray-400">
            @if ($paginator->total() > 0)
                Showing
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->total() }}</span>
                results
            @else
                Showing <span class="font-medium text-gray-700 dark:text-gray-200">0</span> results
            @endif
        </div>

        {{-- Pagination Links --}}
        @if ($paginator->hasPages())
            <div>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endif
