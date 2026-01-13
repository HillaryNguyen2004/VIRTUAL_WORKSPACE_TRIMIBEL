@props([
    'label' => '',
    'name' => '',
    'oldKey' => null,
    'value' => null,
    'placeholder' => '',
    'isRequired' => false,
    'id' => null,
    'rows' => 5,
])

@php
    $key = $oldKey ?? $name;

    $labelClass = "block text-sm font-semibold text-main mb-2";
    $baseClass  = "block w-full bg-canvas border text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all";

    $hasError = $errors->has($key);
    $textareaClass = $baseClass . ' ' . ($hasError ? 'border-danger focus:ring-danger/20 focus:border-danger' : 'border-muted-200');
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if($label)
        <label for="{{ $id ?? $name }}" class="{{ $labelClass }}">
            {{ __($label) }}
            @if($isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        rows="{{ $rows }}"
        class="{{ $textareaClass }}"
        placeholder="{{ __($placeholder) }}"
        @if($isRequired) required @endif
    >{{ old($key, $value) }}</textarea>

    @error($key)
        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
