@props(['type' => 'neutral'])

@php
$classes = match($type) {
    'success' => 'badge-success',
    'warning' => 'badge-warning',
    'error' => 'badge-error',
    'info' => 'badge-info',
    'primary' => 'badge-primary',
    default => 'badge-neutral'
};
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>

