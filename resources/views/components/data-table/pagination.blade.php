@props(['datatable'])

@php
    /** @var \App\DataTables\DataTable $datatable */
    $paginator = $datatable->results();
@endphp

<div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <span>Show</span>
        <select
            @change="$dispatch('per-page-change', $el.value)"
            onchange="if (!window.Alpine) window.location.href = this.value"
            class="pagination-per-page rounded-lg border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
            @foreach($datatable->perPageOptions() as $option)
                <option
                    value="{{ $datatable->perPageUrl($option) }}"
                    {{ $datatable->perPage() === $option ? 'selected' : '' }}
                >{{ $option }}</option>
            @endforeach
        </select>
        <span>entries</span>
    </div>
    <div>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
</div>
