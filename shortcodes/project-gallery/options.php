<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/*
|--------------------------------------------------------------------------
| Build the project picker choices (current + published portfolio projects)
|--------------------------------------------------------------------------
*/
$pg_post_type = 'fw-portfolio';
if ( function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
	$pg_post_type = fw_ext( 'portfolio' )->get_post_type_name();
}

$pg_project_choices = array(
	'current' => __( 'Current project (auto)', 'fw' ),
);

$pg_projects = get_posts( array(
	'post_type'        => $pg_post_type,
	'post_status'      => 'publish',
	'numberposts'      => 200,
	'orderby'          => 'title',
	'order'            => 'ASC',
	'suppress_filters' => false,
) );
foreach ( $pg_projects as $pg_project ) {
	$pg_project_choices[ (string) $pg_project->ID ] = $pg_project->post_title !== ''
		? $pg_project->post_title
		: sprintf( __( '(no title) #%d', 'fw' ), $pg_project->ID );
}

/*
|--------------------------------------------------------------------------
| Registered image sizes for the thumbnail picker
|--------------------------------------------------------------------------
*/
$pg_image_size_choices = array(
	'thumbnail'    => __( 'Thumbnail', 'fw' ),
	'medium'       => __( 'Medium', 'fw' ),
	'medium_large' => __( 'Medium Large', 'fw' ),
	'large'        => __( 'Large', 'fw' ),
	'full'         => __( 'Full', 'fw' ),
);

$options = array(

	/* ==========================================
	   TAB 1 — CONTENT
	   ========================================== */
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(

			'project_id' => array(
				'label'   => __( 'Project', 'fw' ),
				'desc'    => __( 'Which project\'s gallery to display.', 'fw' ),
				'help'    => __( '"Current project (auto)" pulls the gallery of the project being viewed — use this inside a single-project template. Otherwise pick a specific project.', 'fw' ),
				'type'    => 'select',
				'value'   => 'current',
				'choices' => $pg_project_choices,
			),

			'no_results_text' => array(
				'label' => __( 'Empty message', 'fw' ),
				'desc'  => __( 'Shown when the chosen project has no gallery images. Leave empty to render nothing.', 'fw' ),
				'type'  => 'text',
				'value' => '',
			),
		),
	),

	/* ==========================================
	   TAB 2 — LAYOUT
	   ========================================== */
	'tab_layout' => array(
		'title'   => __( 'Layout', 'fw' ),
		'type'    => 'tab',
		'options' => array(

			'columns' => array(
				'label'   => __( 'Columns (Desktop)', 'fw' ),
				'type'    => 'select',
				'value'   => '3',
				'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			),
			'columns_tablet' => array(
				'label'   => __( 'Columns (Tablet)', 'fw' ),
				'type'    => 'select',
				'value'   => '2',
				'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			),
			'columns_mobile' => array(
				'label'   => __( 'Columns (Mobile)', 'fw' ),
				'type'    => 'select',
				'value'   => '1',
				'choices' => array( '1' => '1', '2' => '2' ),
			),
			'gap' => array(
				'label' => __( 'Gap (px)', 'fw' ),
				'desc'  => __( 'Space between images.', 'fw' ),
				'type'  => 'short-text',
				'value' => '16',
			),
			'ratio' => array(
				'label'   => __( 'Image Ratio', 'fw' ),
				'desc'    => __( 'Crop each thumbnail to a fixed aspect ratio, or keep natural proportions.', 'fw' ),
				'type'    => 'select',
				'value'   => '4-3',
				'choices' => array(
					'16-9' => __( '16:9 (Wide)', 'fw' ),
					'4-3'  => __( '4:3 (Standard)', 'fw' ),
					'3-2'  => __( '3:2', 'fw' ),
					'1-1'  => __( '1:1 (Square)', 'fw' ),
					'2-3'  => __( '2:3 (Portrait)', 'fw' ),
					'auto' => __( 'Auto (natural)', 'fw' ),
				),
			),
			'image_size' => array(
				'label'   => __( 'Thumbnail Image Size', 'fw' ),
				'desc'    => __( 'Registered WordPress image size used for the grid thumbnails. The lightbox always opens the full-size image.', 'fw' ),
				'type'    => 'select',
				'value'   => 'large',
				'choices' => $pg_image_size_choices,
			),
			'lightbox' => array(
				'label' => __( 'Lightbox', 'fw' ),
				'desc'  => __( 'Open images in a full-screen lightbox on click.', 'fw' ),
				'type'  => 'switch',
				'value' => 'yes',
			),
			'captions' => array(
				'label' => __( 'Show Captions', 'fw' ),
				'desc'  => __( 'Display each image\'s title beneath its thumbnail.', 'fw' ),
				'type'  => 'switch',
				'value' => 'no',
			),
		),
	),
);

/*
|--------------------------------------------------------------------------
| Shared Styling / Animations / Advanced tabs (from the shortcodes extension).
| Guarded so the element still works if those helpers aren't loaded.
|--------------------------------------------------------------------------
*/
if ( function_exists( 'sc_color_field_compact' ) && function_exists( 'sc_font_size_field' ) ) {
	$options['tab_styling'] = array(
		'title'   => __( 'Styling', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_colors' => array(
				'type'    => 'group',
				'options' => array(
					'text_color'       => sc_color_field_compact( array( 'label' => __( 'Text Color', 'fw' ),       'kind' => 'text' ) ),
					'bg_color'         => sc_color_field_compact( array( 'label' => __( 'Background Color', 'fw' ), 'kind' => 'bg' ) ),
					'font_size_preset' => sc_font_size_field(),
				),
			),
			'group_spacings' => array(
				'type'    => 'group',
				'options' => array(
					'spacing' => array(
						'type'  => 'spacing',
						'label' => __( 'Margin & Padding', 'fw' ),
						'desc'  => __( 'All Sides applies to every side at once; any per-side value overrides it for that direction.', 'fw' ),
					),
				),
			),
		),
	);
}

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
