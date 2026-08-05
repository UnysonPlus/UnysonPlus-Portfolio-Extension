<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/*
|--------------------------------------------------------------------------
| Resolve the portfolio category taxonomy (multi-select auto-populates it)
|--------------------------------------------------------------------------
*/
$pf_taxonomy = 'fw-portfolio-category';
if ( function_exists( 'fw_ext' ) && fw_ext( 'portfolio' ) ) {
	$pf_taxonomy = fw_ext( 'portfolio' )->get_taxonomy_name();
}

$options = array(

	/* ==========================================
	   TAB 1 — CONTENT
	   ========================================== */
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(

			'categories' => array(
				'label'      => __( 'Categories', 'fw' ),
				'desc'       => __( 'Limit the grid to these portfolio categories. Leave empty to include all.', 'fw' ),
				'type'       => 'multi-select',
				'population' => 'taxonomy',
				'source'     => $pf_taxonomy,
				'value'      => array(),
			),
			'count' => array(
				'label' => __( 'Number of projects', 'fw' ),
				'desc'  => __( 'Maximum projects to show. Use -1 for all.', 'fw' ),
				'type'  => 'short-text',
				'value' => '-1',
			),
			'featured_only' => array(
				'label' => __( 'Featured only', 'fw' ),
				'desc'  => __( 'Show only projects marked as Featured.', 'fw' ),
				'type'  => 'switch',
				'value' => 'no',
			),
			'orderby' => array(
				'label'   => __( 'Order by', 'fw' ),
				'type'    => 'select',
				'value'   => 'date',
				'choices' => array(
					'date'       => __( 'Date', 'fw' ),
					'menu_order' => __( 'Custom order', 'fw' ),
					'title'      => __( 'Title', 'fw' ),
					'rand'       => __( 'Random', 'fw' ),
				),
			),
			'pagination' => array(
				'label'   => __( 'Pagination', 'fw' ),
				'desc'    => __( 'With "Load more", the number above becomes the page size (per batch) and a button fetches the next batch.', 'fw' ),
				'type'    => 'select',
				'value'   => 'none',
				'choices' => array(
					'none'     => __( 'None (show the set number)', 'fw' ),
					'loadmore' => __( 'Load more button', 'fw' ),
				),
			),
			'link_to' => array(
				'label'   => __( 'Cards link to', 'fw' ),
				'type'    => 'select',
				'value'   => 'project',
				'choices' => array(
					'project'  => __( 'The project page', 'fw' ),
					'lightbox' => __( 'The cover image in a lightbox', 'fw' ),
					'none'     => __( 'Nothing', 'fw' ),
				),
			),
			'order' => array(
				'label'   => __( 'Order direction', 'fw' ),
				'type'    => 'select',
				'value'   => 'DESC',
				'choices' => array(
					'DESC' => __( 'Descending', 'fw' ),
					'ASC'  => __( 'Ascending', 'fw' ),
				),
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

			'layout' => array(
				'label'   => __( 'Layout', 'fw' ),
				'desc'    => __( 'Masonry keeps each image\'s natural height; List renders full-width rows.', 'fw' ),
				'type'    => 'select',
				'value'   => 'grid',
				'choices' => array(
					'grid'    => __( 'Grid', 'fw' ),
					'masonry' => __( 'Masonry', 'fw' ),
					'list'    => __( 'List', 'fw' ),
				),
			),
			'columns' => array(
				'label'   => __( 'Columns (Desktop)', 'fw' ),
				'type'    => 'select',
				'value'   => '3',
				'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			),
			'ratio' => array(
				'label'   => __( 'Image ratio', 'fw' ),
				'desc'    => __( 'Ignored by Masonry (it always keeps the original proportions).', 'fw' ),
				'type'    => 'select',
				'value'   => '4-3',
				'choices' => array(
					'1-1'  => __( 'Square (1:1)', 'fw' ),
					'4-3'  => __( 'Landscape (4:3)', 'fw' ),
					'3-2'  => __( 'Landscape (3:2)', 'fw' ),
					'16-9' => __( 'Wide (16:9)', 'fw' ),
					'3-4'  => __( 'Portrait (3:4)', 'fw' ),
					'auto' => __( 'Original', 'fw' ),
				),
			),
			'hover' => array(
				'label'   => __( 'Hover style', 'fw' ),
				'type'    => 'select',
				'value'   => 'zoom',
				'choices' => array(
					'zoom'      => __( 'Image zoom', 'fw' ),
					'overlay'   => __( 'Overlay caption', 'fw' ),
					'grayscale' => __( 'Grayscale to color', 'fw' ),
					'none'      => __( 'None', 'fw' ),
				),
			),
			'gap' => array(
				'label' => __( 'Gap (px)', 'fw' ),
				'type'  => 'short-text',
				'value' => '24',
			),
			'image_size' => array(
				'label'   => __( 'Thumbnail Image Size', 'fw' ),
				'type'    => 'select',
				'value'   => 'large',
				'choices' => array(
					'thumbnail'    => __( 'Thumbnail', 'fw' ),
					'medium'       => __( 'Medium', 'fw' ),
					'medium_large' => __( 'Medium Large', 'fw' ),
					'large'        => __( 'Large', 'fw' ),
					'full'         => __( 'Full', 'fw' ),
				),
			),
			'show_filters' => array(
				'label' => __( 'Category filters', 'fw' ),
				'desc'  => __( 'Show the category filter buttons above the grid.', 'fw' ),
				'type'  => 'switch',
				'value' => 'yes',
			),
			'show_summary' => array(
				'label' => __( 'Show summary', 'fw' ),
				'desc'  => __( 'Display each project\'s short summary under its title.', 'fw' ),
				'type'  => 'switch',
				'value' => 'no',
			),
			'show_category' => array(
				'label' => __( 'Show category', 'fw' ),
				'desc'  => __( 'Display each project\'s category label above its title.', 'fw' ),
				'type'  => 'switch',
				'value' => 'no',
			),
		),
	),
);

/*
|--------------------------------------------------------------------------
| Shared Styling / Animations / Advanced tabs (from the shortcodes extension).
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
