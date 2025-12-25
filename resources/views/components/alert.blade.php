@props(['type' => 'info', 'title' => null, 'dismissible' => false])

@php
$classes = match($type) {
    'success' => 'alert-success',
    'warning' => 'alert-warning',
    'error' => 'alert-error',
    default => 'alert-info'
};

$icons = [
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} @if($dismissible) x-data="{ show: true }" x-show="show" @endif>
    <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {!! $icons[$type] ?? $icons['info'] !!}
    </svg>
    <div class="alert-content">
        @if($title)
        <div class="alert-title">{{ $title }}</div>
        @endif
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
    <button @click="show = false" class="ml-auto text-current opacity-70 hover:opacity-100 transition-opacity">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    @endif
</div>

