---
description: Login-Flow (Magic Link) gegen SQL-Injection und verwandte Schwachstellen prüfen und härten
---

# Login-Audit: Magic Link

Diese App nutzt einen Magic-Link-Login (E-Mail eingeben → Link mit Token → Login). Prüfe und härte den kompletten Login-Flow gegen SQL-Injection und verwandte Schwachstellen. Maßstab sind die Abschnitte „Anmeldung“ und „Sicherheitsregeln“ in `docs/STANDARDS.md`.

Arbeite in dieser Reihenfolge: 1. Analyse, 2. Bericht, 3. Fixes, 4. Tests.
**Ändere nichts, bevor du mir den Analyse-Bericht gezeigt hast und ich zugestimmt habe.**

## 1. Analyse – finde ALLE Stellen, an denen Login-Daten die Datenbank berühren

- Anforderung des Magic Links (E-Mail-Eingabe → User-Lookup)
- Token-Erzeugung und Speicherung
- Token-Validierung beim Klick auf den Link (Token aus URL → DB-Lookup)
- Session- und Remember-Erstellung nach erfolgreichem Login
- Logging, Audit-Tabellen, Rate-Limit-Speicher, Redirect-Parameter
- Passkey- und 2FA-Routen, falls vorhanden

Liste jede Fundstelle mit Datei, Zeile und Bewertung (sicher / riskant / kritisch).

## 2. SQL-Injection – Pflichtregeln

- Ausschließlich parametrisierte Queries oder ORM-Methoden mit Bindings.
- KEINE String-Konkatenation oder Interpolation in SQL – auch nicht in `whereRaw`, `orderByRaw`, `DB::raw`, `selectRaw`, `havingRaw` o. ä. Falls Raw-SQL nötig ist: nur mit `?`-Bindings.
- Spalten- und Tabellennamen niemals aus User-Input; falls dynamisch nötig, nur über Whitelist.
- Suche explizit nach Mustern wie `"... WHERE email = '" . $email`, `->whereRaw("token = '$token'")`, `query("... $var ...")`, Interpolation in Heredocs.
- Input-Validierung zusätzlich (nicht als Ersatz):
  - E-Mail: serverseitig validieren, normalisieren (trim, lowercase), Maximallänge 255.
  - Token: festes Format prüfen (z. B. exakt 64 Hex-Zeichen), alles andere sofort mit 404 ablehnen, BEVOR eine DB-Abfrage passiert.
- DB-Benutzer der App: nur nötige Rechte (kein DROP/ALTER/GRANT außerhalb der Migrationen, kein Superuser) – im Bericht vermerken, was sich aus dem Code nicht prüfen lässt.
- Fehlermeldungen: keine SQL-Fehler, Stacktraces oder Query-Details an den Client. `APP_DEBUG=false` in Produktion (auch in `deploy/docker-compose.coolify.yml` prüfen).

## 3. Magic-Link-spezifische Härtung (prüfen und ggf. fixen)

- Token mit kryptografisch sicherem Zufall (mindestens 32 Byte, `random_bytes`).
- In der DB nur der SHA-256-Hash, nie der Klartext; Lookup über den Hash.
- Vergleich zeitkonstant (`hash_equals`), falls nicht direkt per DB-Lookup gelöst.
- Gültigkeit höchstens 15 Minuten und Einmal-Nutzung: Token nach Verwendung sofort invalidieren – atomar, damit ein doppelter Klick nicht zweimal anmeldet.
- Neue Anforderung invalidiert ältere offene Tokens desselben Users; erfolgreicher Login löscht alle übrigen.
- Keine User-Enumeration: Antwort beim Anfordern immer gleich („Falls die Adresse existiert, wurde ein Link gesendet“), auch zeitlich.
- Rate-Limiting auf Anforderung (pro E-Mail und pro IP) und auf Token-Validierung; hinter Cloudflare die echte Besucher-IP verwenden.
- Nach Login: Session-ID regenerieren (Session Fixation).
- Redirect-Parameter (`?next=`, `?redirect=`) nur auf interne Pfade (Open Redirect).
- Token nicht in Logs oder Aktivitätsprotokoll; `Referrer-Policy` setzen, damit das Token nicht über den Referer-Header abfließt.
- Link-Host nur aus `APP_URL` oder einer Allowlist (Host-Header-Poisoning).
- Link-Klick per GET zeigt eine Bestätigungsseite, erst der POST meldet an (Mail-Scanner).
- Admin-Bereich: eigener fester Pfad-Präfix, damit er hinter Cloudflare Access liegen kann; Rechte trotzdem in der App geprüft.

## 4. Tests

Schreibe automatisierte Tests (Pest, gegen PostgreSQL) für:

- SQLi-Payloads im E-Mail-Feld und im Token, z. B. `' OR '1'='1`, `' OR 1=1 --`, `admin'--`, `'; DROP TABLE users; --`, `" OR ""="`, `1' UNION SELECT NULL--`, URL-kodierte Varianten. Erwartung: kein Login, kein 500, keine DB-Fehler in der Antwort.
- Abgelaufenes Token, bereits benutztes Token, manipuliertes Token, falsches Format.
- Doppeltes Einlösen desselben Tokens meldet nur einmal an.
- Gleiche Antwort für existierende und nicht existierende E-Mail.
- Rate-Limit greift.
- Open Redirect wird abgewiesen.

## 5. Abschluss

Gib mir am Ende:

- Liste aller gefundenen Probleme mit Schweregrad
- Was du geändert hast (Datei + kurze Begründung)
- Was offen bleibt oder meine Entscheidung braucht
- Befehl zum Ausführen der Tests
