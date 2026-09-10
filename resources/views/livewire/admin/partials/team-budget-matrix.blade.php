@php
    $monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $currentMonth = (int) date('n'); // 1-12
    
    $categories = [
        'personal_goals' => ['title' => 'Persönliche Ziele', 'color' => 'primary', 'bgColor' => 'bg-primary-50', 'textColor' => 'text-primary-700'],
        'team_goals' => ['title' => 'Teamziele', 'color' => 'purple', 'bgColor' => 'bg-purple-50', 'textColor' => 'text-purple-700'],
        'internal_training' => ['title' => 'Interne Schulungen', 'color' => 'teal', 'bgColor' => 'bg-teal-50', 'textColor' => 'text-teal-700'],
        'other' => ['title' => 'Sonstiges', 'color' => 'amber', 'bgColor' => 'bg-amber-50', 'textColor' => 'text-amber-700'],
    ];
    
    // Berechne Gesamtsummen über alle Kategorien
    $grandTotalIst = 0;
    $grandTotalSoll = 0;
    $grandYtdIst = 0;
    $grandYtdSoll = 0;
    $grandMonthTotals = array_fill(1, 12, ['ist' => 0, 'soll' => 0]);
    
    foreach ($categories as $key => $cat) {
        $data = $monthlyBreakdown[$key] ?? null;
        if ($data) {
            $grandTotalIst += $data['total_ist'] ?? 0;
            $grandTotalSoll += $data['total_soll'] ?? 0;
            for ($m = 1; $m <= 12; $m++) {
                $grandMonthTotals[$m]['ist'] += ($data['month_totals'][$m]['ist'] ?? 0);
                $grandMonthTotals[$m]['soll'] += ($data['month_totals'][$m]['soll'] ?? 0);
                // YTD nur bis aktuellen Monat
                if ($m <= $currentMonth) {
                    $grandYtdIst += ($data['month_totals'][$m]['ist'] ?? 0);
                    $grandYtdSoll += ($data['month_totals'][$m]['soll'] ?? 0);
                }
            }
        }
    }
    
    // Hilfsfunktion für Verwendungsgrad
    $calcUsage = function($ist, $soll) {
        if ($soll <= 0) return null;
        return round(($ist / $soll) * 100, 0);
    };
@endphp

