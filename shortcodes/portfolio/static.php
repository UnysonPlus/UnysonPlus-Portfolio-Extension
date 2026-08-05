<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Enqueue the portfolio components CSS (cards, grid, filter bar) + the
 * dependency-free filtering JS. Assets live in the portfolio extension.
 */
$portfolio = function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
if ( ! $portfolio ) {
	return;
}

$version = $portfolio->manifest->get_version();

wp_enqueue_style(
	'fw-ext-portfolio-components',
	fw_min_uri( $portfolio->get_declared_URI( '/static/css/portfolio-components.css' ) ),
	array(),
	$version
);

wp_enqueue_script(
	'fw-ext-portfolio-grid',
	fw_min_uri( $portfolio->get_declared_URI( '/static/js/portfolio-grid.js' ) ),
	array(),
	$version,
	true
);

wp_localize_script( 'fw-ext-portfolio-grid', 'fwPortfolioGrid', array(
	'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	'nonce'   => wp_create_nonce( 'fw-portfolio-load' ),
	'loading' => __( 'Loading…', 'fw' ),
	'shown'   => __( '%d projects shown', 'fw' ),
) );
