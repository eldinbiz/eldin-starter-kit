# shadcn Select Template (`resources/views/themes/shadcn/components/select.blade.php`)

```blade
@props([
    'name' => null,
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'error' => null,
])

@php
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

$selectClasses = 'flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs ring-offset-white placeholder:text-zinc-500 focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 ' . (
    $hasError
        ? 'border-red-500 focus:ring-red-500 dark:border-red-900 dark:focus:ring-red-900'
        : 'border-zinc-200 focus:ring-zinc-950 dark:border-zinc-800 dark:focus:ring-zinc-300'
);
@endphp

<div class="w-full space-y-1.5">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-zinc-950 dark:text-zinc-50">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            @if($name) name="{{ $name }}" id="{{ $name }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @if ($placeholder)
                <option value="" disabled selected>{{ $placeholder }}</option>
            @endif

            @if (count($options) > 0)
                @foreach ($options as $value => $optionLabel)
                    <option value="{{ $value }}">{{ $optionLabel }}</option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>
    </div>

    @if ($errorMessage)
        <p class="text-xs font-medium text-red-500 dark:text-red-900">{{ $errorMessage }}</p>
    @elseif ($helper)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $helper }}</p>
    @endif
</div>
```
