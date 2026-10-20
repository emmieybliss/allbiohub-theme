# AllBioHub Pulse migration map

This map is built from the live-site audit taken on 2026-10-20 at 09:15 UTC: WordPress 7.1.3, Foxiz 2.7.7, 994 posts, 29 pages and 1,215 media items.

Every row that could affect **URLs, SEO, content, media or functionality** says what happens. The labels in the Impact column mean:

- **None**: Pulse only reads the item.
- **Display only**: the item is shown differently, but the underlying data doesn't change.
- **Additive, approval**: Pulse adds something only after an admin clicks Apply in AllBioHub → Migration & Health. Every applied step is logged and can be undone.

Nothing in this map deletes, renames, re-slugs or re-parents anything.

---

## 1. Existing categories → AllBioHub sections

Pulse adds a **section map** on top of the existing categories. Category slugs, URLs, parents and Rank Math term meta are left unchanged.

| Existing category (posts) | → Pulse section | What happens | URL / SEO impact |
|---|---|---|---|
| BIOGRAPHY `biography` (380) | **Biography** | Feeds the Featured Biography section, People You Should Know, the biography article template and related biographies | None. `/category/biography/` stays the same |
| CELEBRITY NEWS `celebrity-news` (350) | **Celebrity News** | Homepage section, navigation item, archive | None |
| STARTUP FOUNDERS & INNOVATOR `startup-founders-innovator` (29) | **Business & Startups** | Combined with Money & Career in one homepage section. Each category keeps its own archive page | None. Both archives keep their URLs and Rank Math titles and descriptions |
| MONEY & CAREER `money-career` (13) | **Business & Startups** | As above | None |
| WOMEN IN TECH `women-in-tech` (5) | **Technology** | Combined with the tags `allbiohub-tech`, `africatech` and `tech-news-nigeria` | None |
| REVIEWS `reviews` (14) | **Reviews** | Homepage section, archive, and a review card style | None |
| AROUND THE WEB `around-the-web` (212) | **Trending / Latest News** | Used as a source for the Trending ticker and the “Latest” strip | None |
| SPONSORED `sponsored` (0) | **Sponsored** | Any post in this category is automatically shown with the SPONSORED badge and `rel="sponsored"` outbound links | None |
| EDITORIAL SUBMISSION `editorial-submission` (1) | (not shown on the homepage) | Kept, and listed for review | None |
| (none) | **Lifestyle** | The section stays **hidden** until a Lifestyle category exists | None |

**Optional, after approval: create Technology and Lifestyle categories.** Pulse lists the Around the Web posts that are tagged with tech or lifestyle topics, and an editor ticks the ones to *add* to the new category.
- Posts keep all their existing categories.
- Because permalinks are `/%postname%/`, **no post URL changes**.
- Each post's Rank Math primary category is left as it is.
- This only adds categories. It is logged and can be undone. Nothing runs automatically.

**Tags (344):** used as they are, for related stories and search. 25 empty tags and a few near-duplicates (`ladymotara`/`theladymotara`, and a `women-in-tech` tag with the same name as the category) are **listed for review only**. Tags are never merged or deleted automatically.

---

## 2. Existing post types → Pulse templates

| Post type (count) | Template | What happens | Impact |
|---|---|---|---|
| `post` in Biography (380) | `single-biography.php`, chosen automatically when the post is in `biography` | Profile hero image, name, Quick Profile box, TOC built from existing H2/H3 headings, styled section headings, related biographies. **Article content is not edited.** The Quick Profile is filled from an optional meta box and stays hidden when it's empty | Display only |
| `post`, everything else (614) | `single.php` | Category, title, subtitle, author, dates, reading time, share buttons, hero image, content, TOC, related stories, newsletter, comments, ad slots | Display only |
| `page` (29) | `page.php` | The page content is rendered unchanged inside Pulse typography. The two Elementor pages are rendered by Elementor. | Display only |
| `abh_startup` (208), `abh_founder` (1) | **Not taken over.** The AllBioHub Startup Database plugin keeps its own templates | Pulse only provides the header, footer and fonts around them | None |
| `attachment` | `image.php` falls back to `single.php` | Display only | |
| `elementor_library`, `rb-etemplate` (7), `wpcode`, `adstxt`, `app-adstxt`, Rank Math types | Not used by the theme | Left as they are | None |

Every post field Pulse reads, in order of preference:

| Field | Source | Fallback |
|---|---|---|
| Subtitle | `ruby_tagline` (277 posts) | `tie_post_sub_title` (72), then the excerpt |
| Primary category | `rank_math_primary_category` (924) | `tie_primary_category` (51), then the first category |
| Reading time | Live word count of the content (≈200 wpm) | `foxiz_content_total_word` (1,029) is used only as a cache |
| Views | `_allbiohub_views` (new) | Seeded once from `_ahcpro_total_views` (214) and `tie_views` (21). This is read-only, and the old keys are left untouched |

