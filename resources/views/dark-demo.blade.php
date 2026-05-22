<x-layouts.dark title="Meine td-Schulungen">
    @section('breadcrumb')
        Dashboard / <b>Meine td-Schulungen</b>
    @endsection

    {{-- Hero Section --}}
    <section class="dark-hero">
        <div class="dark-hero-left">
            <div class="dark-hero-greet">WILLKOMMEN ZURÜCK, {{ strtoupper(Auth::user()->first_name ?? 'TINA') }}</div>
            <h1>Dein <em>Upskilling</em>-Plan</h1>
            <p class="dark-hero-sub">
                Acht Module bringen dich von Specialist auf das nächste Level.
                Buch dir deinen ersten Termin und leg los.
            </p>
            
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <span class="dark-badge-path">
                    <span>🤝</span> Account Management
                </span>
                <span class="dark-badge-level">Stufe: Specialist</span>
                <span class="dark-badge-next">
                    <i class="ti ti-arrow-up-right"></i> Nächstes Level: Senior
                </span>
            </div>
            
            <div class="dark-stat-row">
                <div class="dark-stat">
                    <div class="dark-stat-value">8</div>
                    <div class="dark-stat-label">Module gesamt</div>
                </div>
                <div class="dark-stat">
                    <div class="dark-stat-value accent">0</div>
                    <div class="dark-stat-label">Eingeschrieben</div>
                </div>
                <div class="dark-stat">
                    <div class="dark-stat-value">0</div>
                    <div class="dark-stat-label">Abgeschlossen</div>
                </div>
                <div class="dark-stat">
                    <div class="dark-stat-value">8</div>
                    <div class="dark-stat-label">Offen</div>
                </div>
            </div>
        </div>

        {{-- Progress Ring --}}
        <div class="dark-ring-wrap">
            <div class="dark-ring">
                <svg width="184" height="184" viewBox="0 0 184 184">
                    <circle cx="92" cy="92" r="80" fill="none" stroke="#262626" stroke-width="14"/>
                    <circle cx="92" cy="92" r="80" fill="none" stroke="#00B3C7" stroke-width="14"
                            stroke-linecap="round" stroke-dasharray="502.4"
                            stroke-dashoffset="502.4" id="ringArc"/>
                </svg>
                <div class="dark-ring-center">
                    <div class="dark-ring-pct" id="ringPct">0<span>%</span></div>
                    <div class="dark-ring-label">ABGESCHLOSSEN</div>
                </div>
            </div>
            <div class="dark-ring-caption">Noch <b>8 Module</b> bis Senior</div>
        </div>
    </section>

    {{-- Module Section --}}
    <div class="dark-section-head">
        <h2>Deine Module</h2>
        <div class="dark-tabs">
            <div class="dark-tab active">Alle <span class="n">8</span></div>
            <div class="dark-tab">Pflicht <span class="n">8</span></div>
            <div class="dark-tab">Wahl <span class="n">0</span></div>
            <div class="dark-tab">Nach Fortschritt</div>
        </div>
    </div>

    {{-- Module Grid --}}
    <div class="dark-grid">
        
        {{-- Module 1 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>How to Strategiemeeting</h3>
            <p>Nach der Teilnahme kannst du Strategiemeetings eigenständig planen und durchführen.</p>
            <div class="dark-flow">
                <div class="dark-step active">
                    <div class="dark-step-dot">1</div>
                    <div class="dark-step-label">Einschreiben</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 2 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Erstgespräche & Pitches</h3>
            <p>Du lernst, wie ein Closer denkt, agiert und Deals souverän abschließt.</p>
            <div class="dark-flow">
                <div class="dark-step active">
                    <div class="dark-step-dot">1</div>
                    <div class="dark-step-label">Termin</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Teilnahme</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">3</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 3 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>TD Sales Prozesse</h3>
            <p>Die internen Sales-Prozesse von trafficdesign, sauber und nachvollziehbar erklärt.</p>
            <div class="dark-flow">
                <div class="dark-step done">
                    <div class="dark-step-dot"><i class="ti ti-check"></i></div>
                    <div class="dark-step-label">Einschreiben</div>
                </div>
                <div class="dark-flow-bar done"></div>
                <div class="dark-step active">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 4 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Delegieren</h3>
            <p>Aufgaben klar abgeben, ohne die Verantwortung aus der Hand zu geben.</p>
            <div class="dark-flow">
                <div class="dark-step active">
                    <div class="dark-step-dot">1</div>
                    <div class="dark-step-label">Einschreiben</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 5 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Umgang mit challenging Kunden</h3>
            <p>Schwierige Kundensituationen souverän und lösungsorientiert steuern.</p>
            <div class="dark-flow">
                <div class="dark-step done">
                    <div class="dark-step-dot"><i class="ti ti-check"></i></div>
                    <div class="dark-step-label">Termin</div>
                </div>
                <div class="dark-flow-bar done"></div>
                <div class="dark-step done">
                    <div class="dark-step-dot"><i class="ti ti-check"></i></div>
                    <div class="dark-step-label">Teilnahme</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step active">
                    <div class="dark-step-dot">3</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 6 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Konfliktmanagement</h3>
            <p>Konflikte früh erkennen, einordnen und konstruktiv auflösen.</p>
            <div class="dark-flow">
                <div class="dark-step active">
                    <div class="dark-step-dot">1</div>
                    <div class="dark-step-label">Termin</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Teilnahme</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">3</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 7 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Leadership im Projekt</h3>
            <p>Teams im Projektkontext klar führen, auch ohne formale Führungsrolle.</p>
            <div class="dark-flow">
                <div class="dark-step active">
                    <div class="dark-step-dot">1</div>
                    <div class="dark-step-label">Einschreiben</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

        {{-- Module 8 --}}
        <article class="dark-module">
            <div class="dark-module-top">
                <span class="dark-path-chip"><span>🤝</span> Account Mgmt</span>
                <span class="dark-pill-pflicht">PFLICHT</span>
                <i class="ti ti-arrow-right dark-module-arrow"></i>
            </div>
            <h3>Gesprächsführung</h3>
            <p>Gespräche strukturiert führen und sicher zum Ergebnis bringen.</p>
            <div class="dark-flow">
                <div class="dark-step done">
                    <div class="dark-step-dot"><i class="ti ti-check"></i></div>
                    <div class="dark-step-label">Termin</div>
                </div>
                <div class="dark-flow-bar done"></div>
                <div class="dark-step active">
                    <div class="dark-step-dot">2</div>
                    <div class="dark-step-label">Teilnahme</div>
                </div>
                <div class="dark-flow-bar"></div>
                <div class="dark-step">
                    <div class="dark-step-dot">3</div>
                    <div class="dark-step-label">Quiz</div>
                </div>
            </div>
        </article>

    </div>

    {{-- Legend --}}
    <div class="dark-legend">
        <div class="dark-legend-item">
            <span class="dark-legend-dot" style="background: #6E6E6E"></span> Offen
        </div>
        <div class="dark-legend-item">
            <span class="dark-legend-dot" style="background: #00B3C7"></span> Termin gebucht
        </div>
        <div class="dark-legend-item">
            <span class="dark-legend-dot" style="background: #F9EE80"></span> Teilgenommen
        </div>
        <div class="dark-legend-item">
            <span class="dark-legend-dot" style="background: #FF8A3D"></span> Quiz offen
        </div>
        <div class="dark-legend-item">
            <span class="dark-legend-dot" style="background: #3DD68C"></span> Abgeschlossen
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const arc = document.getElementById('ringArc');
            const pctEl = document.getElementById('ringPct');
            const demo = 0;
            const circ = 502.4;
            
            setTimeout(() => {
                arc.style.transition = 'stroke-dashoffset 1.1s cubic-bezier(.22,1,.36,1)';
                arc.style.strokeDashoffset = circ - circ * (demo/100);
                pctEl.innerHTML = demo + '<span>%</span>';
            }, 250);

            document.querySelectorAll('.dark-tab').forEach(t => {
                t.addEventListener('click', () => {
                    document.querySelectorAll('.dark-tab').forEach(x => x.classList.remove('active'));
                    t.classList.add('active');
                });
            });
        });
    </script>
    @endpush
</x-layouts.dark>
