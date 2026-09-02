<tr {{ $attributes->merge(['class' => 'border-b border-zinc-200 transition-colors hover:bg-zinc-100/50 dark:border-zinc-800 dark:hover:bg-zinc-800/50']) }}>
    {{ $slot }}
</tr>
