# shadcn Input Template (`resources/views/themes/shadcn/components/input.blade.php`)

```blade
@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'error' => null,
    'leadingIcon' => null,
    'trailingIcon' => null,
])

@php
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

$inputClasses = 'flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-zinc-950 placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:file:text-zinc-50 dark:placeholder:text-zinc-400 ' . (
    $hasError
        ? 'border-red-500 focus-visible:ring-red-500 dark:border-red-900 dark:focus-visible:ring-red-900'
        : 'border-zinc-200 focus-visible:ring-zinc-950 dark:border-zinc-800 dark:focus-visible:ring-zinc-300'
);

if ($leadingIcon) $inputClasses .= ' pl-9';
if ($trailingIcon) $inputClasses .= ' pr-9';
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
        @if ($leadingIcon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-500 dark:text-zinc-400 [&_svg]:size-4">
                {{ $leadingIcon }}
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" id="{{ $name }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />

        @if ($trailingIcon)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-500 dark:text-zinc-400 [&_svg]:size-4">
                {{ $trailingIcon }}
            </div>
        @endif
    </div>

    @if ($errorMessage)
        <p class="text-xs font-medium text-red-500 dark:text-red-900">{{ $errorMessage }}</p>
    @elseif ($helper)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $helper }}</p>
    @endif
</div>
```
