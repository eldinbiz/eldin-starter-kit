<div {{ $attributes->merge(['class' => 'w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-gray-900']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            {{ $slot }}
        </table>
    </div>
    @if(isset($pagination))
        <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
            {{ $pagination }}
        </div>
    @endif
</div>
