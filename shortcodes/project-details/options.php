<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/* Project picker choices (current + published projects). */
$pd_post_type = 'fw-portfolio';
if ( function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
	$pd_post_type = fw_ext( 'portfolio' )->get_post_type_name();
}

$pd_project_choices = array( 'current' => __( 'Current project (auto)', 'fw' ) );

$pd_projects = get_posts( array(
	'post_type'        => $pd_post_type,
	'post_status'      => 'publish',
	'numberposts'      => 200,
	'orderby'          => 'title',
	'order'            => 'ASC',
	'suppress_filters' => false,
) );
foreach ( $pd_projects as $pd_project ) {
	$pd_project_choices[ (string) $pd_project->ID ] = $pd_project->post_title !== ''
		? $pd_project->post_title
		: sprintf( __( '(no title) #%d', 'fw' ), $pd_project->ID );
}

$options = array(
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'project_id' => array(
				'label'   => __( 'Project', 'fw' ),
				'desc'    => __( '"Current project (auto)" reads the project being viewed — use it inside a single-project layout.', 'fw' ),
				'type'    => 'select',
				'value'   => 'current',
				'choices' => $pd_project_choices,
			),
			'heading' => array(
				'label' => __( 'Heading', 'fw' ),
				'desc'  => __( 'Optional heading above the list. Leave empty for none.', 'fw' ),
				'type'  => 'text',
				'value' => '',
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
