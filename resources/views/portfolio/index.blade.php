<x-app-layout>
    @section('page-title', 'Digitale Mappe')

    <div class="space-y-6" x-data="portfolioPreview()">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Digitale Mappe</h1>
                <p class="text-surface-500 mt-1">Lade Bilder und Scans deiner Arbeiten hoch.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Upload Form --}}
            <div>
                <x-card title="Neue Datei hochladen">
                    <form method="POST" action="{{ route('portfolio.store') }}" enctype="multipart/form-data" class="space-y-4"
                          x-data="{ fileName: '', dragover: false }">
                        @csrf

                        <div>
                            <label class="label label-required">Modul zuordnen</label>
                            <select name="module_id" class="select-field" required>
                                <option value="">Modul wählen...</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}" {{ old('module_id') == $module->id ? 'selected' : '' }}>
                                    {{ $module->title }}
                                </option>
                                @endforeach
                            </select>
                            @error('module_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Datei</label>
                            <div class="file-upload"
                                 :class="{ 'file-upload-active': dragover }"
                                 @dragover.prevent="dragover = true"
                                 @dragleave.prevent="dragover = false"
                                 @drop.prevent="dragover = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name || ''">
                                <input type="file" name="file" required accept="image/*,.pdf" class="hidden" x-ref="fileInput"
                                       @change="fileName = $event.target.files[0]?.name || ''">
                                <div @click="$refs.fileInput.click()" class="cursor-pointer text-center">
                                    <svg class="file-upload-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="file-upload-text" x-show="!fileName">Klicke oder ziehe Datei hierher</p>
                                    <p class="file-upload-text text-brand-primary font-medium" x-show="fileName" x-text="fileName"></p>
                                    <p class="file-upload-hint">JPG, PNG, GIF, WebP, PDF &middot; Max. 10 MB</p>
                                </div>
                            </div>
                            @error('file') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Notizen</label>
                            <textarea name="notes" class="input-field" rows="3" placeholder="Optionale Beschreibung..." maxlength="500">{{ old('notes') }}</textarea>
                            @error('notes') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Hochladen
                        </button>
                    </form>
                </x-card>
            </div>

            {{-- Uploads List --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Search Bar --}}
                <div class="card-tool">
                    <div class="card-tool-body !py-3">
                        <form method="GET" action="{{ route('portfolio.index') }}" class="flex gap-2">
                            <div class="input-group flex-1">
                                <svg class="input-group-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input
                                    type="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    class="input-field"
                                    placeholder="Dateiname, Modul oder Notiz suchen..."
                                >
                            </div>
                            <button type="submit" class="btn-secondary">Suchen</button>
                            @if(request('search'))
                            <a href="{{ route('portfolio.index') }}" class="btn-ghost">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                            @endif
                        </form>
                        @if(request('search'))
                        <p class="text-xs text-surface-500 mt-2">
                            {{ $uploads->count() }} Ergebnis{{ $uploads->count() !== 1 ? 'se' : '' }} für
                            <span class="font-medium text-brand-dark">&bdquo;{{ request('search') }}&ldquo;</span>
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Training Materials (from attended modules) --}}
                @if($groupedMaterials->isNotEmpty())
                    @foreach($groupedMaterials as $moduleId => $moduleMaterials)
                        @php $matModule = $modules->firstWhere('id', $moduleId); @endphp
                        <div class="card-tool" x-data="{ collapsed: false }">
                            <div class="card-tool-header cursor-pointer select-none" @click="collapsed = !collapsed">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-brand-primary flex-shrink-0 transition-transform" :class="{ '-rotate-90': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div>
                                        <h2 class="font-semibold text-brand-dark">{{ $matModule?->title ?? 'Unbekanntes Modul' }}</h2>
                                        <p class="text-xs text-surface-500">{{ $moduleMaterials->count() }} Schulungsunterlage{{ $moduleMaterials->count() !== 1 ? 'n' : '' }}</p>
                                    </div>
                                </div>
                                <span class="badge-success text-xs">Schulungsunterlagen</span>
                            </div>
                            <div class="card-tool-body !p-0" x-show="!collapsed" x-collapse>
                                <div class="overflow-x-auto">
                                    <table class="table-tool">
                                        <thead>
                                            <tr>
                                                <th>Datei</th>
                                                <th>Größe</th>
                                                <th>Datum</th>
                                                <th class="text-right">Download</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($moduleMaterials as $material)
                                            <tr>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        @if(str_starts_with($material->mime_type, 'image/'))
                                                        <svg class="w-5 h-5 text-ui-info flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        @else
                                                        <svg class="w-5 h-5 text-ui-error flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                        </svg>
                                                        @endif
                                                        <div>
                                                            <span class="font-medium text-brand-dark text-sm truncate max-w-[250px] block">{{ $material->original_filename }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-sm text-surface-500">{{ number_format($material->file_size / 1024, 0) }} KB</td>
                                                <td class="text-sm text-surface-500">{{ $material->created_at->format('d.m.Y H:i') }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('portfolio.material.download', $material) }}" class="btn-secondary btn-xs" title="Herunterladen">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Own Uploads grouped by Module --}}
                @if($groupedUploads->isNotEmpty())
                    @foreach($groupedUploads as $moduleId => $moduleUploads)
                        @php $module = $modules->firstWhere('id', $moduleId); @endphp
                        <div class="card-tool" x-data="{ collapsed: false }">
                            <div class="card-tool-header cursor-pointer select-none" @click="collapsed = !collapsed">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-brand-primary flex-shrink-0 transition-transform" :class="{ '-rotate-90': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div>
                                        <h2 class="font-semibold text-brand-dark">{{ $module?->title ?? 'Unbekanntes Modul' }}</h2>
                                        <p class="text-xs text-surface-500">{{ $moduleUploads->count() }} {{ $moduleUploads->count() === 1 ? 'Datei' : 'Dateien' }}</p>
                                    </div>
                                </div>
                                <span class="badge-info">{{ $module?->methodLabel() ?? '–' }}</span>
                            </div>
                            <div class="card-tool-body !p-0" x-show="!collapsed" x-collapse>
                                <div class="overflow-x-auto">
                                    <table class="table-tool">
                                        <thead>
                                            <tr>
                                                <th>Datei</th>
                                                <th>Größe</th>
                                                <th>Datum</th>
                                                <th class="text-right">Aktionen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($moduleUploads as $upload)
                                            <tr>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        @if(str_starts_with($upload->mime_type, 'image/'))
                                                        <svg class="w-5 h-5 text-ui-info flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        @else
                                                        <svg class="w-5 h-5 text-ui-error flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                        </svg>
                                                        @endif
                                                        <div>
                                                            <button
                                                                @click="openPreview('{{ route('portfolio.preview', $upload) }}', '{{ $upload->mime_type }}', '{{ route('portfolio.download', $upload) }}')"
                                                                class="font-medium text-brand-primary hover:text-brand-primary-hover text-sm truncate max-w-[250px] text-left cursor-pointer hover:underline block"
                                                            >
                                                                {{ $upload->original_filename }}
                                                            </button>
                                                            @if($upload->notes)
                                                            <div class="text-xs text-surface-500 truncate max-w-[250px]">{{ $upload->notes }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-sm text-surface-500">{{ $upload->fileSizeFormatted() }}</td>
                                                <td class="text-sm text-surface-500">{{ $upload->created_at->format('d.m.Y H:i') }}</td>
                                                <td class="text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a href="{{ route('portfolio.download', $upload) }}" class="btn-secondary btn-xs" title="Herunterladen">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                            </svg>
                                                        </a>
                                                        <form method="POST" action="{{ route('portfolio.destroy', $upload) }}" class="inline"
                                                              onsubmit="return confirm('Datei wirklich löschen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-danger btn-xs" title="Löschen">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if($groupedUploads->isEmpty() && $groupedMaterials->isEmpty())
                    <div class="card-tool">
                        <div class="card-tool-body">
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                                <div class="empty-state-title">
                                    {{ request('search') ? 'Keine Ergebnisse' : 'Noch keine Uploads' }}
                                </div>
                                <div class="empty-state-description">
                                    {{ request('search')
                                        ? 'Für deine Suche wurden keine Dateien gefunden.'
                                        : 'Lade dein erstes Bild oder Dokument über das Formular links hoch.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Preview Modal --}}
        <template x-teleport="body">
            <div x-show="open" class="relative z-50" style="display: none;">
                {{-- Backdrop --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="closePreview()"
                    class="fixed inset-0 z-40 bg-black/60"
                ></div>

                {{-- Modal Content --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @keydown.escape.window="closePreview()"
                    class="fixed z-50 top-1/2 left-1/2 w-full max-w-4xl bg-white rounded-lg shadow-modal"
                    style="transform: translate(-50%, -50%);"
                >
                    {{-- Header --}}
                    <div class="modal-header">
                        <h3 class="modal-title truncate pr-4" x-text="filename"></h3>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a
                                :href="downloadUrl"
                                class="btn-secondary btn-sm"
                                x-show="downloadUrl"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download
                            </a>
                            <button @click="closePreview()" class="btn-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-4 flex items-center justify-center bg-surface-100 rounded-b-lg" style="min-height: 300px; max-height: 75vh; overflow: auto;">
                        {{-- Loading --}}
                        <div x-show="loading" class="flex flex-col items-center gap-3">
                            <span class="spinner-md text-brand-primary"></span>
                            <span class="text-sm text-surface-500">Datei wird geladen...</span>
                        </div>

                        {{-- Error --}}
                        <div x-show="error && !loading" class="flex flex-col items-center gap-3 text-center">
                            <svg class="w-12 h-12 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="text-sm text-surface-600">Vorschau konnte nicht geladen werden.</span>
                        </div>

                        {{-- Image Preview --}}
                        <img
                            x-show="!loading && !error && isImage"
                            :src="previewUrl"
                            :alt="filename"
                            class="max-w-full max-h-[70vh] object-contain rounded"
                            x-ref="previewImage"
                        >

                        {{-- PDF Preview --}}
                        <iframe
                            x-show="!loading && !error && isPdf"
                            :src="previewUrl"
                            class="w-full rounded border border-surface-200"
                            style="height: 70vh;"
                            x-ref="previewPdf"
                        ></iframe>

                        {{-- Other File Types --}}
                        <div x-show="!loading && !error && !isImage && !isPdf && previewUrl" class="flex flex-col items-center gap-4 text-center py-8">
                            <svg class="w-16 h-16 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-surface-600">Vorschau nicht verfügbar.</p>
                            <a :href="downloadUrl" class="btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Datei herunterladen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
    function portfolioPreview() {
        return {
            open: false,
            loading: false,
            error: false,
            previewUrl: '',
            downloadUrl: '',
            filename: '',
            mimeType: '',

            get isImage() {
                return this.mimeType.startsWith('image/');
            },

            get isPdf() {
                return this.mimeType === 'application/pdf';
            },

            async openPreview(endpoint, mimeType, downloadEndpoint) {
                this.open = true;
                this.loading = true;
                this.error = false;
                this.previewUrl = '';
                this.downloadUrl = downloadEndpoint;
                this.filename = '';
                this.mimeType = mimeType;
                document.body.style.overflow = 'hidden';

                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error('Fehler beim Laden');

                    const data = await response.json();
                    this.previewUrl = data.url;
                    this.filename = data.filename;

                    if (this.isImage) {
                        const img = new Image();
                        img.onload = () => { this.loading = false; };
                        img.onerror = () => { this.loading = false; this.error = true; };
                        img.src = data.url;
                    } else {
                        this.loading = false;
                    }
                } catch (e) {
                    this.loading = false;
                    this.error = true;
                }
            },

            closePreview() {
                this.open = false;
                this.previewUrl = '';
                this.downloadUrl = '';
                this.filename = '';
                this.mimeType = '';
                document.body.style.overflow = '';
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
