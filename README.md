# td Academy - Lernplattform für trafficdesign

> Interne Lernplattform zur strukturierten Weiterentwicklung aller Mitarbeitenden bei trafficdesign.  
> Karrierestufen, Module, Prüfungen und nahtlose Integration in bestehende Systeme.

---

## Vision

Die **td Academy** ist die zentrale Lernplattform von trafficdesign. Sie bildet den gesamten Karrierepfad eines Mitarbeitenden ab – vom Onboarding bis zur Spezialisierung. Ziel ist eine transparente, messbare und motivierende Weiterentwicklung über alle Abteilungen hinweg.

### Karrierestufen

Jeder Mitarbeitende durchläuft klar definierte Karrierestufen:

| Stufe | Beschreibung |
|-------|-------------|
| **Junior** | Einstieg, Grundlagen-Module, begleitetes Onboarding |
| **Mid-Level** | Vertiefung in Fachbereichen, erste eigenständige Projekte |
| **Senior** | Spezialisierung, Mentoring, strategische Aufgaben |
| **Lead / Expert** | Teamführung, Wissenstransfer, Prozessgestaltung |

Jede Stufe hat zugeordnete **Pflicht-Module** und **optionale Vertiefungen**, die im System hinterlegt sind.

### Lernmodule

Module bestehen aus strukturierten Lerneinheiten:

- **Theorie-Blöcke** (Text, Video, Dokumente)
- **Praxis-Aufgaben** mit Bewertung
- **Wissenstests / Quizzes** zur Lernkontrolle
- **Digitale Mappe** (Portfolio) – Upload von Arbeitsergebnissen (S3-Storage)
- **Fortschrittstracking** mit visueller Darstellung (Progress Bars, Stepper)

### Integrationen

| System | Zweck | Beschreibung |
|--------|-------|-------------|
| **Google Calendar** | Terminplanung | Automatische Synchronisation von Schulungsterminen, Deadlines und Prüfungsterminen in den persönlichen Kalender |
| **Personio** | HR-Daten | Abgleich von Mitarbeiterdaten, Karrierestufen und Abteilungszugehörigkeit; automatisiertes Onboarding neuer Mitarbeitender |
| **Asana** | Aufgabenmanagement | Erstellung von Lernaufgaben als Asana-Tasks, Tracking von Abgabefristen, Benachrichtigungen bei Fälligkeiten |

### Kernfunktionen

- **Dashboard** – Persönlicher Lernfortschritt, anstehende Module, KPIs
- **Modulkatalog** – Übersicht aller verfügbaren Lernmodule nach Karrierestufe
- **Prüfungen** – Online-Tests mit automatischer Auswertung
- **Digitale Mappe** – Persönliches Portfolio mit S3-basiertem Datei-Upload
- **Admin-Bereich** – Modul-Verwaltung, Nutzer-Management, Fortschrittsberichte
- **Benachrichtigungen** – Erinnerungen an Fristen, neue Module, Feedback

---

## Tech-Stack

| Technologie | Einsatz |
|-------------|---------|
| **Laravel** (Breeze/Blade) | Backend, Routing, Auth |
| **Tailwind CSS** | Styling via trafficdesign UI Kit |
| **Alpine.js** | Frontend-Interaktivität |
| **MySQL** | Datenbank |
| **S3-Storage** | Datei-Uploads (Digitale Mappe) |
| **Vite** | Build-Tooling |

### UI Kit

Das Projekt nutzt das **trafficdesign Tool UI Kit** – ein Enterprise SaaS Design System mit:

- Brand Colors (`brand-primary: #00AFCE`, `brand-dark: #1d1d1d`)
- 50+ vordefinierte CSS-Komponenten-Klassen (`.btn-primary`, `.card-tool`, `.table-tool`, etc.)
- Alpine.js Notification-Store (`notify()`)

Referenz: [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) | [`docs/USAGE.md`](docs/USAGE.md)

---

## Entwicklung

```bash
# Dependencies installieren
composer install
npm install

# Entwicklungsserver starten
npm run dev

# Produktion bauen
npm run build
```

### Environment

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Produktions-URL: `https://academy.trafficdesign.de`

---

## Dokumentation

Feature-Dokumentation befindet sich in `docs/features/`:

| Datei | Bereich |
|-------|---------|
| `AUTH-FEATURES.md` | Authentifizierung, Rollen, Berechtigungen |
| `IMPORT-FEATURES.md` | Datenimport, Personio-/Asana-Sync |
| `REPORTING-FEATURES.md` | Fortschrittsberichte, KPIs, Auswertungen |
| `ADMIN-FEATURES.md` | Administration, Modul-Verwaltung |

---

## Lizenz

Internes Projekt – trafficdesign GmbH
