# SEO

Zeitfinder tells search engines and AI crawlers which pages are public and how to describe them. Everything is managed in the admin area under **SEO** (`/admin/seo`).

## What is public

Only these pages may be indexed:

| Page | Path |
| --- | --- |
| Home page | `/` |
| New poll | `/neu` |
| Privacy | `/datenschutz` |

Polls (`/u/…`), sign-in, account and admin pages are always `noindex`, never appear in the sitemap and are disallowed in `robots.txt`. Poll links stay private even when someone shares them publicly.

## Settings (admin area → SEO)

| Field | Used for |
| --- | --- |
| **Meta-Titel** (home page) | `<title>` and `og:title` of the home page, at most 70 characters. |
| **Meta-Beschreibung** (home page) | `<meta name="description">` and `og:description` of the home page, at most 160 characters. Also the summary in `llms.txt` and in the structured data. |
| **Name der Website** | Appended to other page titles ("Neue Umfrage · Zeitfinder"); `og:site_name`; name in the structured data and in `llms.txt`. |
| **Standard-Beschreibung** | Meta description of all other pages. |
| **Betreiber (Schema.org)** | Optional. Adds an `Organization` to the structured data of the home page. |
| **Indexierung erlauben** | Off: every page gets `noindex,nofollow`, `robots.txt` disallows everything, the sitemap is empty, `llms.txt` returns 404 and IndexNow is not used. Useful for a staging copy. |
| **Zusätzliche Regeln für robots.txt** | Extra lines appended below the default rules, for example to block a specific AI crawler. |
| **Einleitung für llms.txt** | Optional Markdown text describing the site for language models. |
| **IndexNow verwenden** | Notify IndexNow after saving (see below). |

Empty text fields use built-in defaults, shown as placeholders in the form. Every change is recorded in the activity log (`seo.updated`).

## Generated files

| URL | Content |
| --- | --- |
| `/sitemap.xml` | XML sitemap of the public pages. |
| `/robots.txt` | Allows everything except `/admin`, `/u/`, `/konto`, `/anmelden`, plus your extra rules and the sitemap link. |
| `/llms.txt` | Markdown summary for language models ([llmstxt.org](https://llmstxt.org)): site name, description, your introduction and the public pages. |
| `/<key>.txt` | IndexNow key file (only while IndexNow is on). |

These responses set no cookies and have their own rate limit (60 requests per minute per IP).

## Meta tags and structured data

Every public page renders title, meta description and robots tag. Indexable pages also get a canonical link and Open Graph tags. The home page additionally contains [Schema.org](https://schema.org) structured data as JSON-LD: `WebSite`, `WebApplication` (free, web-based, German) and, if set, the operator as `Organization`.

## IndexNow

[IndexNow](https://www.indexnow.org) informs Bing, Yandex, Seznam and others about changed pages right away (Google does not take part).

- A random key is created during installation. Its file is served at `https://<your-domain>/<key>.txt`; the SEO page shows the exact URL.
- With **IndexNow verwenden** switched on, saving the SEO settings submits all sitemap URLs after the response.
- **An IndexNow senden** (button at the top of the SEO page) submits them manually.
- At most one submission every 10 minutes. Submissions only happen when `APP_URL` is a public `https` address.
- The last submission time and HTTP status are shown on the SEO page and recorded in the activity log (`seo.indexnow`). Failures are written to the runtime log.

## For developers

Pages are added to the sitemap through the `SitemapRegistry`, head tags come from the `PageHeadRegistry`; see [Architecture](Architecture). A page that should be indexed also needs `robots="index,follow"` on its layout component.
