<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 *
 * Renders a portfolio project's image gallery through the shared
 * fw_ext_portfolio_render_gallery() helper, inside a styled wrapper that
 * carries the shortcode's Styling / Animation / Advanced classes.
 */

// Local att accessor with fallback (don't depend on the posts view's sc_get()).
$pg = function ( $key, $default ) use ( $atts ) {
	return isset( $atts[ $key ] ) && $atts[ $key ] !== '' ? $atts[ $key ] : $default;
};

// Resolve which project to read.
$project_id = (string) $pg( 'project_id', 'current' );
$resolved_id = ( $project_id === 'current' ) ? (int) get_the_ID() : (int) $project_id;

$gallery = '';
if ( $resolved_id && function_exists( 'fw_ext_portfolio_render_gallery' ) ) {
	$gallery = fw_ext_portfolio_render_gallery( $resolved_id, array(
		'columns'        => (int) $pg( 'columns', 3 ),
		'columns_tablet' => (int) $pg( 'columns_tablet', 2 ),
		'columns_mobile' => (int) $pg( 'columns_mobile', 1 ),
		'gap'            => (int) $pg( 'gap', 16 ),
		'ratio'          => $pg( 'ratio', '4-3' ),
		'lightbox'       => $pg( 'lightbox', 'yes' ) === 'yes',
		'captions'       => $pg( 'captions', 'no' ) === 'yes',
		'image_size'     => $pg( 'image_size', 'large' ),
	) );
}

$empty_text = trim( (string) $pg( 'no_results_text', '' ) );

// Nothing to show and no empty message → render nothing at all.
if ( $gallery === '' && $empty_text === '' ) {
	return;
}

// Build the styled wrapper (applies Styling/Animation/Advanced via filters).
$atts['base_class']       = 'fw-project-gallery';
$atts['unique_id_prefix'] = 'pg-';
$attr = function_exists( 'sc_build_wrapper_attr' )
	? sc_build_wrapper_attr( $atts )
	: array( 'class' => 'fw-project-gallery' );

$attr_html = function_exists( 'fw_attr_to_html' ) ? fw_attr_to_html( $attr ) : 'class="fw-project-gallery"';
?>
<div <?php echo $attr_html; ?>>
	<?php
	if ( $gallery !== '' ) {
		echo $gallery;
	} else {
		echo '<p class="fw-pg__empty">' . esc_html( $empty_text ) . '</p>';
	}
	?>
</div>
