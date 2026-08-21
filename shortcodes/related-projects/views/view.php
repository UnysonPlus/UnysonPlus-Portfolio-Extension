<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 *
 * "Related" needs a current project for context, so this renders only on a
 * single-project page.
 */

$pf = function ( $key, $default ) use ( $atts ) {
	return isset( $atts[ $key ] ) && $atts[ $key ] !== '' ? $atts[ $key ] : $default;
};

$portfolio = function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
if ( ! $portfolio || ! is_singular( $portfolio->get_post_type_name() ) ) {
	// Relatedness is derived from the CURRENT project, so there has to be one.
	if ( fw_is_editor_context() && function_exists( 'sc_editor_notice' ) ) {
		echo sc_editor_notice( __( 'Related projects need a current project — place this in a project template.', 'fw' ) );
	}
	return;
}

$html = function_exists( 'fw_ext_portfolio_render_related' )
	? fw_ext_portfolio_render_related( 0, array(
		'count'       => (int) $pf( 'count', 3 ),
		'heading'     => (string) $pf( 'heading', __( 'Related Projects', 'fw' ) ),
		'heading_tag' => (string) $pf( 'heading_tag', 'h2' ),
	) )
	: '';

if ( $html === '' ) {
	if ( fw_is_editor_context() && function_exists( 'sc_editor_notice' ) ) {
		echo sc_editor_notice( __( 'No related projects found — relatedness comes from the current project, so this needs a project template.', 'fw' ) );
	}
	return;
}

$atts['base_class']       = 'fw-related-projects-sc';
$atts['unique_id_prefix'] = 'prel-';
$attr = function_exists( 'sc_build_wrapper_attr' )
	? sc_build_wrapper_attr( $atts )
	: array( 'class' => 'fw-related-projects-sc' );

$attr_html = function_exists( 'fw_attr_to_html' ) ? fw_attr_to_html( $attr ) : 'class="fw-related-projects-sc"';
?>
<div <?php echo $attr_html; ?>>
	<?php echo $html; ?>
</div>