---

## 3. Biography posts detected

- **380 published posts** are in the `biography` category. This is the source for Featured Biography, People You Should Know and the biography template.
- **Detection rule:** a post is in `biography`, or has the tag `biography` (19), `allbiohub-biography` (18) or a tag ending in `-biography`. A post that matches only by tag goes into a “Possible biography” list for review instead of being switched over automatically.
- **Person's name for People You Should Know:** the post title, with common suffixes removed (“Biography”, “Bio”, “Net Worth”, “Age”, “Wiki”, and anything after “:”, “–”, “|” or “,”). If that can't be done cleanly, the full title is shown. The descriptor is the first matching profession word in the excerpt or tags (for example Actress, Chef, Blogger, Founder or Wrestler), with “Biography” as the fallback.
- **Section links** (Early Life, Career, Personal Life, Achievements) come from the post's existing H2 headings. If a heading doesn't exist, that link is not shown.

## 4. Featured images detected

| Finding | Count | What happens | Impact |
|---|---|---|---|
| Published posts with a valid featured image | 985 of 994 | Used everywhere through `wp_get_attachment_image()` with `srcset`, width and height | Display only |
| Posts **without** a featured image | 9 | The fallback chain runs at render time: first image in the content → first attached image → AllBioHub placeholder. **Nothing is written.** They are listed for an editor (see 10) | None |
| Broken featured image IDs | 0 | Not applicable | Not applicable |
| Image formats | 967 JPEG, 140 WebP, 91 PNG, 5 AVIF | Image Optimization plugin keeps serving WebP and AVIF | None |
| Image sizes | Core sizes plus `foxiz_crop_*` | Pulse registers its own sizes: `pulse-hero` 1200×675, `pulse-card` 640×480, `pulse-thumb` 240×180 and `pulse-square` 400×400. **New thumbnails are only created for new uploads.** Existing posts use the closest existing size through `srcset` until an admin runs a thumbnail regeneration. This is optional, adds files only, and doesn't touch the originals | None |
| Attachments with an old `http://` GUID | 229 | Ignored. GUIDs aren't used for display | None |

## 5. Pages detected (29)

| Page | Builder | What happens |
|---|---|---|
| **AllBioHub** (9822), the current static front page | Elementor | **Not edited.** `front-page.php` renders the Pulse homepage at `/`. The page record, its Rank Math meta and its Elementor data stay in place. Rank Math still uses page 9822's SEO title and description for `/`. If you switch back to Foxiz, the old homepage returns exactly as it was. |
| Popcult Home 2 (9825) | Elementor | Untouched. It is still rendered by Elementor at its own URL |
| Blog (749) | classic, empty | Stays the posts page. It gets the Pulse “Latest stories” archive layout |
| About Us, Contact Us, Privacy Policy, Terms Of Use, Terms and Conditions, Disclaimer, DMCA Notice, Cookie Policy, Copyright Policy, Editorial Policy, Advertising & Partnership Policy, Meet Our Team, Donation, Submit a Story, Sponsored & Guest Post Publication Rate, YouTube Resources, iCodeGPT.com is For Sale, BlissBlock Privacy, SwapBliss | classic | **Content unchanged.** Pulse provides the page typography and layout only |
| Advertise With Us (4821) | classic (371 words) | The **existing text is kept.** You can optionally assign the new “Advertise With Us” template, which shows your existing content first, followed by the ad-format cards and a contact link. It shows **no traffic numbers** |
| Startups, Claim a Startup, List Your Startup, My Startup Account, Startup Dashboard, Startup Pricing & Plans | classic or Gutenberg (shortcode pages) | Untouched. The Startup Database plugin's shortcodes render them |

## 6. Menus detected

| Menu (items) | Current location (Foxiz) | → Pulse location | Note |
|---|---|---|---|
| Main Menu (7) | `foxiz_main`, `foxiz_mobile` | `primary`, `mobile` | Used as it is. You can add Technology and Lifestyle items once those categories exist |
| Secondary Menu (6) | `foxiz_mobile_quick` | `mobile-quick` | |
| Top Bar Menu (8) | not assigned | `top-bar` | |
| Footer Menu (9) + Footer Menu 2 (7) | not assigned | `footer-1`, `footer-2` | ⚠ “Advertise With Us” links to `mediakitad.netlify.app` here but to `/advertise-with-us/` in the Top Bar |
| Social Menu (6) | not assigned | `social` | ⚠ The links point to `OptimistSky` handles and **Google Plus**, which has been shut down |
| TieLabs Main Menu (26), TieLabs Secondry Menu (4) | not assigned | Not used | Leftover Jannah demo content with placeholder links. Left in place and flagged |
| Header Menu #1/#2, Collapsable Menu (and copies), 404 Menu, Options Panel | not assigned | Not used | Stubs. Left in place |

