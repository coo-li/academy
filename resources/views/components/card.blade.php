@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card-tool']) }}>
    @if($title || isset($header))
    <div class="card-tool-header">
        @if($title)
        <h2 class="font-semibold text-brand-dark">{{ $title }}</h2>
        @endif
        {{ $header ?? '' }}
    </div>
    @endif
    
    <div class="card-tool-body">
        {{ $slot }}
    </div>
    
    @isset($footer)
    <div class="card-tool-footer">
        {{ $footer }}
    </div>
    @endisset
</div>

