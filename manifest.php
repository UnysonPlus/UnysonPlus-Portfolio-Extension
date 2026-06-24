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

$manifest['version']     = '1.0.16';
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
