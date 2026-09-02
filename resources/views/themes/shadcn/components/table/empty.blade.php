@props(['colspan' => 1, 'title' => 'No results found', 'description' => null])

<tr>
    <td colspan="{{ $colspan }}" class="p-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        <div class="flex flex-col items-center justify-center space-y-1.5">
            <svg class="size-6 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <div class="font-medium text-zinc-900 dark:text-zinc-100 text-sm">{{ $title }}</div>
            @if($description)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </td>
</tr>
