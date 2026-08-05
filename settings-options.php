<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Portfolio extension settings.
 *
 * Rendered by Unyson as the extension's settings tab. Read anywhere with
 * fw_get_db_ext_settings_option( 'portfolio', '<key>' ) — or the convenience
 * wrappers on the extension class ( ->get_setting() / ->feature_enabled() ).
 *
 * Layout follows the house metabox-holder + group container convention: each
 * section is a `box`, its fields wrapped in a border-less `group`.
 */

$options = [

	'general_box' => [
		'title'   => __( 'Archive & Listing', 'fw' ),
		'type'    => 'box',
		'options' => [
			'group_general' => [
				'type'    => 'group',
				'options' => [
					'archive_columns'  => [
						'label'   => __( 'Archive columns', 'fw' ),
						'desc'    => __( 'How many projects per row on the portfolio archive / category pages.', 'fw' ),
						'type'    => 'select',
						'value'   => '3',
						'choices' => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ],
					],
					'archive_per_page' => [
						'label' => __( 'Projects per page', 'fw' ),
						'desc'  => __( 'Number of projects shown per page on the archive before pagination. Use 0 for the WordPress default.', 'fw' ),
						'type'  => 'text',
						'value' => '12',
					],
					'orderby'          => [
						'label'   => __( 'Order projects by', 'fw' ),
						'type'    => 'select',
						'value'   => 'date',
						'choices' => [
							'date'       => __( 'Date published', 'fw' ),
							'menu_order' => __( 'Custom order (Page Attributes / Order)', 'fw' ),
							'title'      => __( 'Title', 'fw' ),
							'rand'       => __( 'Random', 'fw' ),
						],
					],
					'order'            => [
						'label'   => __( 'Order direction', 'fw' ),
						'type'    => 'select',
						'value'   => 'DESC',
						'choices' => [
							'DESC' => __( 'Descending (newest / Z–A first)', 'fw' ),
							'ASC'  => __( 'Ascending (oldest / A–Z first)', 'fw' ),
						],
					],
					'featured_first'   => [
						'label' => __( 'Featured projects first', 'fw' ),
						'desc'  => __( 'Float projects marked as Featured to the top of the archive, before the chosen order is applied.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'archive_filter_bar' => [
						'label' => __( 'Category filter bar', 'fw' ),
						'desc'  => __( 'Show category filter links above the archive grid. Each filter is a real category URL, so it works with pagination and is crawlable.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
				],
			],
		],
	],

	'features_box' => [
		'title'   => __( 'Features', 'fw' ),
		'type'    => 'box',
		'options' => [
			'group_features' => [
				'type'    => 'group',
				'options' => [
					'enable_gallery'         => [
						'label' => __( 'Project galleries', 'fw' ),
						'desc'  => __( 'Add a multi-image gallery box to each project and render it on the single-project view.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'enable_project_details' => [
						'label' => __( 'Project details', 'fw' ),
						'desc'  => __( 'Add a Project Details box (client, date, URL, services, …) to each project.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'enable_tags'            => [
						'label' => __( 'Project tags', 'fw' ),
						'desc'  => __( 'Register a non-hierarchical Tag taxonomy for projects in addition to Categories.', 'fw' ),
						'type'  => 'switch',
						'value' => false,
					],
				],
			],
		],
	],

	'single_box' => [
		'title'   => __( 'Single Project', 'fw' ),
		'type'    => 'box',
		'options' => [
			'group_single' => [
				'type'    => 'group',
				'options' => [
					'show_gallery_single' => [
						'label' => __( 'Show gallery', 'fw' ),
						'desc'  => __( 'Render the project gallery above the content on the single-project view.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'single_columns'     => [
						'label'   => __( 'Gallery columns', 'fw' ),
						'desc'    => __( 'Columns for the gallery grid on the single-project view.', 'fw' ),
						'type'    => 'select',
						'value'   => '3',
						'choices' => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ],
					],
					'show_meta_single'   => [
						'label' => __( 'Show project details', 'fw' ),
						'desc'  => __( 'Render the Project Details list (client, date, …) on the single-project view.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'enable_prevnext'    => [
						'label' => __( 'Previous / next navigation', 'fw' ),
						'desc'  => __( 'Show previous/next project links (with thumbnails) beneath the single-project content.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'prevnext_same_category' => [
						'label' => __( 'Navigate within the same category', 'fw' ),
						'desc'  => __( 'Constrain previous/next to projects sharing a portfolio category.', 'fw' ),
						'type'  => 'switch',
						'value' => false,
					],
					'enable_related'     => [
						'label' => __( 'Related projects', 'fw' ),
						'desc'  => __( 'Show a row of related projects (sharing a category) beneath the single-project content.', 'fw' ),
						'type'  => 'switch',
						'value' => true,
					],
					'related_count'      => [
						'label' => __( 'Related count', 'fw' ),
						'desc'  => __( 'How many related projects to display.', 'fw' ),
						'type'  => 'text',
						'value' => '3',
					],
					'related_heading'    => [
						'label' => __( 'Related heading', 'fw' ),
						'desc'  => __( 'Heading shown above the related projects. Leave empty to hide it.', 'fw' ),
						'type'  => 'text',
						'value' => __( 'Related Projects', 'fw' ),
					],
				],
			],
		],
	],
];
