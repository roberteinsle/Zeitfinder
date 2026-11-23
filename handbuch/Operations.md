# Operations

## Stack

PHP 8.5, Laravel 13, Filament 5, PostgreSQL 18, `laravel/passkeys`, Tailwind CSS 4, vanilla JavaScript (no inline scripts, strict CSP).

## Configuration (environment)

All environment-specific values come from Coolify environment variables. Only `.env.example` (without real values) is in the repository.

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Public base URL. **All links in emails are built from it**, never from the request's Host header. Also the passkey relying party (domain). |
| `APP_KEY` | Encryption key. Never change it in production (encrypted TOTP secrets and cookies depend on it). |
| `ADMIN_EMAIL` | First administrator. |
| `BEHIND_CLOUDFLARE` | `true` only if the server accepts web traffic exclusively via Cloudflare. The real visitor IP is then taken from `CF-Connecting-IP` for rate limits and logs. |
| `DB_*` | PostgreSQL (separate Coolify resource, reached via the `coolify` network). |
| `MAIL_*` | SMTP for magic links and notifications. `MAIL_FROM_ADDRESS` must be set. |
| `PASSKEYS_USER_HANDLE_SECRET` | Optional; defaults to `APP_KEY`. |

`APP_DEBUG` is always `false` in production.

## Deployment

GitHub Actions runs tests (PostgreSQL), Pint, Larastan, `composer audit`, `npm audit`, Gitleaks, builds the image, scans it with Trivy and pushes it to GHCR. Coolify only pulls the image (`deploy/docker-compose.coolify.yml`). Migrations run at container start. Rollback: set `APP_TAG` to a `sha-…` tag and redeploy.

The deploy webhook is triggered for pushes to the repository's default branch when the repository variable `AUTO_DEPLOY=true` and the secrets `COOLIFY_WEBHOOK` and `COOLIFY_TOKEN` are set.

There is no queue worker: emails are sent right after the response (`defer()`), so pages stay fast. A failed mail is logged and recorded in the activity log, but never undoes the action.

## Logs and errors

In production the app logs to `stderr` (`LOG_CHANNEL=stderr` in `deploy/docker-compose.coolify.yml`). PHP-FPM forwards worker output to the container log (`catch_workers_output = yes` in the image), so everything shows up in Coolify under **Runtime Logs**:

- unhandled exceptions with stack trace (visitors only see a generic error page, `APP_DEBUG=false`),
- failed mails: `Magic link mail could not be sent.` or `Notification mail failed.` with the exception class and the transport message, e.g. the SMTP reply (`535 Authentication failed`, `Connection could not be established …`). Mail content and tokens are never logged,
- failed or rejected IndexNow submissions.

Failed notification mails and admin actions additionally appear in the admin area's activity log.

## Favicons and caching

Static files are served with `Cache-Control: immutable` for a year and cached by Cloudflare. The layout therefore links the favicons with a content hash (`favicon.svg?v=…`), so a changed icon shows up after the next deploy. Browsers that request `/favicon.ico` directly may keep the old file until it expires or the Cloudflare cache is purged for that URL.

## Cloudflare

The app is public. Recommended:

- Serve web traffic only through Cloudflare (Tunnel, or a firewall that admits only Cloudflare IPs) and set `BEHIND_CLOUDFLARE=true`.
- Protect the path `/admin*` with a Cloudflare Access application whose policy includes every administrator. The app still enforces admin rights and the passkey requirement itself.

## Security headers

Every response carries CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer` (keeps tokens in URLs from leaking) and, over HTTPS, HSTS. Public pages use a nonce-based CSP without `unsafe-eval`; the Filament admin area needs `unsafe-eval`/`unsafe-inline`.

## Rate limits

Every route has a rate limit (baseline 300 requests per minute per IP). Stricter limits: poll creation 20/hour per IP and 100/day per user, voting 20/minute per IP, secret admin/edit links 20/minute per IP, management 60/minute per IP, crawler files (`sitemap.xml`, `robots.txt`, `llms.txt`, IndexNow key) 60/minute per IP, IndexNow submissions at most one per 10 minutes, plus the sign-in limits in [Accounts and Sign-in](Accounts-and-Sign-in). A poll accepts at most 500 participants.

## Local development

```bash
docker compose up -d db     # PostgreSQL
php artisan migrate
php artisan serve           # plus: npm run dev
vendor/bin/pest             # tests against PostgreSQL (database app_test)
```

With `MAIL_MAILER=log` the magic links appear in `storage/logs/laravel.log`.

## Wiki

The GitHub wiki is generated from `handbuch/`:

```bash
python3 scripts/build-wiki.py ../Zeitfinder.wiki   # then commit and push the wiki repo
```
