# Administration

The admin area is at **`/admin`** (Filament). All admin functions live under this prefix, so a Cloudflare Access application can protect exactly this path.

## Access rules

1. The account must be an administrator.
2. The current session must be **verified with a passkey**. Signing in by magic link or password is not enough: you are redirected to **Passkey bestätigen** (`/konto/passkey-bestaetigen`).
   - Without a passkey you set one up there and confirm it immediately.
   - With a passkey you confirm the session with it.
3. Administrators who already own a passkey can add or remove passkeys only in a passkey-verified session (a stolen magic-link session cannot register its own passkey).

## First administrator

Set the environment variable `ADMIN_EMAIL`. When an account with this address signs in (or is created by the first magic-link sign-in), it becomes an administrator. Rights are never revoked automatically. The address is never hard-coded.

Flow for the first start: open `/anmelden`, request a magic link for the `ADMIN_EMAIL` address, open it, then open `/admin` and register your passkey.

## Sections

- **Umfragen** – all polls with status, number of options and participants; view details, open the public page, delete.
- **Benutzer** – all accounts with admin flag, passkeys, password+2FA status, last sign-in. Actions: edit name, block/unblock, grant/revoke admin rights, delete. You cannot block, demote or delete yourself. Accounts are only created by signing in.
- **Rechtliches** – operator details, hoster and other providers for the imprint and the privacy notice; see [Legal pages and data retention](Legal-and-Retention).
- **SEO** – meta title and description, indexing switch, extra `robots.txt` rules, `llms.txt` introduction and IndexNow; see [SEO](SEO).
- **Aktivitätsprotokoll** – changes to domain data (polls created/updated/finalized/deleted, votes, sign-ins, admin actions, SEO changes and IndexNow submissions, failed mails). Secrets such as tokens and passwords are never logged. Entries are deleted after 6 months.

## Emergency: lost passkeys

If an administrator lost every passkey, remove them on the server and set up a new one:

```bash
php artisan zeitfinder:reset-passkeys admin@example.com
```

Afterwards sign in by magic link and register a new passkey on the step-up page.
