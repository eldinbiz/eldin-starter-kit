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
    ? 'border-red-500 dark:border-red-500 focus-visible:ring-red-500 text-red-900 dark:text-red-100 placeholder:text-red-400'
    : 'border-zinc-200 dark:border-zinc-800 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 text-zinc-900 dark:text-zinc-50 placeholder:text-zinc-500 dark:placeholder:text-zinc-400';
@endphp

<div class="w-full space-y-1.5">
    @if($label)
        <label for="{{ $textareaId }}" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
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
        {{ $attributes->merge(['class' => "flex min-h-[60px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 {$stateClasses}"]) }}
    >{{ $value ?? $slot }}</textarea>

    @if($errorMessage)
        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
    @elseif($helper)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $helper }}</p>
    @endif
</div>
