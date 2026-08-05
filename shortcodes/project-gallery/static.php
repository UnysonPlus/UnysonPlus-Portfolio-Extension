<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Enqueue the shared portfolio gallery + lightbox assets (they live in the
 * portfolio extension, not the shortcodes extension). Same handles as the
 * single-project view's static.php, so WordPress de-dupes if both run.
 */
$portfolio = function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
if ( ! $portfolio ) {
	return;
}

$version = $portfolio->manifest->get_version();

wp_enqueue_style(
	'fw-ext-portfolio-gallery',
	fw_min_uri( $portfolio->get_declared_URI( '/static/css/portfolio-gallery.css' ) ),
	array(),
	$version
);

wp_enqueue_script(
	'fw-ext-portfolio-lightbox',
	fw_min_uri( $portfolio->get_declared_URI( '/static/js/portfolio-lightbox.js' ) ),
	array(),
	$version,
	true
);

wp_localize_script( 'fw-ext-portfolio-lightbox', 'fwPortfolioLightboxL10n', array(
	'gallery'  => __( 'Image gallery', 'fw' ),
	'close'    => __( 'Close', 'fw' ),
	'previous' => __( 'Previous image', 'fw' ),
	'next'     => __( 'Next image', 'fw' ),
) );
