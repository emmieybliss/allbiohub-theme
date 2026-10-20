# Visual QA: AllBioHub Pulse

Judged as a premium African editorial publication, not as a generic WordPress theme. Checked on a local WordPress 6.8 copy that mirrors the live site's structure: the same category slugs and uppercase names, legacy meta keys (`ruby_tagline`, `tie_post_sub_title`), no Rank Math, and a static front page. The test content is fictional and the images are generated placeholders. Nothing was copied from the live site.

Widths checked: 390 px (phone) and 1440 px (desktop), in light and dark mode. Every template was loaded with `WP_DEBUG` on. No PHP notices, no JavaScript errors and no horizontal scroll were found at either width.

## What was fixed in this pass

| Area | Problem found | Fix |
|---|---|---|
| Header | The dark-mode button showed the moon and the sun at the same time | Only the icon for the next scheme shows |
| Hero | The “Presented by” sponsor line used the headline serif and competed with the story | Small sans line with an amber “Sponsored” label |
| Sponsored panel | The panel stretched next to the sponsored story card and left an empty block | The image fills the panel height and the copy is centred |
| Category sections | A section with only 2 stories looked unfinished, and the lead story was taller than its list | Sections need at least 3 stories to show, and they show 5 by default (1 lead and 4 rows) |
| Featured Biography | The portrait was very tall on phones | 4:3 crop on phones, 4:5 from tablet up |
| Cards and excerpts | Paragraphs ran together in excerpts (“profile).This”) | Block tags become spaces before trimming |
| Article contents | The numbering skipped a number when a story had sub-headings | Sub-headings are nested under their chapter |
| Biography header | The text sat at the bottom, with empty space above | Vertically centred next to the portrait |
| Footer | The columns collapsed into one on desktop, and old widgets used large default headings | 2 columns on tablet and a row on desktop; widget titles match the column labels |
| Ads | A large image creative inside an archive grid filled the screen | Banner and inline creatives are capped at 300 px high |
| Video page | The player stopped short of the content width | Full content width |
| Bylines | A post whose author had no display name showed an empty “By” | The byline and author box are hidden |
| **View counter** | **The view-count endpoint crashed on PHP 8 (a core function used as a REST validator)** | **Fixed and re-tested: views and ad impressions now record** |

## What passed

- **Hierarchy.** One dominant hero, a numbered Trending list in the serif face, and a single purple “pulse rule” under every section title.
- **Typography.** Instrument Serif headlines and Inter body text are self-hosted, so there are no Google Font calls. The article measure is about 70 characters with a line height of 1.75.
- **Images.** Every card has a fixed aspect ratio, and width and height are set, so there is no layout shift. Posts without a featured image use the first image in the content. Posts with no image at all get the branded placeholder, never a broken image.
- **Card consistency.** One story-card component in four variants (card, row, feature and compact) is used everywhere.
- **Pinned stories.** The numbered purple “Pinned” tab sits on each image. When nothing is pinned, the section is titled “Top stories” and shows no badge.
- **Sponsored.** Everything sponsored uses amber, and editorial uses purple. Sponsored items are always labelled “Sponsored” and links carry `rel="sponsored"`.
- **Biography.** The portrait header, Quick Profile, chapter list, “More biographies” and “People You Should Know” (circular portraits) all render.
- **Video.** The dark band works in both schemes. The YouTube iframe loads only after a click, from youtube-nocookie. The badge, duration and “More from AllBioHub Video” rail render.
- **Mobile.** The hero image runs full-bleed. Pinned stories, People and More videos scroll sideways. The drawer menu works, focus moves into it and stays there, and Escape closes it.
- **Dark mode.** System, light and dark all work. The choice is remembered, and the page does not flash on load.
- **Empty placements.** These are invisible to visitors. Admins see a dashed “set this up” hint instead.

## Still to check on the real site

These could not be judged with test content:

1. **Real photography.** Check how real portraits crop in the 4:5 and circular frames. Faces are positioned 20% from the top, which suits most press portraits.
2. **The existing Foxiz widgets.** When the theme is switched, WordPress moves active widgets into Pulse's sidebars. Look at the article sidebar and footer once, and remove anything that doesn't belong.
3. **Logo.** The wordmark shown here is the fallback. The site's own logo will be used automatically. A dark-mode logo can be added in Theme Settings.
4. **Brevo form.** The newsletter block stays hidden until the Brevo form shortcode is added in Theme Settings → Newsletter.
5. **Rank Math.** Pulse outputs no meta or schema of its own while Rank Math is active. Confirm this with a page-source check after activation.
