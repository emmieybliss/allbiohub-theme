# AllBioHub Pulse

A custom WordPress theme for [AllBioHub](https://allbiohub.com), an African digital media and editorial publication. *Stories that inspire.*

## Status

The theme is built and has passed a visual QA pass on a local copy that mirrors the live site's structure. It has not been installed on the live site yet.

- **[docs/PLAN.md](docs/PLAN.md)**: audit findings, architecture, design system and build phases
- **[docs/MIGRATION-MAP.md](docs/MIGRATION-MAP.md)**: what changes, and what happens to URLs, SEO, content and media for each change
- **[docs/VISUAL-QA.md](docs/VISUAL-QA.md)**: what was checked, what was fixed, and what still needs checking on the real site

## Installing safely

1. Take a full backup (files and database). Use a staging copy if your host offers one.
2. Upload `allbiohub-pulse/` to `wp-content/themes/` (or zip the folder and use Appearance → Themes → Add New → Upload).
3. Activate it. Nothing is migrated on activation: Pulse only detects what is there.
4. Open **AllBioHub → Migration & Health** and apply the steps you want. Each one is logged and can be undone.
5. Switching back to Foxiz restores the old site exactly, because Pulse never edits Foxiz settings, posts or the Elementor home page.

## Repository layout

| Path | What it is |
|---|---|
| `docs/PLAN.md` | Audit findings, migration map and design plan |
| `tools/allbiohub-audit/` | Read-only audit plugin. Install it on the live site, then go to **Tools → AllBioHub Audit → Download audit JSON** |
| `allbiohub-pulse/` | The theme |
| `docs/MIGRATION-MAP.md` | Per-change impact on URLs, SEO, content and media |
| `docs/VISUAL-QA.md` | Visual QA report |

## Safety

Nothing in this repository deletes posts, pages, media, terms, authors, comments, SEO metadata or URLs. Migration steps are detected first, approved by an admin, logged, and can be reversed.
