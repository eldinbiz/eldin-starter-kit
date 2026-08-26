@props([
    'field' => '',
    'label' => '',
    'sortable' => true,
    'align' => 'left',
])

@php
    $alignClass = match ($align) {
        'right' => 'text-right justify-end',
        'center' => 'text-center justify-center',
        default => 'text-left justify-start',
    };
    $isCurrentSort = $this->sortField === $field;
@endphp

<th scope="col" class="py-3 px-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400">
    @if ($sortable && $field)
        <button
            type="button"
            wire:click="sortBy('{{ $field }}')"
            class="group inline-flex items-center gap-1.5 font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors cursor-pointer {{ $alignClass }}"
        >
            <span>{{ $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field)) }}</span>

            <span class="inline-flex flex-col">
                @if ($isCurrentSort)
                    @if ($this->sortDirection === 'asc')
                        {{-- Ascending Active --}}
                        <svg class="h-3.5 w-3.5 text-brand-500 dark:text-brand-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                    @else
                        {{-- Descending Active --}}
                        <svg class="h-3.5 w-3.5 text-brand-500 dark:text-brand-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    @endif
                @else
                    {{-- Neutral / Inactive Sort --}}
                    <svg class="h-3.5 w-3.5 text-gray-300 group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-400 opacity-60" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                @endif
            </span>
        </button>
    @else
        <div class="flex items-center {{ $alignClass }}">
            <span>{{ $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field)) }}</span>
        </div>
    @endif
</th>
