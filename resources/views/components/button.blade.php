@props(['variant' => 'primary', 'size' => '', 'type' => 'button', 'icon' => false])

@php
$classes = match($variant) {
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary',
    'ghost' => 'btn-ghost',
    'danger' => 'btn-danger',
    'success' => 'btn-success',
    'icon' => 'btn-icon',
    'icon-primary' => 'btn-icon-primary',
    default => 'btn-primary'
};

$sizeClass = match($size) {
    'xs' => 'btn-xs',
    'sm' => 'btn-sm',
    'lg' => 'btn-lg',
    default => ''
};
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => trim("$classes $sizeClass")]) }}>
    {{ $slot }}
</button>

