@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 sm:p-6 text-zinc-950 dark:text-zinc-50']) }}>
    @if($title)
        <div class="mb-4 space-y-1">
            <h3 class="text-base font-semibold leading-none tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
            @if($description)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
