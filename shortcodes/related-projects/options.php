<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$options = array(
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'count' => array(
				'label' => __( 'How many projects', 'fw' ),
				'type'  => 'short-text',
				'value' => '3',
			),
			'heading' => array(
				'label' => __( 'Heading', 'fw' ),
				'desc'  => __( 'Leave empty to hide the heading.', 'fw' ),
				'type'  => 'text',
				'value' => __( 'Related Projects', 'fw' ),
			),
			'heading_tag' => array(
				'label'   => __( 'Heading tag', 'fw' ),
				'desc'    => __( 'Pick by position in the page outline (no skipped levels), not by size.', 'fw' ),
				'type'    => 'select',
				'value'   => 'h2',
				'choices' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'div' ),
			),
		),
	),
);

if ( function_exists( 'sc_get_animation_fields' ) ) {
	$options['tab_animation'] = array(
		'title'   => __( 'Animations', 'fw' ),
		'type'    => 'tab',
		'options' => sc_get_animation_fields(),
	);
}

if ( function_exists( 'sc_get_advanced_tab' ) ) {
	$options['tab_advanced'] = array(
		'title'   => __( 'Advanced', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'advanced_settings' => array(
				'type'    => 'group',
				'options' => sc_get_advanced_tab(),
			),
		),
	);
}
