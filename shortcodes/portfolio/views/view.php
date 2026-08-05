<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 *
 * Renders a filterable portfolio grid through fw_ext_portfolio_render_grid(),
 * inside a styled wrapper carrying the shortcode's Styling / Animation /
 * Advanced classes.
 */

$pf = function ( $key, $default ) use ( $atts ) {
	return isset( $atts[ $key ] ) && $atts[ $key ] !== '' ? $atts[ $key ] : $default;
};

$grid = '';
if ( function_exists( 'fw_ext_portfolio_render_grid' ) ) {
	$link_to = (string) $pf( 'link_to', 'project' );

	$grid = fw_ext_portfolio_render_grid( array(
		'categories'    => (array) $pf( 'categories', array() ),
		'count'         => (int) $pf( 'count', -1 ),
		'layout'        => (string) $pf( 'layout', 'grid' ),
		'columns'       => (int) $pf( 'columns', 3 ),
		'gap'           => (int) $pf( 'gap', 24 ),
		'ratio'         => (string) $pf( 'ratio', '4-3' ),
		'hover'         => (string) $pf( 'hover', 'zoom' ),
		'pagination'    => (string) $pf( 'pagination', 'none' ),
		'link_to'       => $link_to,
		'show_filters'  => $pf( 'show_filters', 'yes' ) === 'yes',
		'show_summary'  => $pf( 'show_summary', 'no' ) === 'yes',
		'show_category' => $pf( 'show_category', 'no' ) === 'yes',
		'featured_only' => $pf( 'featured_only', 'no' ) === 'yes',
		'orderby'       => $pf( 'orderby', 'date' ),
		'order'         => $pf( 'order', 'DESC' ),
		'image_size'    => $pf( 'image_size', 'large' ),
	) );

	// Lightbox card mode needs the shared gallery lightbox assets (same
	// handles as the single-project view / [project_gallery], so WP de-dupes).
	if ( 'lightbox' === $link_to && $grid !== '' && function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
		$pf_ext  = fw_ext( 'portfolio' );
		$version = $pf_ext->manifest->get_version();

		wp_enqueue_style(
			'fw-ext-portfolio-gallery',
			fw_min_uri( $pf_ext->get_declared_URI( '/static/css/portfolio-gallery.css' ) ),
			array(),
			$version
		);
		wp_enqueue_script(
			'fw-ext-portfolio-lightbox',
			fw_min_uri( $pf_ext->get_declared_URI( '/static/js/portfolio-lightbox.js' ) ),
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

if ( $grid === '' ) {
	return;
}

$atts['base_class']       = 'fw-portfolio-grid-sc';
$atts['unique_id_prefix'] = 'pf-';
$attr = function_exists( 'sc_build_wrapper_attr' )
	? sc_build_wrapper_attr( $atts )
	: array( 'class' => 'fw-portfolio-grid-sc' );

$attr_html = function_exists( 'fw_attr_to_html' ) ? fw_attr_to_html( $attr ) : 'class="fw-portfolio-grid-sc"';
?>
<div <?php echo $attr_html; ?>>
	<?php echo $grid; ?>
</div>
