@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-default dark:border-gray-800 dark:bg-gray-900 sm:p-6']) }}>
    @if($title)
        <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">{{ $title }}</h3>
            @if($description)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
