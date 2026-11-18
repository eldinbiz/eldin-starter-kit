# TailAdmin Input Template (`resources/views/themes/tailadmin/components/input.blade.php`)

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

$inputClasses = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden disabled:bg-gray-100 disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 ' . (
    $hasError
        ? 'border-error-500 focus:border-error-500 focus:ring-3 focus:ring-error-500/10 dark:border-error-500'
        : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
);

if ($leadingIcon) $inputClasses .= ' pl-11';
if ($trailingIcon) $inputClasses .= ' pr-11';
@endphp

<div class="w-full">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
            @if ($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($leadingIcon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-gray-500 [&_svg]:size-5">
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
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 dark:text-gray-500 [&_svg]:size-5">
                {{ $trailingIcon }}
            </div>
        @endif
    </div>

    @if ($errorMessage)
        <p class="mt-1 text-xs text-error-500">{{ $errorMessage }}</p>
    @elseif ($helper)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $helper }}</p>
    @endif
</div>
```
