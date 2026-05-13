@props(['question', 'index'])

<p class="text-xs text-surface-500 mb-3">Fülle die Lücken im Text aus.</p>

<div x-init="initClozeAnswer({{ $index }}, {{ count($question['blanks'] ?? []) }})">
    @php
        $template = $question['text_template'] ?? '';
        $parts = preg_split('/(\{\{\d+\}\})/', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
    @endphp

    <div class="text-sm text-brand-dark leading-relaxed space-y-2">
        @foreach($parts as $part)
            @if(preg_match('/\{\{(\d+)\}\}/', $part, $matches))
                @php $blankIdx = (int) $matches[1]; @endphp
                <input type="text"
                       name="answers[{{ $index }}][{{ $blankIdx }}]"
                       class="input-field inline-block w-40 mx-1"
                       placeholder="Lücke {{ $blankIdx + 1 }}"
                       @input="updateClozeBlank({{ $index }}, {{ $blankIdx }}, $event.target.value)"
                       autocomplete="off">
            @else
                <span>{{ $part }}</span>
            @endif
        @endforeach
    </div>
</div>
