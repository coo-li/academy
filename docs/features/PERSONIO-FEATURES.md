# Personio Integration – Feature-Dokumentation

**Status:** Implementiert  
**Letzte Aktualisierung:** März 2026

---

## Übersicht

Die Personio-Integration synchronisiert automatisch Mitarbeiterdaten aus Personio in die td Academy und ordnet Mitarbeitern anhand ihrer Position automatisch Karrierepfade zu.

---

## 1. Personio API-Service

**Datei:** `app/Services/PersonioService.php`

### Konfiguration

| Env-Variable | Beschreibung |
|---|---|
| `PERSONIO_CLIENT_ID` | Personio API Client ID |
| `PERSONIO_CLIENT_SECRET` | Personio API Client Secret |
| `PERSONIO_BASE_URL` | API Base URL (default: `https://api.personio.de/v1`) |

Config: `config/services.php` → `personio`

### Funktionen

- **`isConfigured()`** – Prüft ob API-Credentials gesetzt sind
- **`getAccessToken()`** – Authentifiziert sich bei Personio, Token wird 5 Min gecached
- **`getActiveEmployees()`** – Ruft alle aktiven Mitarbeiter ab (paginiert, 200er Batches), inkl. Custom Fields
- **`syncEmployees()`** – Vollständiger Sync: Mitarbeiter abrufen, User erstellen/aktualisieren, Karrierepfade anwenden, Auto-Match versuchen, Head-of-Beziehungen setzen
- **`syncHeadOfRelationships()`** – Mappt `supervisor`-Attribut aus Personio auf `users.head_of_user_id` (self-referencing FK)
- **`applyCareerMapping(User)`** – Wendet die Positions-Karrierepfad-Zuordnung auf einen User an
- **`tryAutoMatch(User)`** – Versucht automatische Zuordnung basierend auf `personio_path_raw` → CareerPath Name
- **`extractCustomField()`** – Extrahiert Werte aus dynamischen Personio Custom Fields
- **`ensurePositionMappingsExist()`** – Erstellt Mapping-Einträge für alle neuen Positionen

### Custom Fields (Personio API)

| Custom Field | Personio ID | User-Spalte |
|---|---|---|
| Karrierestufe | `dynamic_64635748cc5007.58702590` | `personio_level_raw` |
| Career Path | `dynamic_66d5b20d2c0b40.25958507` | `personio_path_raw` |

### Sync-Logik

1. Authentifizierung bei Personio API
2. Alle aktiven Mitarbeiter abrufen (Pagination), inkl. Custom Fields (Karrierestufe, Career Path)
3. Für jeden Mitarbeiter:
   - Match über `personio_id` oder `email`
   - Neuen User erstellen ODER bestehenden aktualisieren
   - **Name** (Vorname + Nachname), Position, Abteilung, `personio_level_raw`, `personio_path_raw` speichern
   - Name wird bei jedem Sync aus Personio übernommen (Zugangsdaten bleiben unverändert)
   - Karrierepfad-Mapping anwenden (falls konfiguriert)
   - **Auto-Match:** Wenn `personio_path_raw` einem CareerPath Namen entspricht, automatisch zuordnen
4. Head-of-Beziehungen setzen: `supervisor`-Attribut aus Personio wird auf `users.head_of_user_id` gemappt
5. Neue Positionen in `personio_position_mappings` anlegen
6. Sync-Log speichern

### Auto-Match Logik

1. Prüfe ob User bereits einen `career_level_id` hat → Abbruch
2. Prüfe ob `personio_path_raw` gesetzt → Falls nein, Abbruch
3. Suche CareerPath mit passendem Namen (case-insensitive)
4. Falls `personio_level_raw` gesetzt: Suche passendes Level innerhalb des Path
5. Fallback: Erstes Level des Path
6. Markiere Mapping als `is_auto_matched = true`

---

## 2. Artisan Command

```bash
# Vollständiger Sync
php artisan academy:sync-personio

# Nur Vorschau (keine Änderungen)
php artisan academy:sync-personio --dry-run
```

---

## 3. Karriere-Matrix (Admin)

**Route:** `GET /admin/matrix` → `admin.matrix.index`  
**Controller:** `AdminMatrixController`

### Features