<div class="border border-gray-200 rounded-lg overflow-hidden">
    {{-- Scrollbarer Container mit fester Höhe für sticky header --}}
    <div class="overflow-auto max-h-[70vh]">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            {{-- EINZIGE Kopfzeile für alle Kategorien - sticky beim Scrollen --}}
            <thead class="bg-gray-800 sticky top-0 z-20">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider sticky left-0 top-0 bg-gray-800 min-w-[250px] z-30">
                        Ziel / Budget
                    </th>
                    @for($m = 1; $m <= 12; $m++)
                        <th class="px-2 py-3 text-center text-xs font-medium {{ $m <= $currentMonth ? 'text-gray-200' : 'text-gray-500' }} uppercase tracking-wider w-16">
                            {{ $monthNames[$m - 1] }}
                        </th>
                    @endfor
                    <th class="px-3 py-3 text-center text-xs font-medium text-cyan-300 uppercase tracking-wider bg-gray-900 min-w-[90px] border-l border-gray-600">
                        YTD
                        <div class="text-[10px] font-normal normal-case text-gray-400">bis heute</div>
                    </th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-cyan-300 uppercase tracking-wider bg-gray-900 min-w-[50px]">
                        %
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider bg-gray-900 min-w-[90px] border-l border-gray-700">
                        Jahr
                    </th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach($categories as $key => $cat)
                    @php
                        $data = $monthlyBreakdown[$key] ?? null;
                        $hasData = $data && count($data['goals']) > 0;
                        
                        // Kategorie YTD berechnen
                        $catYtdIst = 0;
                        $catYtdSoll = 0;
                        if ($data) {
                            for ($m = 1; $m <= $currentMonth; $m++) {
                                $catYtdIst += ($data['month_totals'][$m]['ist'] ?? 0);
                                $catYtdSoll += ($data['month_totals'][$m]['soll'] ?? 0);
                            }
                        }
                        $catUsage = $calcUsage($catYtdIst, $catYtdSoll);
                    @endphp
                    
                    {{-- Kategorie-Header als Zwischenzeile --}}
                    <tr class="{{ $cat['bgColor'] }} border-t-2 border-gray-300">
                        <td colspan="16" class="px-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold {{ $cat['textColor'] }}">
                                    {{ $cat['title'] }}
                                    @if($hasData)
                                        <span class="font-normal text-sm">({{ count($data['goals']) }} Ziele)</span>
                                    @endif
                                </span>
                                @if($hasData)
                                    <span class="text-sm font-medium {{ $cat['textColor'] }}">
                                        YTD: {{ number_format($catYtdIst, 0, ',', '.') }} € / {{ number_format($catYtdSoll, 0, ',', '.') }} €
                                        @if($catUsage !== null)
                                            <span class="ml-2 px-2 py-0.5 rounded text-xs {{ $catUsage > 100 ? 'bg-red-200 text-red-800' : ($catUsage > 80 ? 'bg-yellow-200 text-yellow-800' : 'bg-green-200 text-green-800') }}">
                                                {{ $catUsage }}%
                                            </span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    
                    @if($hasData)
                        @foreach($data['goals'] as $goal)
                            @php
                                // YTD für dieses Ziel berechnen
                                $goalYtdIst = 0;
                                $goalYtdSoll = 0;
                                for ($m = 1; $m <= $currentMonth; $m++) {
                                    $goalYtdIst += ($goal['months'][$m]['ist'] ?? 0);
                                    $goalYtdSoll += ($goal['months'][$m]['soll'] ?? 0);
                                }
                                $goalUsage = $calcUsage($goalYtdIst, $goalYtdSoll);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 sticky left-0 bg-white">
                                    <div class="font-medium text-gray-900 text-sm whitespace-normal break-words min-w-[220px] max-w-[300px]">
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
                                        $monthData = $goal['months'][$m] ?? ['ist' => 0, 'soll' => 0, 'users' => []];
                                        $hasValue = ($monthData['ist'] ?? 0) > 0 || ($monthData['soll'] ?? 0) > 0;
                                        $isPast = $m <= $currentMonth;
                                    @endphp
                                    <td class="px-2 py-2 text-center {{ !$isPast ? 'bg-gray-50' : '' }}">
                                        @if($hasValue)
                                            <span class="font-medium {{ $isPast ? 'text-gray-900' : 'text-gray-400' }}">{{ number_format($monthData['ist'] ?? 0, 0, ',', '.') }}</span>
                                            @if(($monthData['soll'] ?? 0) > 0)
                                                <div class="text-xs text-gray-400">({{ number_format($monthData['soll'], 0, ',', '.') }})</div>
                                            @endif
                                        @else
                                            <span class="text-gray-200">-</span>
                                        @endif
                                    </td>
                                @endfor
                                {{-- YTD Spalte --}}
                                <td class="px-3 py-2 text-center bg-cyan-50 border-l border-gray-200">
                                    <span class="font-semibold text-gray-900">{{ number_format($goalYtdIst, 0, ',', '.') }}</span>
                                    @if($goalYtdSoll > 0)
                                        <div class="text-xs text-gray-500">({{ number_format($goalYtdSoll, 0, ',', '.') }})</div>
                                    @endif
                                </td>
                                {{-- Verwendungsgrad Spalte --}}
                                <td class="px-2 py-2 text-center bg-cyan-50">
                                    @if($goalUsage !== null)
                                        <span class="font-semibold {{ $goalUsage > 100 ? 'text-red-600' : ($goalUsage > 80 ? 'text-yellow-600' : 'text-green-600') }}">
                                            {{ $goalUsage }}%
                                        </span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                {{-- Gesamtjahr Spalte --}}
                                <td class="px-3 py-2 text-right bg-gray-50 border-l border-gray-200">
                                    <span class="text-gray-500">{{ number_format($goal['total_ist'], 0, ',', '.') }} €</span>
                                    @if(($goal['total_soll'] ?? 0) > 0)
                                        <div class="text-xs text-gray-400">({{ number_format($goal['total_soll'], 0, ',', '.') }} €)</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        {{-- Keine Daten für diese Kategorie --}}
                        <tr>
                            <td colspan="16" class="px-4 py-4 text-center text-gray-400 text-sm">
                                Keine {{ $cat['title'] }}
                            </td>
                        </tr>
                    @endif
                @endforeach
                
                {{-- GESAMTSUMME über alle Kategorien --}}
                @php
                    $grandUsage = $calcUsage($grandYtdIst, $grandYtdSoll);
                @endphp
                <tr class="bg-gray-800 font-semibold border-t-2 border-gray-600">
                    <td class="px-3 py-3 text-white sticky left-0 bg-gray-800">
                        Gesamtsumme
                    </td>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $monthTotal = $grandMonthTotals[$m] ?? ['ist' => 0, 'soll' => 0];
                            $isPast = $m <= $currentMonth;
                        @endphp
                        <td class="px-2 py-3 text-center {{ !$isPast ? 'opacity-50' : '' }}">
                            @if(($monthTotal['ist'] ?? 0) > 0 || ($monthTotal['soll'] ?? 0) > 0)
                                <span class="text-white">{{ number_format($monthTotal['ist'] ?? 0, 0, ',', '.') }}</span>
                                @if(($monthTotal['soll'] ?? 0) > 0)
                                    <div class="text-xs text-gray-400">({{ number_format($monthTotal['soll'], 0, ',', '.') }})</div>
                                @endif
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>
                    @endfor
                    {{-- YTD Summe --}}
                    <td class="px-3 py-3 text-center bg-cyan-900 border-l border-gray-600">
                        <span class="font-bold text-white">{{ number_format($grandYtdIst, 0, ',', '.') }}</span>
                        @if($grandYtdSoll > 0)
                            <div class="text-xs text-cyan-300">({{ number_format($grandYtdSoll, 0, ',', '.') }})</div>
                        @endif
                    </td>
                    {{-- Gesamtverwendungsgrad --}}
                    <td class="px-2 py-3 text-center bg-cyan-900">
                        @if($grandUsage !== null)
                            <span class="font-bold text-xl {{ $grandUsage > 100 ? 'text-red-400' : ($grandUsage > 80 ? 'text-yellow-400' : 'text-green-400') }}">
                                {{ $grandUsage }}%
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    {{-- Gesamtjahr --}}
                    <td class="px-3 py-3 text-right bg-gray-900 border-l border-gray-700">
                        <span class="text-gray-400">{{ number_format($grandTotalIst, 0, ',', '.') }} €</span>
                        @if($grandTotalSoll > 0)
                            <div class="text-xs text-gray-500">({{ number_format($grandTotalSoll, 0, ',', '.') }} €)</div>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
