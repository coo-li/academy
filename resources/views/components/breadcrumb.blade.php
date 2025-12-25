@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'breadcrumbs']) }}>
    @foreach($items as $item)
        @if(!$loop->last)
            <a href="{{ $item['url'] ?? '#' }}" class="breadcrumbs-item">
                {{ $item['label'] }}
            </a>
            <span class="breadcrumbs-separator">/</span>
        @else
            <span class="breadcrumbs-current">{{ $item['label'] }}</span>
        @endif
    @endforeach
    
    {{ $slot }}
</nav>

