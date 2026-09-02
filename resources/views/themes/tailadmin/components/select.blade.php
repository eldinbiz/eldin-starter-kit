@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'error' => null,
])

@php
$selectId = $id ?? $name ?? 'select-' . uniqid();
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

$stateClasses = $hasError
    ? 'border-error-500 dark:border-error-500 focus:border-error-500 focus:ring-3 focus:ring-error-500/20 text-error-900 dark:text-error-200'
    : 'border-gray-300 dark:border-gray-700 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/20 text-gray-800 dark:text-white/90';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $selectId }}" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            @if($name) name="{{ $name }}" @endif
            id="{{ $selectId }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => "block w-full appearance-none rounded-lg border bg-transparent py-2.5 pl-4 pr-10 text-sm shadow-theme-xs transition-colors focus:outline-none disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-900 {$stateClasses}"]) }}
        >
            @if($placeholder)
                <option value="" class="text-gray-400 dark:bg-gray-900">{{ $placeholder }}</option>
            @endif

            @if(count($options) > 0)
                @foreach($options as $val => $optLabel)
                    <option value="{{ $val }}" class="text-gray-800 dark:bg-gray-900 dark:text-white/90">{{ $optLabel }}</option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 dark:text-gray-500">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if($errorMessage)
        <p class="mt-1 text-xs text-error-500 dark:text-error-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $helper }}</p>
    @endif
</div>
