<?php
/**
 * Portfolio archive / category template (also loaded by taxonomy.php).
 *
 * Resolved via template_include in hooks.php when the theme ships no
 * archive-fw-portfolio.php of its own; a theme can also override this file at
 * framework-customizations/extensions/portfolio/views/archive.php.
 *
 * Renders: archive header → category filter links (real taxonomy URLs, so
 * pagination + SEO stay correct) → project-card grid from the MAIN query
 * (per-page / order / featured-first are applied in pre_get_posts by the
 * extension) → numbered pagination. The "Archive columns" setting drives the
 * grid. Uses the parent theme's wrapper/header helpers when present so the
 * page inherits the site layout; falls back to plain containers elsewhere.
 */

if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/** @var FW_Extension_Portfolio $portfolio */
$portfolio = fw_ext_portfolio();

get_header();

$has_theme_wrapper = function_exists( 'unysonplus_main_wrapper_open' ) && function_exists( 'unysonplus_main_wrapper_close' );

if ( $has_theme_wrapper ) {
	unysonplus_main_wrapper_open( 'content-area fw-portfolio-archive-area' );
} else {
	echo '<div class="fw-portfolio-archive-wrap">';
}

/* Archive header (title + description) ------------------------------------ */
if ( function_exists( 'unysonplus_render_archive_header' ) ) {
	/** Fires in the portfolio archive before the archive header renders, letting code inject markup above the title. */
	do_action( 'unysonplus_before_archive_title' );
	unysonplus_render_archive_header();
	/** Fires in the portfolio archive after the archive header renders, letting code inject markup below the title. */
	do_action( 'unysonplus_after_archive_title' );
} else {
	echo '<header class="page-header fw-portfolio-archive__header">';
	the_archive_title( '<h1 class="page-title">', '</h1>' );
	the_archive_description( '<div class="archive-description">', '</div>' );
	echo '</header>';
}

echo '<div class="fw-portfolio-archive">';

/* Category filter links ---------------------------------------------------- */
if ( $portfolio && $portfolio->feature_enabled( 'archive_filter_bar', true ) ) {
	echo fw_ext_portfolio_render_archive_filter_links();
}

/* Project grid (main query) ------------------------------------------------ */
if ( have_posts() ) {
	// Display knobs — the extension settings page holds the basics; the theme
	// (Theme Settings → Portfolio) can override any of them through the
	// fw:ext:portfolio:setting bridge, including theme-only keys like
	// archive_hover that have no extension-side field.
	$columns = $portfolio ? (int) $portfolio->get_setting( 'archive_columns', 3 ) : 3;
	$columns = max( 1, min( 4, $columns ) );

	$grid_attrs = fw_ext_portfolio_grid_attrs( array(
		'columns' => $columns,
		'gap'     => $portfolio ? (int) $portfolio->get_setting( 'archive_gap', 24 ) : 24,
		'ratio'   => $portfolio ? (string) $portfolio->get_setting( 'archive_ratio', '4-3' ) : '4-3',
		'hover'   => $portfolio ? (string) $portfolio->get_setting( 'archive_hover', 'zoom' ) : 'zoom',
	) );

	$show_category = $portfolio ? $portfolio->feature_enabled( 'card_show_category', false ) : false;
	$show_summary  = $portfolio ? $portfolio->feature_enabled( 'card_show_summary', true ) : true;

	echo '<div class="' . esc_attr( $grid_attrs['class'] ) . '" style="' . esc_attr( $grid_attrs['style'] ) . '">';
	while ( have_posts() ) {
		the_post();
		echo fw_ext_portfolio_render_card( get_post(), array(
			'show_summary'  => $show_summary,
			'show_category' => $show_category,
			'image_size'    => 'large',
		) );
	}
	echo '</div>';

	the_posts_pagination( array(
		'mid_size'  => 2,
		'prev_text' => __( '&larr; Previous', 'fw' ),
		'next_text' => __( 'Next &rarr;', 'fw' ),
	) );
} else {
	echo '<p class="fw-portfolio-archive__empty">' . esc_html__( 'No projects found.', 'fw' ) . '</p>';
}

echo '</div><!-- .fw-portfolio-archive -->';

if ( $has_theme_wrapper ) {
	unysonplus_main_wrapper_close();
} else {
	echo '</div>';
}

get_footer();
