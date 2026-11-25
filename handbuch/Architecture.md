# Architecture

## Modules

Code lives in modules under `app/Modules`, each with its own service provider and routes:

| Module | Responsibility |
| --- | --- |
| `Accounts` | Sign-in (magic link, passkeys via `laravel/passkeys`, password + TOTP), account and security pages, admin step-up, blocking, admin bootstrap. |
| `Polls` | Polls, options, participants, votes; creating, voting, managing, finalizing; results; organizer/participant access grants; calendar download. |
| `Notifications` | All emails about polls (admin link, vote receipt/edit link, new vote, final date with `.ics`). |
| `Seo` | SEO settings (single row `seo_settings`), head tags, `sitemap.xml`, `robots.txt`, `llms.txt`, IndexNow. |

Shared kernel (`app/Support`, `app/Models`): `User`, `ActivityLog`, `ActivityLogger`, `MailDelivery`, `Tokens`, `SafeRedirect`, `EmailAddress`, `Crawlers`, `DisplayTime`, `IcsCalendar`.

Business logic lives in actions (`Modules/*/Actions`); controllers, Filament resources and models only call them.

## Registries

Modules never call each other's internals. They connect only through registries (`app/Support/Registries`, singletons):

- **`DomainEventRegistry`** – modules publish event objects (`Polls\Events\PollCreated`, `ParticipantVoted`, `DuplicateVoteAttempted`, `PollFinalized`); the Notifications module registers listeners. The Polls module knows nothing about mail.
- **`AuthMethodRegistry`** – sign-in methods shown on the login page (each with its own partial view and order).
- **`DashboardSectionRegistry`** – sections on the account page; the Polls module contributes "my polls" and "polls I voted in".
- **`PageHeadRegistry`** – partials rendered into `<head>` of the public layout. The layout passes a `PageContext` (route name, page title, robots value); the Seo module contributes title, description, canonical, Open Graph and JSON-LD. Without contributions the layout renders a plain title and robots tag.
- **`SitemapRegistry`** – public, indexable pages (`SitemapEntry`: URL, title, description, change frequency, priority). The app registers the home and privacy pages, the Polls module the create page. The Seo module builds the sitemap, `llms.txt` and the IndexNow submission from it without knowing the other modules.

A new feature registers itself in the relevant registry in its service provider.

## Access model

- **Organizers without account**: a secret admin link (`/u/<id>/verwalten/<token>`) grants management rights stored in an encrypted HTTP-only cookie (`zf_manage`, one year).
- **Participants without account**: after voting or opening the edit link the participant id is stored in an encrypted cookie (`zf_votes`).
- **Signed-in users**: own polls via `polls.user_id`; own votes via `participants.user_id` or the (verified) account email.
- **Admins**: Filament panel with policies (`app/Policies`), plus `EnsurePasskeySession` in the panel's auth middleware.

## Tokens

Magic-link, admin and edit tokens are 32 random bytes (64 hex characters). Only the SHA-256 hash is stored. The format is enforced by route constraints before any query runs. Magic links are redeemed atomically (`UPDATE … WHERE used_at IS NULL`).

## Data

All timestamps are `timestamptz` in UTC and displayed in Europe/Berlin. One participant per email and poll (unique index). Internal fields (`is_admin`, `blocked_at`, `password`, `two_factor_*`, `status`, token hashes) are only set via `forceFill`. TOTP secrets use the `encrypted` cast.

## Tests

Pest against PostgreSQL (`app_test`). Covered: SEO head tags, crawler files, IndexNow and the SEO admin page; logging of mail failures; magic-link rules including SQL-injection payloads, malformed/expired/used/manipulated tokens, enumeration, rate limits and open redirects; password + TOTP including replay; passkey step-up and admin access per role; poll creation, voting, duplicate handling, editing, finalizing and notifications; security headers; results ranking; ICS output.
