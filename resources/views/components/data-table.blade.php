@props(['datatable'])

{{-- Flash Messages --}}
@if (session('success'))
    <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
        {{ session('error') }}
    </div>
@endif

{{-- Filters Panel --}}
<x-data-table.filters :datatable="$datatable" />

{{-- Active Filter Chips --}}
<x-data-table.filter-chips :datatable="$datatable" />

@if($datatable->results()->isEmpty())
    <div class="py-10 text-center">
        <p class="text-gray-500 dark:text-gray-400">No results match your filters.</p>
        <a href="{{ $datatable->clearFiltersUrl() }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
            Clear filters
        </a>
    </div>
@else
    {{-- Desktop Table View --}}
    <div class="hidden md:block w-full overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    @foreach($datatable->columns() as $column)
                        <x-data-table.th :column="$column" :datatable="$datatable" />
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @if($datatable->bodyView())
                    @include($datatable->bodyView(), array_merge($datatable->sharedViewData(), ['rows' => $datatable->results(), 'datatable' => $datatable]))
                @elseif($datatable->rowView())
                    @php($sharedData = $datatable->sharedViewData())
                    @foreach($datatable->results() as $row)
                        @include($datatable->rowView(), array_merge($sharedData, $datatable->rowViewData($row)))
                    @endforeach
                @else
                    @foreach($datatable->results() as $row)
                        {!! $datatable->renderRow($row) !!}
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    {{-- Mobile Card View --}}
    <div class="block md:hidden">
        @if($datatable->mobileBodyView())
            @include($datatable->mobileBodyView(), array_merge($datatable->sharedViewData(), ['rows' => $datatable->results(), 'datatable' => $datatable]))
        @elseif($datatable->mobileRowView() || $datatable->rowView())
            <div class="grid grid-cols-1 gap-4">
                @php($mobileView = $datatable->mobileRowView() ?: $datatable->rowView())
                @php($sharedData = $datatable->sharedViewData())
                @foreach($datatable->results() as $row)
                    @include($mobileView, array_merge($sharedData, $datatable->rowViewData($row)))
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($datatable->results() as $row)
                    {!! $datatable->renderMobileRow($row) !!}
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    <x-data-table.pagination :datatable="$datatable" />
@endif
