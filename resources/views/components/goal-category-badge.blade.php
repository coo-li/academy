@props(['category'])

@php
$config = match($category) {
    'A' => ['label' => 'A-Ziel', 'class' => 'bg-red-100 text-red-800 border-red-200'],
    'B' => ['label' => 'B-Ziel', 'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200'],
    'C' => ['label' => 'C-Ziel', 'class' => 'bg-gray-100 text-gray-600 border-gray-200'],
    'none' => ['label' => 'Keine Kat.', 'class' => 'bg-slate-100 text-slate-600 border-slate-200'],
    default => null,
};
@endphp

@if($config)
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded border ' . $config['class']]) }}>
    {{ $config['label'] }}
</span>
@endif
