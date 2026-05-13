@props(['question', 'index'])

<p class="text-xs text-surface-500 mb-3">Gib deine Antwort als kurzen Text ein.</p>

<div>
    <input type="text"
           name="answers[{{ $index }}]"
           class="input-field w-full"
           placeholder="Deine Antwort..."
           @input="updateShortAnswer({{ $index }}, $event.target.value)"
           autocomplete="off">
</div>
