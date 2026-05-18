# Budget Import - Google Apps Script

Dieses Dokument beschreibt, wie das Google Sheet "Kapas interne Budgets" mit der TD Academy verbunden wird, um Budget-Einträge automatisch zu importieren.

## Bestehendes Script (Legacy-Format)

Falls du bereits ein Script mit `syncBudgetsToAcademy()` verwendest, das Daten im Format `{ api_key, year, data: [...] }` sendet: **Das funktioniert weiterhin!**

Die API erkennt automatisch das Format und verarbeitet es entsprechend:
- `ist_stunden` wird als Betrag verwendet (Stunden)
- `monat` wird automatisch zu einem Datum konvertiert (z.B. "01/2026" → "2026-01-01")
- Einträge mit 0 Stunden werden übersprungen
- `sheet_origin` wird dem Label vorangestellt

### Neues Script für Ist/Plan-Struktur

Das Sheet hat folgende Struktur:
- **Zeilen 7-60**: Ist-Werte (tatsächlich verbraucht)
- **Ab Zeile 62**: Plan-Werte (noch geplant)
- **Spalte A**: Budget-Name
- **Spalte B**: Person
- **Spalten C-N**: Januar bis Dezember

Ersetze die `syncBudgetsToAcademy` Funktion komplett mit dieser Version:

