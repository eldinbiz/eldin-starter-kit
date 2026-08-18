@props(['datatable'])

{{-- Desktop Skeleton Rows --}}
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
            @for ($i = 0; $i < 5; $i++)
                <tr class="animate-pulse">
                    @foreach($datatable->columns() as $column)
                        <td class="py-4 px-3">
                            <div class="h-4 bg-gray-200 rounded-md dark:bg-gray-700/60 {{ $loop->first ? 'w-48' : ($loop->last ? 'w-16 ml-auto' : 'w-24') }}"></div>
                        </td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
</div>

{{-- Mobile Skeleton Cards --}}
<div class="block md:hidden">
    <div class="grid grid-cols-1 gap-4">
        @for ($i = 0; $i < 3; $i++)
            <div class="animate-pulse rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700/60 shrink-0"></div>
                    <div class="space-y-2 flex-1">
                        <div class="h-4 bg-gray-200 rounded-md dark:bg-gray-700/60 w-3/4"></div>
                        <div class="h-3 bg-gray-200 rounded-md dark:bg-gray-700/60 w-1/2"></div>
                    </div>
                </div>
                <div class="flex gap-2 mb-3">
                    <div class="h-5 bg-gray-200 rounded-full dark:bg-gray-700/60 w-20"></div>
                    <div class="h-5 bg-gray-200 rounded-full dark:bg-gray-700/60 w-24"></div>
                </div>
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="h-3 bg-gray-200 rounded-md dark:bg-gray-700/60 w-28"></div>
                    <div class="h-6 bg-gray-200 rounded-md dark:bg-gray-700/60 w-16"></div>
                </div>
            </div>
        @endfor
    </div>
</div>
