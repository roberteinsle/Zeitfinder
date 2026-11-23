# Accounts and Sign-in

Accounts are optional. With an account you find all your polls and the polls you voted in under **Konto**, and you can manage your polls without the admin link.

The first sign-in with an address creates the account. Signing in and signing up are the same flow.

## Methods

### Magic link (email)

1. Enter your email on **Anmelden** (`/anmelden`) and click **Anmeldelink senden**.
2. The answer is always the same, whether or not the address is known.
3. Open the link from the email and confirm with **Jetzt anmelden**. (The link only shows a button; mail scanners that pre-open links cannot sign you in.)

Rules: the link is valid for 15 minutes and works once. Requesting a new link invalidates older ones; a successful sign-in removes all others. After the first sign-in by link the app offers to set up a passkey.

### Passkey

Sign in with Face ID, fingerprint, device PIN or a security key. Manage passkeys under **Konto → Sicherheit**: add a passkey (optionally named), see when each was last used, remove passkeys.

### Password with two-factor code

A password exists only together with a TOTP code (authenticator app).

1. Sign in once by magic link or passkey.
2. Under **Konto → Sicherheit → Passwort mit Zwei-Faktor-Code** choose a password (at least 12 characters, must not appear in known data breaches).
3. Scan the QR code (generated locally) with an authenticator app and confirm with a code. Only then is the password stored.

Signing in: email + password, then the 6-digit code. A code can be used only once. Password login can be disabled at any time.

## Limits

To protect against abuse, sign-in requests are rate-limited:

| Action | Limit |
| --- | --- |
| Request magic link | 3 per address per 15 minutes, 20 per IP per hour |
| Open/confirm magic link | 10 per IP per minute |
| Password | 5 per address per 15 minutes, 10 per IP per minute |
| Two-factor code | 5 per account per minute, 10 per IP per minute |
| Passkey endpoints | 10 per IP per minute |

## Blocked accounts

Administrators can block accounts. A blocked account cannot sign in with any method and existing sessions end on the next request.
