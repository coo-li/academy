@php
    $monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    
    $categories = [
        'personal_goals' => ['title' => 'Persönliche Ziele', 'color' => 'primary', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'team_goals' => ['title' => 'Teamziele', 'color' => 'purple', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'internal_training' => ['title' => 'Interne Schulungen', 'color' => 'teal', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'other' => ['title' => 'Sonstiges', 'color' => 'amber', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
    ];
    
    $getColorClass = function($percentage) {
        if ($percentage < 50) return 'bg-red-100 text-red-800';
        if ($percentage < 80) return 'bg-orange-100 text-orange-800';
        if ($percentage <= 110) return 'bg-green-100 text-green-800';
        return 'bg-red-100 text-red-800';
    };
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
                @if($hasData && $data['total_soll'] > 0)
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $getColorClass($data['verwendung']) }}">
                        {{ $data['verwendung'] }}% genutzt
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
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 w-20">
                                    %
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($data['goals'] as $goal)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 sticky left-0 bg-white">
                                        <div class="text-gray-900 font-medium text-sm whitespace-normal break-words min-w-[180px] max-w-[280px]">
                                            {{ $goal['name'] }}
                                            @if($goal['category'] ?? null)
                                                <x-goal-category-badge :category="$goal['category']" />
                                            @endif
                                        </div>
                                    </td>
                                    @for($m = 1; $m <= 12; $m++)
                                        @php
                                            $monthData = $goal['months'][$m] ?? ['ist' => 0, 'soll' => 0];
                                            $hasValue = $monthData['ist'] > 0 || $monthData['soll'] > 0;
                                        @endphp
                                        <td class="px-2 py-2 text-center {{ $hasValue ? '' : 'text-gray-300' }}">
                                            @if($monthData['ist'] > 0 && $monthData['soll'] > 0)
                                                {{-- Beide Werte: IST fett, SOLL in grau dahinter --}}
                                                <span class="font-medium text-gray-900">{{ number_format($monthData['ist'], 1, ',', '') }}</span>
                                                <span class="text-gray-400 text-xs ml-0.5">({{ number_format($monthData['soll'], 1, ',', '') }})</span>
                                            @elseif($monthData['ist'] > 0)
                                                {{-- Nur IST --}}
                                                <span class="font-medium text-gray-900">{{ number_format($monthData['ist'], 1, ',', '') }}</span>
                                            @elseif($monthData['soll'] > 0)
                                                {{-- Nur SOLL geplant, noch nicht genutzt --}}
                                                <span class="text-gray-400 text-xs">({{ number_format($monthData['soll'], 1, ',', '') }})</span>
                                            @else
                                                <span class="text-gray-200">-</span>
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="px-3 py-2 text-right bg-gray-50 font-medium">
                                        <span class="text-gray-900">{{ number_format($goal['total_ist'], 1, ',', '') }}</span>
                                        <span class="text-gray-400">/</span>
                                        <span class="text-gray-500">{{ number_format($goal['total_soll'], 1, ',', '') }}</span>
                                        <span class="text-gray-400 text-xs">h</span>
                                    </td>
                                    <td class="px-3 py-2 text-center bg-gray-50">
                                        @if($goal['total_soll'] > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $getColorClass($goal['verwendung']) }}">
                                                {{ $goal['verwendung'] }}%
                                            </span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
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
                                        $monthTotal = $data['month_totals'][$m] ?? ['ist' => 0, 'soll' => 0];
                                    @endphp
                                    <td class="px-2 py-2 text-center">
                                        @if($monthTotal['ist'] > 0 && $monthTotal['soll'] > 0)
                                            <span class="text-gray-900">{{ number_format($monthTotal['ist'], 1, ',', '') }}</span>
                                            <span class="text-gray-400 text-xs ml-0.5">({{ number_format($monthTotal['soll'], 1, ',', '') }})</span>
                                        @elseif($monthTotal['ist'] > 0)
                                            <span class="text-gray-900">{{ number_format($monthTotal['ist'], 1, ',', '') }}</span>
                                        @elseif($monthTotal['soll'] > 0)
                                            <span class="text-gray-400 text-xs">({{ number_format($monthTotal['soll'], 1, ',', '') }})</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="px-3 py-2 text-right bg-gray-200">
                                    <span class="text-gray-900">{{ number_format($data['total_ist'], 1, ',', '') }}</span>
                                    <span class="text-gray-500">/</span>
                                    <span class="text-gray-600">{{ number_format($data['total_soll'], 1, ',', '') }}</span>
                                    <span class="text-gray-500 text-xs">h</span>
                                </td>
                                <td class="px-3 py-2 text-center bg-gray-200">
                                    @if($data['total_soll'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $getColorClass($data['verwendung']) }}">
                                            {{ $data['verwendung'] }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
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
                    <p class="text-sm">Keine {{ $cat['title'] }} für {{ $selectedYear ?? date('Y') }}</p>
                </div>
            @endif
        </div>
    @endforeach
</div>

{{-- Legende --}}
<div class="mt-6 flex flex-wrap items-center gap-4 text-xs text-gray-500 border-t border-gray-200 pt-4">
    <span class="font-medium text-gray-700">Legende:</span>
    <span><strong>5,2</strong> <span class="text-gray-400">(5,2)</span> = Ist / (Soll)</span>
    <span class="text-gray-400">(8,0) = nur Soll geplant</span>
    <span><span class="inline-block w-3 h-3 rounded bg-red-100 mr-1"></span>&lt;50%</span>
    <span><span class="inline-block w-3 h-3 rounded bg-orange-100 mr-1"></span>50-80%</span>
    <span><span class="inline-block w-3 h-3 rounded bg-green-100 mr-1"></span>80-110%</span>
    <span><span class="inline-block w-3 h-3 rounded bg-red-100 mr-1"></span>&gt;110%</span>
</div>