```javascript
// KONFIGURATION
const CONFIG = {
  API_URL: "https://academy.trafficdesign.de/api/budget/webhook",
  API_KEY: "0f05a17172a7c419bfe5131398e3d15ff10c3ccba77103f56a9dce3c5f259677",
  
  // Zeilen-Bereiche (1-basiert)
  IST_START_ROW: 7,      // Erste Zeile mit Ist-Daten
  IST_END_ROW: 60,       // Letzte Zeile mit Ist-Daten
  PLAN_START_ROW: 62,    // Erste Zeile mit Plan-Daten
  PLAN_END_ROW: 115,     // Letzte Zeile mit Plan-Daten (ca. gleiche Anzahl wie Ist)
  
  // Spalten (1-basiert)
  COL_LABEL: 1,          // Spalte A: Budget-Name
  COL_NAME: 2,           // Spalte B: Person
  COL_FIRST_MONTH: 3,    // Spalte C: Januar
  COL_LAST_MONTH: 14,    // Spalte N: Dezember
  
  // Sheets die ignoriert werden sollen
  IGNORED_SHEETS: ["Settings", "Team Rentabilität"],
};

function syncBudgetsToAcademy() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const allSheets = ss.getSheets();
  let allData = [];
  let sheetsProcessed = 0;
  
  allSheets.forEach(sheet => {
    const sheetName = sheet.getName();
    
    if (CONFIG.IGNORED_SHEETS.indexOf(sheetName) !== -1) {
      return; // Skip ignored sheets
    }
    
    sheetsProcessed++;
    
    // Ist-Werte auslesen (Zeilen 7-60)
    const istEntries = readBudgetBlock(sheet, sheetName, CONFIG.IST_START_ROW, CONFIG.IST_END_ROW, 'used');
    allData = allData.concat(istEntries);
    
    // Plan-Werte auslesen (ab Zeile 62)
    const planEntries = readBudgetBlock(sheet, sheetName, CONFIG.PLAN_START_ROW, CONFIG.PLAN_END_ROW, 'available');
    allData = allData.concat(planEntries);
  });
  
  if (allData.length === 0) {
    SpreadsheetApp.getUi().alert("Keine Daten gefunden.\n\nGeprüfte Sheets: " + sheetsProcessed);
    return;
  }
  
  // In Batches senden (max 500 pro Request)
  const batchSize = 500;
  let totalProcessed = 0;
  let totalErrors = [];
  
  for (let i = 0; i < allData.length; i += batchSize) {
    const batch = allData.slice(i, i + batchSize);
    const result = sendToApi(batch);
    
    totalProcessed += result.processed || 0;
    if (result.errors) {
      totalErrors = totalErrors.concat(result.errors);
    }
  }
  
  // Ergebnis anzeigen
  let message = "Sync abgeschlossen!\n\n";
  message += "Sheets verarbeitet: " + sheetsProcessed + "\n";
  message += "Einträge gesendet: " + allData.length + "\n";
  message += "Erfolgreich: " + totalProcessed + "\n";
  
  if (totalErrors.length > 0) {
    message += "\nFehler (" + totalErrors.length + "):\n";
    totalErrors.slice(0, 10).forEach(err => {
      message += "• " + err + "\n";
    });
    if (totalErrors.length > 10) {
      message += "... und " + (totalErrors.length - 10) + " weitere\n";
    }
  }
  
  SpreadsheetApp.getUi().alert(message);
}

/**
 * Liest einen Block von Budget-Daten (Ist oder Plan)
 */
function readBudgetBlock(sheet, sheetName, startRow, endRow, budgetType) {
  const entries = [];
  const year = getYear();
  
  // Prüfen ob genug Zeilen vorhanden
  const lastRow = sheet.getLastRow();
  if (startRow > lastRow) return entries;
  
  const actualEndRow = Math.min(endRow, lastRow);
  
  for (let row = startRow; row <= actualEndRow; row++) {
    const label = sheet.getRange(row, CONFIG.COL_LABEL).getValue();
    const name = sheet.getRange(row, CONFIG.COL_NAME).getValue();
    
    // Zeile überspringen wenn kein Name
    if (!name || String(name).trim() === '') continue;
    if (!label || String(label).trim() === '') continue;
    
    // Jeden Monat auslesen (Spalten C-N = Januar-Dezember)
    for (let col = CONFIG.COL_FIRST_MONTH; col <= CONFIG.COL_LAST_MONTH; col++) {
      const month = col - CONFIG.COL_FIRST_MONTH + 1; // 1-12
      const value = sheet.getRange(row, col).getValue();
      const amount = parseFloat(value) || 0;
      
      // Nur Einträge mit Werten > 0
      if (amount <= 0) continue;
      
      entries.push({
        label: String(label).trim(),
        name: String(name).trim(),
        amount: amount,
        budget_type: budgetType, // 'used' oder 'available'
        date: year + '-' + String(month).padStart(2, '0') + '-01',
        cost_type: 'time',
        sheet_origin: sheetName
      });
    }
  }
  
  return entries;
}

/**
 * Sendet Daten an die TD Academy API
 */
function sendToApi(entries) {
  const payload = {
    api_key: CONFIG.API_KEY,
    entries: entries
  };
  
  const options = {
    method: 'post',
    contentType: 'application/json',
    headers: {
      'X-Budget-Import-Key': CONFIG.API_KEY
    },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };
  
  try {
    const response = UrlFetchApp.fetch(CONFIG.API_URL, options);
    const result = JSON.parse(response.getContentText());
    Logger.log('API Response: ' + JSON.stringify(result));
    return result;
  } catch (e) {
    Logger.log('API Error: ' + e.toString());
    return { processed: 0, errors: [e.toString()] };
  }
}

/**
 * Holt das Jahr aus den Settings
 */
function getYear() {
  try {
    const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Settings");
    if (sheet) {
      return sheet.getRange("B14").getValue() || new Date().getFullYear();
    }
  } catch (e) {}
  return new Date().getFullYear();
}

/**
 * Menü erstellen
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu("trafficdesign")
    .addItem("Personalbedarf aus BT aktualisieren", "updateProjectBudgets")
    .addSeparator()
    .addItem("Alle Budgets an TD Academy senden", "syncBudgetsToAcademy")
    .addItem("Test: Verbindung prüfen", "testConnection")
    .addToUi();
}

/**
 * Testet die Verbindung zur API
 */
function testConnection() {
  const testUrl = CONFIG.API_URL.replace('/webhook', '/test');
  
  try {
    const response = UrlFetchApp.fetch(testUrl, {
      method: 'GET',
      headers: { 'X-Budget-Import-Key': CONFIG.API_KEY },
      muteHttpExceptions: true
    });
    
    const result = JSON.parse(response.getContentText());
    
    SpreadsheetApp.getUi().alert(
      'Verbindungstest:\n\n' +
      'Status: ' + result.status + '\n' +
      'API-Key gültig: ' + (result.api_key_valid ? 'Ja' : 'Nein')
    );
  } catch (e) {
    SpreadsheetApp.getUi().alert('Verbindungsfehler: ' + e.toString());
  }
}
```

### Wichtig: Zeilen-Nummern anpassen!

