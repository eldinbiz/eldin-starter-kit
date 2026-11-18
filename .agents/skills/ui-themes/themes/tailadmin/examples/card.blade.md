# TailAdmin Card Template (`resources/views/themes/tailadmin/components/card.blade.php`)

```blade
@props([
    'title' => null,
    'description' => null,
    'header' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    @if ($title || $description || $header)
        <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
            @if ($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800 flex items-center justify-end gap-3">
            {{ $footer }}
        </div>
    @endif
</div>
```
