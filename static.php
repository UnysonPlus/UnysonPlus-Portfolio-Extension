<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

if ( ! is_admin() ) {
	/**
	 * @var FW_Extension_Portfolio $portfolio
	 */
	$portfolio = fw()->extensions->get( 'portfolio' );

	if ( is_singular( $portfolio->get_post_type_name() ) ) {
		$version = $portfolio->manifest->get_version();

		// Modern, dependency-free gallery + lightbox (replaces NivoSlider).
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

		// Project-detail list + related-project cards styling.
		wp_enqueue_style(
			'fw-ext-portfolio-components',
			fw_min_uri( $portfolio->get_declared_URI( '/static/css/portfolio-components.css' ) ),
			array(),
			$version
		);
	}
}