Passe in der `CONFIG` Sektion die Zeilen-Nummern an dein Sheet an:

```javascript
IST_START_ROW: 7,      // Erste Zeile mit Ist-Daten
IST_END_ROW: 60,       // Letzte Zeile mit Ist-Daten  
PLAN_START_ROW: 62,    // Erste Zeile mit Plan-Daten
PLAN_END_ROW: 115,     // Letzte Zeile mit Plan-Daten
```

---

## Voraussetzungen

1. Ein Google Sheet mit Budget-Daten im Matrix-Format
2. Der API-Key für den Budget-Import (in der `.env` als `BUDGET_IMPORT_KEY`)
3. Die Webhook-URL der TD Academy

## Sheet-Struktur (Matrix-Format)

Das Google Sheet hat folgende Struktur:

| Spalte A | Spalte B | Spalte C | Spalte D | ... |
|----------|----------|----------|----------|-----|
| Projekt-ID | | 230 | | |
| Summe (MIXED) | | 17.828 € | 22.815 € | ... |
| Monat | | 01/2026 | 02/2026 | ... |
| ... | | | | |
| **Budget-Name** | **Person** | **Betrag Jan** | **Betrag Feb** | ... |
| 360 Features-Videos | Jan Kunze | 0 € | 0 € | ... |
| Brand Awareness | Kilian Oberlinger | 0 € | 813 € | ... |

**Wichtig:**
- **Spalte A**: Name des Budgets/Projekts (wird als Label verwendet)
- **Spalte B**: Name der zuständigen Person
- **Spalten C-N**: Monatliche Beträge (01/2026 bis 12/2026)

### Label-Präfixe

Die folgenden Präfixe werden automatisch erkannt:

- `Persönliches Ziel:` - Wird vom 3000€ Topf abgezogen
- `Teamziel:` - Nur Zeit-Tracking, kein Abzug
- `Interne Schulung:` - Nur Zeit-Tracking
- `PE ` - Andere interne Projekte (z.B. "PE- Web & Marketing")

## Google Apps Script einrichten

### 1. Script erstellen

1. Öffne das Google Sheet
2. Gehe zu **Erweiterungen → Apps Script**
3. Lösche den vorhandenen Code und füge folgendes Script ein:

