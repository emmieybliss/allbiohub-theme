# AllBioHub Pulse: audit, migration map and design plan

Status: **Phase 1 (audit) is blocked on site access. Phases 2–3 are drafted below. No changes have been made to the live site.**

---

## 1. Phase 1: audit of the existing site

### What happened

The audit could not be run against the live site from the build environment:

| Check | Result |
|---|---|
| `https://allbiohub.com/` and `/wp-json/` | Not reachable. A public DNS lookup for `allbiohub.com` and `www.allbiohub.com` failed (`ENOTFOUND`). |
| WordPress.com / Jetpack connection | The site is listed (blog ID 229726419, “AllBioHub Media”, tagline “Read. Discover. Connect In One Hub.”, self-hosted Jetpack). Jetpack reports it as **disconnected**, so no remote tools can read it. |

Either the domain is not currently resolving (expired, DNS changed, or the host is down) or it is blocked for the build environment. Someone needs to confirm this from a normal browser.

### How the audit will be run instead

`tools/allbiohub-audit/` is a small **read-only** plugin. It only reads data and writes nothing to posts, terms, users or options. After it is installed and activated, it appears under **Tools → AllBioHub Audit**. It then reports every item in section 1 of the brief:

- Active theme, parent/child relationship, block theme or classic theme, and installed themes
- WordPress, PHP and database versions, front-page settings, logo and site icon
- Active, inactive and must-use plugins
- Post types, taxonomies, all categories (with slug, parent, count, URL and meta), and the top 200 tags plus the number of empty tags
- Pages (template, word count, and which builder was used), menus and their locations, sidebars and widgets
- Media totals by MIME type, attachments whose GUIDs point at another host, and registered image sizes
- Posts that have no featured image, and posts whose featured image points to an attachment that has been deleted
- Authors (post count, whether a bio exists, social and SEO user-meta keys)
- The 300 post-meta keys used most on posts and pages, which shows custom fields and SEO fields
- The SEO plugin and how much of its title, description, canonical and primary-category meta is in use; breadcrumb support
- Permalink structure, category and tag bases, and sample URLs
- Redirects (Redirection, Rank Math, Yoast Premium, and `_wp_old_slug`)
- Comment counts and the default comment setting
- Shortcodes used in content, and whether each one is still registered (unregistered ones are left over from an old theme)
- Gutenberg blocks used, and counts of Elementor, WPBakery and Divi content
- Theme-mod keys (the old Customizer options)
- Integrations: Jetpack, Site Kit, WooCommerce, Mailchimp, MailPoet, Newsletter, Brevo, ad plugins, cache plugins, share plugins, view counters, and GA IDs in theme mods

The plugin was smoke-tested on a clean WordPress 6.8.3 install with PHP 8.3 and produced no warnings.

**Needed from AllBioHub:** one of the following.
1. Install the audit plugin, click **Download audit JSON**, and share the file. This is the preferred option.
2. Reconnect Jetpack, and make sure allbiohub.com resolves publicly.
3. Send a full export from **Tools → Export → All content** (WXR) and a copy of **Tools → Site Health → Info**.

---

## 2. Phase 2: migration map (draft until the audit arrives)

Nothing in this map runs automatically until the audit has been reviewed. Every rule is **additive**: it adds, links or redirects, and never deletes.

### 2.1 Target category structure

| New section | Slug (kept if it already exists) | Matches existing categories whose name or slug contains |
|---|---|---|
| Celebrity News | `celebrity` | celebrity, celeb, entertainment, gossip, nollywood, music, movies |
| Biography | `biography` | biography, bio, profile, people, net worth, wiki |
| Business & Startups | `business` | business, startup, founder, entrepreneur, finance, money, company |
| Technology | `technology` | tech, technology, ai, gadget, software, internet |
| Lifestyle | `lifestyle` | lifestyle, fashion, health, travel, food, relationship, beauty |
| Reviews | `reviews` | review, rating, vs |
| Real-life Stories | `stories` | story, stories, real life, inspiration |

Rules:
- **Mapping is by slug or name, never by ID.** The map is stored in the `allbiohub_category_map` option and can be edited under **AllBioHub → Migration & Health**.
- **An existing category that matches one target is made a child of that target.** This keeps its URL, posts, description and SEO meta. The post itself is not moved.
- **An existing category that matches several targets, or none, is marked “Needs manual review”.** The tool does not guess.
- **Duplicate categories** (for example “Tech” and “Technology”) are only merged after an admin approves each pair. A merge works like this:
  1. Every post in the source category is also assigned to the destination with `wp_set_post_categories(..., true)`.
  2. If the source has SEO term meta and the destination does not, it is copied over.
  3. A 301 redirect is recorded from the old term URL to the new one.
  4. The source term is kept, set to `noindex`, and hidden from menus. It is never deleted by the theme. Deletion stays a manual decision.
