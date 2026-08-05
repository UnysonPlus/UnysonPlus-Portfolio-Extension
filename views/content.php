<?php
/**
 * Single-project content. In order:
 *   1. the project gallery (responsive grid → accessible lightbox),
 *   2. the project content,
 *   3. the Project Details list (client, date, services, …),
 *   4. previous / next project navigation,
 *   5. a row of related projects.
 *
 * Each block is gated by the extension settings (Single Project tab). The
 * gallery markup comes from fw_ext_portfolio_render_gallery() so it stays
 * identical to the [project_gallery] shortcode. Assets are enqueued in
 * static.php.
 *
 * @var string $the_content
 */

if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$portfolio = function_exists( 'fw_ext_portfolio' ) ? fw_ext_portfolio() : null;
$post_id   = (int) get_the_ID();

/* 1. Gallery -------------------------------------------------------------- */
if ( $portfolio && $portfolio->gallery_enabled() && $portfolio->feature_enabled( 'show_gallery_single', true ) ) {
	$single_cols = (int) $portfolio->get_setting( 'single_columns', 3 );

	echo fw_ext_portfolio_render_gallery( $post_id, array(
		'columns'        => $single_cols,
		'columns_tablet' => min( 2, $single_cols ),
		'columns_mobile' => 1,
		'gap'            => 16,
		'ratio'          => '4-3',
		'lightbox'       => true,
		'captions'       => false,
		'image_size'     => 'large',
	) );
}

/* 2. Content -------------------------------------------------------------- */
echo $the_content;

/* 2b. Results / metrics band (renders only when metrics are filled in) ----- */
if ( function_exists( 'fw_ext_portfolio_render_results' ) ) {
	echo fw_ext_portfolio_render_results( $post_id );
}

/* 2c. Client testimonial (renders only when a quote is set) ---------------- */
if ( function_exists( 'fw_ext_portfolio_render_testimonial' ) ) {
	echo fw_ext_portfolio_render_testimonial( $post_id );
}

/* 3. Project details ------------------------------------------------------ */
if (
	$portfolio
	&& $portfolio->feature_enabled( 'enable_project_details', true )
	&& $portfolio->feature_enabled( 'show_meta_single', true )
	&& function_exists( 'fw_ext_portfolio_render_project_meta' )
) {
	echo fw_ext_portfolio_render_project_meta( $post_id );
}

/* 4. Previous / next project ---------------------------------------------- */
if (
	$portfolio
	&& $portfolio->feature_enabled( 'enable_prevnext', true )
	&& function_exists( 'fw_ext_portfolio_render_prevnext' )
) {
	echo fw_ext_portfolio_render_prevnext( $post_id, array(
		'same_category' => $portfolio->feature_enabled( 'prevnext_same_category', false ),
	) );
}

/* 5. Related projects ----------------------------------------------------- */
if (
	$portfolio
	&& $portfolio->feature_enabled( 'enable_related', true )
	&& function_exists( 'fw_ext_portfolio_render_related' )
) {
	echo fw_ext_portfolio_render_related( $post_id, array(
		'count'   => (int) $portfolio->get_setting( 'related_count', 3 ),
		'heading' => (string) $portfolio->get_setting( 'related_heading', __( 'Related Projects', 'fw' ) ),
	) );
}