```javascript
/**
 * TD Academy Budget Import Script
 * Version: 2.0 - Matrix-Format für "Kapas interne Budgets"
 */

// KONFIGURATION - Diese Werte anpassen!
const CONFIG = {
  WEBHOOK_URL: 'https://academy.trafficdesign.de/api/budget/webhook',
  API_KEY: 'IHR_API_KEY_HIER',
  
  // Spalten-Mapping (1-basiert)
  COLUMNS: {
    LABEL: 1,     // Spalte A: Budget-Name/Projekt
    NAME: 2,      // Spalte B: Zuständige Person
    FIRST_MONTH: 3, // Spalte C: Erster Monat (01/2026)
  },
  
  // Zeilen-Konfiguration
  MONTH_HEADER_ROW: 4,  // Zeile mit "Monat" und den Datumsangaben
  START_ROW: 8,         // Erste Datenzeile (nach Headers und Summen)
  
  // Welche Zeilen sollen übersprungen werden? (z.B. Summen-Zeilen)
  SKIP_LABELS: ['Summe (MIXED)', 'Summe (SOLL)', 'Summe (IST)', 'Monat', 'Verwendungsgrad', 'Projekt-ID'],
};

/**
 * Wird bei jeder Änderung im Sheet aufgerufen
 */
function onEdit(e) {
  if (!e || !e.range) return;
  
  const sheet = e.source.getActiveSheet();
  const row = e.range.getRow();
  const col = e.range.getColumn();
  
  // Header-Zeilen ignorieren
  if (row < CONFIG.START_ROW) return;
  
  // Nur bei Änderungen in Betrags-Spalten reagieren
  if (col < CONFIG.COLUMNS.FIRST_MONTH) return;
  
  // Eintrag für diesen Monat senden
  sendEntryForCell(sheet, row, col);
}

/**
 * Sendet einen Eintrag für eine bestimmte Zelle (Zeile + Monatsspalte)
 */
function sendEntryForCell(sheet, row, col) {
  const entry = getEntryFromCell(sheet, row, col);
  
  if (!entry) {
    Logger.log('Kein gültiger Eintrag in Zeile ' + row + ', Spalte ' + col);
    return;
  }
  
  if (entry.amount === 0) {
    Logger.log('Betrag ist 0, überspringe');
    return;
  }
  
  const result = sendToApi({ entries: [entry] });
  Logger.log('Gesendet: ' + JSON.stringify(entry) + ' -> ' + JSON.stringify(result));
}

/**
 * Liest einen Eintrag aus einer bestimmten Zelle
 */
function getEntryFromCell(sheet, row, col) {
  const label = sheet.getRange(row, CONFIG.COLUMNS.LABEL).getValue();
  const name = sheet.getRange(row, CONFIG.COLUMNS.NAME).getValue();
  const amount = sheet.getRange(row, col).getValue();
  
  // Summen-Zeilen überspringen
  if (!label || !name || CONFIG.SKIP_LABELS.includes(String(label).trim())) {
    return null;
  }
  
  // Monat aus Header-Zeile lesen
  const monthHeader = sheet.getRange(CONFIG.MONTH_HEADER_ROW, col).getValue();
  const date = parseMonthToDate(monthHeader);
  
  if (!date) {
    Logger.log('Konnte Monat nicht parsen: ' + monthHeader);
    return null;
  }
  
  // Betrag parsen (kann "813 €" oder 813 sein)
  const parsedAmount = parseAmount(amount);
  
  return {
    name: String(name).trim(),
    label: String(label).trim(),
    amount: parsedAmount,
    date: date,
    cost_type: 'monetary',
  };
}

/**
 * Parst Monatsangabe (z.B. "01/2026") zu ISO-Datum
 */
function parseMonthToDate(monthStr) {
  if (!monthStr) return null;
  
  const str = String(monthStr).trim();
  
  // Format: "01/2026" oder "1/2026"
  const match = str.match(/^(\d{1,2})\/(\d{4})$/);
  if (match) {
    const month = match[1].padStart(2, '0');
    const year = match[2];
    return year + '-' + month + '-01';
  }
  
  // Format: Date-Objekt
  if (monthStr instanceof Date) {
    return Utilities.formatDate(monthStr, 'Europe/Berlin', 'yyyy-MM-dd');
  }
  
  return null;
}

/**
 * Parst Betrag (entfernt € und Tausender-Punkte)
 */
function parseAmount(value) {
  if (typeof value === 'number') return value;
  if (!value) return 0;
  
  const str = String(value)
    .replace(/€/g, '')
    .replace(/\./g, '')  // Tausender-Punkte entfernen
    .replace(/,/g, '.')  // Komma zu Punkt
    .trim();
  
  return parseFloat(str) || 0;
}

/**
 * Sendet alle Einträge für den aktuellen Monat
 */
function sendCurrentMonth() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const now = new Date();
  const currentMonth = (now.getMonth() + 1).toString().padStart(2, '0');
  const currentYear = now.getFullYear().toString();
  const searchMonth = currentMonth + '/' + currentYear;
  
  // Finde die Spalte für den aktuellen Monat
  const lastCol = sheet.getLastColumn();
  let monthCol = null;
  
  for (let col = CONFIG.COLUMNS.FIRST_MONTH; col <= lastCol; col++) {
    const header = sheet.getRange(CONFIG.MONTH_HEADER_ROW, col).getValue();
    if (String(header).trim() === searchMonth) {
      monthCol = col;
      break;
    }
  }
  
  if (!monthCol) {
    SpreadsheetApp.getUi().alert('Spalte für Monat ' + searchMonth + ' nicht gefunden.');
    return;
  }
  
  sendEntriesForColumn(sheet, monthCol);
}

/**
 * Sendet alle Einträge für eine bestimmte Spalte (Monat)
 */
function sendEntriesForColumn(sheet, col) {
  const lastRow = sheet.getLastRow();
  const entries = [];
  
  for (let row = CONFIG.START_ROW; row <= lastRow; row++) {
    const entry = getEntryFromCell(sheet, row, col);
    if (entry && entry.amount > 0) {
      entries.push(entry);
    }
  }
  
  if (entries.length === 0) {
    SpreadsheetApp.getUi().alert('Keine Einträge mit Beträgen > 0 gefunden.');
    return;
  }
  
  const result = sendToApi({ entries: entries });
  
  SpreadsheetApp.getUi().alert(
    'Import abgeschlossen:\n' +
    '- Einträge gesendet: ' + entries.length + '\n' +
    '- Verarbeitet: ' + (result.processed || 0) + '\n' +
    '- Fehler: ' + (result.errors ? result.errors.length : 0)
  );
}

/**
 * Sendet ALLE Einträge für ALLE Monate
 */
function sendAllEntries() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    'Alle Einträge senden?',
    'Dies sendet alle Budget-Einträge für alle Monate an die TD Academy. Fortfahren?',
    ui.ButtonSet.YES_NO
  );
  
  if (response !== ui.Button.YES) return;
  
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const lastRow = sheet.getLastRow();
  const lastCol = sheet.getLastColumn();
  const entries = [];
  
  for (let row = CONFIG.START_ROW; row <= lastRow; row++) {
    for (let col = CONFIG.COLUMNS.FIRST_MONTH; col <= lastCol; col++) {
      const entry = getEntryFromCell(sheet, row, col);
      if (entry && entry.amount > 0) {
        entries.push(entry);
      }
    }
  }
  
  if (entries.length === 0) {
    ui.alert('Keine Einträge gefunden.');
    return;
  }
  
  // In Batches senden (max 100 pro Request)
  const batchSize = 100;
  let processed = 0;
  let errors = [];
  
  for (let i = 0; i < entries.length; i += batchSize) {
    const batch = entries.slice(i, i + batchSize);
    const result = sendToApi({ entries: batch });
    processed += result.processed || 0;
    if (result.errors) errors = errors.concat(result.errors);
  }
  
  ui.alert(
    'Import abgeschlossen:\n' +
    '- Einträge gesendet: ' + entries.length + '\n' +
    '- Verarbeitet: ' + processed + '\n' +
    '- Fehler: ' + errors.length
  );
}

/**
 * Sendet Daten an die TD Academy API
 */
function sendToApi(payload) {
  const options = {
    method: 'POST',
    contentType: 'application/json',
    headers: {
      'X-Budget-Import-Key': CONFIG.API_KEY,
    },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true,
  };
  
  try {
    const response = UrlFetchApp.fetch(CONFIG.WEBHOOK_URL, options);
    const code = response.getResponseCode();
    const body = JSON.parse(response.getContentText());
    
    Logger.log('API Response (' + code + '): ' + JSON.stringify(body));
    
    if (code >= 400) {
      Logger.log('API Error: ' + JSON.stringify(body));
    }
    
    return body;
    
  } catch (error) {
    Logger.log('Fetch Error: ' + error.message);
    return { error: error.message, processed: 0, errors: [error.message] };
  }
}

/**
 * Testet die Verbindung zur API
 */
function testConnection() {
  const testUrl = CONFIG.WEBHOOK_URL.replace('/webhook', '/test');
  
  try {
    const response = UrlFetchApp.fetch(testUrl, {
      method: 'GET',
      headers: {
        'X-Budget-Import-Key': CONFIG.API_KEY,
      },
      muteHttpExceptions: true,
    });
    
    const body = JSON.parse(response.getContentText());
    
    SpreadsheetApp.getUi().alert(
      'Verbindungstest:\n\n' +
      'Status: ' + body.status + '\n' +
      'API-Key gültig: ' + (body.api_key_valid ? 'Ja' : 'Nein') + '\n' +
      'Server-Zeit: ' + body.timestamp
    );
    
  } catch (error) {
    SpreadsheetApp.getUi().alert('Verbindungsfehler: ' + error.message);
  }
}

/**
 * Testet die ausgewählte Zelle (ohne zu speichern)
 */
function testSelectedCell() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const range = sheet.getActiveRange();
  const row = range.getRow();
  const col = range.getColumn();
  
  if (row < CONFIG.START_ROW) {
    SpreadsheetApp.getUi().alert('Bitte eine Datenzeile auswählen (nicht den Header).');
    return;
  }
  
  if (col < CONFIG.COLUMNS.FIRST_MONTH) {
    SpreadsheetApp.getUi().alert('Bitte eine Betrags-Spalte auswählen (ab Spalte C).');
    return;
  }
  
  const entry = getEntryFromCell(sheet, row, col);
  
  if (!entry) {
    SpreadsheetApp.getUi().alert('Kein gültiger Eintrag in dieser Zelle.');
    return;
  }
  
  const testUrl = CONFIG.WEBHOOK_URL.replace('/webhook', '/test-import');
  
  const options = {
    method: 'POST',
    contentType: 'application/json',
    headers: {
      'X-Budget-Import-Key': CONFIG.API_KEY,
    },
    payload: JSON.stringify(entry),
    muteHttpExceptions: true,
  };
  
  try {
    const response = UrlFetchApp.fetch(testUrl, options);
    const body = JSON.parse(response.getContentText());
    
    let message = 'Test für Zelle:\n\n';
    message += 'Person: ' + entry.name + '\n';
    message += 'Budget: ' + entry.label + '\n';
    message += 'Betrag: ' + entry.amount + ' €\n';
    message += 'Datum: ' + entry.date + '\n\n';
    message += 'User gefunden: ' + (body.user_found ? 'Ja ✓' : 'Nein ✗') + '\n';
    
    if (body.user) {
      message += 'Erkannt als: ' + body.user.name + '\n';
      message += 'E-Mail: ' + body.user.email + '\n';
    }
    
    message += '\nStatus: ' + body.message;
    
    SpreadsheetApp.getUi().alert(message);
    
  } catch (error) {
    SpreadsheetApp.getUi().alert('Fehler: ' + error.message);
  }
}

/**
 * Sucht einen User nach Name
 */
function lookupUser() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.prompt('User suchen', 'Name eingeben:', ui.ButtonSet.OK_CANCEL);
  
  if (response.getSelectedButton() !== ui.Button.OK) return;
  
  const name = response.getResponseText().trim();
  if (!name) {
    ui.alert('Bitte einen Namen eingeben.');
    return;
  }
  
  const lookupUrl = CONFIG.WEBHOOK_URL.replace('/webhook', '/lookup-user') + '?name=' + encodeURIComponent(name);
  
  try {
    const response = UrlFetchApp.fetch(lookupUrl, {
      method: 'GET',
      headers: {
        'X-Budget-Import-Key': CONFIG.API_KEY,
      },
      muteHttpExceptions: true,
    });
    
    const body = JSON.parse(response.getContentText());
    
    if (body.found) {
      ui.alert(
        'User gefunden:\n\n' +
        'Name: ' + body.user.name + '\n' +
        'E-Mail: ' + body.user.email
      );
    } else {
      ui.alert('User "' + name + '" nicht gefunden.\n\nBitte prüfe die Schreibweise.');
    }
    
  } catch (error) {
    ui.alert('Fehler: ' + error.message);
  }
}

/**
 * Erstellt das Menü beim Öffnen des Sheets
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('TD Academy')
    .addItem('🔗 Verbindung testen', 'testConnection')
    .addItem('🔍 User suchen', 'lookupUser')
    .addSeparator()
    .addItem('✓ Ausgewählte Zelle testen', 'testSelectedCell')
    .addItem('📅 Aktuellen Monat senden', 'sendCurrentMonth')
    .addItem('📊 Alle Einträge senden', 'sendAllEntries')
    .addToUi();
}
```

