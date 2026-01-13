@props([
    'label' => '',
    'name' => '',
    'oldKey' => null,
    'value' => null,
    'placeholder' => '',
    'isRequired' => false,
    'type' => 'text',
    'id' => null,
])

@php
    $key = $oldKey ?? $name;

    $labelClass = "block text-sm font-semibold text-main mb-2";
    $inputBase  = "block w-full bg-canvas border text-main h-[50px] px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all";

    $hasError = $errors->has($key);
    $inputClass = $inputBase . ' ' . ($hasError ? 'border-danger focus:ring-danger/20 focus:border-danger' : 'border-muted-200');
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        class="{{ $inputClass }}"
        placeholder="{{ __($placeholder) }}"
        value="{{ old($key, $value) }}"
        @if($isRequired) required @endif
    >

    @error($key)
        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
