<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Components CSS (details list / cards / related) lives in the portfolio
 * extension. Same handle as the other portfolio elements, so WP de-dupes.
 */
$portfolio = function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
if ( ! $portfolio ) {
	return;
}

wp_enqueue_style(
	'fw-ext-portfolio-components',
	fw_min_uri( $portfolio->get_declared_URI( '/static/css/portfolio-components.css' ) ),
	array(),
	$portfolio->manifest->get_version()
);
