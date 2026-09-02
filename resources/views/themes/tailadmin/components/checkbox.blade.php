@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'error' => null,
])

@php
$checkboxId = $id ?? $name ?? 'checkbox-' . uniqid();
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp

<div class="relative flex items-start">
    <div class="flex h-5 items-center">
        <input
            type="checkbox"
            @if($name) name="{{ $name }}" @endif
            id="{{ $checkboxId }}"
            value="{{ $value }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'size-4 rounded border-gray-300 text-brand-500 shadow-theme-xs focus:ring-3 focus:ring-brand-500/20 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-brand-500/20 cursor-pointer']) }}
        />
    </div>

    @if($label || $description || $slot->isNotEmpty())
        <div class="ml-3 text-sm leading-5">
            @if($label)
                <label for="{{ $checkboxId }}" class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    {{ $label }}
                </label>
            @endif

            @if($description)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif

            {{ $slot }}

            @if($errorMessage)
                <p class="mt-1 text-xs text-error-500 dark:text-error-400">{{ $errorMessage }}</p>
            @endif
        </div>
    @endif
</div>
