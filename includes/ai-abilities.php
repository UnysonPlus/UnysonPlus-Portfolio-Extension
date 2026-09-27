<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * AI Assistant abilities for the Portfolio.
 *
 *   portfolio-list          projects with status, categories, featured flag and main details
 *   portfolio-describe      the project fields (client, date, services, results, gallery …) with types
 *   portfolio-save-project  create or update a project: title, content, excerpt, categories / tags,
 *                           featured image, gallery, and the project details — validated against the
 *                           same field definitions the edit screen uses; undoable
 *
 * When the page builder is enabled for projects, the project BODY is a builder tree and is edited with
 * the AI Assistant's page abilities (get_page / insert_items … with the project id).
 */

if ( ! function_exists( 'fw_ext_portfolio_ai_register' ) ) :

	/**
	 * The project detail fields (the edit screen's post options), flattened: id => option.
	 *
	 * @return array
	 */
	function fw_ext_portfolio_ai_fields() {
		$ext = fw_ext( 'portfolio' );
		if ( ! $ext ) {
			return array();
		}
		$fields = fw_extract_only_options( (array) $ext->_filter_admin_add_post_options( array(), $ext->get_post_type_name() ) );
		foreach ( $fields as $id => $opt ) {
			if ( in_array( $opt['type'] ?? '', array( 'html', 'html-full', 'html-fixed' ), true ) ) {
				unset( $fields[ $id ] );
			}
		}
		return $fields;
	}

	/**
	 * @param WP_Post $p
	 * @return array
	 */
	function fw_ext_portfolio_ai_row( $p ) {
		$ext = fw_ext( 'portfolio' );
		return array(
			'id'         => (int) $p->ID,
			'title'      => $p->post_title,
			'status'     => $p->post_status,
			'url'        => get_permalink( $p ),
			'categories' => wp_get_object_terms( $p->ID, $ext->get_taxonomy_name(), array( 'fields' => 'names' ) ),
			'featured'   => (bool) fw_get_db_post_option( $p->ID, 'project_featured', false ),
			'client'     => (string) fw_get_db_post_option( $p->ID, 'project_client', '' ),
			'image_id'   => (int) get_post_thumbnail_id( $p ),
		);
	}

	function fw_ext_portfolio_ai_register() {
		if ( ! function_exists( 'fw_ai_register_ability' ) || ! fw_ext( 'portfolio' ) ) {
			return;
		}
		$pt = fw_ext( 'portfolio' )->get_post_type_name();

		fw_ai_register_ability( 'portfolio-list', array(
			'label'       => __( 'List portfolio projects', 'fw' ),
			'description' => 'Portfolio projects (drafts included) with status, url, categories, featured flag, client and featured image id; plus the existing project categories.',
			'permission'  => 'edit_posts',
			'readonly'    => true,
			'execute'     => function () use ( $pt ) {
				$ext = fw_ext( 'portfolio' );
				return array(
					'projects'   => array_map( 'fw_ext_portfolio_ai_row', get_posts( array( 'post_type' => $pt, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => 200, 'orderby' => 'menu_order date', 'order' => 'ASC' ) ) ),
					'categories' => get_terms( array( 'taxonomy' => $ext->get_taxonomy_name(), 'hide_empty' => false, 'fields' => 'names' ) ),
				);
			},
		) );

		fw_ai_register_ability( 'portfolio-describe', array(
			'label'       => __( 'Describe project fields', 'fw' ),
			'description' => 'The project detail fields portfolio_save_project accepts under details: id, type, label, choices and default (client, url, date, services, role, tools, summary, results list, testimonial, gallery …).',
			'permission'  => 'edit_posts',
			'readonly'    => true,
			'execute'     => function () {
				$out = array();
				foreach ( fw_ext_portfolio_ai_fields() as $id => $opt ) {
					$row = array( 'id' => (string) $id, 'type' => (string) $opt['type'] );
					if ( ! empty( $opt['label'] ) && is_string( $opt['label'] ) ) {
						$row['label'] = wp_strip_all_tags( $opt['label'] );
					}
					if ( in_array( $opt['type'], FW_AI_Schema::CHOICE_TYPES, true ) ) {
						$row['choices'] = FW_AI_Schema::flat_choices( $opt['choices'] ?? array() );
					}
					$inner = FW_AI_Schema::inner_leaves( $opt );
					if ( $inner ) {
						$row['inner_keys'] = array_keys( $inner );
					}
					if ( $opt['type'] === 'multi-upload' ) {
						$row['note'] = 'Set the gallery with gallery_ids (attachment ids) instead.';
					}
					$out[] = $row;
				}
				return array( 'fields' => $out );
			},
		) );

		fw_ai_register_ability( 'portfolio-save-project', array(
			'label'       => __( 'Create or update a project', 'fw' ),
			'description' => 'Creates a portfolio project (omit project_id; a DRAFT unless status says otherwise) or updates one. content is the body HTML (when the page builder is on for projects, build the body with insert_items on the project id instead); categories / tags are names (created if new); featured_image_id and gallery_ids are Media Library attachment ids; details holds project fields by id (see portfolio_describe). Only what you pass changes. Undo with undo_change.',
			'input'       => array(
				'project_id'        => array( 'type' => 'integer' ),
				'title'             => array( 'type' => 'string' ),
				'status'            => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ) ),
				'content'           => array( 'type' => 'string' ),
				'excerpt'           => array( 'type' => 'string' ),
				'categories'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				'tags'              => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				'featured_image_id' => array( 'type' => 'integer', 'minimum' => 0 ),
				'gallery_ids'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'details'           => array( 'type' => 'object' ),
			),
			'permission'  => function ( $in ) use ( $pt ) {
				$id = (int) ( $in['project_id'] ?? 0 );
				if ( $id ) {
					return get_post_type( $id ) === $pt && current_user_can( 'edit_post', $id );
				}
				$type = get_post_type_object( $pt );
				return $type && current_user_can( ( $in['status'] ?? 'draft' ) === 'publish' ? $type->cap->publish_posts : $type->cap->edit_posts );
			},
			'execute'     => 'fw_ext_portfolio_ai_save',
		) );
	}
	add_action( 'fw_ai_assistant_register_abilities', 'fw_ext_portfolio_ai_register' );

	/**
	 * @param array $in
	 * @return array|WP_Error
	 */
	function fw_ext_portfolio_ai_save( $in ) {
		$ext = fw_ext( 'portfolio' );
		$pt  = $ext->get_post_type_name();
		$tax = $ext->get_taxonomy_name();
		$id  = (int) ( $in['project_id'] ?? 0 );

		// Validate the details against the edit screen's field definitions.
		$details = isset( $in['details'] ) ? (array) json_decode( wp_json_encode( $in['details'] ), true ) : array();
		$fields  = fw_ext_portfolio_ai_fields();
		$errors  = array();
		foreach ( $details as $k => $v ) {
			if ( ! isset( $fields[ $k ] ) ) {
				$errors[] = sprintf( 'details.%s: not a project field (see portfolio_describe).', $k );
				continue;
			}
			FW_AI_Schema::check_deep( $fields[ $k ], $v, 'details.' . $k, $errors );
		}
		foreach ( array_merge( array( (int) ( $in['featured_image_id'] ?? 0 ) ), (array) ( $in['gallery_ids'] ?? array() ) ) as $att ) {
			if ( $att && get_post_type( (int) $att ) !== 'attachment' ) {
				$errors[] = sprintf( '%d is not a Media Library attachment id.', (int) $att );
			}
		}
		if ( $errors ) {
			return new WP_Error( 'fw_pf_ai_invalid', 'Nothing was changed: ' . implode( ' | ', $errors ) );
		}
		$tags_tax = taxonomy_exists( 'fw-portfolio-tag' ) ? 'fw-portfolio-tag' : '';

		if ( $id ) {
			$rev = fw_ai_snapshot( array(
				'post_fields' => array( $id => array( 'post_title', 'post_content', 'post_excerpt', 'post_status' ) ),
				'post_meta'   => array( $id => array( 'fw_options', '_thumbnail_id', '_fw_portfolio_featured', '_fw_portfolio_hidden' ) ),
				'post_terms'  => array( $id => array_filter( array( $tax, $tags_tax ) ) ),
			), 'unysonplus/portfolio-save-project', sprintf( 'Changed project "%s"', get_the_title( $id ) ) );
			$update = array( 'ID' => $id );
			if ( isset( $in['title'] ) ) {
				$update['post_title'] = sanitize_text_field( (string) $in['title'] );
			}
			if ( isset( $in['content'] ) ) {
				$update['post_content'] = wp_kses_post( (string) $in['content'] );
			}
			if ( isset( $in['excerpt'] ) ) {
				$update['post_excerpt'] = sanitize_textarea_field( (string) $in['excerpt'] );
			}
			if ( isset( $in['status'] ) ) {
				$update['post_status'] = (string) $in['status'];
			}
			if ( count( $update ) > 1 ) {
				$r = wp_update_post( wp_slash( $update ), true );
				if ( is_wp_error( $r ) ) {
					return $r;
				}
			}
			$action = 'Updated';
		} else {
			if ( empty( $in['title'] ) ) {
				return new WP_Error( 'fw_pf_ai_title', 'A new project needs a title.' );
			}
			$id = wp_insert_post( wp_slash( array(
				'post_type'    => $pt,
				'post_status'  => (string) ( $in['status'] ?? 'draft' ),
				'post_title'   => sanitize_text_field( (string) $in['title'] ),
				'post_content' => wp_kses_post( (string) ( $in['content'] ?? '' ) ),
				'post_excerpt' => sanitize_textarea_field( (string) ( $in['excerpt'] ?? '' ) ),
			) ), true );
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			$rev    = fw_ai_snapshot( array( 'created_posts' => array( $id ) ), 'unysonplus/portfolio-save-project', sprintf( 'Created project "%s"', get_the_title( $id ) ) );
			$action = 'Created';
		}

		if ( isset( $in['categories'] ) ) {
			wp_set_object_terms( $id, array_map( 'sanitize_text_field', (array) $in['categories'] ), $tax );
		}
		if ( isset( $in['tags'] ) && $tags_tax ) {
			wp_set_object_terms( $id, array_map( 'sanitize_text_field', (array) $in['tags'] ), $tags_tax );
		}
		if ( isset( $in['featured_image_id'] ) ) {
			if ( (int) $in['featured_image_id'] ) {
				set_post_thumbnail( $id, (int) $in['featured_image_id'] );
			} else {
				delete_post_thumbnail( $id );
			}
		}
		foreach ( $details as $k => $v ) {
			fw_set_db_post_option( $id, $k, $v );
		}
		if ( isset( $in['gallery_ids'] ) ) {
			$gallery = array();
			foreach ( (array) $in['gallery_ids'] as $att ) {
				$gallery[] = array( 'attachment_id' => (int) $att, 'url' => (string) wp_get_attachment_url( (int) $att ) );
			}
			fw_set_db_post_option( $id, 'project-gallery', $gallery );
		}
		// The archive's "featured" / "hidden" ordering reads mirrored meta, normally synced on save_post.
		$ext->_action_sync_featured_meta( $id );

		return array(
			'ok'               => true,
			'message'          => sprintf( '%s project "%s".', $action, get_the_title( $id ) ),
			'post_id'          => (int) $id,
			'undo_revision_id' => $rev,
		) + fw_ext_portfolio_ai_row( get_post( $id ) );
	}

endif;
