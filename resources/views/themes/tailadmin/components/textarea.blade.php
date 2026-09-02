@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'rows' => 3,
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'helper' => null,
    'error' => null,
])

@php
$textareaId = $id ?? $name ?? 'textarea-' . uniqid();
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

$stateClasses = $hasError
    ? 'border-error-500 dark:border-error-500 focus:border-error-500 focus:ring-3 focus:ring-error-500/20 text-error-900 dark:text-error-200'
    : 'border-gray-300 dark:border-gray-700 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/20 text-gray-800 dark:text-white/90';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $textareaId }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <textarea
        @if($name) name="{{ $name }}" @endif
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->merge(['class' => "block w-full rounded-lg border bg-transparent p-3 text-sm placeholder-gray-400 shadow-theme-xs transition-colors focus:outline-none disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-900 dark:placeholder-gray-500 {$stateClasses}"]) }}
    >{{ $value ?? $slot }}</textarea>

    @if($errorMessage)
        <p class="mt-1 text-xs text-error-500 dark:text-error-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $helper }}</p>
    @endif
</div>