### 2. Konfiguration anpassen

Ersetze in der `CONFIG`-Sektion:

- `WEBHOOK_URL`: Bereits auf `https://academy.trafficdesign.de/api/budget/webhook` gesetzt
- `API_KEY`: Den API-Key aus der `.env`-Datei eintragen

**Wichtig - Zeilen-Konfiguration anpassen:**

Schau dir dein Sheet an und passe diese Werte an:

```javascript
MONTH_HEADER_ROW: 4,  // In welcher Zeile steht "Monat" mit 01/2026, 02/2026, etc.?
START_ROW: 8,         // Ab welcher Zeile beginnen die echten Budget-Daten?
```

Im Screenshot ist das:
- Zeile 4: "Monat" mit den Datumsangaben
- Zeile 8: Erste Budget-Zeile ("360 Features-Videos für Website")

### 3. Trigger einrichten (optional)

Wenn Änderungen automatisch gesendet werden sollen:

1. Im Apps Script Editor: Klicke auf das **Uhr-Symbol** (Trigger)
2. Klicke auf **+ Trigger hinzufügen**
3. Konfiguriere:
   - Funktion: `onEdit`
   - Ereignisquelle: `Aus Tabelle`
   - Ereignistyp: `Bei Bearbeitung`
4. Speichern

