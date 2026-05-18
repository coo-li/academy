@if($entries->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ziel / Budget</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Soll</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ist</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Verwendung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($entries as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $item['budget_name'] }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ number_format($item['ist_euros'], 0, ',', '.') }} € / {{ number_format($item['soll_euros'], 0, ',', '.') }} €
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">
                            {{ number_format($item['soll_hours'], 1, ',', '.') }} h
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                            {{ number_format($item['ist_hours'], 1, ',', '.') }} h
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($item['soll_hours'] > 0)
                                @php
                                    $percentage = $item['verwendung'];
                                    // < 50% = Rot (zu wenig), 50-80% = Orange, 80-110% = Grün (optimal), > 110% = Rot (überzogen)
                                    $colorClass = $percentage < 50 ? 'bg-red-100 text-red-800' : 
                                                 ($percentage < 80 ? 'bg-orange-100 text-orange-800' : 
                                                 ($percentage <= 110 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'));
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                    {{ number_format($percentage, 0) }}%
                                </span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-700">Gesamt</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-600">
                        {{ number_format($entries->sum('soll_hours'), 1, ',', '.') }} h
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                        {{ number_format($entries->sum('ist_hours'), 1, ',', '.') }} h
                    </td>
                    <td class="px-4 py-3 text-right">
                        @php
                            $totalSoll = $entries->sum('soll_hours');
                            $totalIst = $entries->sum('ist_hours');
                            $totalPercentage = $totalSoll > 0 ? ($totalIst / $totalSoll) * 100 : 0;
                        @endphp
                        @if($totalSoll > 0)
                            @php
                                // < 50% = Rot (zu wenig), 50-80% = Orange, 80-110% = Grün (optimal), > 110% = Rot (überzogen)
                                $colorClass = $totalPercentage < 50 ? 'bg-red-100 text-red-800' : 
                                             ($totalPercentage < 80 ? 'bg-orange-100 text-orange-800' : 
                                             ($totalPercentage <= 110 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'));
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                {{ number_format($totalPercentage, 0) }}%
                            </span>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="text-center py-12 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-lg font-medium">{{ $emptyMessage ?? 'Keine Einträge vorhanden' }}</p>
        <p class="text-sm text-gray-400 mt-1">Für diesen Bereich wurden keine Budgets hinterlegt.</p>
    </div>
@endif
