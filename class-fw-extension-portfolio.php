<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Extension_Portfolio extends FW_Extension {

	private $post_type = 'fw-portfolio';
	private $slug = 'project';
	private $taxonomy_slug = 'portfolio';
	private $taxonomy_name = 'fw-portfolio-category';
	private $taxonomy_tag_name = 'fw-portfolio-tag';
	private $taxonomy_tag_slug = 'portfolio-tag';

	/**
	 * @internal
	 */
	public function _init() {
		$this->define_slugs();

		add_action( 'init', array( $this, '_action_register_post_type' ) );
		add_action( 'init', array( $this, '_action_register_taxonomy' ) );

		// Settings-driven archive query tuning (front end only).
		add_action( 'pre_get_posts', array( $this, '_action_tune_archive_query' ) );

		// Mirror the per-project "Featured" option into a real, queryable meta
		// key (Unyson keeps all post options in one serialized blob, which can't
		// be ordered on) so archives can float featured projects to the front.
		// Priority 99 on the generic save_post runs AFTER Unyson persists the
		// post options (its handler is on save_post @ 7); the per-post-type hook
		// would fire too early (before save_post).
		add_action( 'save_post', array( $this, '_action_sync_featured_meta' ), 99, 2 );

		// AJAX filter / load-more for the [portfolio] grid element. The handler
		// only runs at request time, so no settings are read during boot.
		add_action( 'wp_ajax_fw_portfolio_load', array( $this, '_action_ajax_load_grid' ) );
		add_action( 'wp_ajax_nopriv_fw_portfolio_load', array( $this, '_action_ajax_load_grid' ) );

		// CreativeWork structured data on single projects.
		add_action( 'wp_head', array( $this, '_action_single_project_jsonld' ) );

		if ( is_admin() ) {
			$this->save_permalink_structure();
			$this->add_admin_actions();
			$this->add_admin_filters();
		}
	}

	/**
	 * Read a saved extension-settings value, falling back to the option default
	 * declared in settings-options.php.
	 *
	 * @param string $key
	 * @param mixed  $default Returned when the option type yields null.
	 *
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		// Forward the default so an unsaved option short-circuits inside the
		// options model BEFORE it loads/processes settings-options.php (which
		// would trigger Unyson's option-types init). Important: never call this
		// during _init()/extension boot — only from `init` or later hooks.
		$value = fw_get_db_ext_settings_option( $this->get_name(), $key, $default );

		$value = ( null === $value ) ? $default : $value;

		/**
		 * Display-setting bridge: lets the active theme override any setting
		 * (the parent theme's Theme Settings → Portfolio tab hooks this; an
		 * "Inherit" choice there leaves $value untouched). Keys not present in
		 * settings-options.php also flow through here, so purely theme-driven
		 * display knobs (card hover style, aspect ratio, …) can be read with
		 * get_setting( 'key', <code default> ) without an extension-side field.
		 */
		return apply_filters( 'fw:ext:portfolio:setting', $value, $key, $default );
	}

	/**
	 * Boolean convenience around get_setting() for the feature/switch toggles.
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return bool
	 */
	public function feature_enabled( $key, $default = true ) {
		$value = $this->get_setting( $key, $default );

		// `switch` options persist as 'yes'/'no' or 1/0/true depending on context.
		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( 'yes', '1', 'true', 'on' ), true );
		}

		return (bool) $value;
	}

	/**
	 * Whether the per-project gallery box + single-view gallery is active.
	 * Settings win; the legacy `has-gallery` config is the ultimate fallback.
	 *
	 * @return bool
	 */
	public function gallery_enabled() {
		if ( $this->get_config( 'has-gallery' ) !== true ) {
			return false;
		}

		return $this->feature_enabled( 'enable_gallery', true );
	}

	/**
	 * Whether the project Tag taxonomy should be registered. Settings drive the
	 * default; the legacy filter still wins so existing code keeps working.
	 *
	 * @return bool
	 */
	public function tags_enabled() {
		return (bool) apply_filters( 'fw:ext:portfolio:enable-tags', $this->feature_enabled( 'enable_tags', false ) );
	}

	private function define_slugs() {
		$this->slug = apply_filters(
			'fw_ext_portfolio_post_slug',
			$this->get_db_data( 'permalinks/post', $this->slug )
		);

		$this->taxonomy_slug = apply_filters(
			'fw_ext_portfolio_taxonomy_slug',
			$this->get_db_data( 'permalinks/taxonomy', $this->taxonomy_slug )
		);
	}

	private function add_admin_actions() {
		add_action( 'admin_init', array( $this, '_action_add_permalink_in_settings' ) );
		add_action( 'admin_menu', array( $this, '_action_admin_rename_projects' ) );
		add_action( 'admin_menu', array( $this, '_action_admin_register_import_page' ) );
		add_action( 'restrict_manage_posts', array( $this, '_action_admin_add_portfolio_edit_page_filter' ) );
		// listing screen
		add_action( 'manage_' . $this->post_type . '_posts_custom_column',
			array(
				$this,
				'_action_admin_manage_custom_column'
			),
			10,
			2 );

		// add / edit screen
		add_action( 'do_meta_boxes', array( $this, '_action_admin_featured_image_label' ) );

		add_action( 'admin_enqueue_scripts', array( $this, '_action_admin_add_static' ) );

		add_action( 'admin_head', array( $this, '_action_admin_initial_nav_menu_meta_boxes' ), 999 );
	}

	private function save_permalink_structure() {

		if ( ! isset( $_POST['permalink_structure'] ) && ! isset( $_POST['category_base'] ) ) {
			return;
		}

		$post = FW_Request::POST( 'fw_ext_portfolio_project_slug',
			apply_filters( 'fw_ext_portfolio_post_slug', $this->slug )
		);

		$taxonomy = FW_Request::POST( 'fw_ext_portfolio_portfolio_slug',
			apply_filters( 'fw_ext_portfolio_taxonomy_slug', $this->taxonomy_slug )
		);


		$this->set_db_data( 'permalinks/post', $post );
		$this->set_db_data( 'permalinks/taxonomy', $taxonomy );
	}

	/**
	 * @internal
	 **/
	public function _action_add_permalink_in_settings() {
		add_settings_field(
			'fw_ext_portfolio_project_slug',
			__( 'Project base', 'fw' ),
			array( $this, '_project_slug_input' ),
			'permalink',
			'optional'
		);

		add_settings_field(
			'fw_ext_portfolio_portfolio_slug',
			__( 'Portfolio category base', 'fw' ),
			array( $this, '_portfolio_slug_input' ),
			'permalink',
			'optional'
		);
	}

	/**
	 * @internal
	 */
	public function _project_slug_input() {
		?>
		<input type="text" name="fw_ext_portfolio_project_slug" value="<?php echo esc_attr( $this->slug ); ?>">
		<code>/my-project</code>
		<?php
	}

	/**
	 * @internal
	 */
	public function _portfolio_slug_input() {
		?>
		<input type="text" name="fw_ext_portfolio_portfolio_slug" value="<?php echo esc_attr( $this->taxonomy_slug ); ?>">
		<code>/my-portfolio</code>
		<?php
	}

	public function add_admin_filters() {
		add_filter( 'parse_query', array( $this, '_filter_admin_filter_portfolios_by_portfolio_category' ), 10, 2 );
		add_filter( 'months_dropdown_results', array( $this, '_filter_admin_remove_select_by_date_filter' ) );
		add_filter( 'manage_edit-' . $this->post_type . '_columns',
			array(
				$this,
				'_filter_admin_manage_edit_columns'
			),
			10,
			1 );

		// Always attach; the callback decides per-feature. Reading the extension
		// settings here (during _init/boot) would force Unyson's option-types
		// init too early — before the page-builder extension registers its
		// `page-builder` option type — breaking the page builder. The callback
		// runs late (when post options are collected), where reading settings is
		// safe.
		add_filter( 'fw_post_options', array( $this, '_filter_admin_add_post_options' ), 10, 2 );
	}

	/**
	 * @internal
	 */
	public function _action_admin_add_static() {
		$projects_listing_screen  = array(
			'only' => array(
				array(
					'post_type' => $this->post_type,
					'base'      => array( 'edit' )
				)
			)
		);
		$projects_add_edit_screen = array(
			'only' => array(
				array(
					'post_type' => $this->post_type,
					'base'      => 'post'
				)
			)
		);

		if ( fw_current_screen_match( $projects_listing_screen ) ) {
			wp_enqueue_style(
				'fw-extension-' . $this->get_name() . '-listing',
				fw_min_uri($this->get_declared_URI( '/static/css/admin-listing.css' )),
				array(),
				fw()->manifest->get_version()
			);
		}

		if ( fw_current_screen_match( $projects_add_edit_screen ) ) {
			wp_enqueue_style(
				'fw-extension-' . $this->get_name() . '-add-edit',
				fw_min_uri($this->get_declared_URI( '/static/css/admin-add-edit.css' )),
				array(),
				fw()->manifest->get_version()
			);
			wp_enqueue_script(
				'fw-extension-' . $this->get_name() . '-add-edit',
				fw_min_uri($this->get_declared_URI( '/static/js/admin-add-edit.js' )),
				array( 'jquery' ),
				fw()->manifest->get_version(),
				true
			);
		}
	}

	/**
	 * @internal
	 */
	public function _action_register_post_type() {

		$post_names = apply_filters( 'fw_ext_projects_post_type_name',
			array(
				'singular' => __( 'Project', 'fw' ),
				'plural'   => __( 'Projects', 'fw' )
			) );

		$supports = apply_filters(
			'fw_ext_projects_feature_supports',
			array(
				'title', /* Text input field to create a post title. */
				'editor',
				'thumbnail', /* Displays a box for featured image. */
				'revisions',
				'page-attributes' /* Order field — powers the "Custom order" (menu_order) sorting. */
			)
		);

		register_post_type( $this->post_type,
			array(
				'labels'             => array(
					'name'               => $post_names['plural'], //__( 'Portfolio', 'fw' ),
					'singular_name'      => $post_names['singular'], //__( 'Portfolio project', 'fw' ),
					'add_new'            => __( 'Add New', 'fw' ),
					'add_new_item'       => sprintf( __( 'Add New %s', 'fw' ), $post_names['singular'] ),
					'edit'               => __( 'Edit', 'fw' ),
					'edit_item'          => sprintf( __( 'Edit %s', 'fw' ), $post_names['singular'] ),
					'new_item'           => sprintf( __( 'New %s', 'fw' ), $post_names['singular'] ),
					'all_items'          => sprintf( __( 'All %s', 'fw' ), $post_names['plural'] ),
					'view'               => sprintf( __( 'View %s', 'fw' ), $post_names['singular'] ),
					'view_item'          => sprintf( __( 'View %s', 'fw' ), $post_names['singular'] ),
					'search_items'       => sprintf( __( 'Search %s', 'fw' ), $post_names['plural'] ),
					'not_found'          => sprintf( __( 'No %s Found', 'fw' ), $post_names['plural'] ),
					'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'fw' ), $post_names['plural'] ),
					'parent_item_colon'  => '' /* text for parent types */
				),
				'description'        => __( 'Create a portfolio item', 'fw' ),
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'publicly_queryable' => true,
				/* queries can be performed on the front end */
				'has_archive'        => true,
				'rewrite'            => array(
					'slug' => $this->slug
				),
				'menu_position'      => 4,
				'show_in_nav_menus'  => true,
				'menu_icon'          => 'dashicons-portfolio',
				'hierarchical'       => false,
				'query_var'          => true,
				'show_in_rest'       => true,
				/* Sets the query_var key for this post type. Default: true - set to $post_type */
				'taxonomies'         => array_values( array_filter( array(
					$this->taxonomy_name,
					$this->tags_enabled() ? $this->taxonomy_tag_name : null,
				) ) ),
				'supports'           => $supports,
				'capabilities'       => array(
					'edit_post'              => 'edit_pages',
					'read_post'              => 'edit_pages',
					'delete_post'            => 'edit_pages',
					'edit_posts'             => 'edit_pages',
					'edit_others_posts'      => 'edit_pages',
					'publish_posts'          => 'edit_pages',
					'read_private_posts'     => 'edit_pages',
					'read'                   => 'edit_pages',
					'delete_posts'           => 'edit_pages',
					'delete_private_posts'   => 'edit_pages',
					'delete_published_posts' => 'edit_pages',
					'delete_others_posts'    => 'edit_pages',
					'edit_private_posts'     => 'edit_pages',
					'edit_published_posts'   => 'edit_pages',
				),
			) );

	}

	/**
	 * @internal
	 */
	public function _action_register_taxonomy() {

		$category_names = apply_filters( 'fw_ext_portfolio_category_name', array(
			'singular' => __( 'Category', 'fw' ),
			'plural'   => __( 'Categories', 'fw' )
		) );

		register_taxonomy( $this->taxonomy_name, $this->post_type, array(
			'labels'            => array(
				'name'              => sprintf( _x( 'Portfolio %s', 'taxonomy general name', 'fw' ), $category_names['plural'] ),
				'singular_name'     => sprintf( _x( 'Portfolio %s', 'taxonomy singular name', 'fw' ), $category_names['singular'] ),
				'search_items'      => sprintf( __( 'Search %s', 'fw' ), $category_names['plural'] ),
				'all_items'         => sprintf( __( 'All %s', 'fw' ), $category_names['plural'] ),
				'parent_item'       => sprintf( __( 'Parent %s', 'fw' ), $category_names['singular'] ),
				'parent_item_colon' => sprintf( __( 'Parent %s:', 'fw' ), $category_names['singular'] ),
				'edit_item'         => sprintf( __( 'Edit %s', 'fw' ), $category_names['singular'] ),
				'update_item'       => sprintf( __( 'Update %s', 'fw' ), $category_names['singular'] ),
				'add_new_item'      => sprintf( __( 'Add New %s', 'fw' ), $category_names['singular'] ),
				'new_item_name'     => sprintf( __( 'New %s Name', 'fw' ), $category_names['singular'] ),
				'menu_name'         => $category_names['plural'],
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'rewrite'           => array(
				'slug' => $this->taxonomy_slug
			),
		) );

		if ( $this->tags_enabled() ) {
			$tag_names = apply_filters( 'fw_ext_portfolio_tag_name', array(
				'singular' => __( 'Tag', 'fw' ),
				'plural'   => __( 'Tags', 'fw' )
			) );

			register_taxonomy($this->taxonomy_tag_name, $this->post_type, array(
				'hierarchical' => false,
				'labels' => array(
					'name'              => $tag_names['plural'],
					'singular_name'     => $tag_names['singular'],
					'search_items'      => sprintf( __('Search %s','fw'), $tag_names['plural']),
					'popular_items'     => sprintf( __( 'Popular %s','fw' ), $tag_names['plural']),
					'all_items'         => sprintf( __('All %s','fw'), $tag_names['plural']),
					'parent_item'       => null,
					'parent_item_colon' => null,
					'edit_item'         => sprintf( __('Edit %s','fw'), $tag_names['singular'] ),
					'update_item'       => sprintf( __('Update %s','fw'), $tag_names['singular'] ),
					'add_new_item'      => sprintf( __('Add New %s','fw'), $tag_names['singular'] ),
					'new_item_name'     => sprintf( __('New %s Name','fw'), $tag_names['singular'] ),
					'separate_items_with_commas'    => sprintf( __( 'Separate %s with commas','fw' ), strtolower($tag_names['plural'])),
					'add_or_remove_items'           => sprintf( __( 'Add or remove %s','fw' ), strtolower($tag_names['plural'])),
					'choose_from_most_used'         => sprintf( __( 'Choose from the most used %s','fw' ), strtolower($tag_names['plural'])),
				),
				'public' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_rest' => true,
				'query_var' => true,
				'rewrite' => array(
					'slug' => $this->taxonomy_tag_slug
				),
			));
		}
	}

	/**
	 * @internal
	 *
	 * @param array $options
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function _filter_admin_add_post_options( $options, $post_type ) {
		if ( $post_type !== $this->post_type ) {
			return $options;
		}

		if ( $this->feature_enabled( 'enable_project_details', true ) ) {
			$options[] = array(
				'project-details' => array(
					'context' => 'normal',
					'title'   => __( 'Project Details', 'fw' ),
					'type'    => 'box',
					'options' => array(
						'group_project_details' => array(
							'type'    => 'group',
							'options' => array(
								'project_featured' => array(
									'label' => __( 'Featured project', 'fw' ),
									'desc'  => __( 'Highlight this project and float it to the front of archives / featured queries.', 'fw' ),
									'type'  => 'switch',
									'value' => false,
								),
								'project_client'   => array(
									'label' => __( 'Client', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_url'      => array(
									'label' => __( 'Project URL', 'fw' ),
									'desc'  => __( 'Live link to the project / launched site.', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_date'     => array(
									'label' => __( 'Completion date', 'fw' ),
									'type'  => 'date-picker',
									'value' => '',
								),
								'project_services' => array(
									'label' => __( 'Services / Role', 'fw' ),
									'desc'  => __( 'Comma-separated list, e.g. "Design, Development, SEO".', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_role'     => array(
									'label' => __( 'Your role', 'fw' ),
									'desc'  => __( 'e.g. "Lead Designer", "Full-stack Developer".', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_tools'    => array(
									'label' => __( 'Tools / Tech stack', 'fw' ),
									'desc'  => __( 'Comma-separated, e.g. "Figma, WordPress, Three.js".', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_industry' => array(
									'label' => __( 'Industry', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_repo_url' => array(
									'label' => __( 'Repository URL', 'fw' ),
									'desc'  => __( 'e.g. a GitHub link, for development projects.', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_summary'  => array(
									'label' => __( 'Short summary', 'fw' ),
									'desc'  => __( 'A one or two line description used in listings and the details panel.', 'fw' ),
									'type'  => 'textarea',
									'value' => '',
								),
								'project_results'  => array(
									'label'         => __( 'Results / metrics', 'fw' ),
									'desc'          => __( 'Key outcomes shown as a metrics band, e.g. value "+38%" with label "Conversion rate".', 'fw' ),
									'type'          => 'addable-box',
									'value'         => array(),
									'template'      => '{{- value }} — {{- label }}',
									'box-options'   => array(
										'value' => array( 'label' => __( 'Value', 'fw' ), 'type' => 'text', 'value' => '' ),
										'label' => array( 'label' => __( 'Label', 'fw' ), 'type' => 'text', 'value' => '' ),
									),
								),
								'project_testimonial_quote'   => array(
									'label' => __( 'Testimonial quote', 'fw' ),
									'desc'  => __( 'A short client quote about this project. Leave empty to hide the testimonial block.', 'fw' ),
									'type'  => 'textarea',
									'value' => '',
								),
								'project_testimonial_author'  => array(
									'label' => __( 'Testimonial author', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
								'project_testimonial_company' => array(
									'label' => __( 'Testimonial company / role', 'fw' ),
									'type'  => 'text',
									'value' => '',
								),
							),
						),
					),
				),
			);
		}

		if ( $this->gallery_enabled() ) {
			$options[] = array(
				'general' => array(
					'context' => 'side',
					'title'   => __( 'Project', 'fw' ) . ' ' . __( 'Gallery', 'fw' ),
					'type'    => 'box',
					'options' => array(
						'project-gallery' => array(
							'label' => false,
							'type'  => 'multi-upload',
							'desc'  => false,
							'texts' => array(
								'button_add'  => __( 'Set project gallery', 'fw' ),
								'button_edit' => __( 'Edit project gallery', 'fw' )
							)
						)
					)
				)
			);
		}

		$options[] = array(
			'project-card' => array(
				'context' => 'side',
				'title'   => __( 'Card & Visibility', 'fw' ),
				'type'    => 'box',
				'options' => array(
					'project_card_image' => array(
						'label' => __( 'Card thumbnail', 'fw' ),
						'desc'  => __( 'Optional image used on grid/archive cards instead of the Cover Image — the crop that works in a grid is rarely the hero crop.', 'fw' ),
						'type'  => 'upload',
						'images_only' => true,
					),
					'project_hidden' => array(
						'label' => __( 'Hide from archives', 'fw' ),
						'desc'  => __( 'Exclude this project from the archive and portfolio grids. It stays reachable at its own URL.', 'fw' ),
						'type'  => 'switch',
						'value' => false,
					),
				),
			),
		);

		return $options;
	}

	/**
	 * Tune the front-end portfolio archive / category query from the saved
	 * settings (per-page, order, featured-first). Leaves admin + secondary
	 * queries untouched.
	 *
	 * @internal
	 *
	 * @param WP_Query $query
	 */
	public function _action_tune_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$is_portfolio_archive = $query->is_post_type_archive( $this->post_type )
		                        || $query->is_tax( $this->taxonomy_name )
		                        || ( $this->tags_enabled() && $query->is_tax( $this->taxonomy_tag_name ) );

		if ( ! $is_portfolio_archive ) {
			return;
		}

		$per_page = (int) $this->get_setting( 'archive_per_page', 12 );
		if ( $per_page > 0 ) {
			$query->set( 'posts_per_page', $per_page );
		}

		$orderby = (string) $this->get_setting( 'orderby', 'date' );
		$order   = (string) $this->get_setting( 'order', 'DESC' );

		// Projects flagged "Hide from archives" are excluded everywhere.
		$hidden_clause = array( 'key' => '_fw_portfolio_hidden', 'compare' => 'NOT EXISTS' );

		if ( $this->feature_enabled( 'featured_first', true ) && $orderby !== 'rand' ) {
			// Float featured projects first, then apply the chosen order. The
			// mirrored '_fw_portfolio_featured' meta only exists on featured
			// projects, so EXISTS/NOT-EXISTS lets every project sort cleanly.
			// (WP flattens named clauses recursively, so orderby can reference
			// fw_featured inside the nested OR group.)
			$query->set( 'meta_query', array(
				'relation'  => 'AND',
				'fw_hidden' => $hidden_clause,
				array(
					'relation'      => 'OR',
					'fw_featured'   => array( 'key' => '_fw_portfolio_featured', 'compare' => 'EXISTS' ),
					'fw_unfeatured' => array( 'key' => '_fw_portfolio_featured', 'compare' => 'NOT EXISTS' ),
				),
			) );
			$query->set( 'orderby', array(
				'fw_featured' => 'DESC',
				$orderby      => $order,
			) );
		} else {
			$query->set( 'meta_query', array( $hidden_clause ) );
			$query->set( 'orderby', $orderby );
			$query->set( 'order', $order );
		}
	}

	/**
	 * AJAX: filter / load-more for the [portfolio] grid element. Receives the
	 * grid's exported query JSON (re-sanitized through the same whitelist used
	 * at render time), an optional category filter and a page number; responds
	 * with the rendered cards + pagination state.
	 *
	 * @internal
	 */
	public function _action_ajax_load_grid() {
		check_ajax_referer( 'fw-portfolio-load', 'nonce' );

		// Browsing action: each call runs a WP_Query. Generous enough that a
		// visitor clicking through a grid never notices, tight enough that a
		// scripted loop stops being free.
		fw_rate_limit_ajax( 'portfolio_load', 60, 60 );

		$query = json_decode( wp_unslash( isset( $_POST['query'] ) ? $_POST['query'] : '' ), true );
		if ( ! is_array( $query ) ) {
			wp_send_json_error( array( 'message' => 'bad query' ), 400 );
		}

		$args   = fw_ext_portfolio_sanitize_grid_args( $query );
		$page   = max( 1, (int) ( isset( $_POST['page'] ) ? $_POST['page'] : 1 ) );
		$filter = (int) ( isset( $_POST['filter'] ) ? $_POST['filter'] : 0 );

		if ( 'loadmore' === $args['pagination'] && $args['count'] < 1 ) {
			$args['count'] = 12; // mirror render-time page-size fallback
		}

		if ( $filter > 0 ) {
			// A filter narrows the grid to one term — but never outside the
			// element's own category restriction.
			if ( empty( $args['categories'] ) || in_array( $filter, $args['categories'], true ) ) {
				$args['categories'] = array( $filter );
			}
		}

		$result = fw_ext_portfolio_query_projects( $args, $page );

		wp_send_json_success( array(
			'html' => fw_ext_portfolio_render_cards( $result['posts'], $args ),
			'page' => $page,
			'max'  => (int) $result['max'],
		) );
	}

	/**
	 * CreativeWork JSON-LD for single projects, built from the Project Details
	 * meta. Emitted on wp_head; skipped when another plugin already prints a
	 * CreativeWork for the page is not detectable, so themes can disable via
	 * the fw_ext_portfolio_jsonld filter (return empty array).
	 *
	 * @internal
	 */
	public function _action_single_project_jsonld() {
		if ( ! is_singular( $this->post_type ) ) {
			return;
		}

		$pid  = (int) get_queried_object_id();
		$meta = function_exists( 'fw_ext_portfolio_get_project_meta' )
			? fw_ext_portfolio_get_project_meta( $pid )
			: array();

		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => 'CreativeWork',
			'name'     => get_the_title( $pid ),
			'url'      => get_permalink( $pid ),
		);

		$summary = ! empty( $meta['summary'] ) ? $meta['summary'] : get_the_excerpt( $pid );
		if ( $summary ) {
			$data['description'] = wp_strip_all_tags( $summary );
		}

		if ( has_post_thumbnail( $pid ) ) {
			$image = wp_get_attachment_image_url( get_post_thumbnail_id( $pid ), 'full' );
			if ( $image ) {
				$data['image'] = $image;
			}
		}

		if ( ! empty( $meta['date'] ) ) {
			$ts = strtotime( $meta['date'] );
			if ( $ts ) {
				$data['dateCreated'] = gmdate( 'Y-m-d', $ts );
			}
		}

		$keywords = array();
		$terms    = get_the_terms( $pid, $this->taxonomy_name );
		if ( is_array( $terms ) ) {
			$keywords = wp_list_pluck( $terms, 'name' );
		}
		if ( ! empty( $meta['services'] ) ) {
			$keywords = array_merge( $keywords, array_filter( array_map( 'trim', explode( ',', $meta['services'] ) ) ) );
		}
		if ( ! empty( $keywords ) ) {
			$data['keywords'] = implode( ', ', array_unique( $keywords ) );
		}

		$data['creator'] = array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
		);

		$data = apply_filters( 'fw_ext_portfolio_jsonld', $data, $pid );
		if ( empty( $data ) ) {
			return;
		}

		echo '<script type="application/ld+json">'
			. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			. '</script>' . "\n";
	}

	/**
	 * Register the Jetpack Portfolio import tool under the Portfolio menu.
	 *
	 * @internal
	 */
	public function _action_admin_register_import_page() {
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,
			__( 'Import Jetpack Portfolio', 'fw' ),
			__( 'Import', 'fw' ),
			'manage_options',
			'fw-portfolio-import',
			array( $this, '_render_import_page' )
		);
	}

	/**
	 * The Jetpack Portfolio import tool. Converts `jetpack-portfolio` posts to
	 * this extension's post type and maps their taxonomies:
	 * jetpack-portfolio-type → portfolio category (found-or-created by name),
	 * jetpack-portfolio-tag → portfolio tag (only when tags are enabled).
	 * Bespoke management UI — exempt from the metabox-holder settings layout.
	 *
	 * @internal
	 */
	public function _render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$jetpack_posts = get_posts( array(
			'post_type'      => 'jetpack-portfolio',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$converted = null;

		if (
			! empty( $_POST['fw_portfolio_import'] )
			&& check_admin_referer( 'fw-portfolio-import' )
			&& ! empty( $jetpack_posts )
		) {
			$converted = 0;

			foreach ( $jetpack_posts as $pid ) {
				// Map taxonomy terms BEFORE the type switch (the old terms stay
				// attached; we add the new-taxonomy equivalents).
				$types = get_the_terms( $pid, 'jetpack-portfolio-type' );
				if ( is_array( $types ) ) {
					$new_ids = array();
					foreach ( $types as $type_term ) {
						$existing = get_term_by( 'name', $type_term->name, $this->taxonomy_name );
						if ( $existing ) {
							$new_ids[] = (int) $existing->term_id;
						} else {
							$created = wp_insert_term( $type_term->name, $this->taxonomy_name );
							if ( ! is_wp_error( $created ) ) {
								$new_ids[] = (int) $created['term_id'];
							}
						}
					}
					if ( $new_ids ) {
						wp_set_object_terms( $pid, $new_ids, $this->taxonomy_name );
					}
				}

				if ( $this->tags_enabled() ) {
					$tags = get_the_terms( $pid, 'jetpack-portfolio-tag' );
					if ( is_array( $tags ) ) {
						wp_set_object_terms( $pid, wp_list_pluck( $tags, 'name' ), $this->taxonomy_tag_name );
					}
				}

				set_post_type( $pid, $this->post_type );
				$converted ++;
			}

			// Fresh permalinks for the converted posts.
			flush_rewrite_rules();

			$jetpack_posts = array();
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Import Jetpack Portfolio', 'fw' ) . '</h1>';

		if ( null !== $converted ) {
			echo '<div class="notice notice-success"><p>'
				. esc_html( sprintf( _n( '%d project imported.', '%d projects imported.', $converted, 'fw' ), $converted ) )
				. '</p></div>';
		}

		if ( empty( $jetpack_posts ) ) {
			echo '<p>' . esc_html__( 'No Jetpack Portfolio (jetpack-portfolio) items found — nothing to import.', 'fw' ) . '</p>';
		} else {
			echo '<p>' . esc_html( sprintf(
				_n(
					'Found %d Jetpack Portfolio item. Importing converts it to a UnysonPlus Project and maps its Project Types to portfolio categories.',
					'Found %d Jetpack Portfolio items. Importing converts them to UnysonPlus Projects and maps their Project Types to portfolio categories.',
					count( $jetpack_posts ),
					'fw'
				),
				count( $jetpack_posts )
			) ) . '</p>';

			echo '<form method="post">';
			wp_nonce_field( 'fw-portfolio-import' );
			echo '<p><button type="submit" name="fw_portfolio_import" value="1" class="button button-primary">'
				. esc_html__( 'Import now', 'fw' ) . '</button></p>';
			echo '</form>';
		}

		echo '</div>';
	}

	/**
	 * Mirror the serialized "Featured" project option into a dedicated,
	 * queryable post meta key so archive ordering can use it.
	 *
	 * @internal
	 *
	 * @param int $post_id
	 */
	public function _action_sync_featured_meta( $post_id, $post = null ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post_type = $post ? $post->post_type : get_post_type( $post_id );
		if ( $post_type !== $this->post_type ) {
			return;
		}

		$featured = fw_get_db_post_option( $post_id, 'project_featured', false );

		if ( $featured ) {
			update_post_meta( $post_id, '_fw_portfolio_featured', 1 );
		} else {
			delete_post_meta( $post_id, '_fw_portfolio_featured' );
		}

		// Same mirror for the "Hide from archives" flag — archive queries and
		// grid queries exclude on this meta key (NOT EXISTS keeps unhidden
		// projects cheap to match).
		$hidden = fw_get_db_post_option( $post_id, 'project_hidden', false );

		if ( $hidden ) {
			update_post_meta( $post_id, '_fw_portfolio_hidden', 1 );
		} else {
			delete_post_meta( $post_id, '_fw_portfolio_hidden' );
		}
	}

	/**
	 * internal
	 */
	public function _action_admin_rename_projects() {
		global $menu;

		foreach ( $menu as $key => $menu_item ) {
			if ( $menu_item[2] == 'edit.php?post_type=' . $this->post_type ) {
				$menu[ $key ][0] = __( 'Portfolio', 'fw' );
			}
		}
	}

	/**
	 * Change the title of Featured Image Meta box
	 * @internal
	 */
	public function _action_admin_featured_image_label() {
		remove_meta_box( 'postimagediv', $this->post_type, 'side' );
		add_meta_box(
			'postimagediv',
			__( 'Project Cover Image', 'fw' ),
			'post_thumbnail_meta_box',
			$this->post_type,
			'side'
		);
	}

	/**
	 * @internal
	 *
	 * @param string $column_name
	 * @param int $id
	 */
	public function _action_admin_manage_custom_column( $column_name, $id ) {

		switch ( $column_name ) {
			case 'image':
				if ( get_the_post_thumbnail( intval( $id ) ) ) {
					$value = '<a href="' . get_edit_post_link( $id,
							true ) . '" title="' . esc_attr( __( 'Edit this item', 'fw' ) ) . '">' .
					         wp_get_attachment_image( get_post_thumbnail_id( intval( $id ) ),
						         array( 150, 100 ),
						         true ) .
					         '</a>';
				} else {
					$value = '<img src="' . esc_url( $this->get_declared_URI( '/static/images/no-image.png' ) ) . '" alt="" />';
				}
				echo $value;
				break;

			default:
				break;
		}
	}

	/**
	 * @internal
	 */
	public function _action_admin_initial_nav_menu_meta_boxes() {
		$screen = array(
			'only' => array(
				'base' => 'nav-menus'
			)
		);
		if ( ! fw_current_screen_match( $screen ) ) {
			return;
		}

		if ( get_user_option( 'fw-metaboxhidden_nav-menus' ) !== false ) {
			return;
		}

		$user              = wp_get_current_user();
		$hidden_meta_boxes = get_user_meta( $user->ID, 'metaboxhidden_nav-menus' );

		// The meta may be missing entirely (fresh user) — nothing to unhide then.
		if ( empty( $hidden_meta_boxes ) || ! is_array( $hidden_meta_boxes[0] ) ) {
			update_user_option( $user->ID, 'fw-metaboxhidden_nav-menus', 'updated', true );

			return;
		}

		$hidden = $hidden_meta_boxes[0];

		// Strict !== false: index 0 is a valid position (the old falsy check
		// could never unhide the first hidden box).
		if ( false !== ( $key = array_search( 'add-' . $this->taxonomy_name, $hidden, true ) ) ) {
			unset( $hidden[ $key ] );
		}

		update_user_option( $user->ID, 'metaboxhidden_nav-menus', $hidden, true );
		update_user_option( $user->ID, 'fw-metaboxhidden_nav-menus', 'updated', true );
	}

	/**
	 * @internal
	 */
	public function _action_admin_add_portfolio_edit_page_filter() {
		$screen = fw_current_screen_match( array(
			'only' => array(
				'base'      => 'edit',
				'id'        => 'edit-' . $this->post_type,
				'post_type' => $this->post_type,
			)
		) );

		if ( ! $screen ) {
			return;
		}

		$terms = get_terms( array( 'taxonomy' => $this->taxonomy_name ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<select name="' . $this->get_name() . '-filter-by-portfolio-category"><option value="0">' . __( 'View all categories',
					'fw' ) . '</option></select>';

			return;
		}

		$get = FW_Request::GET( $this->get_name() . '-filter-by-portfolio-category' );
		$id  = ( ! empty( $get ) ) ? (int) $get : 0;

		$dropdown_options = array(
			'selected'        => $id,
			'name'            => $this->get_name() . '-filter-by-portfolio-category',
			'taxonomy'        => $this->taxonomy_name,
			'show_option_all' => __( 'View all categories', 'fw' ),
			'hide_empty'      => true,
			'hierarchical'    => 1,
			'show_count'      => 0,
			'orderby'         => 'name',
		);

		wp_dropdown_categories( $dropdown_options );
	}

	/**
	 * @internal
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function _filter_admin_manage_edit_columns( $columns ) {
		$new_columns          = array();
		$new_columns['cb']    = $columns['cb']; // checkboxes for all projects page
		$new_columns['image'] = __( 'Cover Image', 'fw' );

		return array_merge( $new_columns, $columns );
	}

	/**
	 * @internal
	 *
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public function _filter_admin_filter_portfolios_by_portfolio_category( $query ) {
		$screen = fw_current_screen_match( array(
			'only' => array(
				'base'      => 'edit',
				'id'        => 'edit-' . $this->post_type,
				'post_type' => $this->post_type,
			)
		) );

		if ( ! $screen || ! $query->is_main_query() ) {
			return $query;
		}

		$filter_value = FW_Request::GET( $this->get_name() . '-filter-by-portfolio-category' );

		if ( empty( $filter_value ) ) {
			return $query;
		}

		$filter_value = (int) $filter_value;

		$query->set( 'tax_query',
			array(
				array(
					'taxonomy' => $this->taxonomy_name,
					'field'    => 'id',
					'terms'    => $filter_value,
				)
			) );

		return $query;
	}

	/**
	 * @internal
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function _filter_admin_remove_select_by_date_filter( $filters ) {
		$screen = array(
			'only' => array(
				'base' => 'edit',
				'id'   => 'edit-' . $this->post_type,
			)
		);

		if ( ! fw_current_screen_match( $screen ) ) {
			return $filters;
		}

		return array();
	}

	/**
	 * @internal
	 *
	 * @return string
	 */
	public function _get_link() {
		return self_admin_url( 'edit.php?post_type=' . $this->post_type );
	}

	public function get_settings() {

		$response = array(
			'post_type'         => $this->post_type,
			'slug'              => $this->slug,
			'taxonomy_slug'     => $this->taxonomy_slug,
			'taxonomy_name'     => $this->taxonomy_name,
			'taxonomy_tag_name' => $this->taxonomy_tag_name,
			'tags_enabled'      => $this->tags_enabled(),
		);

		return $response;
	}

	public function get_taxonomy_tag_name() {
		return $this->taxonomy_tag_name;
	}

	public function get_post_type_name() {
		return $this->post_type;
	}

	public function get_taxonomy_name() {
		return $this->taxonomy_name;
	}
}
