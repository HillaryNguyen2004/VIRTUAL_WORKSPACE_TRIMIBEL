@props([
    'label' => '',
    'name' => '',
    'oldKey' => null,
    'value' => null,
    'placeholder' => '',
    'isRequired' => false,
    'type' => 'text',
    'id' => null,
    'disabled' => false,
])

@php
    $key = $oldKey ?? $name;

    $labelClass = "block text-sm font-semibold text-main mb-2";
    $inputBase  = "block w-full bg-canvas border text-main h-12 px-4 rounded-xl placeholder-muted-400 hover:border-primary/50 focus:bg-white outline-none focus-within:ring-1 focus-within:ring-primary/20 focus:border-primary transition-all" . ($disabled ? 'bg-gray-200 cursor-not-allowed' : '');

    $hasError = $errors->has($key);
    $inputClass = $inputBase . ' ' . ($hasError ? 'border-danger focus:ring-danger/20 focus:border-danger' : 'border-muted-300');

    $wrapperClass = $attributes->get('class');

    $inputAttrs = $attributes->except('class');
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
        @if($disabled) disabled @endif
        {{ $inputAttrs }}
    >

    @error($key)
        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
