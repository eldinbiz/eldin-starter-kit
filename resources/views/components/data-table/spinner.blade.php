@props(['size' => '10', 'label' => null])

<div class="flex flex-col items-center justify-center gap-3">
    <div class="h-10 w-10 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent shadow-theme-xs"></div>
    @if ($label)
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 animate-pulse">{{ $label }}</span>
    @endif
</div>
