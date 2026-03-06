# Academy Features - Kern-Workflows

## 1. Meine Academy (Studenten-Dashboard)

**Route:** `GET /dashboard`  
**Controller:** `AcademyController@dashboard`

### Funktionen
- Anzeige der aktuellen Karrierestufe (Level-Nummer, Titel, Pfad-Name)
- Fortschrittsbalken mit %-Anzeige (abgeschlossene/gesamte Module)
- Stat-Cards: Module gesamt, Abgeschlossen, Eingeschrieben, Offen
- **Zwei Modul-Quellen:** Karrierestufen-Module + direkt zugewiesene Module (zusammengeführt, dedupliziert)
- Modulkarten mit Status-Icons und **3-Stufen-Stepper** im Footer:
  - **Offen** (grau) – nicht eingeschrieben
  - **Gebucht** (primary/blau) – eingeschrieben, warte auf Teilnahme-Bestätigung
  - **Quiz offen** (warning/orange) – Teilnahme bestätigt, Quiz muss noch absolviert werden
  - **Abgeschlossen** (success/grün) – Quiz bestanden
  - **Storniert** (error/rot) – Buchung wurde storniert

### Modul-Workflow (Stepper)
Jede Modulkarte zeigt einen 3-Stufen-Fortschrittsindikator:
1. **Terminbuchung** – User bucht einen Workshop-Termin
2. **Teilnahme** – Lehrer bestätigt Anwesenheit in der Lehrer-Konsole
3. **Quiz** – Erst nach Teilnahme-Bestätigung freigeschaltet; Bestehen schließt das Modul ab

Status-Mapping:
- `enrolled` → Schritt 1 erledigt, Schritt 2 aktiv
- `attended` → Schritte 1+2 erledigt, Schritt 3 aktiv
- `completed` → Alle 3 Schritte erledigt

Aktionen pro Status:
- **Gebucht:** Gebuchter Termin wird angezeigt (Datum, Uhrzeit, Ort); Stornieren + Umbuchen möglich
- **Quiz offen:** "Quiz starten"-Button
- **Abgeschlossen:** Abschlussdatum

### Einschreiben-Workflow
1. POST `/enroll` mit `module_id` und `training_session_id` (Pflichtfeld)
2. Buchung nur möglich, wenn ein konkreter Termin existiert (keine Buchung ohne TrainingSession)
3. Wenn kein Termin vorhanden: Hinweis "Aktuell nicht buchbar – keine Termine geplant" + Ansprechpartner (Accountable)
4. Falls ein storniertes Enrollment existiert: wird reaktiviert (wg. Unique-Constraint `user_id + module_id`)
5. Enrollment-Eintrag wird erstellt/reaktiviert (Status: `enrolled`)
6. Google Calendar: User wird als Teilnehmer zum Event hinzugefügt
7. Asana Task wird erstellt. Die `asana_task_gid` wird auf dem Enrollment gespeichert.

### Umbuchen-Workflow
1. PATCH `/enrollment/{enrollment}/rebook` mit neuer `training_session_id`
2. Nur möglich im Status `enrolled` (vor Teilnahme-Bestätigung)
3. `training_session_id` wird aktualisiert
4. Google Calendar: Alter Termin entfernt, neuer Termin hinzugefügt

### Stornieren-Workflow
1. PATCH `/enrollment/{enrollment}/cancel`
2. Enrollment-Status → `cancelled`, `cancelled_at` wird gesetzt
3. Asana: Kommentar auf der Task + Task wird als erledigt markiert
4. Google Calendar: User wird als Teilnehmer vom Event entfernt
5. Toast-Notification mit Bestätigung

### Rückkopplung: Google Calendar Absagen-Sync
- **Cronjob:** `academy:sync-cancellations` läuft alle 10 Minuten via Laravel Scheduler
- Prüft alle zukünftigen TrainingSessions mit `google_event_id`
- Wenn ein eingeschriebener User im Google Calendar **abgesagt** hat (`responseStatus: declined`) oder **nicht mehr als Teilnehmer** gelistet ist:
  - Enrollment wird automatisch auf `cancelled` gesetzt
  - Asana-Task wird abgeschlossen (falls vorhanden)
  - Log-Eintrag wird geschrieben
