<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 */

$pf = function ( $key, $default ) use ( $atts ) {
	return isset( $atts[ $key ] ) && $atts[ $key ] !== '' ? $atts[ $key ] : $default;
};

$project_id = (string) $pf( 'project_id', 'current' );
$pid        = ( 'current' === $project_id ) ? (int) get_the_ID() : (int) $project_id;

$html = ( $pid && function_exists( 'fw_ext_portfolio_render_results' ) )
	? fw_ext_portfolio_render_results( $pid )
	: '';

if ( $html === '' ) {
	return;
}

$atts['base_class']       = 'fw-project-results-sc';
$atts['unique_id_prefix'] = 'pr-';
$attr = function_exists( 'sc_build_wrapper_attr' )
	? sc_build_wrapper_attr( $atts )
	: array( 'class' => 'fw-project-results-sc' );

$attr_html = function_exists( 'fw_attr_to_html' ) ? fw_attr_to_html( $attr ) : 'class="fw-project-results-sc"';
?>
<div <?php echo $attr_html; ?>>
	<?php echo $html; ?>
</div>
