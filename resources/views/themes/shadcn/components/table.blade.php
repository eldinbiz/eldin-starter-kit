<div {{ $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 shadow-xs']) }}>
    <div class="overflow-x-auto">
        <table class="w-full caption-bottom text-sm text-left">
            {{ $slot }}
        </table>
    </div>
    @if(isset($pagination))
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            {{ $pagination }}
        </div>
    @endif
</div>