- Post permalinks are not affected if the permalink structure doesn't include `%category%`. If it does, the old URL of every affected post is stored and redirected.

### 2.2 Content handling

| Item | Action |
|---|---|
| Posts, pages, comments, authors | Not touched. The theme only reads them. |
| Featured images | Used as they are. Missing ones go through the fallback chain (featured → first content image → attached image → AllBioHub placeholder) **at render time**, so the database is not changed. Posts that have a broken thumbnail ID are listed for review. |
| Old-theme shortcodes (unregistered) | Registered as harmless pass-through shortcodes that output their inner content, and listed in the report. Content is not rewritten. |
| Builder content (Elementor and others) | Left in place. Pages built with a builder keep working while the builder plugin stays active, and they are flagged. |
| Menus | Existing menus are assigned to the new `primary` and `footer` locations by matching names. Unmatched menus are left alone. |
| Widgets | Sidebar IDs are mapped to the new sidebars. Empty widgets are reported, not removed. |
| Theme mods / Customizer | The old theme's mods stay stored under the old theme (WordPress keeps them per theme). The logo, site icon and colours are copied into Pulse settings. |
| SEO meta | Not touched. Pulse outputs no titles, meta or schema when Yoast, Rank Math or AIOSEO is active. It only adds its own `Article` and `Person` schema when no SEO plugin is present. |
| Redirects | New redirects are stored in Pulse's own table. Any existing redirect plugin is left alone, and the two are checked against each other for conflicts. |
| Pinned stories | Stored in `_allbiohub_pinned` and `_allbiohub_pin_order` post meta, and managed only through the admin UI. |
| View counts | Read from Jetpack, Post Views Counter or WP-PostViews if one is present. Otherwise Pulse keeps its own lightweight `_allbiohub_views` count. |

### 2.3 Backup and rollback

1. **Before activation:** take a full backup through the host or a backup plugin (database and `wp-content/uploads`). The Migration screen won't enable “Apply” until an admin confirms a backup exists.
2. **On activation:** Pulse runs **detection only** (steps 1–11 of section 41 in the brief) and writes the result to `allbiohub_migration_report`. Nothing is applied yet.
3. **Applying:** each change is a separate step that an admin approves. Every applied change is written to a log (`allbiohub_migration_log`) along with the data needed to undo it: the term relationships added, the term parents changed, and the redirects created.
4. **Rollback:** a button on the Migration screen reverses the log in reverse order. Switching back to the old theme always works, because no old data is removed.
5. **Report:** Changed / Fixed / Skipped / Needs manual review, exportable as CSV.

---

## 3. Phase 3: theme architecture

Classic PHP theme, so that per-section admin control and the admin screens are straightforward. Gutenberg is supported for post content. There is no build step and no framework. Vanilla JS is used only for the drawer, search, theme toggle, ticker and admin sorting.

```
allbiohub-pulse/
├── style.css               theme header, version
├── functions.php           loads inc/*
├── index.php  front-page.php  single.php  single-biography.php  page.php
├── archive.php  category.php  search.php  author.php  404.php
├── page-templates/advertise.php
├── assets/
│   ├── css/  tokens.css  base.css  components.css  home.css  article.css  admin.css
│   ├── js/   site.js (≈5 KB)  admin.js
│   ├── images/ placeholder.svg
│   └── fonts/  self-hosted WOFF2 subsets (see 4.2)
├── inc/
│   ├── setup.php           supports, menus, image sizes, sidebars
│   ├── enqueue.php         conditional CSS/JS, font preloads
│   ├── helpers.php         reading time, image fallback, primary category, views
│   ├── theme-options.php   AllBioHub → Theme Settings (Settings API, tabbed)
│   ├── homepage.php        section registry and order
│   ├── pinned-stories.php  pin UI, meta, REST endpoint for reordering
│   ├── trending.php        auto, manual and hybrid sources, cached in transients
│   ├── biography.php       bio detection, Quick Profile meta box, TOC
│   ├── people.php          People You Should Know query
│   ├── advertising.php     placements, scheduling, rendering, inventory
│   ├── sponsored.php       sponsored flag, labels, rel="sponsored"
│   ├── migration.php       detection, category map, apply and rollback log
│   ├── redirects.php       301 table and handler
│   ├── seo.php             SEO-plugin detection and fallback schema
│   └── performance.php     head cleanup, preloads, query caching
├── template-parts/  hero  trending  pinned-stories  sponsored  biography  people
│                    story-card  category-section  newsletter  footer  ad-slot
└── admin/  css/  js/  views/  (settings, ads, inventory, migration)
```

Admin menu: **AllBioHub** → Theme Settings · Pinned Stories · Ads & Sponsored Content · Ad Inventory · Migration & Health.