**Hinweis:** Der automatische Trigger sendet nur Einträge mit Beträgen > 0.

### 4. Berechtigungen erteilen

Beim ersten Ausführen wirst du nach Berechtigungen gefragt. Erlaube dem Script:
- Zugriff auf das aktuelle Sheet
- Verbindung zu externen Diensten (für die API-Aufrufe)

## Verwendung

### TD Academy Menü

Nach dem Einrichten erscheint ein neues Menü **"TD Academy"** im Sheet:

| Menüpunkt | Beschreibung |
|-----------|--------------|
| 🔗 Verbindung testen | Prüft ob API erreichbar und Key gültig |
| 🔍 User suchen | Sucht einen User nach Name |
| ✓ Ausgewählte Zelle testen | Testet die markierte Zelle (Dry-Run) |
| 📅 Aktuellen Monat senden | Sendet alle Einträge des aktuellen Monats |
| 📊 Alle Einträge senden | Sendet ALLE Einträge für ALLE Monate |

### Workflow

1. **Erstmalig**: "Alle Einträge senden" um bestehende Daten zu importieren
2. **Laufend**: Änderungen werden automatisch gesendet (wenn Trigger aktiv) ODER monatlich "Aktuellen Monat senden"

### Debugging

- **User suchen**: Prüft ob ein Name in der TD Academy gefunden wird
- **Ausgewählte Zelle testen**: Klicke auf eine Betrags-Zelle und teste, ob der Import funktionieren würde

