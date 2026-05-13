@props(['question', 'index'])

<p class="text-xs text-surface-500 mb-3">Wähle alle zutreffenden Antworten aus.</p>

<div class="space-y-2">
    @foreach($question['options'] as $optIndex => $option)
    <label class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 hover:border-brand-primary hover:bg-brand-primary-light cursor-pointer transition-colors"
           :class="{ 'border-brand-primary bg-brand-primary-light': isMultipleChoiceSelected({{ $index }}, {{ $optIndex }}) }">
        <input type="checkbox"
               name="answers[{{ $index }}][]"
               value="{{ $optIndex }}"
               class="checkbox-field"
               @change="toggleMultipleChoice({{ $index }}, {{ $optIndex }})">
        <span class="text-sm text-brand-dark">{{ $option }}</span>
    </label>
    @endforeach
</div>
