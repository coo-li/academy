<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Meine Milestones</h1>
        <p class="text-surface-500 mt-1.5 text-base">Anforderungen, die in deiner aktuellen Karrierestufe on-the-job erwartet werden.</p>
    </div>

    @if($milestonesByCategory->isNotEmpty())
        @foreach(App\Models\Milestone::CATEGORIES as $categoryKey => $categoryLabel)
            @if($milestonesByCategory->has($categoryKey))
            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-3">{{ $categoryLabel }}</h2>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        @foreach($milestonesByCategory[$categoryKey] as $milestone)
                        <div class="px-5 py-4 flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0 {{ $milestone->type === 'aktiv' ? 'bg-primary-500' : 'bg-gray-300' }}"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ $milestone->title }}</div>
                                @if($milestone->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $milestone->description }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $milestone->type === 'aktiv' ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-600' }}">{{ $milestone->typeLabel() }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <p class="text-gray-500">Keine Milestones für deine aktuelle Karrierestufe vorhanden.</p>
        </div>
    @endif
</div>
