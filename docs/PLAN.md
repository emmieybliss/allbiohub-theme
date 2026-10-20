# AllBioHub Pulse: audit, migration map and design plan

Status: **Phase 1 audit is complete (2026-10-20). The Phase 2 migration map is based on real site data. No changes have been made to the live site.**

---

## 1. Phase 1: audit of the existing site (completed 2026-10-20)

The audit was produced on the live site by `tools/allbiohub-audit` on 2026-10-20 at 09:15 UTC. The raw report is not committed, because the repository is public.

### 1.1 Platform

| Item | Finding |
|---|---|
| WordPress / PHP / DB | 7.1.3 · PHP 8.3.35 · MariaDB 11.4 |
| Active theme | **Foxiz 2.7.7** (parent theme, no child theme), with the **Foxiz Core** plugin. Twenty Twenty-Five is also installed. |
| Front page | Static page **“AllBioHub” (ID 9822), built with Elementor**. The blog page is “Blog” (ID 749). 12 posts per page. |
| Permalinks | `/%postname%/`, with the default `/category/` and `/tag/` bases. **Post URLs do not contain the category**, so category work cannot change any article URL. |
| Logo / icon | `uploads/2024/02/cropped-20240221_200933_0000-1.png` and `uploads/2026/08/cropped-ChatGPT-Image-Aug-18-2026-06_24_29-PM.png`. Pulse will reuse both. |
| Theme mods | Only `custom_css_post_id` and `nav_menu_locations`. Foxiz keeps its settings in its own option, not in theme mods. |

### 1.2 Content

| Type | Count | Notes |
|---|---|---|
| Posts | 994 published · 10 drafts | |
| Pages | 29 | Two use Elementor (Home 9822 and “Popcult Home 2”). The rest use the classic editor. |
| Startups (`abh_startup`) | 208 | From the AllBioHub Startup Database plugin, at `/startups/`. Pulse must not affect them. |
| Founders (`abh_founder`) | 1 | Same plugin |
| Media | 1,215 | 967 JPEG · 140 WebP · 91 PNG · 5 AVIF · 4 MP4 · 4 PDF. 229 attachments have a GUID that doesn't start with `https://allbiohub.com`, most likely older `http://` GUIDs. This is harmless because GUIDs are not used for display. |
| Featured images | 9 published posts have none · **0 broken** | The 9 posts are listed in the raw report. The fallback chain covers them. |
| Comments | 3 approved · 5 spam | Comments are open by default. |
| Authors | 5 | AllBioHub Editorial (645 posts, has a bio and socials), Abubakar Idris (176, no bio), EmmanuelTife (128, has a bio), Muhammad (45, no bio), Coolest (0) |
| Elementor posts | 25 | Kept as they are. They render while Elementor is active. |
| Gutenberg | 7 posts | The classic editor is active site-wide. |

### 1.3 Categories (9) and tags (344)

| Category | Slug | Posts | SEO meta |
|---|---|---|---|
| BIOGRAPHY | `biography` | 380 | Rank Math focus keyword |
| CELEBRITY NEWS | `celebrity-news` | 350 | none |
| AROUND THE WEB | `around-the-web` | 212 | Rank Math title, description and canonical |
| STARTUP FOUNDERS & INNOVATOR | `startup-founders-innovator` | 29 | Rank Math title and description |
| REVIEWS | `reviews` | 14 | Rank Math title and description |
| MONEY & CAREER | `money-career` | 13 | Rank Math title and description |
| WOMEN IN TECH | `women-in-tech` | 5 | none |
| EDITORIAL SUBMISSION | `editorial-submission` | 1 | none |
| SPONSORED | `sponsored` | 0 | none |

**There are no Technology or Lifestyle categories.** Technology content is tagged instead (`allbiohub-tech`, `africatech`, `tech-news-nigeria`), or filed under Around the Web.

Tags: 344 in total, 25 of them empty. There are a few near-duplicates, such as `theladymotara` / `ladymotara`, and a `women-in-tech` tag that has the same name as the category.

### 1.4 Menus, widgets and ads

- **Locations** (Foxiz): `foxiz_main` and `foxiz_mobile` use “Main Menu” (Startups and the 6 categories), and `foxiz_mobile_quick` uses “Secondary Menu”.
- **Other real menus:** Footer Menu (9 items), Footer Menu 2 (7), Top Bar Menu (8), and Social Menu (6). Several Social Menu links look out of date: `OptimistSky` handles and **Google Plus**. These need review. The footer “Advertise With Us” item points to `mediakitad.netlify.app`, while the Top Bar points to `/advertise-with-us/`.
- **Leftover demo menus:** “TieLabs Main Menu” and “TieLabs Secondry Menu” (Jannah demo with placeholder items), plus 4 “Header Menu / Collapsable Menu” stubs. These are not assigned to any location, so they are left alone and flagged.
- **Sidebars:** Foxiz sidebars contain **Foxiz ad-script widgets**: header (`widget-ad-script-15`), single top (`-12`) and bottom (`-7`), default sidebar (`-9`), blog (`-14`), more menu (`-11`), and sb2 (`-10`). There are 19 widgets in the inactive pool.
- **Ads:** **Ad Inserter** is active, Ads.txt Manager handles `ads.txt` and `app-ads.txt`, and there are Foxiz ad widgets. `_advads_ad_settings` meta is left over from Advanced Ads, which is no longer installed.