- **Positions-Tabelle:** Alle Personio-Positionen mit Zuordnung zu Karrierepfaden
- **Neue Spalten:** Personio Path, Personio Level (aus Custom Fields)
- **Auto-Match Status:** Badge zeigt ob Zuordnung automatisch oder manuell erfolgte
- **Double-Check-Logik:**
  - Rot markiert: Positionen ohne Karrierepfad-Zuordnung
  - Gelb markiert: Auto-Match fehlgeschlagen (Personio-Daten vorhanden, aber nicht zuordenbar)
  - `badge-error "Lücke: Keine Schulungen hinterlegt"`: Karrierepfad zugeordnet, aber keine Module in Strukturverwaltung
  - `badge-success "Manuell"` / `badge-info "Automatisch zugeordnet"`: Status-Badges
- **Modal-Zuordnung:** "Zuordnen"-Button öffnet Modal zur permanenten CareerLevel-Zuweisung
- **Sync-Button:** Personio-Sync direkt aus der UI starten
- **Statistik-Cards:** Personio-User, Positionen, Zugeordnet, Auto-Matched, Ohne Zuordnung
- **Struktur-Warnung:** Rote Warnung bei Positionen ohne hinterlegte Schulungen
- **Sync-Status:** Alert mit Details zum letzten Sync
- **Legende:** Erklärung der Farben und Badges am Ende der Seite

### Routen

| Methode | Route | Aktion |
|---|---|---|
| GET | `/admin/matrix` | Matrix-Übersicht |
| PATCH | `/admin/matrix/{mapping}` | Positions-Zuordnung aktualisieren |
| POST | `/admin/matrix/sync` | Personio-Sync starten |

---

## 4. Auto-Zuweisung beim Login

**Listener:** `App\Listeners\AssignCareerPathOnLogin`  
**Event:** `Illuminate\Auth\Events\Login`

Beim Login eines Personio-Users ohne Karrierepfad wird automatisch geprüft, ob ein Mapping für seine Position existiert. Falls ja, wird der Karrierepfad zugewiesen.

---

## 5. Dashboard-Integration

Admins sehen auf dem Dashboard:
- **Warnung** wenn Personio-User ohne Karrierepfad existieren (mit Link zur Matrix)
- **Fehler-Alert** wenn der letzte Sync fehlgeschlagen ist

---

## 6. Datenmodell

### Neue Felder auf `users`

| Feld | Typ | Beschreibung |
|---|---|---|
| `personio_position` | string, nullable | Position aus Personio |
| `personio_department` | string, nullable | Abteilung aus Personio |
| `personio_level_raw` | string, nullable | Karrierestufe Rohwert aus Personio Custom Field |
| `personio_path_raw` | string, nullable | Career Path Rohwert aus Personio Custom Field |
| `personio_synced_at` | timestamp, nullable | Letzter Sync-Zeitpunkt |
| `head_of_user_id` | FK users, nullable | Head-of des Mitarbeiters (aus Personio Supervisor) |

### Neue Tabelle: `personio_position_mappings`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | bigint | PK |
| `personio_position` | string, unique | Positionsname aus Personio |
| `career_path_id` | FK, nullable | Zugeordneter Karrierepfad |
| `career_level_id` | FK, nullable | Zugeordnetes Karrierelevel |
| `is_auto_matched` | boolean, default false | Ob die Zuordnung automatisch erfolgte |

### Neue Tabelle: `personio_sync_logs`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | bigint | PK |
| `status` | enum | success, error, partial |
| `employees_fetched` | int | Anzahl abgerufener Mitarbeiter |
| `users_created` | int | Neu erstellte User |
| `users_updated` | int | Aktualisierte User |
| `users_skipped` | int | Übersprungene Einträge |
| `error_message` | text, nullable | Fehlermeldung |
| `details` | json, nullable | Detaillierte Sync-Infos |
| `started_at` | timestamp | Start-Zeitpunkt |
| `finished_at` | timestamp, nullable | End-Zeitpunkt |

---

## 7. Dateien

| Datei | Zweck |
|---|---|
| `app/Services/PersonioService.php` | API-Client & Sync-Logik |
| `app/Console/Commands/SyncPersonioCommand.php` | Artisan Command |
| `app/Http/Controllers/AdminMatrixController.php` | Matrix Admin-Controller |
| `app/Models/PersonioPositionMapping.php` | Positions-Mapping Model |
| `app/Models/PersonioSyncLog.php` | Sync-Log Model |
| `app/Listeners/AssignCareerPathOnLogin.php` | Login Event Listener |
| `resources/views/admin/matrix/index.blade.php` | Matrix Admin-View |
| `config/services.php` | Personio Config |
