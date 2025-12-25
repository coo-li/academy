<x-app-layout>
    @section('page-title', 'UI-Kit Demo')
    
    <div class="space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">UI-Kit Demo</h1>
                <p class="text-surface-500 mt-1">Alle Komponenten des trafficdesign Tool UI-Kits auf einen Blick</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="secondary" size="sm" onclick="notify('Info Toast!', 'info')">Info Toast</x-button>
                <x-button variant="success" size="sm" onclick="notify('Erfolg!', 'success')">Success Toast</x-button>
                <x-button variant="danger" size="sm" onclick="notify('Fehler!', 'error')">Error Toast</x-button>
            </div>
        </div>

        <!-- Buttons Section -->
        <x-card title="Buttons">
            <x-slot:header>
                <span class="badge-primary">Interaktiv</span>
            </x-slot:header>
            
            <div class="space-y-4">
                <!-- Button Variants -->
                <div>
                    <p class="text-sm font-medium text-surface-500 mb-2">Varianten</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button variant="primary">Primary</x-button>
                        <x-button variant="secondary">Secondary</x-button>
                        <x-button variant="ghost">Ghost</x-button>
                        <x-button variant="danger">Danger</x-button>
                        <x-button variant="success">Success</x-button>
                    </div>
                </div>
                
                <!-- Button Sizes -->
                <div>
                    <p class="text-sm font-medium text-surface-500 mb-2">Größen</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button variant="primary" size="xs">Extra Small</x-button>
                        <x-button variant="primary" size="sm">Small</x-button>
                        <x-button variant="primary">Default</x-button>
                        <x-button variant="primary" size="lg">Large</x-button>
                    </div>
                </div>
                
                <!-- Buttons with Icons -->
                <div>
                    <p class="text-sm font-medium text-surface-500 mb-2">Mit Icons</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button variant="primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Hinzufügen
                        </x-button>
                        <x-button variant="secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Upload
                        </x-button>
                        <x-button variant="icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </x-button>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Badges Section -->
        <x-card title="Badges & Status">
            <div class="flex flex-wrap items-center gap-3">
                <x-badge type="primary">Primary</x-badge>
                <x-badge type="success">Aktiv</x-badge>
                <x-badge type="warning">Ausstehend</x-badge>
                <x-badge type="error">Fehler</x-badge>
                <x-badge type="info">Neu</x-badge>
                <x-badge type="neutral">Archiviert</x-badge>
            </div>
        </x-card>

        <!-- Alerts Section -->
        <x-card title="Alerts & Benachrichtigungen">
            <div class="space-y-3">
                <x-alert type="success" title="Erfolg!">
                    Die Aktion wurde erfolgreich abgeschlossen.
                </x-alert>
                
                <x-alert type="info" title="Information">
                    Hier ist ein hilfreicher Hinweis für Sie.
                </x-alert>
                
                <x-alert type="warning" title="Warnung">
                    Bitte beachten Sie diese wichtige Information.
                </x-alert>
                
                <x-alert type="error" title="Fehler" dismissible>
                    Ein Fehler ist aufgetreten. Klicken Sie zum Schließen.
                </x-alert>
            </div>
        </x-card>

        <!-- Form Inputs Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Form Inputs">
                <div class="space-y-4">
                    <x-input 
                        label="Name" 
                        name="demo_name" 
                        placeholder="Max Mustermann"
                        required
                    />
                    
                    <x-input 
                        type="email" 
                        label="E-Mail" 
                        name="demo_email" 
                        placeholder="max@beispiel.de"
                        help="Wir werden Ihre E-Mail niemals teilen."
                    />
                    
                    <x-input 
                        label="Fehlerfeld" 
                        name="demo_error" 
                        placeholder="Fehlerhafter Wert"
                        error="Dieses Feld ist ungültig."
                    />
                    
                    <x-input 
                        type="search" 
                        label="Mit Icon" 
                        name="demo_search" 
                        placeholder="Suchen..."
                        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z\"></path></svg>'"
                    />
                </div>
            </x-card>

            <x-card title="Select & Optionen">
                <div class="space-y-4">
                    <x-select 
                        label="Land" 
                        name="demo_country"
                        :options="['de' => 'Deutschland', 'at' => 'Österreich', 'ch' => 'Schweiz']"
                    />
                    
                    <x-select 
                        label="Status" 
                        name="demo_status"
                        required
                    >
                        <option value="active">Aktiv</option>
                        <option value="pending">Ausstehend</option>
                        <option value="inactive">Inaktiv</option>
                    </x-select>
                    
                    <!-- Checkbox -->
                    <div>
                        <label class="label">Optionen</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="checkbox-field" checked>
                                <span class="text-sm">Newsletter abonnieren</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="checkbox-field">
                                <span class="text-sm">AGB akzeptieren</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Toggle Switch -->
                    <div x-data="{ enabled: true }">
                        <label class="label">Benachrichtigungen</label>
                        <button 
                            @click="enabled = !enabled"
                            :data-checked="enabled"
                            class="toggle"
                        >
                            <span class="toggle-knob"></span>
                        </button>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Table Section -->
        <x-card title="Tabellen">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <x-button variant="primary" size="sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Neu
                    </x-button>
                </div>
            </x-slot:header>
            
            <x-table striped>
                <x-slot:head>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Erstellt</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </x-slot:head>
                
                <tr>
                    <td class="font-medium">#001</td>
                    <td>Max Mustermann</td>
                    <td>max@beispiel.de</td>
                    <td><x-badge type="success">Aktiv</x-badge></td>
                    <td>24.12.2025</td>
                    <td class="text-right">
                        <x-button variant="ghost" size="xs">Bearbeiten</x-button>
                    </td>
                </tr>
                <tr>
                    <td class="font-medium">#002</td>
                    <td>Anna Schmidt</td>
                    <td>anna@beispiel.de</td>
                    <td><x-badge type="warning">Ausstehend</x-badge></td>
                    <td>23.12.2025</td>
                    <td class="text-right">
                        <x-button variant="ghost" size="xs">Bearbeiten</x-button>
                    </td>
                </tr>
                <tr>
                    <td class="font-medium">#003</td>
                    <td>Thomas Müller</td>
                    <td>thomas@beispiel.de</td>
                    <td><x-badge type="error">Inaktiv</x-badge></td>
                    <td>22.12.2025</td>
                    <td class="text-right">
                        <x-button variant="ghost" size="xs">Bearbeiten</x-button>
                    </td>
                </tr>
            </x-table>
            
            <x-slot:footer>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500">3 Einträge</span>
                    <div class="pagination">
                        <button class="pagination-item-disabled">←</button>
                        <button class="pagination-item-active">1</button>
                        <button class="pagination-item">2</button>
                        <button class="pagination-item">3</button>
                        <button class="pagination-item">→</button>
                    </div>
                </div>
            </x-slot:footer>
        </x-card>

        <!-- Modal Section -->
        <x-card title="Modals">
            <div class="flex flex-wrap gap-2">
                <x-modal name="example-modal" title="Beispiel Modal" maxWidth="md">
                    <x-slot:trigger>
                        <x-button variant="primary">Modal öffnen</x-button>
                    </x-slot:trigger>
                    
                    <p class="text-surface-600">
                        Dies ist ein Beispiel-Modal mit dem trafficdesign UI Kit. 
                        Modals können für Bestätigungen, Formulare oder andere Inhalte verwendet werden.
                    </p>
                    
                    <x-slot:footer>
                        <x-button variant="secondary" @click="open = false">Abbrechen</x-button>
                        <x-button variant="primary" @click="open = false; notify('Aktion bestätigt!', 'success')">Bestätigen</x-button>
                    </x-slot:footer>
                </x-modal>
                
                <x-modal name="delete-modal" title="Löschen bestätigen" maxWidth="sm">
                    <x-slot:trigger>
                        <x-button variant="danger">Löschen Modal</x-button>
                    </x-slot:trigger>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-ui-error-light mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-6 h-6 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <p class="text-surface-600">
                            Sind Sie sicher, dass Sie diesen Eintrag löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </div>
                    
                    <x-slot:footer>
                        <x-button variant="secondary" @click="open = false">Abbrechen</x-button>
                        <x-button variant="danger" @click="open = false; notify('Eintrag gelöscht!', 'error')">Löschen</x-button>
                    </x-slot:footer>
                </x-modal>
            </div>
        </x-card>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <div class="stat-label">Gesamtumsatz</div>
                <div class="stat-value">€124.520</div>
                <div class="stat-trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span>+12.5%</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Besucher</div>
                <div class="stat-value">45.821</div>
                <div class="stat-trend-up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span>+8.2%</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Bestellungen</div>
                <div class="stat-value">1.284</div>
                <div class="stat-trend-down">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span>-3.1%</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Conversion Rate</div>
                <div class="stat-value">3.24%</div>
                <div class="stat-trend-neutral">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                    </svg>
                    <span>0.0%</span>
                </div>
            </div>
        </div>

        <!-- Loading States & Empty States -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Loading States">
                <div class="space-y-4">
                    <!-- Spinners -->
                    <div>
                        <p class="text-sm font-medium text-surface-500 mb-2">Spinners</p>
                        <div class="flex items-center gap-4">
                            <span class="spinner-sm text-brand-primary"></span>
                            <span class="spinner-md text-brand-primary"></span>
                            <span class="spinner-lg text-brand-primary"></span>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div>
                        <p class="text-sm font-medium text-surface-500 mb-2">Progress Bar</p>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: 65%"></div>
                        </div>
                    </div>
                    
                    <!-- Skeleton -->
                    <div>
                        <p class="text-sm font-medium text-surface-500 mb-2">Skeleton</p>
                        <div class="flex items-center gap-3">
                            <div class="skeleton-avatar w-10 h-10"></div>
                            <div class="flex-1 space-y-2">
                                <div class="skeleton-title w-1/2"></div>
                                <div class="skeleton-text w-3/4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Empty State">
                <div class="empty-state py-8">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    </svg>
                    <div class="empty-state-title">Keine Daten vorhanden</div>
                    <div class="empty-state-description">
                        Es wurden noch keine Einträge erstellt. Klicken Sie auf den Button unten, um zu beginnen.
                    </div>
                    <x-button variant="primary" class="mt-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Ersten Eintrag erstellen
                    </x-button>
                </div>
            </x-card>
        </div>

        <!-- Chart Container -->
        <x-card title="Chart Container (ApexCharts)">
            <x-slot:header>
                <x-badge type="info">ApexCharts integriert</x-badge>
            </x-slot:header>
            
            <div id="demo-chart" class="chart-container"></div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof createChart === 'function') {
                        createChart(document.querySelector('#demo-chart'), {
                            chart: {
                                type: 'area',
                                height: 320
                            },
                            series: [{
                                name: 'Umsatz',
                                data: [31, 40, 28, 51, 42, 109, 100]
                            }, {
                                name: 'Besucher',
                                data: [11, 32, 45, 32, 34, 52, 41]
                            }],
                            xaxis: {
                                categories: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
                            }
                        });
                    }
                });
            </script>
        </x-card>

        <!-- Avatars & Tabs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Avatars">
                <div class="space-y-4">
                    <!-- Avatar Sizes -->
                    <div>
                        <p class="text-sm font-medium text-surface-500 mb-2">Größen</p>
                        <div class="flex items-center gap-3">
                            <div class="avatar-xs"><span>XS</span></div>
                            <div class="avatar-sm"><span>SM</span></div>
                            <div class="avatar-md"><span>MD</span></div>
                            <div class="avatar-lg"><span>LG</span></div>
                            <div class="avatar-xl"><span>XL</span></div>
                        </div>
                    </div>
                    
                    <!-- Avatar with Status -->
                    <div>
                        <p class="text-sm font-medium text-surface-500 mb-2">Mit Status</p>
                        <div class="flex items-center gap-3">
                            <div class="avatar-md">
                                <span>ON</span>
                                <span class="avatar-status-online"></span>
                            </div>
                            <div class="avatar-md">
                                <span>AW</span>
                                <span class="avatar-status-away"></span>
                            </div>
                            <div class="avatar-md">
                                <span>BU</span>
                                <span class="avatar-status-busy"></span>
                            </div>
                            <div class="avatar-md">
                                <span>OF</span>
                                <span class="avatar-status-offline"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Tabs">
                <div x-data="{ activeTab: 'tab1' }">
                    <div class="tabs">
                        <button @click="activeTab = 'tab1'" :class="activeTab === 'tab1' ? 'tab-active' : 'tab'">
                            Übersicht
                        </button>
                        <button @click="activeTab = 'tab2'" :class="activeTab === 'tab2' ? 'tab-active' : 'tab'">
                            Details
                        </button>
                        <button @click="activeTab = 'tab3'" :class="activeTab === 'tab3' ? 'tab-active' : 'tab'">
                            Einstellungen
                        </button>
                    </div>
                    <div class="p-4">
                        <div x-show="activeTab === 'tab1'" x-transition>
                            <h4 class="font-semibold mb-2">Übersicht</h4>
                            <p class="text-surface-600">Dies ist der Inhalt des ersten Tabs.</p>
                        </div>
                        <div x-show="activeTab === 'tab2'" x-transition>
                            <h4 class="font-semibold mb-2">Details</h4>
                            <p class="text-surface-600">Detaillierte Informationen werden hier angezeigt.</p>
                        </div>
                        <div x-show="activeTab === 'tab3'" x-transition>
                            <h4 class="font-semibold mb-2">Einstellungen</h4>
                            <p class="text-surface-600">Konfigurationsoptionen befinden sich hier.</p>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- File Upload -->
        <x-card title="File Upload">
            <div class="file-upload" x-data="{ dragging: false }" 
                 @dragover.prevent="dragging = true" 
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false"
                 :class="{ 'file-upload-active': dragging }">
                <svg class="file-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <div class="file-upload-text">
                    <span class="text-brand-primary font-medium">Klicken zum Hochladen</span> oder Datei hierher ziehen
                </div>
                <div class="file-upload-hint">PNG, JPG oder PDF bis zu 10MB</div>
            </div>
        </x-card>
    </div>
</x-app-layout>

