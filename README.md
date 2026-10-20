# AllBioHub Pulse

A custom WordPress theme for [AllBioHub](https://allbiohub.com), an African digital media and editorial publication. *Stories that inspire.*

## Status

Phase 1 (audit) and the plan are in progress. See **[docs/PLAN.md](docs/PLAN.md)** for the audit status, migration map, architecture, design system and build phases.

## Repository layout

| Path | What it is |
|---|---|
| `docs/PLAN.md` | Audit findings, migration map and design plan |
| `tools/allbiohub-audit/` | Read-only audit plugin. Install it on the live site, then go to **Tools → AllBioHub Audit → Download audit JSON** |
| `allbiohub-pulse/` | The theme (coming in Phase 3) |

## Safety

Nothing in this repository deletes posts, pages, media, terms, authors, comments, SEO metadata or URLs. Migration steps are detected first, approved by an admin, logged, and can be reversed.
