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

<div class="flex items-start space-x-2">
    <div class="flex h-5 items-center">
        <input
            type="checkbox"
            @if($name) name="{{ $name }}" @endif
            id="{{ $checkboxId }}"
            value="{{ $value }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'size-4 rounded-sm border border-zinc-900 text-zinc-900 shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-50 dark:text-zinc-50 dark:focus-visible:ring-zinc-300 dark:bg-zinc-950 cursor-pointer']) }}
        />
    </div>

    @if($label || $description || $slot->isNotEmpty())
        <div class="grid gap-1.5 leading-none">
            @if($label)
                <label for="{{ $checkboxId }}" class="text-sm font-medium text-zinc-900 dark:text-zinc-100 cursor-pointer select-none">
                    {{ $label }}
                </label>
            @endif

            @if($description)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif

            {{ $slot }}

            @if($errorMessage)
                <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
            @endif
        </div>
    @endif
</div>
