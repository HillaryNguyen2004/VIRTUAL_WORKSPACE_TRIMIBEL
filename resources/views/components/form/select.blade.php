@props([
    'label' => '',
    'name' => '',
    'oldKey' => null,
    'value' => null,
    'placeholder' => null,
    'isRequired' => false,
    'id' => null,
    'options' => [],
    'optionValue' => null,
    'optionLabel' => null,
    'showChevron' => true,
    'disabled' => false,
])

@php
    $key = $oldKey ?? $name;
    $selected = old($key, $value);

    $labelClass = "block text-sm font-semibold text-main mb-2";

    $padding = $showChevron ? "pl-4 pr-12" : "px-4";

    $baseClass = "block w-full bg-canvas border text-main cursor-pointer h-12 {$padding} rounded-xl placeholder-muted-400 focus:bg-white outline-none focus:ring-1 focus:ring-primary/20 focus:border-primary transition-all appearance-none " . ($disabled ? 'bg-gray-200 cursor-not-allowed' : '');

    $hasError = $errors->has($key);
    $selectClass = $baseClass . ' ' . ($hasError ? 'border-danger focus:ring-danger/20 focus:border-danger' : 'border-muted-200');

    $isPlaceholderSelected = is_null($selected) || $selected === '';
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

    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $id ?? $name }}"
            class="{{ $selectClass }}"
            @if($isRequired) required @endif
            @if($disabled) disabled @endif
        >
            @if(!is_null($placeholder))
                <option value="" disabled hidden @selected($isPlaceholderSelected)>
                    {{ __($placeholder) }}
                </option>
            @endif

            @if(is_iterable($options) && (is_array($options) || $options instanceof \Illuminate\Support\Collection) && !($optionValue && $optionLabel))
                @foreach($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string)$selected === (string)$optValue)>
                        {{ $optLabel }}
                    </option>
                @endforeach
            @else
                @foreach($options as $opt)
                    @php
                        $v = data_get($opt, $optionValue);
                        $l = data_get($opt, $optionLabel);
                    @endphp
                    <option value="{{ $v }}" @selected((string)$selected === (string)$v)>
                        {{ $l }}
                    </option>
                @endforeach
            @endif
        </select>

        @if($showChevron)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        @endif
    </div>

    @error($key)
        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
