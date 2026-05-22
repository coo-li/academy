<x-layouts.dark title="Budget-Dashboard">
    @section('breadcrumb')
        Admin / <b>Budget-Overview</b>
    @endsection

    {{-- KPI Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        {{-- KPI 1: Gesamtbudget --}}
        <div class="dark-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-bold text-dark-tx-3 uppercase tracking-widest mb-1">Gesamtbudget</div>
                    <div class="font-dark-display font-black text-3xl text-dark-tx">€ 245.000</div>
                    <div class="flex items-center gap-1 mt-2 text-sm text-dark-st-done">
                        <i class="ti ti-trending-up text-base"></i>
                        <span class="font-bold">+12%</span>
                        <span class="text-dark-tx-3 ml-1">vs. Vorjahr</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-dark-tuerkis-dark flex items-center justify-center">
                    <i class="ti ti-wallet text-2xl text-dark-tuerkis"></i>
                </div>
            </div>
        </div>

        {{-- KPI 2: Verbraucht --}}
        <div class="dark-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-bold text-dark-tx-3 uppercase tracking-widest mb-1">Verbraucht</div>
                    <div class="font-dark-display font-black text-3xl text-dark-tx">€ 127.450</div>
                    <div class="flex items-center gap-1 mt-2 text-sm">
                        <span class="font-bold text-dark-tuerkis">52%</span>
                        <span class="text-dark-tx-3 ml-1">des Jahresbudgets</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-dark-st-done/15 flex items-center justify-center">
                    <i class="ti ti-chart-pie text-2xl text-dark-st-done"></i>
                </div>
            </div>
        </div>

        {{-- KPI 3: Geplant --}}
        <div class="dark-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-bold text-dark-tx-3 uppercase tracking-widest mb-1">Geplant</div>
                    <div class="font-dark-display font-black text-3xl text-dark-tx">€ 68.200</div>
                    <div class="flex items-center gap-1 mt-2 text-sm">
                        <span class="font-bold text-dark-gelb">28%</span>
                        <span class="text-dark-tx-3 ml-1">in Buchung</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-dark-gelb/15 flex items-center justify-center">
                    <i class="ti ti-calendar-stats text-2xl text-dark-gelb"></i>
                </div>
            </div>
        </div>

        {{-- KPI 4: Verfügbar --}}
        <div class="dark-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-bold text-dark-tx-3 uppercase tracking-widest mb-1">Verfügbar</div>
                    <div class="font-dark-display font-black text-3xl text-dark-tuerkis">€ 49.350</div>
                    <div class="flex items-center gap-1 mt-2 text-sm">
                        <span class="font-bold text-dark-tx-2">20%</span>
                        <span class="text-dark-tx-3 ml-1">noch frei</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-dark-tuerkis-dark flex items-center justify-center">
                    <i class="ti ti-coin text-2xl text-dark-tuerkis"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        
        {{-- Chart Section (2 cols) --}}
        <div class="xl:col-span-2 dark-card">
            <div class="dark-card-header">
                <div>
                    <h3 class="font-dark-display font-bold text-lg text-dark-tx">Budget-Verlauf</h3>
                    <p class="text-xs text-dark-tx-3 mt-0.5">Monatliche Ausgaben im Vergleich zum Plan</p>
                </div>
                <div class="dark-tabs">
                    <div class="dark-tab active">2025</div>
                    <div class="dark-tab">2024</div>
                </div>
            </div>
            <div class="dark-card-body">
                <canvas id="budgetChart" height="280"></canvas>
            </div>
        </div>

        {{-- Donut Chart (1 col) --}}
        <div class="dark-card">
            <div class="dark-card-header">
                <div>
                    <h3 class="font-dark-display font-bold text-lg text-dark-tx">Nach Kategorie</h3>
                    <p class="text-xs text-dark-tx-3 mt-0.5">Verteilung der Ausgaben</p>
                </div>
            </div>
            <div class="dark-card-body flex flex-col items-center">
                <canvas id="categoryChart" height="200"></canvas>
                <div class="grid grid-cols-2 gap-x-6 gap-y-2 mt-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-dark-tuerkis"></span>
                        <span class="text-dark-tx-2">Schulungen</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-dark-gelb"></span>
                        <span class="text-dark-tx-2">Konferenzen</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-dark-st-done"></span>
                        <span class="text-dark-tx-2">Zertifikate</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-dark-st-quiz"></span>
                        <span class="text-dark-tx-2">Sonstiges</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team Budget Table --}}
    <div class="dark-card mb-8">
        <div class="dark-card-header">
            <div>
                <h3 class="font-dark-display font-bold text-lg text-dark-tx">Team-Budgets</h3>
                <p class="text-xs text-dark-tx-3 mt-0.5">Übersicht aller Teams und deren Budgetnutzung</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="dark-sidebar-search" style="margin: 0; width: 200px;">
                    <i class="ti ti-search"></i>
                    <input type="text" placeholder="Team suchen...">
                </div>
                <button class="dark-btn-secondary">
                    <i class="ti ti-download"></i>
                    Export
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="dark-table">
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Budget</th>
                        <th>Verbraucht</th>
                        <th>Geplant</th>
                        <th>Verfügbar</th>
                        <th>Fortschritt</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold text-dark-tx">Account Management</td>
                        <td>€ 45.000</td>
                        <td>€ 28.500</td>
                        <td>€ 8.200</td>
                        <td class="text-dark-tuerkis font-bold">€ 8.300</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-done rounded-full" style="width: 63%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">63%</span>
                            </div>
                        </td>
                        <td><span class="dark-badge-level">Im Plan</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-dark-tx">Tech / Development</td>
                        <td>€ 68.000</td>
                        <td>€ 52.400</td>
                        <td>€ 12.000</td>
                        <td class="text-dark-st-quiz font-bold">€ 3.600</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-quiz rounded-full" style="width: 77%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">77%</span>
                            </div>
                        </td>
                        <td><span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-dark-st-quiz/15 text-dark-st-quiz">Kritisch</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-dark-tx">SEA</td>
                        <td>€ 35.000</td>
                        <td>€ 15.200</td>
                        <td>€ 9.800</td>
                        <td class="text-dark-tuerkis font-bold">€ 10.000</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-tuerkis rounded-full" style="width: 43%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">43%</span>
                            </div>
                        </td>
                        <td><span class="dark-badge-level">Im Plan</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-dark-tx">SEO / Content</td>
                        <td>€ 42.000</td>
                        <td>€ 18.750</td>
                        <td>€ 15.200</td>
                        <td class="text-dark-tuerkis font-bold">€ 8.050</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-gelb rounded-full" style="width: 45%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">45%</span>
                            </div>
                        </td>
                        <td><span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-dark-gelb/15 text-dark-gelb">Geplant</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-dark-tx">Design / UX</td>
                        <td>€ 28.000</td>
                        <td>€ 8.400</td>
                        <td>€ 6.000</td>
                        <td class="text-dark-tuerkis font-bold">€ 13.600</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-st-done rounded-full" style="width: 30%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">30%</span>
                            </div>
                        </td>
                        <td><span class="dark-badge-level">Im Plan</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-dark-tx">HR / People</td>
                        <td>€ 27.000</td>
                        <td>€ 4.200</td>
                        <td>€ 17.000</td>
                        <td class="text-dark-tuerkis font-bold">€ 5.800</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-dark-line rounded-full overflow-hidden">
                                    <div class="h-full bg-dark-tuerkis rounded-full" style="width: 16%"></div>
                                </div>
                                <span class="text-xs font-bold text-dark-tx-2 w-10">16%</span>
                            </div>
                        </td>
                        <td><span class="dark-badge-level">Im Plan</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Row: Recent Activity + Quick Stats --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        {{-- Recent Activity --}}
        <div class="xl:col-span-2 dark-card">
            <div class="dark-card-header">
                <h3 class="font-dark-display font-bold text-lg text-dark-tx">Letzte Buchungen</h3>
                <a href="#" class="text-sm text-dark-tuerkis font-bold hover:text-dark-tuerkis-hover">Alle anzeigen →</a>
            </div>
            <div class="dark-card-body space-y-4">
                {{-- Activity Item 1 --}}
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-dark-card-hover transition-colors">
                    <div class="w-10 h-10 rounded-full bg-dark-tuerkis-dark flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-school text-dark-tuerkis"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-dark-tx truncate">Google Ads Zertifizierung</div>
                        <div class="text-xs text-dark-tx-3">Max Mustermann · SEA · vor 2 Stunden</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-dark-tx">€ 450</div>
                        <div class="text-xs text-dark-st-done font-bold">Gebucht</div>
                    </div>
                </div>

                {{-- Activity Item 2 --}}
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-dark-card-hover transition-colors">
                    <div class="w-10 h-10 rounded-full bg-dark-gelb/15 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-calendar-event text-dark-gelb"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-dark-tx truncate">OMR Festival 2025</div>
                        <div class="text-xs text-dark-tx-3">Lisa Schmidt · Account Mgmt · vor 5 Stunden</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-dark-tx">€ 1.200</div>
                        <div class="text-xs text-dark-gelb font-bold">Geplant</div>
                    </div>
                </div>

                {{-- Activity Item 3 --}}
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-dark-card-hover transition-colors">
                    <div class="w-10 h-10 rounded-full bg-dark-st-done/15 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-certificate text-dark-st-done"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-dark-tx truncate">Scrum Master PSM I</div>
                        <div class="text-xs text-dark-tx-3">Tom Weber · Tech · vor 1 Tag</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-dark-tx">€ 890</div>
                        <div class="text-xs text-dark-st-done font-bold">Abgeschlossen</div>
                    </div>
                </div>

                {{-- Activity Item 4 --}}
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-dark-card-hover transition-colors">
                    <div class="w-10 h-10 rounded-full bg-dark-tuerkis-dark flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-book text-dark-tuerkis"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-dark-tx truncate">Leadership Workshop</div>
                        <div class="text-xs text-dark-tx-3">Sarah Koch · HR · vor 2 Tagen</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-dark-tx">€ 2.400</div>
                        <div class="text-xs text-dark-st-done font-bold">Gebucht</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="dark-card">
            <div class="dark-card-header">
                <h3 class="font-dark-display font-bold text-lg text-dark-tx">Schnellübersicht</h3>
            </div>
            <div class="dark-card-body space-y-5">
                {{-- Stat 1 --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-dark-tx-2">Mitarbeiter mit Budget</span>
                        <span class="font-bold text-dark-tx">42 / 48</span>
                    </div>
                    <div class="h-2 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-tuerkis rounded-full" style="width: 87%"></div>
                    </div>
                </div>

                {{-- Stat 2 --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-dark-tx-2">Aktive Schulungen</span>
                        <span class="font-bold text-dark-tx">23</span>
                    </div>
                    <div class="h-2 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-st-done rounded-full" style="width: 65%"></div>
                    </div>
                </div>

                {{-- Stat 3 --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-dark-tx-2">Offene Anfragen</span>
                        <span class="font-bold text-dark-st-quiz">7</span>
                    </div>
                    <div class="h-2 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-st-quiz rounded-full" style="width: 20%"></div>
                    </div>
                </div>

                {{-- Stat 4 --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-dark-tx-2">Abgeschlossen Q1</span>
                        <span class="font-bold text-dark-tx">156</span>
                    </div>
                    <div class="h-2 bg-dark-line rounded-full overflow-hidden">
                        <div class="h-full bg-dark-gelb rounded-full" style="width: 78%"></div>
                    </div>
                </div>

                <div class="pt-4 border-t border-dark-line">
                    <div class="flex items-center justify-between text-sm">
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
            // Dark theme colors
            const colors = {
                tuerkis: '#00B3C7',
                tuerkisLight: 'rgba(0, 179, 199, 0.2)',
                gelb: '#F9EE80',
                gelbLight: 'rgba(249, 238, 128, 0.2)',
                done: '#3DD68C',
                quiz: '#FF8A3D',
                line: '#2E2E2E',
                tx2: '#A6A6A6',
                tx3: '#6E6E6E',
            };

            // Budget Chart (Bar + Line)
            const budgetCtx = document.getElementById('budgetChart').getContext('2d');
            new Chart(budgetCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
                    datasets: [
                        {
                            label: 'Ausgaben',
                            data: [18500, 22300, 19800, 25400, 21200, 20100, null, null, null, null, null, null],
                            backgroundColor: colors.tuerkis,
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Geplant',
                            data: [null, null, null, null, null, null, 23000, 21000, 24000, 22000, 19000, 25000],
                            backgroundColor: colors.tuerkisLight,
                            borderColor: colors.tuerkis,
                            borderWidth: 2,
                            borderDash: [5, 5],
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Budget-Linie',
                            type: 'line',
                            data: [20400, 20400, 20400, 20400, 20400, 20400, 20400, 20400, 20400, 20400, 20400, 20400],
                            borderColor: colors.gelb,
                            borderWidth: 2,
                            borderDash: [8, 4],
                            pointRadius: 0,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: colors.tx2,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                font: { family: 'Lato', weight: 'bold', size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1E1E1E',
                            borderColor: colors.line,
                            borderWidth: 1,
                            titleColor: '#fff',
                            bodyColor: colors.tx2,
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { family: 'Mulish', weight: 'bold' },
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': € ' + (ctx.raw ? ctx.raw.toLocaleString('de-DE') : '-')
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: colors.line, drawBorder: false },
                            ticks: { color: colors.tx3, font: { family: 'Lato', weight: 'bold' } }
                        },
                        y: {
                            grid: { color: colors.line, drawBorder: false },
                            ticks: {
                                color: colors.tx3,
                                font: { family: 'Lato', weight: 'bold' },
                                callback: v => '€ ' + (v/1000) + 'k'
                            }
                        }
                    }
                }
            });

            // Category Donut Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Schulungen', 'Konferenzen', 'Zertifikate', 'Sonstiges'],
                    datasets: [{
                        data: [45, 25, 20, 10],
                        backgroundColor: [colors.tuerkis, colors.gelb, colors.done, colors.quiz],
                        borderWidth: 0,
                        spacing: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1E1E1E',
                            borderColor: colors.line,
                            borderWidth: 1,
                            titleColor: '#fff',
                            bodyColor: colors.tx2,
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: ctx => ctx.label + ': ' + ctx.raw + '%'
                            }
                        }
                    }
                }
            });

            // Tab interactions
            document.querySelectorAll('.dark-tab').forEach(t => {
                t.addEventListener('click', () => {
                    t.closest('.dark-tabs').querySelectorAll('.dark-tab').forEach(x => x.classList.remove('active'));
                    t.classList.add('active');
                });
            });
        });
    </script>
    @endpush
</x-layouts.dark>
