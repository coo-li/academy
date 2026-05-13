@props(['question', 'index'])

<p class="text-xs text-surface-500 mb-3">Ordne jedem Begriff links den passenden Begriff rechts zu.</p>

@php
    $rightOptions = collect($question['right'])->map(fn ($item, $idx) => ['idx' => $idx, 'text' => $item]);
    $shuffled = $rightOptions->shuffle();
@endphp

<div x-init="initMatchingAnswer({{ $index }})" class="space-y-3">
    @foreach($question['left'] as $leftIdx => $leftItem)
    <div class="flex items-center gap-3">
        <div class="flex-1 p-3 rounded-lg border border-surface-200 bg-surface-50">
            <span class="text-sm font-medium text-brand-dark">{{ $leftItem }}</span>
        </div>
        <svg class="w-5 h-5 text-surface-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
        </svg>
        <select name="answers[{{ $index }}][{{ $leftIdx }}]"
                class="input-field flex-1"
                @change="selectMatch({{ $index }}, '{{ $leftIdx }}', $event.target.value)"
                :value="getMatchValue({{ $index }}, '{{ $leftIdx }}')">
            <option value="">-- Zuordnen --</option>
            @foreach($shuffled as $opt)
            <option value="{{ $opt['idx'] }}">{{ $opt['text'] }}</option>
            @endforeach
        </select>
    </div>
    @endforeach
</div>
