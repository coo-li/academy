# Admin-Features: Nutzerverwaltung

## Übersicht

Die Nutzerverwaltung (`/admin/users`) ermöglicht Administratoren die vollständige Verwaltung aller Nutzer im System, inklusive Multi-Role-Zuweisung, Einladungen, Passwort-Reset und Archivierung.

## Rollen-System (Multi-Role)

Jeder Nutzer kann **mehrere Rollen gleichzeitig** haben. Die Rollen werden über eine Many-to-Many-Beziehung (`role_user` Pivot-Tabelle) verwaltet.

### Verfügbare Rollen

| Rolle | Slug | Zugriff |
|-------|------|---------|
| Administrator | `admin` | Vollzugriff: System-Bereich (Nutzerverwaltung, Karriere-Matrix, Einstellungen) |
| People Manager | `people_manager` | Verwaltungs-Bereich (Struktur-Verwaltung, Lehrer-Konsole) |
| Head of | `head_of` | Verwaltungs-Bereich (gleiche Rechte wie People Manager) |
| Trainer | `trainer` | Lehrer-Konsole |
| Mitarbeitender | `mitarbeitender` | Basis-Zugriff (Lern-Bereich: Academy, Timeline, Digitale Mappe) |

### Sidebar-Logik

- **Lernen** (alle Rollen): Meine Academy, Meine Timeline, Digitale Mappe
- **Verwaltung** (Admin, People Manager, Head of): Struktur-Verwaltung, Lehrer-Konsole
- **System** (nur Admin): Nutzerverwaltung, Karriere-Matrix, Einstellungen
- **Konto** (alle Rollen): Profil

## Einladungen

- Admin kann pro Nutzer manuell "Einladung senden" klicken
- Nutzt Laravels Password-Reset-Token-Mechanismus
- Empfänger erhält E-Mail mit Link zum Passwort-Setzen
- `invited_at` Timestamp wird auf dem User gesetzt
- Einladungen können mehrfach versendet werden (erneut klicken)

## Passwort zurücksetzen

- Admin kann pro Nutzer "Passwort zurücksetzen" auslösen
- Sendet Standard-Laravel-Password-Reset-E-Mail
- Nutzer setzt neues Passwort über `/reset-password/{token}`

## Archivierung

- Admin kann Nutzer archivieren (außer sich selbst)
- Archivierte Nutzer:
  - Werden sofort ausgeloggt (Session wird gelöscht)
  - Können sich nicht mehr einloggen (Middleware `EnsureUserNotArchived`)
  - Erscheinen im Tab "Archiviert" der Nutzerverwaltung
- Archivierte Nutzer können wiederhergestellt werden
- `archived_at` Timestamp auf dem User steuert den Status

## Nutzerverwaltung UI

### Tabs
- **Aktive Nutzer**: Standard-Ansicht aller aktiven Nutzer
- **Archiviert**: Alle archivierten Nutzer

### Such- und Filteroptionen
- Volltextsuche nach Name oder E-Mail
- Filter nach Rolle (Dropdown)

### Tabellenspalten
- Name (mit Avatar und Abteilung)
- E-Mail
- Rollen (als farbige Badges)
- Personio-Status (Badge wenn aus Personio synchronisiert)
- Status (Archiviert / Eingeladen / Nicht eingeladen)
- Aktionen

### Aktionen pro Nutzer
- **Rollen zuweisen**: Multi-Select Dropdown mit Checkboxen für alle 5 Rollen
- **Einladung senden**: E-Mail mit Passwort-Set-Link
- **Passwort zurücksetzen**: Password-Reset-E-Mail
- **Archivieren**: Zugang sperren, Session löschen
- **Wiederherstellen**: Zugang reaktivieren (nur bei archivierten Nutzern)

## Datenmodell

### Tabellen

```
roles (id, name, slug, description, timestamps)
role_user (id, user_id FK, role_id FK, timestamps) -- UNIQUE(user_id, role_id)
users.archived_at (nullable timestamp)
users.invited_at (nullable timestamp)
```

### Model-Methoden (User)

- `roles()` -- BelongsToMany Relationship
- `hasRole($slug)` -- Prüft ob Nutzer eine bestimmte Rolle hat
- `isAdmin()` -- hasRole('admin')
- `isManager()` -- hasRole(['admin', 'people_manager', 'head_of'])
- `isTeacher()` -- hasRole(['admin', 'people_manager', 'head_of', 'trainer'])
- `isArchived()` -- archived_at !== null
- `scopeActive()` / `scopeArchived()` -- Query Scopes

## Personio-Integration

Nutzer aus Personio-Sync erhalten automatisch die Rolle `mitarbeitender`. Weitere Rollen müssen manuell vom Admin zugewiesen werden.

## Dateien

| Datei | Zweck |
|-------|-------|
| `app/Models/Role.php` | Role Model |
| `app/Models/User.php` | User Model (erweitert) |
| `app/Http/Controllers/AdminUserController.php` | Controller |
| `app/Http/Middleware/EnsureUserNotArchived.php` | Archiv-Middleware |
| `app/Notifications/UserInvitation.php` | Einladungs-E-Mail |
| `resources/views/admin/users/index.blade.php` | Nutzerverwaltungs-View |
| `resources/views/layouts/sidebar.blade.php` | Sidebar (rollenbasiert) |
| `database/migrations/2026_03_06_100000_*` | Migrationen |
