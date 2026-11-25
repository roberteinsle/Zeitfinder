# Zeitfinder

A fast, mobile-friendly scheduling poll app: propose dates, share a link, everyone answers Yes / No / If need be, fix the final date and notify everybody. No account needed; optional sign-in via magic link, passkey or password with TOTP.

## Quick start

1. Open a Codespace (Code → Codespaces → Create codespace on main).
2. `php artisan migrate`
3. `php artisan serve` and in a second terminal `npm run dev`
4. Tests: `vendor/bin/pest` (PostgreSQL, database `app_test`)

Imprint and privacy details are entered in the admin area under `/admin/rechtliches`. SEO (meta tags, sitemap, robots.txt, llms.txt, Schema.org, IndexNow) is managed in the admin area under `/admin/seo`.

Details are in the handbook (`handbuch/`, source of the wiki).

## Operations

GitHub Actions builds the image and pushes it to GHCR; Coolify only pulls it (`deploy/docker-compose.coolify.yml`). PostgreSQL is a separate Coolify resource. Set `APP_URL`, `ADMIN_EMAIL` and the `MAIL_*` variables in Coolify.
