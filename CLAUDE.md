@docs/STANDARDS.md

# Zeitfinder

Scheduling poll app (a nicer, faster alternative to classic date polls): organizers propose dates or time slots, share a link, participants answer Yes / No / If need be without an account, the organizer fixes the final date and everybody is notified by email.

The shared standards above apply in full. This file only lists what is added or deliberately different for this project.

## Project

- Visibility: see GitHub; treat as potentially public (no personal data in the repo).
- UI language: German. Documentation (`handbuch/`, wiki, README, this file): **English**.
- Reachability: public; admin area `/admin` behind Cloudflare Access.
- Deploy: automatic via webhook (`AUTO_DEPLOY=true`) for pushes to the default branch.
- Queue and scheduler: none (`QUEUE_CONNECTION=sync`); mails are sent after the response via `defer()`.

## Commands

```bash
php artisan serve          # app (plus: npm run dev)
vendor/bin/pest            # tests (PostgreSQL, database app_test)
vendor/bin/pint            # code style
vendor/bin/phpstan analyse # static analysis
php artisan zeitfinder:reset-passkeys <email>   # emergency: remove all passkeys of an account
python3 scripts/build-wiki.py ../Zeitfinder.wiki # regenerate the wiki from handbuch/
```

Slash commands: `/login-audit` checks and hardens the login flow.

## Architecture

- Modules in `app/Modules/{Accounts,Polls,Notifications}`, each with a service provider (registered in `bootstrap/providers.php`) and its own `routes.php`. Business logic in `Actions/`.
- Modules interact **only** through registries in `app/Support/Registries`:
  - `DomainEventRegistry` – Polls publishes `PollCreated`, `ParticipantVoted`, `DuplicateVoteAttempted`, `PollFinalized`; Notifications listens. Polls must never send mail itself.
  - `AuthMethodRegistry` – login methods on `/anmelden`.
  - `DashboardSectionRegistry` – sections on `/konto`.
- Shared kernel: `app/Models/User`, `ActivityLog`, `app/Support/*` (`Tokens`, `SafeRedirect`, `EmailAddress`, `DisplayTime`, `IcsCalendar`, `ActivityLogger`).
- Admin: Filament panel `admin` in `app/Filament`, policies in `app/Policies` (registered in `AppServiceProvider`). Panel requires `is_admin` **and** a passkey-verified session (`EnsurePasskeySession`, flag set by a `PasskeyVerified` listener; magic-link/password sign-ins clear it).
- Organizer and participant rights without account: encrypted cookies `zf_manage` / `zf_votes` via `Polls\Support\PollAccess`.

Rules that are easy to break:

- Links in mails come from `route()` with the forced root URL (`APP_URL`); never build URLs from the request host.
- Public pages must work under the strict nonce CSP: no inline scripts, no inline `style` attributes, no external resources. Use `resources/js/*` with `data-*` hooks.
- Do not mix inline `@php(...)` with `@php … @endphp` blocks in one Blade file (compiles wrongly); use block form.
- Our icon component is `<x-glyph>`; `<x-icon>` belongs to blade-icons (Filament).
- Every new route needs a rate limiter; vendor routes outside `web` get `throttle:global` automatically (`AppServiceProvider`).
- Tests do not carry cookies between requests; use `carryCookies()` from `tests/Pest.php`.

## Deviations from the standards

- Documentation (`handbuch/`, wiki, README, CLAUDE.md) is written in English at the maintainer's request; the UI stays German.
- CI triggers on `main` and `master` and deploys from the repository's default branch (the repository was created with `master`).
