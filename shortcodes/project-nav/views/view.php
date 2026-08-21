<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 *
 * Prev/next adjacency comes from the global post, so this renders only on a
 * single-project page (anywhere else there is no adjacency to walk).
 */

$pf = function ( $key, $default ) use ( $atts ) {
	return isset( $atts[ $key ] ) && $atts[ $key ] !== '' ? $atts[ $key ] : $default;
};

$portfolio = function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
if ( ! $portfolio || ! is_singular( $portfolio->get_post_type_name() ) ) {
	if ( fw_is_editor_context() && function_exists( 'sc_editor_notice' ) ) {
		echo sc_editor_notice( __( 'Project navigation only renders on a single project — place it in a project template.', 'fw' ) );
	}
	return;
}

$html = function_exists( 'fw_ext_portfolio_render_prevnext' )
	? fw_ext_portfolio_render_prevnext( 0, array(
		'same_category' => $pf( 'same_category', 'no' ) === 'yes',
	) )
	: '';

if ( $html === '' ) {
	return;
}

$atts['base_class']       = 'fw-project-nav-sc';
$atts['unique_id_prefix'] = 'pnav-';
$attr = function_exists( 'sc_build_wrapper_attr' )
	? sc_build_wrapper_attr( $atts )
	: array( 'class' => 'fw-project-nav-sc' );

$attr_html = function_exists( 'fw_attr_to_html' ) ? fw_attr_to_html( $attr ) : 'class="fw-project-nav-sc"';
?>
<div <?php echo $attr_html; ?>>
	<?php echo $html; ?>
</div>
