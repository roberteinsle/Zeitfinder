#!/bin/bash
set -e

echo "Richte die Entwicklungsumgebung ein ..."

# PHP-Erweiterungen, die das Devcontainer-Image nicht mitbringt
if ! php -m | grep -qi pdo_pgsql || ! php -m | grep -qi '^zip$' || ! php -m | grep -qi '^intl$'; then
    sudo apt-get update -qq
    sudo apt-get install -y -qq libpq-dev libzip-dev libicu-dev libpng-dev postgresql-client
    sudo docker-php-ext-install pdo_pgsql zip intl gd
fi

echo "Starte PostgreSQL ..."
docker compose up -d db
until docker compose exec -T db pg_isready -U app > /dev/null 2>&1; do
    sleep 2
done
# Testdatenbank (siehe phpunit.xml)
docker compose exec -T db createdb -U app app_test 2>/dev/null || true

npm install -g @anthropic-ai/claude-code

if [ ! -f composer.json ]; then
    echo ""
    echo "Noch keine Laravel-App vorhanden. Einmal ausführen:  bin/init-laravel.sh"
    exit 0
fi

composer install --no-interaction
npm ci
[ -f .env ] || cp .env.example .env
grep -q "APP_KEY=base64:" .env || php artisan key:generate
php artisan migrate --force
php artisan storage:link || true

echo ""
echo "Fertig:"
echo "  App:    php artisan serve   (plus: npm run dev)"
echo "  Tests:  vendor/bin/pest"
