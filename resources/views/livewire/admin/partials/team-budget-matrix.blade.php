@php
    $monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    
    $categories = [
        'personal_goals' => ['title' => 'Persönliche Ziele', 'color' => 'primary', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'team_goals' => ['title' => 'Teamziele', 'color' => 'purple', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'internal_training' => ['title' => 'Interne Schulungen', 'color' => 'teal', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'other' => ['title' => 'Sonstiges', 'color' => 'amber', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
    ];
@endphp

<div class="space-y-8">
    @foreach($categories as $key => $cat)
        @php
            $data = $monthlyBreakdown[$key] ?? null;
            $hasData = $data && count($data['goals']) > 0;
        @endphp
        
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            {{-- Kategorie-Header --}}
            <div class="bg-{{ $cat['color'] }}-50 border-b border-{{ $cat['color'] }}-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-{{ $cat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cat['icon'] }}"/>
                    </svg>
                    <h3 class="font-semibold text-{{ $cat['color'] }}-900">{{ $cat['title'] }}</h3>
                    @if($hasData)
                        <span class="text-sm text-{{ $cat['color'] }}-600">({{ count($data['goals']) }} Ziele)</span>
                    @endif
                </div>
                @if($hasData)
                    <span class="text-sm font-medium text-{{ $cat['color'] }}-700">
                        {{ number_format($data['total'], 0, ',', '.') }} €
                    </span>
                @endif
            </div>
            
            @if($hasData)
                {{-- Matrix-Tabelle --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 min-w-[200px]">
                                    Ziel / Budget
                                </th>
                                @for($m = 1; $m <= 12; $m++)
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                        {{ $monthNames[$m - 1] }}
                                    </th>
                                @endfor
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 min-w-[100px]">
                                    Gesamt
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($data['goals'] as $goal)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 sticky left-0 bg-white">
                                        <div class="font-medium text-gray-900 text-sm whitespace-normal break-words min-w-[180px] max-w-[280px]">
                                            {{ $goal['name'] }}
                                            @if($goal['category'] ?? null)
                                                <x-goal-category-badge :category="$goal['category']" />
                                            @endif
                                        </div>
                                        {{-- Mitarbeiter-Namen klein darunter --}}
                                        @if(!empty($goal['users']))
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach($goal['users'] as $user)
                                                    <a href="{{ route('admin.employee-budget', $user['id']) }}" 
                                                       class="text-xs text-gray-400 hover:text-primary-600 hover:underline">
                                                        {{ $user['name'] }}
                                                    </a>
                                                    @if(!$loop->last)<span class="text-gray-300">,</span>@endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    @for($m = 1; $m <= 12; $m++)
                                        @php
                                            $monthData = $goal['months'][$m] ?? ['amount' => 0, 'users' => []];
                                            $hasValue = $monthData['amount'] > 0;
                                        @endphp
                                        <td class="px-2 py-2 text-center {{ $hasValue ? '' : 'text-gray-300' }}">
                                            @if($hasValue)
                                                <span class="font-medium text-gray-900">{{ number_format($monthData['amount'], 0, ',', '.') }}</span>
                                                {{-- Zeige MA-Namen bei Hover --}}
                                                @if(!empty($monthData['users']))
                                                    <div class="text-xs text-gray-400 hidden group-hover:block">
                                                        @foreach($monthData['users'] as $user)
                                                            {{ $user['name'] }}@if(!$loop->last), @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-gray-200">-</span>
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="px-3 py-2 text-right bg-gray-50 font-semibold text-gray-900">
                                        {{ number_format($goal['total'], 0, ',', '.') }} €
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Summenzeile --}}
                            <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                                <td class="px-3 py-2 text-gray-700 sticky left-0 bg-gray-100">
                                    Summe
                                </td>
                                @for($m = 1; $m <= 12; $m++)
                                    @php
                                        $monthTotal = $data['month_totals'][$m] ?? ['amount' => 0];
                                    @endphp
                                    <td class="px-2 py-2 text-center">
                                        @if($monthTotal['amount'] > 0)
                                            <span class="text-gray-900">{{ number_format($monthTotal['amount'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="px-3 py-2 text-right bg-gray-200 font-bold text-gray-900">
                                    {{ number_format($data['total'], 0, ',', '.') }} €
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                {{-- Keine Daten --}}
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-sm">Keine {{ $cat['title'] }}</p>
                </div>
            @endif
        </div>
    @endforeach
</div>
