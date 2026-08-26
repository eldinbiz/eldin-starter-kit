@props([
    'columns' => 4,
    'rows' => 5,
])

<div wire:loading.delay.shortest class="w-full">
    <div class="animate-pulse space-y-3 p-4">
        @for ($r = 0; $r < $rows; $r++)
            <div class="flex items-center gap-4 py-2 border-b border-gray-100 dark:border-gray-800">
                @for ($c = 0; $c < $columns; $c++)
                    <div class="h-4 bg-gray-200 dark:bg-gray-700/60 rounded flex-1"></div>
                @endfor
            </div>
        @endfor
    </div>
</div>
