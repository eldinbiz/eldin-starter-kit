# shadcn Card Template (`resources/views/themes/shadcn/components/card.blade.php`)

```blade
@props([
    'title' => null,
    'description' => null,
    'header' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white text-zinc-950 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50']) }}>
    @if ($title || $description || $header)
        <div class="flex flex-col space-y-1.5 p-6">
            @if ($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            @endif
        </div>
    @endif

    <div class="p-6 {{ $title || $description || $header ? 'pt-0' : '' }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="flex items-center p-6 pt-0">
            {{ $footer }}
        </div>
    @endif
</div>
```