- Dry-Run möglich: `php artisan academy:sync-cancellations --dry-run`

### Abbrechen-Button (UI)
- Dashboard-Modulkarten und Timeline-View zeigen "Stornieren"-Button für Buchungen im Status `enrolled`
- Inline-Bestätigung via Alpine.js ("Ja, stornieren / Nein")
- Nach Stornierung kann der User einen neuen Termin buchen (Enrollment wird reaktiviert)

---

## 1b. Meine Timeline

**Route:** `GET /my-timeline`  
**Controller:** `AcademyController@timeline`

### Funktionen
- **Zwei Tabs** via Alpine.js (`.tabs` / `.tab` / `.tab-active`):
  - **Aktive Buchungen** – zeigt `enrolled` und `attended` Enrollments
  - **Historie** – zeigt `completed` und `cancelled` Enrollments
- Badge-Counter pro Tab (Anzahl der Einträge)
- Chronologische Ansicht innerhalb jedes Tabs (neueste oben)
- Timeline-Darstellung mit `.timeline` / `.timeline-item` / `.timeline-marker` CSS-Klassen
- Status-Badges via `<x-badge>`: Gebucht (primary), Quiz offen (warning), Erledigt (success), Storniert (error)
- Workshop-Datum und Ort aus `training_sessions`
- Teilnahme-Bestätigungsdatum wird angezeigt (falls vorhanden)
- "Quiz starten"-Button bei `attended`-Status, "Stornieren"-Button bei `enrolled`-Status
- Eigener Empty State pro Tab
- Timeline-Item als Blade-Partial: `academy.partials.timeline-item`

### Sidebar-Navigation
- Link "Meine Timeline" unter dem Bereich "Lernen"

---

## 2. Lehrer-Konsole

**Route:** `GET /teacher`  
**Controller:** `TeacherController@dashboard`  
**Zugriff:** Nur Teacher, Manager, Admin (Gate: `teacher`)

### Funktionen
- Formular zum Erstellen neuer Workshop-Termine
- Terminübersicht mit klappbarer Teilnehmer-Liste pro Session (Alpine.js)
- **Anwesenheitsbestätigung**: Lehrer sieht alle eingeschriebenen Teilnehmer pro Session, kann per Checkbox auswählen wer da war, und die Anwesenheit bestätigen
  - POST `/teacher/sessions/{session}/confirm-attendance` mit Array von `attendees` (User-IDs)
  - Setzt Enrollment-Status von `enrolled` auf `attended`
  - Speichert `attendance_confirmed_at` und `attendance_confirmed_by`
  - Erst danach wird das Quiz für den Teilnehmer freigeschaltet
- Bereits bestätigte Teilnehmer werden mit grünem Haken und Bestätigungsdatum angezeigt
- Google Calendar Integration (Event wird bei Erstellung angelegt)
- Termin-Löschung (inkl. Calendar Event)

### Modul-Verantwortlichkeit (Accountable)
- Jedes Modul kann eine/n Verantwortliche/n haben
- Zwei Modi (`modules.accountable_type`):
  - `user`: Ein bestimmter Teacher wird als Accountable gesetzt (`modules.accountable_user_id`)
  - `head_of`: Der eigene Head-of jedes Teilnehmers ist verantwortlich (dynamisch via `users.head_of_user_id`)
- Head-of-Beziehung wird automatisch aus Personio synchronisiert (`supervisor`-Attribut)
- Auswahl im Modul-Formular: Alle Teacher + Option "Eigener Head of"

---

## 3. Digitale Mappe (Portfolio)

**Route:** `GET /portfolio`  
**Controller:** `PortfolioController`

### Funktionen
- Drag & Drop File Upload (Bilder/PDFs)
- Upload zu Google Cloud Storage Bucket `td-academy-files`
- Zuordnung zu Modul + User
- Dateiliste mit Typ-Icons, Größe, Datum
- Löschfunktion

### Technisch
- Storage Disk: `gcs` (konfiguriert in `config/filesystems.php`)
- Pfad-Schema: `portfolio/{user_id}/{module_id}/{filename}`
- Max. 10 MB, erlaubte Formate: JPG, PNG, GIF, WebP, PDF

