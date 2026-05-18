@if($entries->isNotEmpty())
    <div class="space-y-4">
        @foreach($entries as $entry)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                {{-- Entry Header --}}
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-900">{{ $entry['budget_name'] }}</h4>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach($entry['users'] as $user)
                                <a href="{{ route('admin.employee-budget', $user->id) }}" 
                                   class="text-xs text-gray-500 hover:text-primary-600 hover:underline inline-flex items-center gap-1">
                                    <span class="w-4 h-4 rounded-full bg-gray-300 flex items-center justify-center text-[10px] text-white">
                                        {{ substr($user->name, 0, 1) }}
                                    </span>
                                    {{ $user->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-gray-900">{{ number_format($entry['total_amount'], 0, ',', '.') }} €</span>
                    </div>
                </div>
                
                {{-- Einzelne Einträge --}}
                @if($entry['entries']->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($entry['entries'] as $budgetEntry)
                            <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-500">{{ $budgetEntry->date->format('d.m.Y') }}</span>
                                    <span class="text-sm text-gray-700">{{ $budgetEntry->label ?? $budgetEntry->budget_name }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($budgetEntry->amount, 0, ',', '.') }} €</span>
                                    <a href="{{ route('admin.employee-budget', $budgetEntry->user_id) }}" 
                                       class="text-xs text-gray-400 hover:text-primary-600 hover:underline">
                                        {{ $budgetEntry->user->name }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="text-lg font-medium">{{ $emptyMessage ?? 'Keine Einträge' }}</p>
    </div>
@endif
