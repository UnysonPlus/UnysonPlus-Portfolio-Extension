---
type: shortcode
name: portfolio
since: portfolio extension 1.0.14
provides: leaf-shortcode
---

# Portfolio Grid

Renders a **filterable grid of portfolio projects** (the `fw-portfolio` post
type) with category filter buttons. This is the project-listing counterpart to
`[project_gallery]` (which shows ONE project's image gallery): `[portfolio]`
shows MANY projects as cards.

Lives **inside the portfolio extension** (`portfolio/shortcodes/portfolio/`),
not the shortcodes extension — auto-discovered by the shortcodes loader, so it
only exists when the portfolio extension is active. Leaf shortcode (folder name
`portfolio` → tag `portfolio`); no class, no page-builder item class.

## Options schema (atts) — source of truth: `options.php`

### Tab: Content

| Att | Type | Default | Description |
|-----|------|---------|-------------|
| `categories` | `multi-select` (population `taxonomy`) | — | Restrict to these category term IDs; empty = all |
| `count` | `short-text` | `-1` | Max projects (-1 = all) |
| `featured_only` | `switch` | `no` | Only projects flagged Featured (`_fw_portfolio_featured` meta) |
| `orderby` | `select` | `date` | date / menu_order / title / rand |
| `order` | `select` | `DESC` | DESC / ASC |

### Tab: Layout

| Att | Type | Default | Description |
|-----|------|---------|-------------|
| `columns` | `select` 1–6 | `3` | Desktop columns |
| `gap` | `short-text` | `24` | Grid gap (px) |
| `image_size` | `select` | `large` | Card thumbnail size |
| `show_filters` | `switch` | `yes` | Show category filter buttons |
| `show_summary` | `switch` | `no` | Show each project's summary under the title |

Plus shared `sc_*` Styling / Animations / Advanced tabs (added only when the
shortcodes helpers exist).

## Rendering

`views/view.php` calls `fw_ext_portfolio_render_grid( $args )` (in the
extension's `helpers.php`) and wraps the result with `sc_build_wrapper_attr()`.
The helper emits a `[data-fw-portfolio-grid]` wrapper containing an optional
`.fw-portfolio-filters` bar and a `.fw-portfolio-grid` (columns/gap via inline
CSS custom properties). Each project is a `.fw-portfolio-card` (shared
`fw_ext_portfolio_render_card()` — also used by the related-projects row) whose
class list carries `cat-<termId>` tokens for filtering.

`static.php` enqueues the portfolio extension's `portfolio-components.css` +
`portfolio-grid.js`. Filtering is dependency-free (no Isotope/jQuery): clicking
a filter toggles `.is-hidden` on non-matching cards.

## Pitfalls

1. **`categories` values are term IDs** (taxonomy-population multi-select), cast
   with `intval` in the helper.
2. **`featured_only` relies on `_fw_portfolio_featured` meta** — that meta is
   mirrored from the per-project Featured option on save (see the extension
   class `_action_sync_featured_meta`); projects never re-saved since 1.0.14
   won't have it yet.
3. **Cards are shared** with the related row — change card markup in
   `fw_ext_portfolio_render_card()`, not here.
4. **Assets live in the portfolio extension**, resolved via
   `fw_ext('portfolio')->get_declared_URI()`.

## Files

- `config.php` — page-builder config (Content Elements tab, title_template)
- `options.php` — Content + Layout tabs + shared Styling/Animations/Advanced
- `static.php` — enqueues the portfolio components CSS + grid JS
- `views/view.php` — resolves atts, calls the shared render helper, wraps it
- `static/img/page_builder.svg` — Content Elements thumbnail icon