---

## 4. Lernerfolgskontrolle (Quizzes)

**Route:** `GET /quiz/{quiz}`, `POST /quiz/{quiz}/submit`  
**Controller:** `QuizController`

### Funktionen
- Step-by-Step Quiz-Interface (eine Frage pro Karte)
- Fortschrittsbalken oben
- Multiple-Choice mit Radio-Buttons
- Navigation: Zurück/Weiter, Abschließen am Ende
- Ergebnis-Seite mit Score, Richtig/Falsch-Zähler

### Zugangsschutz
- Quiz ist erst zugänglich, wenn die Teilnahme durch einen Lehrer bestätigt wurde (Status `attended`)
- Zugriff ohne bestätigte Teilnahme → Redirect zum Dashboard mit Fehlermeldung

### Logik bei Bestehen (≥70% default)
1. Enrollment-Status → `completed` (von `attended`)
2. Check: Alle Pflichtmodule des Levels abgeschlossen?
3. Wenn ja → User bekommt automatisch das nächste Career Level

### Quiz-Datenformat (JSON in `quizzes.questions`)
```json
[
  {
    "question": "Was ist SEO?",
    "options": ["Search Engine Optimization", "Social Engine Optimization", "Site Engine Optimization"],
    "correct": 0
  }
]
```

---

## 5. Admin: Struktur-Verwaltung

**Route:** `GET /admin/modules`  
**Controller:** `AdminModuleController`  
**Zugriff:** Nur Admin (Gate: `admin`)

### Funktionen
- Karrierepfade erstellen (Name + Stufen)
- Module erstellen/bearbeiten/löschen
- **Module ohne Karrierestufe** ("Allgemeine Module") erstellen – career_level_id ist optional
- Allgemeine Module werden in eigener Sektion vor den Karrierepfaden angezeigt
- Quiz-Editor pro Modul (Fragen + Antworten + Bestehensgrenze)
- Übersicht aller Pfade → Levels → Module in hierarchischer Darstellung
- Accountable-Zuweisung pro Modul (bestimmter Teacher oder "Eigener Head of")
- Methoden-Zuweisung pro Modul

---

## 5c. Mitarbeiter-Verwaltung (People Manager)

**Route:** `GET /manage/employees`  
**Controller:** `EmployeeManagementController`  
**Zugriff:** Manager, People Manager, Head of (Gate: `manager`)

### Konzept
Mitarbeiter-zentrische Verwaltung: People Manager sehen eine Liste ihrer Team-Mitglieder und verwalten Karrierepfade und Module über eine eigene Detailseite pro Mitarbeiter.

### Mitarbeiter-Liste (`GET /manage/employees`)
- Sortierbare Tabelle: Name, Team, Karrierestufe, Karrierepfad, Anzahl Module
- Suchleiste (Name-Suche)
- Filter nach Team und Karrierepfad
- People Manager sehen nur Mitarbeitende ihrer betreuten Teams
- Admins sehen alle aktiven Mitarbeitenden
- Klick auf Zeile navigiert zur Detailseite

### Mitarbeiter-Detailseite (`GET /manage/employees/{user}`)
Vollständige Unterseite mit 2-Spalten-Layout:

**Linke Spalte:**
- **Karrierepfad:** Zuweisen, ändern oder entfernen (Pfad + Stufe)
- **Modul hinzufügen:** Einzelne Module direkt zuweisen mit Filter nach Karrierepfad und Level

**Rechte Spalte:**
- **Module:** Alle zugewiesenen Module (aus Karrierepfad + direkt), mit Status (abgeschlossen/offen)
- **Modul entfernen:** Direkt zugewiesene Module einzeln entfernen
- **Standard-Modul deaktivieren:** Einzelne Karrierepfad-Module pro Mitarbeiter deaktivieren (z.B. Backoffice braucht nicht alle Module)
- **Deaktiviertes Modul reaktivieren:** Deaktivierte Standard-Module wieder aktivieren

**Oben:**
- Zurück-Button zur Mitarbeiter-Liste
- Vorschläge: Fehlende Module der Karrierestufe, nächste Stufe vorschlagen