**Impact:** menu assignments are stored per theme, so the Foxiz assignments are untouched. Pulse's assignment is an **Additive, approval** step and can be undone.

## 7. Ad placements detected

| Where | What | → Pulse placement | What happens |
|---|---|---|---|
| `foxiz_header_ad` | Foxiz ad widget `widget-ad-script-15` | Top Site Advertisement | The code is **copied** into Pulse after approval. The widget stays |
| `foxiz_entry_top` | `widget-ad-script-12` | Article Top | Copied, after approval |
| `foxiz_entry_bottom` | `widget-ad-script-7` | Article Bottom | Copied, after approval |
| `foxiz_sidebar_default` | `widget-ad-script-9` and a Ruby template widget | Sidebar | Ad copied. The template widget is listed for review |
| `foxiz_ms_blog`, `foxiz_ms_sb2`, `foxiz_sidebar_more` | `-14`, `-10`, `-11` | Listed for review (no direct Pulse equivalent) | Not copied automatically |
| Ad Inserter plugin | Inserts ads into content | Not changed | Keeps working, because it hooks into the content |
| Ads.txt Manager | `ads.txt`, `app-ads.txt` | Not changed | |
| `_advads_ad_settings` (572 posts) | Left over from Advanced Ads, which is not installed | Ignored | |
| WPCode | Site-wide header and footer scripts | Not changed | Pulse calls `wp_head()`, `wp_body_open()` and `wp_footer()`, so these keep working |

Pulse's placements stay **disabled until you turn them on**. This means activating the theme never doubles up the ads that Ad Inserter already places.

## 8. SEO system detected

- **Rank Math SEO + PRO** is active. It provides titles, descriptions, Open Graph, Twitter cards, schema (including 36 VideoObject and 34 BlogPosting), breadcrumbs, the sitemap and the primary category.
- **What Pulse does:** it outputs **no** `<title>` override, meta, OG or schema of its own while Rank Math is active, and uses `title-tag` support so Rank Math controls the title. Breadcrumbs are rendered with `rank_math_the_breadcrumbs()`. Category labels use the Rank Math primary category.
- **URLs:** `/%postname%/` is unchanged. Category, tag and author URLs are unchanged. The 26 `_wp_old_slug` redirects are still handled by WordPress. **No redirects need to be added**, because nothing is renamed.
- **Indexing:** `blog_public = 1`. This is unchanged.
- **Homepage SEO:** Rank Math keeps using page 9822's meta for `/`. See 5.
- **Headings:** there is one H1 per page. The homepage H1 is the site name, hidden visually but readable by search engines and screen readers. Each article's H1 is its title.

## 9. Plugins that must remain compatible

| Plugin | Why it matters | How Pulse stays compatible |
|---|---|---|
| Rank Math SEO / PRO | SEO, schema, breadcrumbs | Pulse defers to it entirely (see 8) |
| AllBioHub Startup Database | `/startups/`, 208 startups, dashboards | Its templates and shortcodes are not overridden, and the startups archive is linked in the navigation |
| AllBioHub App API | The mobile app reads posts through the REST API | Pulse doesn't change any REST output |
| AB Analytics & Content Intelligence | Analytics | Only standard hooks are used |
| Site Kit by Google | GA4 and Search Console | It depends on `wp_head()` and `wp_body_open()`, which Pulse calls |
| Elementor | 2 pages and 25 posts | Pages built with Elementor render as they do now. The theme supports Elementor's content width |
| Foxiz Core | Foxiz widgets and shortcodes | Should stay active until the migration report is clean. Pulse doesn't depend on it |
| Ad Inserter, Ads.txt Manager | Revenue | Not changed (see 7) |
| Brevo | Newsletter and web-push | The Pulse newsletter section embeds the Brevo form |
| WPCode | Header and footer scripts | Kept working through the standard hooks |
| LiteSpeed Cache, Image Optimization | Performance | Assets are enqueued normally, with no inline-heavy output. Clear the cache once after switching themes |
| Classic Editor, Classic Widgets, Advanced Editor Tools | Editing | Pulse styles classic-editor output (captions, alignments, tables, embeds) |
| Defender, Loginizer, WPS Hide Login, IP2Location, Akismet, IndexNow, WP Consent API | Security and infrastructure | The theme doesn't interact with them |
| SwapBliss | Widget page | Its page renders as it does now |

## 10. Content that needs manual review

These items are listed in Migration & Health. Pulse will not guess on any of them.

