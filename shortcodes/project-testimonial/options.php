<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/* Project picker choices (current + published projects). */
$pt_post_type = 'fw-portfolio';
if ( function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
	$pt_post_type = fw_ext( 'portfolio' )->get_post_type_name();
}

$pt_project_choices = array( 'current' => __( 'Current project (auto)', 'fw' ) );

$pt_projects = get_posts( array(
	'post_type'        => $pt_post_type,
	'post_status'      => 'publish',
	'numberposts'      => 200,
	'orderby'          => 'title',
	'order'            => 'ASC',
	'suppress_filters' => false,
) );
foreach ( $pt_projects as $pt_project ) {
	$pt_project_choices[ (string) $pt_project->ID ] = $pt_project->post_title !== ''
		? $pt_project->post_title
		: sprintf( __( '(no title) #%d', 'fw' ), $pt_project->ID );
}

$options = array(
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'project_id' => array(
				'label'   => __( 'Project', 'fw' ),
				'desc'    => __( 'Whose testimonial to display. The quote lives on the project\'s Project Details box.', 'fw' ),
				'type'    => 'select',
				'value'   => 'current',
				'choices' => $pt_project_choices,
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
