<x-layouts.dark title="Budget-Dashboard">
    @section('breadcrumb')
        Admin / <b>Budget-Overview</b>
    @endsection

    {{-- Kompakte KPI Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {{-- KPI 1: Gesamtbudget --}}
        <div class="dark-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-dark-tuerkis-dark flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-wallet text-lg text-dark-tuerkis"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Jahresbudget</div>
                    <div class="font-dark-display font-black text-xl text-dark-tx">€ 245.000</div>
                </div>
            </div>
        </div>

        {{-- KPI 2: Verbraucht --}}
        <div class="dark-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-dark-st-done/15 flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-check text-lg text-dark-st-done"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Ausgegeben</div>
                    <div class="font-dark-display font-black text-xl text-dark-tx">€ 127.450 <span class="text-sm font-bold text-dark-st-done">52%</span></div>
                </div>
            </div>
        </div>

        {{-- KPI 3: Geplant --}}
        <div class="dark-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-dark-gelb/15 flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-clock text-lg text-dark-gelb"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Gebucht (offen)</div>
                    <div class="font-dark-display font-black text-xl text-dark-tx">€ 68.200 <span class="text-sm font-bold text-dark-gelb">28%</span></div>
                </div>
            </div>
        </div>

        {{-- KPI 4: Verfügbar --}}
        <div class="dark-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-dark-tuerkis-dark flex items-center justify-center flex-shrink-0">
                    <i class="ti ti-coin text-lg text-dark-tuerkis"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Noch verfügbar</div>
                    <div class="font-dark-display font-black text-xl text-dark-tuerkis">€ 49.350 <span class="text-sm font-bold text-dark-tx-3">20%</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Budget-Formel (visuell) --}}
    <div class="flex items-center gap-2 mb-6 text-sm text-dark-tx-3 flex-wrap">
        <span class="px-2 py-1 rounded bg-dark-card border border-dark-line font-bold">Jahresbudget</span>
        <span>−</span>
        <span class="px-2 py-1 rounded bg-dark-st-done/15 text-dark-st-done font-bold">Ausgegeben</span>
        <span>−</span>
        <span class="px-2 py-1 rounded bg-dark-gelb/15 text-dark-gelb font-bold">Gebucht</span>
        <span>=</span>
        <span class="px-2 py-1 rounded bg-dark-tuerkis-dark text-dark-tuerkis font-bold">Verfügbar</span>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">
        {{-- Budget-Verlauf (3 cols) --}}
        <div class="lg:col-span-3 dark-card">
            <div class="px-4 py-3 border-b border-dark-line flex items-center justify-between">
                <h3 class="font-dark-display font-bold text-sm text-dark-tx">Monatliche Ausgaben</h3>
                <div class="flex gap-1">
                    <button class="dark-tab active text-xs py-1 px-3">2025</button>
                    <button class="dark-tab text-xs py-1 px-3">2024</button>
                </div>
            </div>
            <div class="p-4">
                <canvas id="budgetChart" height="180"></canvas>
            </div>
        </div>

        {{-- Donut (2 cols) --}}
        <div class="lg:col-span-2 dark-card">
            <div class="px-4 py-3 border-b border-dark-line">
                <h3 class="font-dark-display font-bold text-sm text-dark-tx">Nach Kategorie</h3>
            </div>
            <div class="p-4 flex items-center gap-4">
                <div class="w-32 h-32 flex-shrink-0">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-dark-tuerkis"></span>
                        <span class="text-dark-tx-2">Schulungen</span>
                        <span class="ml-auto font-bold text-dark-tx">45%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-dark-gelb"></span>
                        <span class="text-dark-tx-2">Konferenzen</span>
                        <span class="ml-auto font-bold text-dark-tx">25%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-dark-st-done"></span>
                        <span class="text-dark-tx-2">Zertifikate</span>
                        <span class="ml-auto font-bold text-dark-tx">20%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-dark-st-quiz"></span>
                        <span class="text-dark-tx-2">Sonstiges</span>
                        <span class="ml-auto font-bold text-dark-tx">10%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team Budget Table --}}
    <div class="dark-card mb-6">
        <div class="px-4 py-3 border-b border-dark-line flex items-center justify-between flex-wrap gap-2">
            <h3 class="font-dark-display font-bold text-sm text-dark-tx">Team-Budgets</h3>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 bg-[#181818] border border-dark-line rounded-lg px-3 py-1.5 text-dark-tx-3">
                    <i class="ti ti-search text-sm"></i>
                    <input type="text" placeholder="Suchen..." class="bg-transparent border-none outline-none text-dark-tx text-xs w-24">
                </div>
                <button class="dark-btn-secondary text-xs py-1.5">
                    <i class="ti ti-download text-sm"></i>
                    Export
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-dark-card-hover border-b border-dark-line">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Team</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Budget</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Ausgegeben</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Gebucht</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Verfügbar</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider w-32">Nutzung</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-dark-tx-3 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-line">
                    <tr class="hover:bg-dark-tuerkis-dark/20 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-dark-tx">Account Management</td>
                        <td class="px-4 py-2.5 text-right text-dark-tx-2">€ 45.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-done">€ 28.500</td>
                        <td class="px-4 py-2.5 text-right text-dark-gelb">€ 8.200</td>
                        <td class="px-4 py-2.5 text-right text-dark-tuerkis font-bold">€ 8.300</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-done rounded-full" style="width: 82%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-dark-tx-3 w-8">82%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-dark-st-done/15 text-dark-st-done">OK</span></td>
                    </tr>
                    <tr class="hover:bg-dark-tuerkis-dark/20 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-dark-tx">Tech / Development</td>
                        <td class="px-4 py-2.5 text-right text-dark-tx-2">€ 68.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-done">€ 52.400</td>
                        <td class="px-4 py-2.5 text-right text-dark-gelb">€ 12.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-quiz font-bold">€ 3.600</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-quiz rounded-full" style="width: 95%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-dark-tx-3 w-8">95%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-dark-st-quiz/15 text-dark-st-quiz">Kritisch</span></td>
                    </tr>
                    <tr class="hover:bg-dark-tuerkis-dark/20 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-dark-tx">SEA</td>
                        <td class="px-4 py-2.5 text-right text-dark-tx-2">€ 35.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-done">€ 15.200</td>
                        <td class="px-4 py-2.5 text-right text-dark-gelb">€ 9.800</td>
                        <td class="px-4 py-2.5 text-right text-dark-tuerkis font-bold">€ 10.000</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-tuerkis rounded-full" style="width: 71%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-dark-tx-3 w-8">71%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-dark-st-done/15 text-dark-st-done">OK</span></td>
                    </tr>
                    <tr class="hover:bg-dark-tuerkis-dark/20 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-dark-tx">SEO / Content</td>
                        <td class="px-4 py-2.5 text-right text-dark-tx-2">€ 42.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-done">€ 18.750</td>
                        <td class="px-4 py-2.5 text-right text-dark-gelb">€ 15.200</td>
                        <td class="px-4 py-2.5 text-right text-dark-tuerkis font-bold">€ 8.050</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-gelb rounded-full" style="width: 81%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-dark-tx-3 w-8">81%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-dark-gelb/15 text-dark-gelb">Geplant</span></td>
                    </tr>
                    <tr class="hover:bg-dark-tuerkis-dark/20 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-dark-tx">Design / UX</td>
                        <td class="px-4 py-2.5 text-right text-dark-tx-2">€ 28.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-st-done">€ 8.400</td>
                        <td class="px-4 py-2.5 text-right text-dark-gelb">€ 6.000</td>
                        <td class="px-4 py-2.5 text-right text-dark-tuerkis font-bold">€ 13.600</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-done rounded-full" style="width: 51%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-dark-tx-3 w-8">51%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-dark-st-done/15 text-dark-st-done">OK</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Recent Activity --}}
        <div class="lg:col-span-2 dark-card">
            <div class="px-4 py-3 border-b border-dark-line flex items-center justify-between">
                <h3 class="font-dark-display font-bold text-sm text-dark-tx">Letzte Buchungen</h3>
                <a href="#" class="text-xs text-dark-tuerkis font-bold hover:text-dark-tuerkis-hover">Alle →</a>
            </div>
            <div class="divide-y divide-dark-line">
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-dark-card-hover transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-dark-tuerkis-dark flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-certificate text-dark-tuerkis text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm text-dark-tx truncate">Google Ads Zertifizierung</div>
                        <div class="text-[10px] text-dark-tx-3">Max Mustermann · SEA</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-dark-tx">€ 450</div>
                        <div class="text-[10px] text-dark-st-done font-bold">Gebucht</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-dark-card-hover transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-dark-gelb/15 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-calendar-event text-dark-gelb text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm text-dark-tx truncate">OMR Festival 2025</div>
                        <div class="text-[10px] text-dark-tx-3">Lisa Schmidt · Account Mgmt</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-dark-tx">€ 1.200</div>
                        <div class="text-[10px] text-dark-gelb font-bold">Geplant</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-dark-card-hover transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-dark-st-done/15 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-school text-dark-st-done text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm text-dark-tx truncate">Scrum Master PSM I</div>
                        <div class="text-[10px] text-dark-tx-3">Tom Weber · Tech</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-dark-tx">€ 890</div>
                        <div class="text-[10px] text-dark-st-done font-bold">Abgeschlossen</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="dark-card">
            <div class="px-4 py-3 border-b border-dark-line">
                <h3 class="font-dark-display font-bold text-sm text-dark-tx">Schnellübersicht</h3>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-dark-tx-2">MA mit Budget</span>
                        <span class="font-bold text-dark-tx">42 / 48</span>
                    </div>
                    <div class="h-1.5 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-tuerkis rounded-full" style="width: 87%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-dark-tx-2">Aktive Schulungen</span>
                        <span class="font-bold text-dark-tx">23</span>
                    </div>
                    <div class="h-1.5 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-st-done rounded-full" style="width: 65%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-dark-tx-2">Offene Anfragen</span>
                        <span class="font-bold text-dark-st-quiz">7</span>
                    </div>
                    <div class="h-1.5 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-st-quiz rounded-full" style="width: 20%"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-dark-line">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-dark-tx-3">Ø Kosten pro MA</span>
                        <span class="font-bold text-dark-tuerkis">€ 3.034</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colors = {
                tuerkis: '#00B3C7',
                tuerkisLight: 'rgba(0, 179, 199, 0.2)',
                gelb: '#F9EE80',
                done: '#3DD68C',
                quiz: '#FF8A3D',
                line: '#2E2E2E',
                tx2: '#A6A6A6',
                tx3: '#6E6E6E',
            };

            // Budget Chart
            new Chart(document.getElementById('budgetChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
                    datasets: [
                        {
                            label: 'Ausgegeben',
                            data: [18500, 22300, 19800, 25400, 21200, 20100, null, null, null, null, null, null],
                            backgroundColor: colors.tuerkis,
                            borderRadius: 4,
                        },
                        {
                            label: 'Gebucht',
                            data: [null, null, null, null, null, null, 23000, 21000, 24000, 22000, 19000, 25000],
                            backgroundColor: colors.tuerkisLight,
                            borderColor: colors.tuerkis,
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Budget/Monat',
                            type: 'line',
                            data: Array(12).fill(20400),
                            borderColor: colors.gelb,
                            borderWidth: 2,
                            borderDash: [6, 3],
                            pointRadius: 0,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: colors.tx2,
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 10, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1E1E1E',
                            borderColor: colors.line,
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': € ' + (ctx.raw ? ctx.raw.toLocaleString('de-DE') : '-')
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: colors.line },
                            ticks: { color: colors.tx3, font: { size: 10 } }
                        },
                        y: {
                            grid: { color: colors.line },
                            ticks: { color: colors.tx3, font: { size: 10 }, callback: v => '€' + (v/1000) + 'k' }
                        }
                    }
                }
            });

            // Category Donut
            new Chart(document.getElementById('categoryChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Schulungen', 'Konferenzen', 'Zertifikate', 'Sonstiges'],
                    datasets: [{
                        data: [45, 25, 20, 10],
                        backgroundColor: [colors.tuerkis, colors.gelb, colors.done, colors.quiz],
                        borderWidth: 0,
                        spacing: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '65%',
                    plugins: { legend: { display: false } }
                }
            });

            document.querySelectorAll('.dark-tab').forEach(t => {
                t.addEventListener('click', () => {
                    t.closest('.flex').querySelectorAll('.dark-tab').forEach(x => x.classList.remove('active'));
                    t.classList.add('active');
                });
            });
        });
    </script>
    @endpush
</x-layouts.dark>