### 1.5 SEO, analytics, newsletter and other plugins

- **SEO: Rank Math + Rank Math PRO**, with breadcrumbs available. 924 posts have a primary category, 160 have a custom description and 17 have a custom title. There are 36 VideoObject and 34 BlogPosting schemas, and 26 `_wp_old_slug` entries that WordPress already redirects. Rank Math has 0 redirects of its own. The site is indexable. **Pulse will output no meta tags or schema while Rank Math is active**, and will use Rank Math breadcrumbs and the primary category.
- **Analytics:** Site Kit by Google and the AllBioHub “AB Analytics & Content Intelligence” plugin. View counts exist in two old meta keys: `_ahcpro_total_views` (214 posts) and `tie_views` (21).
- **Newsletter: Brevo** (`mailin`). Pulse will use Brevo's form shortcode or widget and will not add a new email provider.
- **Cache:** LiteSpeed Cache. Pulse uses only standard enqueue calls so that LiteSpeed's minify and combine features keep working.
- **Other active plugins that matter:** Elementor, Classic Editor, Classic Widgets, Advanced Editor Tools, WPCode (header and footer scripts plus 593 per-post snippets meta), Defender, Loginizer, WPS Hide Login, IndexNow, Image Optimization (WebP/AVIF), IP2Location Country Blocker, WP Consent API, Akismet, SwapBliss, AllBioHub App API, and the AllBioHub Startup Database.
- **No** WooCommerce, Jetpack, Yoast or AIOSEO.

### 1.6 Leftover data from earlier themes (it is read where useful and never deleted)

| Meta | Posts | Origin | Pulse use |
|---|---|---|---|
| `ruby_tagline` | 277 | Foxiz subtitle | **Article subtitle** (1st choice) |
| `tie_post_sub_title` | 72 | Jannah subtitle | Article subtitle (2nd choice) |
| `foxiz_content_total_word` | 1,029 | Foxiz word count | Reading-time cache (recalculated if missing) |
| `rank_math_primary_category` / `tie_primary_category` | 924 / 51 | SEO / Jannah | Primary category label |
| `_ahcpro_total_views` · `tie_views` | 214 · 21 | Old view counters | Initial value for “Most viewed” |
| `tie_trending_post` | 10 | Jannah | Suggested posts for manual Trending |
| `_ez-toc-*` | ~745 | Easy TOC (plugin no longer active) | Ignored. Pulse builds its own TOC from headings. |
| `ocean_*`, `tie_blog_layout`, `_vce_meta`, `rb_global_meta` | small | Older themes and builders | Ignored and reported |

### 1.7 Shortcodes

The only shortcodes that are actually registered and in use are `embed` (86 posts), `caption`, `video` and `abh_startup_dashboard`. Most of the “unregistered” entries in the raw report are false positives, which are ordinary bracketed text like `[As …]` or `[Insert …]` inside articles. The genuine leftovers are `calculate_years` (2 posts) and possibly `citation` (1). Pulse will register `calculate_years` so that it outputs the computed age, which is safer than leaving raw brackets visible. Everything else is left exactly as written.

---

## 2. Phase 2: migration map

### 2.1 Approach: sections map to categories, and nothing gets restructured

The audit shows that the categories are few and clean, they carry Rank Math SEO meta, and post URLs don't depend on them. The safest design is therefore that **Pulse never renames, nests or merges categories automatically.** Pulse instead has a **section map**. Each homepage section, navigation item and archive header is linked to one or more existing categories, and optionally to tags. Admins edit the map under **AllBioHub → Migration & Health**, and it is stored by slug.

| Pulse section | Sourced from (default) | Status |
|---|---|---|
| Biography | `biography` | ✓ Direct |
| Celebrity News | `celebrity-news` | ✓ Direct |
| Business & Startups | `startup-founders-innovator` + `money-career`, plus a link to `/startups/` | ✓ Mapped |
| Technology | `women-in-tech` + tags `allbiohub-tech`, `africatech`, `tech-news-nigeria` | ⚠ **Needs review**: thin content until a Technology category exists |
| Lifestyle | none | ⚠ **Needs review**: there is no category for it. The section is hidden until one exists. |
| Reviews | `reviews` | ✓ Direct |
| Trending / news strip | `around-the-web` | ⚠ **Needs review**: a general news bucket of 212 posts |
| Sponsored | `sponsored` (0 posts) | ✓ Used by the sponsored-content system to label paid posts |
| (hidden) | `editorial-submission` (1 post) | ⚠ Needs review |

