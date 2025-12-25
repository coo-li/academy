@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'error' => null,
    'help' => null,
    'options' => [],
    'placeholder' => 'Bitte wählen...'
])

@php
$selectName = $name ?? $attributes->get('name');
$hasError = $error || ($selectName && $errors->has($selectName));
$errorMessage = $error ?? ($selectName ? $errors->first($selectName) : null);
$selectClasses = $hasError ? 'input-field-error' : 'select-field';
$selectedValue = old($selectName) ?? $attributes->get('value');
@endphp

<div class="w-full">
    @if($label)
    <label for="{{ $selectName }}" class="label {{ $required ? 'label-required' : '' }}">
        {{ $label }}
    </label>
    @endif
    
    <select 
        {{ $attributes->merge(['class' => $selectClasses, 'name' => $selectName, 'id' => $selectName]) }}
        @if($required) required @endif
    >
        @if($placeholder)
        <option value="">{{ $placeholder }}</option>
        @endif
        
        @if(is_array($options) && count($options) > 0)
            @foreach($options as $value => $text)
                <option value="{{ $value }}" {{ $selectedValue == $value ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
        @endif
        
        {{ $slot }}
    </select>
    
    @if($help && !$hasError)
    <p class="help-text">{{ $help }}</p>
    @endif
    
    @if($hasError)
    <p class="error-text">{{ $errorMessage }}</p>
    @endif
</div>

