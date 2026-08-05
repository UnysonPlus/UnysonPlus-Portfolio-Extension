<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Portfolio', 'fw' );
$manifest['slug']        = 'unysonplus-portfolio';
$manifest['description'] = __( 
	'This extension will add a fully fledged portfolio module that will let you display your projects using the built in portfolio pages.',
	'fw' 
);

$manifest['version']     = '1.0.23';
$manifest['display']     = true;
$manifest['standalone']  = true;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-Portfolio-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-Portfolio-Extension';
$manifest['github_branch'] = 'master';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '5.8';

/**
 * Changelog
 * -----------------------------------------------------------------------------
 * 1.0.23 - Single-project template parts as drop-in builder elements. Five
 *          new Components-tab elements let a project page be built entirely
 *          in the page builder (Semplice-style freedom) while the default
 *          template keeps working: [project_details] (details list, with
 *          heading + heading-tag options), [project_results] (metrics band),
 *          [project_testimonial] (client quote), [project_nav] (prev/next
 *          with thumbnails, same-category option; renders only on single
 *          projects) and [related_projects] (count/heading options). Each
 *          renders through the same shared helpers as the single view, on the
 *          current project by default or (details/results/testimonial) any
 *          picked project. The parent theme pairs this with a Theme
 *          Settings - Portfolio - Presets loader shipping four persona
 *          presets (Case Study / Gallery / Cards / Showcase).
 *
 * 1.0.22 - Data-model expansion, structured data + Jetpack import. Project
 *          Details gains role, tools/tech stack, industry, repository URL, a
 *          results/metrics repeater (rendered as a metrics band on the single
 *          view) and a client testimonial (quote + author + company, rendered
 *          as a styled blockquote). New side box "Card & Visibility": a
 *          dedicated card thumbnail (used by grids/archives instead of the
 *          cover image when set) and a "Hide from archives" switch (mirrored
 *          to _fw_portfolio_hidden meta and excluded from the archive query,
 *          grid queries and related projects; the project stays reachable at
 *          its URL). Single projects now emit CreativeWork JSON-LD built from
 *          the details meta (fw_ext_portfolio_jsonld filter to adjust or
 *          disable). New admin tool Portfolio - Import converts Jetpack
 *          Portfolio (jetpack-portfolio) items to Projects, mapping Project
 *          Types to portfolio categories (and tags when enabled). Projects
 *          also gain page-attributes support so "Custom order" sorting has a
 *          real Order field.
 *
 * 1.0.21 - [portfolio] element upgrade: layouts, AJAX filtering, load-more
 *          pagination, lightbox card mode. New Layout tab options: layout
 *          (Grid / Masonry via CSS columns - natural heights, no JS / List
 *          rows), image ratio, hover style, show-category; Content tab gains
 *          pagination (none / Load-more button) and "Cards link to" (project
 *          page / cover image in the shared lightbox / nothing). Category
 *          filtering is now a REAL re-query: filter buttons and the load-more
 *          button call a new wp_ajax fw_portfolio_load endpoint (nonce-
 *          checked; args re-validated server-side through the shared
 *          fw_ext_portfolio_sanitize_grid_args() whitelist), so filters
 *          cooperate with pagination instead of hiding rendered cards, and
 *          the active filter deep-links via #pf=<slug>. render_grid() was
 *          refactored into query_projects() + render_cards() +
 *          grid_query_export() (all public helpers); the filter bar now lists
 *          the whole restriction's terms, not just page 1's. portfolio-grid.js
 *          rewritten (fetch-based, aria-busy states, localized strings, SR
 *          status line); components CSS adds masonry/list layouts, load-more
 *          button and loading states.
 *
 * 1.0.20 - Theme display-settings bridge + card display options. New public
 *          filter fw:ext:portfolio:setting runs inside get_setting(), letting
 *          the active theme override any display setting (the parent theme's
 *          new Theme Settings - Portfolio tab uses it; "Inherit" there defers
 *          to the extension's Settings page). Because unknown keys also flow
 *          through get_setting(), themes can drive display knobs that have no
 *          extension-side field: archive_gap, archive_ratio, archive_hover,
 *          card_show_category, card_show_summary are now read by the archive
 *          view with code defaults. Cards gain a category label option
 *          (show_category arg on render_card/render_grid) and grids gain
 *          aspect-ratio (1:1, 4:3, 3:2, 16:9, 3:4, original) and hover-style
 *          (zoom, overlay caption, grayscale, none) options via the new
 *          fw_ext_portfolio_grid_attrs() helper; the hover variants ship in
 *          portfolio-components.css (touch + reduced-motion safe).
 *
 * 1.0.19 - Real portfolio archive templates + previous/next project
 *          navigation. The extension now ships views/archive.php and
 *          views/taxonomy.php (resolved via template_include; a theme
 *          archive-fw-portfolio.php or a framework-customizations override
 *          still wins), so the Projects archive finally renders as a project
 *          grid instead of the theme's blog list - the "Archive columns"
 *          setting is now actually consumed, and per-page / order /
 *          featured-first apply through the main query. The archive gets a
 *          category filter bar built from REAL taxonomy links (crawlable,
 *          pagination-safe; new "Category filter bar" setting) and numbered
 *          pagination. Single projects gain previous/next navigation with
 *          thumbnails and direction labels (new settings: on/off + "navigate
 *          within the same category"), rendered by a new
 *          fw_ext_portfolio_render_prevnext() helper between the details
 *          list and related projects. Meta/related headings are now h2 by
 *          default and configurable via a heading_tag arg (heading-order
 *          rule), and the components CSS also loads on archive views.
 *
 * 1.0.15 - Settings page, project details, related/featured projects, a
 *          filterable [portfolio] grid, and PHP/WP modernization. (Supersedes
 *          the 1.0.14 build, which had a fatal boot-order regression: reading
 *          the extension settings during the extension's _init() forced
 *          Unyson's one-time option-types init before the page-builder
 *          extension registered its "page-builder" option type, so the page
 *          builder showed "Undefined option type: page-builder". All settings
 *          reads now happen on `init` or later, never during boot.) A new
 *          extension Settings tab (settings-options.php) drives what used to be
 *          hardcoded or filter-only: archive columns / per-page / order,
 *          featured-first ordering, and toggles for galleries, project details,
 *          tags, the single-view gallery + details, and related projects. Each
 *          project gains a Project Details box (client, URL, completion date,
 *          services, summary) plus a Featured switch; the Featured flag is
 *          mirrored to a queryable "_fw_portfolio_featured" post meta on save so
 *          archives can float featured projects to the top. The single-project
 *          view now renders the gallery (settings-driven columns), a details
 *          list, and a row of related projects (same category, topped up with
 *          recent ones) - all via new shared helpers (get_project_meta,
 *          render_project_meta, get_related, render_related, render_card,
 *          render_grid). New Content Elements element / shortcode [portfolio]:
 *          a dependency-free filterable grid of projects with category filter
 *          buttons (vanilla JS, no Isotope). Modernization: project tags are now
 *          a settings toggle (filter still wins), taxonomies gained
 *          show_in_rest + the post type declares its taxonomies, the deprecated
 *          get_terms( $taxonomy, $args ) signature in helpers.php is fixed, and
 *          permalink-setting inputs are escaped.
 *
 * 1.0.13 - Modern gallery + lightbox, and a new [project_gallery] builder
 *          element. The abandoned jQuery NivoSlider on the single-project
 *          view is replaced by a responsive CSS-grid that opens images in a
 *          dependency-free, accessible lightbox (vanilla JS — keyboard,
 *          focus-trap, ARIA dialog, touch swipe; thumbnails use
 *          wp_get_attachment_image() for native srcset + lazy-loading). The
 *          markup is produced by a new shared helper,
 *          fw_ext_portfolio_render_gallery(), so the single view and the
 *          shortcode stay identical. A new Content Elements page-builder
 *          element / shortcode, [project_gallery], renders any project's
 *          gallery (configurable columns, gap, image ratio, lightbox,
 *          captions); it lives inside the portfolio extension and is
 *          auto-discovered by the shortcodes loader. Grids of projects remain
 *          the job of the generic [posts post_type="fw-portfolio"] element.
 *          BREAKING: the NivoSlider assets (jquery.nivo.slider.js,
 *          projects-script.js, nivo-slider.css and the NivoSlider theme
 *          folder) are removed — themes that enqueued or depended on them
 *          must drop those references.
 *
 * 1.0.11 - Security: escaped $post_title with esc_html() in
 *          views/content.php (nivoslider caption). Prevents stored XSS via
 *          attachment post titles.
 */
