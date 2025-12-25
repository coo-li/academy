<x-app-layout>
    @section('page-title', 'Dashboard')
    
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Dashboard</h1>
                <p class="text-surface-500 mt-1">Willkommen zurück, {{ Auth::user()->name }}!</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Export
                </x-button>
                <x-button variant="primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Neu erstellen
                </x-button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <div class="stat-label">Gesamtumsatz</div>
                <div class="stat-value">€124.520</div>
                <div class="stat-trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span>+12.5% gegenüber Vormonat</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Aktive Nutzer</div>
                <div class="stat-value">2.847</div>
                <div class="stat-trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span>+8.2%</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Offene Aufgaben</div>
                <div class="stat-value">24</div>
                <div class="stat-trend-down">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span>-5 seit gestern</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Erfolgsquote</div>
                <div class="stat-value">94.2%</div>
                <div class="stat-trend-neutral">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                    </svg>
                    <span>Keine Änderung</span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart Card -->
            <div class="lg:col-span-2">
                <x-card title="Übersicht">
                    <x-slot:header>
                        <div class="flex items-center gap-2">
                            <select class="select-field w-auto text-sm">
                                <option>Letzte 7 Tage</option>
                                <option>Letzte 30 Tage</option>
                                <option>Dieses Jahr</option>
                            </select>
                        </div>
                    </x-slot:header>
                    
                    <div id="dashboard-chart" class="chart-container"></div>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            if (typeof createChart === 'function') {
                                createChart(document.querySelector('#dashboard-chart'), {
                                    chart: {
                                        type: 'area',
                                        height: 320
                                    },
                                    series: [{
                                        name: 'Umsatz',
                                        data: [31, 40, 28, 51, 42, 109, 100]
                                    }],
                                    xaxis: {
                                        categories: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
                                    }
                                });
                            }
                        });
                    </script>
                </x-card>
            </div>

            <!-- Recent Activity -->
            <x-card title="Letzte Aktivitäten">
                <div class="timeline">
                    <div class="timeline-item">
                        <span class="timeline-marker-success"></span>
                        <div class="timeline-content">
                            <div class="timeline-title">Neuer Benutzer registriert</div>
                            <div class="timeline-time">Vor 5 Minuten</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-marker-primary"></span>
                        <div class="timeline-content">
                            <div class="timeline-title">Projekt aktualisiert</div>
                            <div class="timeline-time">Vor 15 Minuten</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-marker"></span>
                        <div class="timeline-content">
                            <div class="timeline-title">Aufgabe abgeschlossen</div>
                            <div class="timeline-time">Vor 1 Stunde</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-marker-primary"></span>
                        <div class="timeline-content">
                            <div class="timeline-title">Neues Feature deployed</div>
                            <div class="timeline-time">Vor 2 Stunden</div>
                        </div>
                    </div>
                </div>
                
                <x-slot:footer>
                    <a href="#" class="text-brand-primary text-sm font-medium hover:underline">
                        Alle Aktivitäten anzeigen →
                    </a>
                </x-slot:footer>
            </x-card>
        </div>

        <!-- Quick Actions -->
        <x-card title="Schnellzugriff">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('demo') }}" class="panel-padded hover:bg-surface-50 transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-brand-light flex items-center justify-center mb-3 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                        <svg class="w-5 h-5 text-brand-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-brand-dark">UI Demo</h4>
                    <p class="text-xs text-surface-500 mt-1">Alle Komponenten ansehen</p>
                </a>
                
                <a href="{{ route('profile.edit') }}" class="panel-padded hover:bg-surface-50 transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-brand-light flex items-center justify-center mb-3 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                        <svg class="w-5 h-5 text-brand-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-brand-dark">Profil</h4>
                    <p class="text-xs text-surface-500 mt-1">Einstellungen bearbeiten</p>
                </a>
                
                <a href="#" class="panel-padded hover:bg-surface-50 transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-brand-light flex items-center justify-center mb-3 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                        <svg class="w-5 h-5 text-brand-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-brand-dark">Berichte</h4>
                    <p class="text-xs text-surface-500 mt-1">Analysen und Reports</p>
                </a>
                
                <a href="#" class="panel-padded hover:bg-surface-50 transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-brand-light flex items-center justify-center mb-3 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                        <svg class="w-5 h-5 text-brand-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-brand-dark">Einstellungen</h4>
                    <p class="text-xs text-surface-500 mt-1">System konfigurieren</p>
                </a>
            </div>
        </x-card>
    </div>
</x-app-layout>
