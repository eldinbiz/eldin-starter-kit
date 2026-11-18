# TailAdmin Table Templates (`resources/views/themes/tailadmin/components/table/`)

### 1. Master Table Wrapper (`<x-ui::table>`)
```blade
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <div class="max-w-full overflow-x-auto">
        <table class="w-full text-left">
            {{ $slot }}
        </table>
    </div>
</div>
```

### 2. Table Header (`<x-ui::table.header>`)
```blade
<thead {{ $attributes->merge(['class' => 'border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-white/[0.02]']) }}>
    <tr>
        {{ $slot }}
    </tr>
</thead>
```

### 3. Table Row (`<x-ui::table.row>`)
```blade
<tr {{ $attributes->merge(['class' => 'border-b border-gray-100 hover:bg-gray-50/50 dark:border-gray-800 dark:hover:bg-white/[0.01] transition']) }}>
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
    <th {{ $attributes->merge(['class' => "px-5 py-3.5 {$alignClasses} text-theme-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"]) }}>
        {{ $slot }}
    </th>
@else
    <td {{ $attributes->merge(['class' => "px-5 py-4 {$alignClasses} text-sm text-gray-800 dark:text-white/90"]) }}>
        {{ $slot }}
    </td>
@endif
```

### 5. Table Empty State (`<x-ui::table.empty>`)
```blade
@props([
    'colspan' => 1,
    'message' => 'No records found.',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $slot->isNotEmpty() ? $slot : $message }}
    </td>
</tr>
```