1. **9 posts without a featured image.** The placeholder or first content image will show until an editor adds one:
   - AI Coding Startup Cursor Plans New Model to Rival Anthropic, OpenAI
   - Celebrities Who Hid Their Relationships in 2025
   - ‘Declare your income’: FG signs agreement with over 100 countries…
   - #BBNaija: Imisi wins Big Brother Naija 10/10 season
   - WWE Icon, The Undertaker Underwent Emergency Heart Surgery…
   - Dino Melaye’s G-Wagon Involved in Ghastly Accident (Video)
   - Trump responds to Musk saying he’s ‘disappointed’…
   - Jake Tapper Says Cover-Up of Biden’s Mental Decline…
   - Ben Affleck Jennifer Lopez Family
2. ✅ **Technology and Lifestyle categories:** approved. They are created automatically the first time Pulse is activated (logged, and can be undone while they are empty). Moving posts into them stays a manual, tick-box step.
3. **Around the Web (212 posts):** a general news bucket that could be sorted into the new categories.
4. **Editorial Submission (1 post):** decide which section it belongs to.
5. ✅ **Social Menu:** approved to use as is. Pulse shows the links from the existing Social Menu even before menus are assigned (Facebook, YouTube, Twitter/X, Instagram, LinkedIn). Google Plus is skipped because the service is closed. The `OptimistSky` handles are shown as they are until better links are added in Theme Settings → Social Profiles.
6. ✅ **Advertise With Us:** the owner chose `mediakitad.netlify.app`. A new setting (Theme Settings → General → Advertise link) defaults to it. Menu links to `/advertise-with-us/` open the media kit instead; the menus themselves and the page are not edited. Clear the setting to go back to the page.
7. **Foxiz ad widgets with no direct Pulse equivalent:** `-14`, `-10` and `-11`, plus the Ruby template widget in the default sidebar.
8. **Leftover shortcode `[calculate_years]` in 2 posts.** Pulse registers it so that it outputs an age instead of raw brackets. Its exact original behaviour is inferred and should be checked on those 2 posts.
9. **25 empty tags and near-duplicate tags.** These are listed only.
10. **25 Elementor posts.** They render through Elementor. Check them once after the theme switch.
11. **Leftover demo menus** (TieLabs or Jannah) and stub menus. Delete them yourself if you want. Pulse won't delete them.
12. **Authors without a bio:** Abubakar Idris (176 posts) and Muhammad (45). Their author pages will show a name and article list until a bio is added.

## 11. Homepage order (updated by Emmanuel on 2026-10-20) and the AllBioHub Video section

Order: Header → Trending Now → **Hero + Trending Stories** → **Pinned Stories** → **Sponsored Content** → **Featured Biography + People You Should Know** → **AllBioHub Video** → **Celebrity · Business · Technology · Lifestyle** sections (plus Biography and Reviews if enabled) → Sponsored banner → Newsletter → Footer. Each section can be switched on or off and reordered in Theme Settings → Homepage.

**Where the video comes from, in order. Nothing is hard-coded:**
1. **A post the admin picks** in Theme Settings → Video: “Video of the Week”.
2. Otherwise, the **newest post that has a video**. That means a post with a Rank Math `VideoObject` schema (36 posts), or a post whose content contains a YouTube or Vimeo URL, an `[embed]` (86 posts) or a `[video]`.
3. Otherwise, the section is hidden.

**What the section shows:**
- The player itself, which is the post's own embed. It loads as a click-to-play preview (thumbnail plus a play button) so that YouTube's script doesn't slow the page.
- The “VIDEO OF THE WEEK” badge, the post title, and the post's excerpt or subtitle.
- A **Watch Video** button that goes to the post.
- The source (YouTube or Vimeo, detected from the URL) and the duration. The duration is taken **only** from the Rank Math VideoObject `duration` field and is hidden when that's missing, so it's never made up.
- **More Videos →** opens a new `/videos/` listing of all posts that contain video. This is a **new URL** and doesn't replace any existing one. The admin can point it at the YouTube channel instead.

**Impact:** display only. No posts are edited.

---

## What activation will and won't do

**On activation, Pulse:** registers its menus, sidebars, image sizes and settings with safe defaults, runs **detection** (this map, refreshed from live data), and saves the report. It also runs two owner-approved steps once: it creates the Technology and Lifestyle categories, and it sets the accent purple from the main colour of your logo (adjusted so text stays readable). Both are logged under Migration & Health and can be undone. All ad placements start switched off, and pinned stories start empty, so the homepage falls back to the latest posts.

**Pulse never:** deletes or edits posts, pages, media, terms, users, comments, Rank Math data, Foxiz settings or widgets. It never changes permalinks or slugs, and it never writes to post content.

**Pulse only does these after you approve them, each logged and reversible:** assigning menus to Pulse locations, copying Foxiz ad code into Pulse placements, creating Technology and Lifestyle categories and adding chosen posts to them, and seeding view counts once from the old counters into `_allbiohub_views`.
