@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center gap-3">

        {{-- Info text --}}
        <p class="hidden sm:block text-sm text-surface-500">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-medium text-surface-700">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-surface-700">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <span class="font-medium text-surface-700">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>

        {{-- Page links (desktop) --}}
        <div class="hidden sm:flex pagination">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="pagination-item-disabled" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="pagination-item"
                   aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Elements --}}
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="pagination-item-disabled select-none">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="pagination-item-active">{{ $page }}</span>
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="pagination-item"
                               aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif

            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="pagination-item"
                   aria-label="{{ __('pagination.next') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="pagination-item-disabled" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </span>
            @endif

        </div>

        {{-- Mobile: simple prev/next --}}
        <div class="flex gap-2 items-center sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="pagination-item-disabled px-4">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-item px-4">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item px-4">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="pagination-item-disabled px-4">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

    </nav>
@endif
