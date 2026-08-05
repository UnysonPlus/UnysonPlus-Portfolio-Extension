<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

if ( ! is_admin() ) {
	/**
	 * @var FW_Extension_Portfolio $portfolio
	 */
	$portfolio = fw()->extensions->get( 'portfolio' );

	$is_portfolio_archive = is_post_type_archive( $portfolio->get_post_type_name() )
		|| is_tax( $portfolio->get_taxonomy_name() )
		|| ( $portfolio->tags_enabled() && is_tax( $portfolio->get_taxonomy_tag_name() ) );

	// Cards / grid / filter-bar / prev-next styling — needed on the archive
	// views as well as the single view (details list, related row).
	if ( is_singular( $portfolio->get_post_type_name() ) || $is_portfolio_archive ) {
		wp_enqueue_style(
			'fw-ext-portfolio-components',
			fw_min_uri( $portfolio->get_declared_URI( '/static/css/portfolio-components.css' ) ),
			array(),
			$portfolio->manifest->get_version()
		);
	}

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

		wp_localize_script( 'fw-ext-portfolio-lightbox', 'fwPortfolioLightboxL10n', array(
			'gallery'  => __( 'Image gallery', 'fw' ),
			'close'    => __( 'Close', 'fw' ),
			'previous' => __( 'Previous image', 'fw' ),
			'next'     => __( 'Next image', 'fw' ),
		) );
	}
}
