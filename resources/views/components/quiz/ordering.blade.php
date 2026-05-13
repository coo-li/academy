@props(['question', 'index'])

<p class="text-xs text-surface-500 mb-3">Ziehe die Elemente in die richtige Reihenfolge.</p>

@php
    $items = $question['items'] ?? [];
    $indices = range(0, count($items) - 1);
    shuffle($indices);
    // Ensure shuffled order differs from correct order (if more than 1 item)
    if (count($indices) > 1 && $indices === range(0, count($items) - 1)) {
        $indices = array_reverse($indices);
    }
@endphp

<div x-init="initOrdering({{ $index }}, {{ json_encode(array_map('strval', $indices)) }})"
     data-ordering-list="{{ $index }}"
     class="space-y-2">
    @foreach($indices as $pos => $itemIdx)
    <div class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 bg-white cursor-grab active:cursor-grabbing transition-colors hover:border-brand-primary"
         data-order-idx="{{ $itemIdx }}">
        <input type="hidden" name="answers[{{ $index }}][]" value="{{ $itemIdx }}" class="ordering-hidden-input">
        <div class="flex-shrink-0 text-surface-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
            </svg>
        </div>
        <span class="text-sm font-medium text-surface-500 w-6 ordering-position">{{ $pos + 1 }}.</span>
        <span class="text-sm text-brand-dark">{{ $items[$itemIdx] }}</span>
    </div>
    @endforeach
</div>
