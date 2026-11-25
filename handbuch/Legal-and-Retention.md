# Legal pages and data retention

## Imprint and privacy notice

Edited in the admin area under **Rechtliches** (`/admin/rechtliches`). The values are personal data of the operator and live only in the database, never in the repository.

| Field | Shown on |
| --- | --- |
| **Name, Anschrift, E-Mail, Telefon** | Imprint (`/impressum`) and "Verantwortlich" in the privacy notice (`/datenschutz`) |
| **Weitere Angaben im Impressum** | Imprint |
| **Hoster**, **Weitere Dienstleister** | Privacy notice, "Wer bekommt die Daten?". Name every service that receives personal data (mail delivery, reverse proxy …), its purpose and transfers to third countries. |
| **Aufsichtsbehörde** | Privacy notice, "Deine Rechte" |
| **Weitere Angaben** | End of the privacy notice |

- The imprint stays hidden (404, no footer link) until name, address and email are filled in. It is `noindex` and not in the sitemap, because it contains a postal address.
- The texts are plain text; line breaks are kept.
- Changes are recorded in the activity log (`legal.updated`) with field names only.
- The fixed part of the privacy notice describes what the app does. When a feature changes which data is stored, shown or passed on, change `resources/views/legal/privacy.blade.php` in the same commit. The text is no legal advice; have it checked before publishing.

Both pages are linked in the footer on every public page, on phones above the tab bar.

## Automatic deletion

Once a day the app deletes outdated data:

| Data | Deleted |
| --- | --- |
| Polls with options, participants (names, email addresses) and votes | 6 months after the last proposed date, at the earliest 6 months after creation |
| Activity log entries | after 6 months |
| Sign-in links | when expired |

Accounts are not deleted automatically. The period is `retention_months` in `config/zeitfinder.php`; the privacy notice shows the same number. **Deletion is final** and also applies to polls of signed-in users.

There is no scheduler container. The container health check (`/up`, every 30 seconds) starts the clean-up at most once every 24 hours, after the response; a failing task is logged, retried after an hour and never turns the health check red. Results are written to the runtime log (`Maintenance finished.`) and, as counts only, to the activity log (`maintenance.pruned`). To run it by hand:

```bash
php artisan zeitfinder:maintenance
```

Backups of the database and container logs are outside the app: limit their retention in Coolify/Docker, so deleted data does not live on there.