## Test-Endpoints

Die TD Academy bietet folgende Test-Endpoints:

| Endpoint | Methode | Beschreibung |
|----------|---------|--------------|
| `/api/budget/test` | GET | Prüft API-Verfügbarkeit und Key |
| `/api/budget/test-import` | POST | Testet Import ohne zu speichern |
| `/api/budget/lookup-user` | GET | Sucht User nach Name |

### Beispiel: User-Lookup

```bash
curl "https://academy.trafficdesign.de/api/budget/lookup-user?name=Jan%20Kunze" \
  -H "X-Budget-Import-Key: IHR_API_KEY"
```

## Fehlerbehebung

### "User not found"

Der Name in Spalte B muss **exakt** mit dem Namen in der TD Academy übereinstimmen:

- ✅ "Jan Kunze" → "Jan Kunze" 
- ✅ "jan kunze" → "Jan Kunze" (Groß/Kleinschreibung egal)
- ❌ "J. Kunze" → nicht gefunden (Abkürzung)
- ❌ "Jan  Kunze" → nicht gefunden (doppeltes Leerzeichen)

**Lösung:** Nutze "🔍 User suchen" im TD Academy Menü, um die korrekte Schreibweise zu prüfen.

### "Unauthorized"

- Prüfe ob der API-Key im Script korrekt ist
- Der Key steht in der TD Academy `.env` unter `BUDGET_IMPORT_KEY`

### Einträge werden nicht importiert

1. **Betrag ist 0**: Einträge mit 0 € werden übersprungen
2. **Falsche START_ROW**: Prüfe ob die `START_ROW` in der Config korrekt ist
3. **Summen-Zeile**: Zeilen wie "Summe (MIXED)" werden automatisch übersprungen

### Monat wird nicht erkannt

Das Datum in Zeile 4 muss im Format `MM/YYYY` sein (z.B. "01/2026", "12/2026").

## Logs einsehen

Im Apps Script Editor:
1. Klicke auf **Ausführung** in der linken Sidebar
2. Hier siehst du alle bisherigen Script-Ausführungen mit Logs

## Wie es funktioniert

Das Script liest die Matrix-Struktur so:

```
Zeile 8, Spalte C = "360 Features-Videos für Website" + "Jan Kunze" + Betrag + "01/2026"
                  = Entry { name: "Jan Kunze", label: "360 Features...", amount: X, date: "2026-01-01" }
```

Für jede Zelle mit einem Betrag > 0 wird ein separater Budget-Eintrag erstellt.
