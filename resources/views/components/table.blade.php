@props(['compact' => false, 'striped' => false])

@php
$classes = 'table-tool';
if ($compact) $classes .= ' table-tool-compact';
if ($striped) $classes .= ' table-tool-striped';
@endphp

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => $classes]) }}>
        @isset($head)
        <thead>
            {{ $head }}
        </thead>
        @endisset
        
        <tbody>
            {{ $slot }}
        </tbody>
        
        @isset($foot)
        <tfoot>
            {{ $foot }}
        </tfoot>
        @endisset
    </table>
</div>

