@props(['question', 'index'])

<div class="space-y-2">
    @foreach($question['options'] as $optIndex => $option)
    <label class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 hover:border-brand-primary hover:bg-brand-primary-light cursor-pointer transition-colors"
           :class="{ 'border-brand-primary bg-brand-primary-light': isSingleChoiceSelected({{ $index }}, {{ $optIndex }}) }">
        <input type="radio"
               name="answers[{{ $index }}]"
               value="{{ $optIndex }}"
               class="radio-field"
               @click="selectSingleChoice({{ $index }}, {{ $optIndex }})">
        <span class="text-sm text-brand-dark">{{ $option }}</span>
    </label>
    @endforeach
</div>
