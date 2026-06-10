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

$manifest['version']     = '1.0.13';
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
