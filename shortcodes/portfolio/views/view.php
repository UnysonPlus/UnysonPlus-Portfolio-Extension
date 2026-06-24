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
	$grid = fw_ext_portfolio_render_grid( array(
		'categories'    => (array) $pf( 'categories', array() ),
		'count'         => (int) $pf( 'count', -1 ),
		'columns'       => (int) $pf( 'columns', 3 ),
		'gap'           => (int) $pf( 'gap', 24 ),
		'show_filters'  => $pf( 'show_filters', 'yes' ) === 'yes',
		'show_summary'  => $pf( 'show_summary', 'no' ) === 'yes',
		'featured_only' => $pf( 'featured_only', 'no' ) === 'yes',
		'orderby'       => $pf( 'orderby', 'date' ),
		'order'         => $pf( 'order', 'DESC' ),
		'image_size'    => $pf( 'image_size', 'large' ),
	) );
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
