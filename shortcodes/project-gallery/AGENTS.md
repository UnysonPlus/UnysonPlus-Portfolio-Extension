---
type: shortcode
name: project_gallery
since: portfolio extension 1.0.13
provides: leaf-shortcode
---

# Project Gallery

Renders a portfolio project's image gallery (the per-project `multi-upload`
metabox, option id `project-gallery`) as a responsive CSS-grid that opens in a
dependency-free, accessible lightbox. It is the modern replacement for the old
NivoSlider single-project slider, and the only portfolio-specific builder
element — grids of *projects* are handled by the generic `[posts
post_type="fw-portfolio"]` shortcode, so this element deliberately covers only
the one thing `posts` cannot: a single project's gallery images.

This shortcode lives **inside the portfolio extension** (`portfolio/shortcodes/
project-gallery/`), not the shortcodes extension. The shortcodes loader
(`_FW_Shortcodes_Loader::load_extensions_shortcodes()`) scans every active
extension's `shortcodes/` folder, so it is auto-discovered and only present when
the portfolio extension is active — no cross-extension dependency.

## Registration

Leaf shortcode — no `class-fw-shortcode-project-gallery.php`, no page-builder
item class. Auto-instantiated by the loader from the folder name
(`project-gallery` → tag `project_gallery`). `config.php` declares the
Content Elements placement + a `title_template` previewing the chosen project
and column count.

## Options schema (atts)

Source of truth: `options.php`.

### Tab: Content

| Att | Type | Default | Description |
|-----|------|---------|-------------|
| `project_id` | `select` | `current` | `current` = the project being viewed (single-project templates); otherwise a published `fw-portfolio` post ID |
| `no_results_text` | `text` | — | Shown when the project has no gallery images. Empty = render nothing |

### Tab: Layout

| Att | Type | Default | Description |
|-----|------|---------|-------------|
| `columns` | `select` 1–6 | `3` | Desktop columns |
| `columns_tablet` | `select` 1–4 | `2` | Tablet columns (≤ 991px) |
| `columns_mobile` | `select` 1–2 | `1` | Mobile columns (≤ 575px) |
| `gap` | `short-text` | `16` | Grid gap (px) |
| `ratio` | `select` | `4-3` | `16-9` / `4-3` / `3-2` / `1-1` / `2-3` / `auto` (natural) |
| `image_size` | `select` | `large` | Registered WP size for thumbnails (lightbox always opens full) |
| `lightbox` | `switch` | `yes` | Click → full-screen lightbox |
| `captions` | `switch` | `no` | Show each image's title under its thumbnail |

### Tabs: Styling / Animations / Advanced

Shared `sc_*` helpers (`sc_color_field_compact`, `sc_font_size_field`,
`sc_get_animation_fields`, `sc_get_advanced_tab`), each added only when the
helper exists (the portfolio extension can load without the shortcodes
helpers). Applied automatically through the `sc_build_wrapper_attr` filter.

## Rendering

`views/view.php` resolves `project_id` (→ `get_the_ID()` for `current`), calls
`fw_ext_portfolio_render_gallery( $id, $args )` (in the extension's
`helpers.php`), and wraps the result in a `.fw-project-gallery` div built via
`sc_build_wrapper_attr()`. The render helper emits a `.fw-pg` grid driven by
inline CSS custom properties (`--fw-pg-cols`, `--fw-pg-gap`, `--fw-pg-ratio`)
— no per-instance `<style>`. Thumbnails use `wp_get_attachment_image()` (native
`srcset` + `loading="lazy"`); each `a.fw-pg__item` links to the full image with
a `data-caption`, and the container carries `data-fw-pg-lightbox`.

`static.php` enqueues the shared `static/css/portfolio-gallery.css` +
`static/js/portfolio-lightbox.js` from the **portfolio extension** (same handles
as the single-project view, so they de-dupe). The lightbox JS is vanilla
(no jQuery), delegated, keyboard + ARIA accessible.

## Pitfalls

1. **`project_id: current` only works on a single-project template** — off a
   single `fw-portfolio` view `get_the_ID()` won't be a project, so the gallery
   is empty. Pick a specific project for use elsewhere.
2. **Shared markup** — the grid HTML comes from
   `fw_ext_portfolio_render_gallery()`, shared with `views/content.php`. Change
   markup there, not in two places.
3. **Assets live in the portfolio extension** — `static.php` resolves URIs via
   `fw_ext('portfolio')->get_declared_URI()`, NOT the shortcodes extension.
4. **`lightbox` / `captions` are `switch` strings** (`yes`/`no`), not booleans —
   the view compares `=== 'yes'`.

## Verification

1. Add a few gallery images to a project (Project gallery metabox).
2. Drop **Project Gallery** from Content Elements → pick that project → grid of
   its images renders.
3. Click a thumbnail → lightbox opens; ← → / Esc / arrows / swipe work; focus
   returns to the thumbnail on close.
4. Set `ratio: 1-1`, `columns: 4` → square 4-up grid.
5. Toggle `lightbox: no` → thumbnails render with no zoom affordance and don't
   open the lightbox.
6. On a single-project template, leave `project_id: current` → shows the viewed
   project's gallery.

## Files

- `config.php` — page-builder config (Content Elements tab, title_template)
- `options.php` — Content + Layout tabs + shared Styling/Animations/Advanced
- `static.php` — enqueues the portfolio extension's gallery CSS + lightbox JS
- `views/view.php` — resolves the project, calls the shared render helper, wraps it
- `static/img/page_builder.svg` — Content Elements thumbnail icon