### Standard-Module deaktivieren
People Manager können einzelne Module, die über den Karrierepfad zugewiesen sind, pro Mitarbeiter deaktivieren:
- Deaktivierte Module erscheinen nicht mehr im Studenten-Dashboard
- Deaktivierte Module zählen nicht als "fehlend" in Vorschlägen
- Deaktivierte Module werden in einer separaten Sektion im Detail-Panel angezeigt (durchgestrichen, mit Reaktivieren-Button)
- Badge im Header zeigt die Anzahl deaktivierter Module

**Routes:**
- `POST /manage/employees/{user}/modules/{module}/disable` – Standard-Modul deaktivieren
- `DELETE /manage/employees/{user}/modules/{module}/disable` – Standard-Modul reaktivieren

### Teams
- `teams` Tabelle: Automatisch aus Personio-Departments erstellt
- `manager_team` Pivot: Admin weist People Managern betreute Teams zu (in Nutzerverwaltung)
- `team_id` auf `users`: Jeder User gehört zu einem Team

### Datenmodell
- `module_assignments` Pivot-Tabelle: `user_id`, `module_id`, `assigned_by`, `assigned_at`
- `disabled_career_modules` Pivot-Tabelle: `user_id`, `module_id`, `disabled_by`, `disabled_at` (deaktivierte Standard-Module)
- `teams` Tabelle: `name`, `personio_department`
- `manager_team` Pivot: `user_id`, `team_id`
- Zugewiesene Module erscheinen im Studenten-Dashboard neben den Karrierestufen-Modulen

---

## 5b. Methoden-Verwaltung

**Route:** `GET /admin/methods`  
**Controller:** `AdminMethodController`  
**Zugriff:** Nur Admin (Gate: `admin`)

### Funktionen
- CRUD für Lehr-/Lernmethoden (analog Skill-Kategorien)
- Methoden werden Modulen zugewiesen (`modules.method_id`)
- Inline-Bearbeitung mit Alpine.js
- Löschung entkoppelt zugewiesene Module (setzt `method_id = null`)

### Datenmodell
- `methods` Tabelle: `id`, `name` (unique), `description`, `timestamps`
- `modules.method_id` FK → `methods`

---

## DB-Tabellen (neu)

| Tabelle | Beschreibung |
|---------|-------------|
| `portfolio_uploads` | Digitale Mappe Uploads (user_id, module_id, storage_path, etc.) |
| `quiz_attempts` | Quiz-Versuche (user_id, quiz_id, answers, score, passed) |
| `users.career_level_id` | FK zu career_levels (aktuelle Stufe des Users) |
| `users.head_of_user_id` | FK zu users (Head-of aus Personio Supervisor) |
| `methods` | Lehr-/Lernmethoden (name, description) |
| `modules.accountable_type` | Art der Verantwortlichkeit: `user` oder `head_of` |
| `modules.accountable_user_id` | FK zu users (konkreter Accountable bei type=user) |
| `modules.method_id` | FK zu methods |
| `enrollments.asana_task_gid` | Asana Task GID für Stornierung/Updates |
| `enrollments.cancelled_at` | Zeitpunkt der Stornierung |
| `enrollments.attendance_confirmed_at` | Zeitpunkt der Anwesenheitsbestätigung durch Lehrer |
| `enrollments.attendance_confirmed_by` | FK zu users – welcher Lehrer die Anwesenheit bestätigt hat |
| `module_assignments` | Direkte Modul-Zuordnung (user_id, module_id, assigned_by, assigned_at) |
| `disabled_career_modules` | Deaktivierte Standard-Module pro User (user_id, module_id, disabled_by, disabled_at) |
| `modules.career_level_id` | Jetzt nullable – Module ohne Karrierestufe = "Allgemeine Module" |

## .env Konfiguration (Drittanbieter)

| Variable | Beschreibung |
|----------|-------------|
| `ASANA_ACCESS_TOKEN` | Persönlicher Asana API Token |
| `ASANA_PROJECT_ID` | ID des Asana-Projekts für Academy-Buchungen |
| `GOOGLE_CALENDAR_ID` | Google Calendar ID des "trafficdesign academy" Kalenders |
