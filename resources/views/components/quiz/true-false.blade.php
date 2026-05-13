@props(['question', 'index'])

<div class="space-y-2">
    <label class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 hover:border-brand-primary hover:bg-brand-primary-light cursor-pointer transition-colors"
           :class="{ 'border-brand-primary bg-brand-primary-light': isTrueFalseSelected({{ $index }}, 'true') }">
        <input type="radio"
               name="answers[{{ $index }}]"
               value="true"
               class="radio-field"
               @click="selectTrueFalse({{ $index }}, 'true')">
        <span class="text-sm text-brand-dark">Wahr</span>
    </label>

    <label class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 hover:border-brand-primary hover:bg-brand-primary-light cursor-pointer transition-colors"
           :class="{ 'border-brand-primary bg-brand-primary-light': isTrueFalseSelected({{ $index }}, 'false') }">
        <input type="radio"
               name="answers[{{ $index }}]"
               value="false"
               class="radio-field"
               @click="selectTrueFalse({{ $index }}, 'false')">
        <span class="text-sm text-brand-dark">Falsch</span>
    </label>
</div>
