@props(['column', 'datatable'])

@php
    /** @var \App\DataTables\Column $column */
    /** @var \App\DataTables\DataTable $datatable */
@endphp

<th class="py-3 {{ $column->align === 'right' ? 'text-right' : 'text-left' }}">
    @if($column->sortable)
        <a
            href="{{ $datatable->sortUrl($column->key) }}"
            class="inline-flex items-center gap-1 font-medium text-gray-500 text-theme-xs dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
        >
            {{ $column->label ?: ucfirst($column->key) }}
            @if($datatable->sort() === $column->key)
                @if($datatable->direction() === 'asc')
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 8l-6 6h12z"/></svg>
                @else
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
                @endif
            @endif
        </a>
    @else
        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
            {{ $column->label ?: ucfirst($column->key) }}
        </p>
    @endif
</th>
