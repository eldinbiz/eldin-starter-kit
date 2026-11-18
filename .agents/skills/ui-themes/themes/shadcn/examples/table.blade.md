# shadcn Table Templates (`resources/views/themes/shadcn/components/table/`)

### 1. Master Table Wrapper (`<x-ui::table>`)
```blade
<div {{ $attributes->merge(['class' => 'relative w-full overflow-auto rounded-md border border-zinc-200 dark:border-zinc-800']) }}>
    <table class="w-full caption-bottom text-sm">
        {{ $slot }}
    </table>
</div>
```

### 2. Table Header (`<x-ui::table.header>`)
```blade
<thead {{ $attributes->merge(['class' => '[&_tr]:border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50']) }}>
    <tr class="border-b transition-colors">
        {{ $slot }}
    </tr>
</thead>
```

### 3. Table Row (`<x-ui::table.row>`)
```blade
<tr {{ $attributes->merge(['class' => 'border-b border-zinc-200 transition-colors hover:bg-zinc-100/50 data-[state=selected]:bg-zinc-100 dark:border-zinc-800 dark:hover:bg-zinc-800/50 dark:data-[state=selected]:bg-zinc-800']) }}>
    {{ $slot }}
</tr>
```

### 4. Table Cell (`<x-ui::table.cell>`)
```blade
@props([
    'header' => false,
    'align' => 'left',
])

@php
$alignClasses = match($align) {
    'center' => 'text-center',
    'right' => 'text-right',
    default => 'text-left',
};
@endphp

@if ($header)
    <th {{ $attributes->merge(['class' => "h-10 px-4 {$alignClasses} align-middle font-medium text-zinc-500 dark:text-zinc-400 [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]"]) }}>
        {{ $slot }}
    </th>
@else
    <td {{ $attributes->merge(['class' => "p-4 {$alignClasses} align-middle [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px] text-zinc-950 dark:text-zinc-50"]) }}>
        {{ $slot }}
    </td>
@endif
```

### 5. Table Empty State (`<x-ui::table.empty>`)
```blade
@props([
    'colspan' => 1,
    'message' => 'No results found.',
])

<tr>
    <td colspan="{{ $colspan }}" class="h-24 text-center align-middle text-sm text-zinc-500 dark:text-zinc-400">
        {{ $slot->isNotEmpty() ? $slot : $message }}
    </td>
</tr>
```
