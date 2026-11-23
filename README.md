# <App-Name>

<Kurzbeschreibung in einem Satz.>

## Schnellstart

1. Codespace öffnen (Code → Codespaces → Create codespace on main).
2. Nur beim ersten Mal: `bin/init-laravel.sh`
3. `php artisan serve` und in einem zweiten Terminal `npm run dev`

Details stehen im Handbuch (`handbuch/`, Quelle des Wikis).

## Betrieb

GitHub Actions baut das Image und schiebt es nach GHCR, Coolify holt es nur (`deploy/docker-compose.coolify.yml`). PostgreSQL ist eine eigene Coolify-Ressource.