Data: settings use one autoloaded option, `allbiohub_settings`. Ads use `allbiohub_ads` (one record per placement). Pins use post meta. Redirects use a small custom table created with `dbDelta`. All writes require a capability, a nonce and sanitization.

---

## 4. Phase 4: design system (proposal)

### 4.1 Colour tokens

The exact AllBioHub purple will be taken from the existing logo once it is available. Until then the provisional token is **`#5B2BD9`**.

| Token | Light | Dark |
|---|---|---|
| `--bg` | `#FBFAF7` (warm off-white) | `#121014` |
| `--surface` | `#FFFFFF` | `#1B1820` |
| `--ink` | `#111111` | `#F2EFEA` |
| `--ink-2` (meta) | `#5C5A57` | `#A9A4AE` |
| `--rule` | `#E6E2DA` | `#2C2833` |
| `--accent` | `#5B2BD9` | `#A88BFF` (lifted for contrast) |
| `--accent-ink` (text on accent) | `#FFFFFF` | `#121014` |
| `--sponsored` | `#8A6A00` on `#FFF6D6` | `#F1D48A` on `#2A2410` |

Purple is used only for category labels, buttons, active navigation, pinned badges, section markers and links. Sponsored content uses a separate amber treatment, so that ads never look like editorial content.

### 4.2 Typography

- **Headlines:** *Instrument Serif*. It is a tight, contemporary editorial serif and lighter than Playfair, using one weight plus italic.
- **UI and body:** *Inter* as a variable font (400–700).
- Both are self-hosted as WOFF2 with Latin and Latin-Extended subsets, `font-display: swap`, and the headline font preloaded. Google Fonts is not called at runtime.
- Scale (fluid `clamp`): H1 40→64px · H2 30→40 · H3 22→26 · H4 18→20 · body 17/1.65 · meta 13 · category label 12 uppercase with +0.08em tracking · caption 13 italic.

### 4.3 Identity elements

These are what make it recognisably AllBioHub rather than a stock theme:
- **The “pulse rule”.** Each section heading has a short purple bar followed by a thin hairline. It is used consistently across sections.
- **Numbered editorial lists** (01–05) set in the serif face for Trending Stories.
- **Square-cornered imagery** (2px radius at most) with a fixed aspect ratio per context: 16:9 for the hero, 4:3 for cards and 1:1 circles for people. All images have width and height set, which prevents layout shift.
- **Pinned badge.** A small purple tab with a pin glyph and the pin number.
- **Sponsored badge.** Amber, with letter-spaced “SPONSORED” text and the advertiser's name, and never in the editorial purple.

### 4.4 Layout

- Container widths are 1240px for content and 720px for the article measure. The grid is 12 columns on desktop, 6 on tablet and 4 on mobile.
- The header is 64px on desktop and 56px on mobile, sticky. A 36px trending ticker sits under it and is hidden when the user scrolls down.
- Breakpoints: 480, 768, 1024, 1280 and 1536. The layout is checked at every width listed in the brief (320 to 1920).

---

## 5. Build phases and order

| # | Phase | Output |
|---|---|---|
| 1 | Audit | Audit plugin ✓. The **report from the site is still needed** |
| 2 | Migration map | Draft above. It will be finalised against the real categories |
| 3 | Architecture | Above. The skeleton theme is next |
| 4 | Design system | Tokens, type and base components |
| 5 | Header and navigation | Sticky header, dropdowns, mobile drawer, search overlay, dark-mode toggle, ticker |
| 6 | Homepage | Section registry, hero with trending, category sections, newsletter, footer |
| 7 | Pinned stories | Admin list with drag-and-drop ordering (3–5 stories), front-end strip, fallback |
| 8 | Ads and sponsored | Eight placement types, scheduling, inventory screen, impression and click tracking |
| 9 | Biography | Detection, Quick Profile meta box, single-biography template, TOC |
| 10 | People You Should Know | Query, image fallback, scroll strip |
| 11 | Article templates | Single post, related stories, more from the category, share, ad slots |
| 12 | Archives | Category, tag, author, search with filters, 404 |
| 13 | Admin settings | Full settings tabs |
| 14 | Migration | Detection on activation, approve and apply, rollback, report |
| 15 | SEO and performance | Plugin-aware schema, preloads, query caching, image sizes |
| 16–17 | Responsive testing and QA | Checklist from section 52 of the brief, tested logged-in and logged-out |

Phases 3–13 do not depend on access to the live site and can continue now on a local test install with sample content. That content is only for testing and never ships in the theme. Phases 14–17 need the audit report or a staging copy of the site.

## 6. Open questions

1. Can allbiohub.com be reached from a normal browser right now? If it can, it may be blocking automated access.
2. Is there a **staging** copy of the site where the theme can be tested before going live?
3. Where can the original logo files (SVG or PNG) be found, along with the exact brand purple?
4. Which newsletter provider is in use?
