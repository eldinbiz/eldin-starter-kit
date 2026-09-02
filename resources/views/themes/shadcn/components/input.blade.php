@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'type' => 'text',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'helper' => null,
    'error' => null,
    'leadingIcon' => null,
    'trailingIcon' => null,
])

@php
$inputId = $id ?? $name ?? 'input-' . uniqid();
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

$stateClasses = $hasError
    ? 'border-red-500 dark:border-red-500 focus-visible:ring-red-500 text-red-900 dark:text-red-100 placeholder:text-red-400'
    : 'border-zinc-200 dark:border-zinc-800 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-500 dark:placeholder:text-zinc-400';

$paddingClasses = ($leadingIcon ? 'pl-9 ' : 'px-3 ') . ($trailingIcon ? 'pr-9' : 'px-3');
@endphp

<div class="w-full space-y-1.5">
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($leadingIcon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-zinc-400 dark:text-zinc-500">
                {{ $leadingIcon }}
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            id="{{ $inputId }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge(['class' => "flex h-9 w-full rounded-md border bg-transparent py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 {$paddingClasses} {$stateClasses}"]) }}
        />

        @if($trailingIcon)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-zinc-400 dark:text-zinc-500">
                {{ $trailingIcon }}
            </div>
        @endif
    </div>

    @if($errorMessage)
        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $helper }}</p>
    @endif
</div>
