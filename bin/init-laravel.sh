#!/bin/bash
# Installiert Laravel 13, Filament 5, Pest und Larastan in diese Vorlage.
# Nur einmal direkt nach dem Anlegen des Repos ausführen (im Codespace).
set -e
cd "$(dirname "$0")/.."

if [ -f composer.json ]; then
    echo "composer.json ist schon vorhanden, hier ist nichts mehr zu tun."
    exit 1
fi

TMP=$(mktemp -d)
composer create-project laravel/laravel "$TMP/app" "^13.0" --prefer-dist --no-interaction

# Laravel-Dateien übernehmen, ohne Dateien der Vorlage zu überschreiben
cp -rn "$TMP/app/." .
rm -rf "$TMP"

[ -f .env ] || cp .env.example .env
grep -q "APP_KEY=base64:" .env || php artisan key:generate

# PHP-Version an die Vorlage koppeln (Standard: drei Stellen gemeinsam ändern)
php -r '$f="composer.json";$j=json_decode(file_get_contents($f),true);$j["require"]["php"]="^8.5";file_put_contents($f,json_encode($j,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");'

# Tests gegen PostgreSQL statt SQLite
sed -i -E 's#<env name="DB_CONNECTION" value="[^"]*"/>#<env name="DB_CONNECTION" value="pgsql"/>#; s#<env name="DB_DATABASE" value="[^"]*"/>#<env name="DB_DATABASE" value="app_test"/>#; s#<!-- *(<env name="DB_(CONNECTION|DATABASE)"[^>]*/>) *-->#\1#' phpunit.xml
grep -q 'value="pgsql"' phpunit.xml || echo "WARNUNG: DB_CONNECTION in phpunit.xml bitte von Hand auf pgsql/app_test setzen."

composer require filament/filament:"^5.0" -W --no-interaction
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan -W --no-interaction
[ -f tests/Pest.php ] || vendor/bin/pest --init

cat > phpstan.neon <<'NEON'
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 5
NEON

# Filament-Panel anlegen (fragt interaktiv nach der Panel-ID, Vorschlag: admin)
php artisan filament:install --panels

npm install
npm run build
php artisan migrate --force

echo ""
echo "Laravel 13 mit Filament 5, Pest und Larastan ist installiert."
echo "Nächste Schritte: CLAUDE.md und README.md ausfüllen, dann committen und pushen."
