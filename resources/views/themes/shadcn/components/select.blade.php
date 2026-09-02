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
    ? 'border-red-500 dark:border-red-500 focus-visible:ring-red-500 text-red-900 dark:text-red-100'
    : 'border-zinc-200 dark:border-zinc-800 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 text-zinc-900 dark:text-zinc-50';
@endphp

<div class="w-full space-y-1.5">
    @if($label)
        <label for="{{ $selectId }}" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            @if($name) name="{{ $name }}" @endif
            id="{{ $selectId }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => "flex h-9 w-full appearance-none rounded-md border bg-transparent py-1.5 pl-3 pr-8 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-950 {$stateClasses}"]) }}
        >
            @if($placeholder)
                <option value="" class="text-zinc-500 dark:bg-zinc-950">{{ $placeholder }}</option>
            @endif

            @if(count($options) > 0)
                @foreach($options as $val => $optLabel)
                    <option value="{{ $val }}" class="text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">{{ $optLabel }}</option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-zinc-400 dark:text-zinc-500">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if($errorMessage)
        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $helper }}</p>
    @endif
</div>
