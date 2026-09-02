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
    ? 'border-error-500 dark:border-error-500 focus:border-error-500 focus:ring-3 focus:ring-error-500/20 text-error-900 dark:text-error-200'
    : 'border-gray-300 dark:border-gray-700 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/20 text-gray-800 dark:text-white/90';

$paddingClasses = ($leadingIcon ? 'pl-10 ' : 'px-4 ') . ($trailingIcon ? 'pr-10' : 'px-4');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($leadingIcon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-500">
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
            {{ $attributes->merge(['class' => "block w-full rounded-lg border py-2.5 text-sm bg-transparent placeholder-gray-400 shadow-theme-xs transition-colors focus:outline-none disabled:opacity-50 disabled:pointer-events-none dark:placeholder-gray-500 {$paddingClasses} {$stateClasses}"]) }}
        />

        @if($trailingIcon)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 dark:text-gray-500">
                {{ $trailingIcon }}
            </div>
        @endif
    </div>

    @if($errorMessage)
        <p class="mt-1 text-xs text-error-500 dark:text-error-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $helper }}</p>
    @endif
</div>
