<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/* Project picker choices (current + published projects). */
$pr_post_type = 'fw-portfolio';
if ( function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
	$pr_post_type = fw_ext( 'portfolio' )->get_post_type_name();
}

$pr_project_choices = array( 'current' => __( 'Current project (auto)', 'fw' ) );

$pr_projects = get_posts( array(
	'post_type'        => $pr_post_type,
	'post_status'      => 'publish',
	'numberposts'      => 200,
	'orderby'          => 'title',
	'order'            => 'ASC',
	'suppress_filters' => false,
) );
foreach ( $pr_projects as $pr_project ) {
	$pr_project_choices[ (string) $pr_project->ID ] = $pr_project->post_title !== ''
		? $pr_project->post_title
		: sprintf( __( '(no title) #%d', 'fw' ), $pr_project->ID );
}

$options = array(
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'project_id' => array(
				'label'   => __( 'Project', 'fw' ),
				'desc'    => __( 'Whose Results/metrics to display. Metrics are filled in on the project\'s Project Details box.', 'fw' ),
				'type'    => 'select',
				'value'   => 'current',
				'choices' => $pr_project_choices,
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
