@docs/STANDARDS.md

# <App-Name>

<Ein bis zwei Sätze: was die App macht und für wen.>

Die gemeinsamen Standards oben gelten vollständig. Hier steht nur, was für dieses Projekt dazukommt oder bewusst abweicht.

## Projekt

- Sichtbarkeit: <öffentlich/privat>
- Sprache der Oberfläche: Deutsch
- Erreichbarkeit: <öffentlich → Admin-Bereich `/admin` hinter Cloudflare Access / nur im Tailnet>
- Deploy: automatisch per Webhook (`AUTO_DEPLOY=true`)<, oder Begründung für manuell>
- Queue und Scheduler: <keine (QUEUE_CONNECTION=sync) / Worker und Scheduler in deploy/docker-compose.coolify.yml>

## Befehle

```bash
bin/init-laravel.sh        # nur einmal: Laravel 13, Filament 5, Pest, Larastan in die Vorlage installieren
php artisan serve          # App (plus: npm run dev)
vendor/bin/pest            # Tests (PostgreSQL, Datenbank app_test)
vendor/bin/pint            # Code-Stil
vendor/bin/phpstan analyse # statische Analyse
```

Slash-Commands: `/login-audit` prüft und härtet den Login-Flow.

## Architektur

<Module, Registries, wichtige Services und Regeln, die man leicht bricht.>

## Abweichungen von den Standards

Keine.