Category URLs (`/category/biography/` and so on) stay exactly as they are, so no category redirects are needed.

### 2.2 Recommended content step (needs your approval, not automatic)

**Create two new categories, Technology (`technology`) and Lifestyle (`lifestyle`).** This only adds categories. Pulse's migration screen then lists the posts in Around the Web that match tech or lifestyle tags. An editor ticks the posts to **add** to the new category, and each post keeps its existing categories, so no URL or SEO data changes. All of this is logged and can be undone.

### 2.3 Everything else

| Item | Action |
|---|---|
| Posts, pages, comments, authors, startups | Not touched. The theme only reads them. |
| Front page | `front-page.php` renders the Pulse homepage. The Elementor page (9822) stays in place and keeps its URL, title and Rank Math meta. Switching back to Foxiz restores the old homepage exactly. |
| 9 posts without a featured image | Handled by the fallback at render time (first content image → attached image → placeholder) and listed for an editor. |
| Menus | “Main Menu” → `primary`, “Secondary Menu” → `mobile-quick`, “Footer Menu” + “Footer Menu 2” → `footer` columns, “Top Bar Menu” → `top-bar`, “Social Menu” → `social`. Stale social links and the two different “Advertise With Us” links are flagged. The TieLabs and stub menus are ignored, not deleted. |
| Ad widgets | Foxiz ad-script widgets only work while Foxiz Core is active. Pulse reads their code during migration and offers to **copy** it into the matching Pulse placements (header → Top Site, single top/bottom → Article Top/Bottom, sidebar → Sidebar). The original widgets are left in place. Ad Inserter blocks keep working, because they are inserted into the content. |
| Foxiz Core | Stays active during the changeover so that its shortcodes and widgets don't break. It can be deactivated once the migration report is clean. |
| Ruby templates (7) and Elementor library | Not touched |
| Rank Math | Pulse defers to it completely. Pulse's own `Article`/`Person` schema and meta output are switched off while Rank Math is active. |
| Views | “Most viewed” starts from `_ahcpro_total_views` / `tie_views`, then counts new views in `_allbiohub_views` (throttled, and skipped for bots and logged-in editors). |
| Newsletter | Brevo form. The list ID and form are chosen in Settings → Newsletter. |
| Redirects | No category or post URL changes are planned, so no new redirects are needed. The redirect table stays available for later. |

### 2.4 Backup and rollback

1. **Before activation:** take a full backup of the database and `wp-content/uploads`. Defender or the host's backup tool can do this. Also clear the LiteSpeed cache after switching themes.
2. **Test on staging first** if your host offers it.
3. **On activation:** Pulse **detects** and writes `allbiohub_migration_report`. It also runs the two steps the owner approved (Technology/Lifestyle categories, accent from the logo), once, logged and undoable. Nothing else is applied.
4. **Applying:** each step (menu assignment, copying ads, adding posts to new categories) is approved separately, logged in `allbiohub_migration_log` along with the data needed to undo it, and can be reversed from the Migration screen.
5. **Rollback:** switching back to Foxiz always works, because no Foxiz data, widget or option is removed.

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

The AllBioHub purple is read from the site's logo on activation (`inc/brand.php`): the most common strong colour, darkened for 4.5:1 on white and lightened for dark mode. The fallback token is **`#5B2BD9`**.

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
| 1 | Audit | ✓ Done (section 1) |
| 2 | Migration map | ✓ Done (section 2). Open questions are in section 6 |
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

Phases 3–13 are built and tested on a local WordPress install that mirrors the audited setup (Rank Math, Brevo, Elementor, classic editor, the same categories and meta keys). Test content stays local and never ships in the theme. Phases 14–17 are best run on a staging copy of the site.

## 6. Open questions

1. **Technology and Lifestyle categories:** should these be created (adding only) so that those sections have content? This is recommended. See 2.2.
2. Is there a **staging** copy of the site where Pulse can be tested before going live?
3. **Social profiles:** the Social Menu links to `OptimistSky` handles and Google Plus. What are the correct AllBioHub Facebook, X, Instagram, YouTube and TikTok URLs?
4. **Advertise With Us:** should it link to the `/advertise-with-us/` page or to `mediakitad.netlify.app`?
5. Should the exact brand purple come from the current logo (`cropped-20240221_200933_0000-1.png`), or do you have a brand colour code?
