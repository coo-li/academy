@props([
    'type' => 'text',
    'label' => null,
    'name' => null,
    'required' => false,
    'error' => null,
    'help' => null,
    'icon' => null
])

@php
$inputName = $name ?? $attributes->get('name');
$hasError = $error || ($inputName && $errors->has($inputName));
$errorMessage = $error ?? ($inputName ? $errors->first($inputName) : null);
$inputClasses = $hasError ? 'input-field-error' : 'input-field';
@endphp

<div class="w-full">
    @if($label)
    <label for="{{ $inputName }}" class="label {{ $required ? 'label-required' : '' }}">
        {{ $label }}
    </label>
    @endif
    
    @if($icon)
    <div class="input-group">
        <span class="input-group-icon w-4 h-4">{!! $icon !!}</span>
        <input 
            type="{{ $type }}" 
            {{ $attributes->merge(['class' => $inputClasses, 'name' => $inputName, 'id' => $inputName]) }}
            @if($required) required @endif
        >
    </div>
    @else
    <input 
        type="{{ $type }}" 
        {{ $attributes->merge(['class' => $inputClasses, 'name' => $inputName, 'id' => $inputName]) }}
        @if($required) required @endif
    >
    @endif
    
    @if($help && !$hasError)
    <p class="help-text">{{ $help }}</p>
    @endif
    
    @if($hasError)
    <p class="error-text">{{ $errorMessage }}</p>
    @endif
</div>

