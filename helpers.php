<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

function fw_ext_portfolio_get_gallery_images( $post_id = 0 ) {
	if ( 0 === $post_id && null === ( $post_id = get_the_ID() ) ) {
		return array();
	}

	return fw_get_db_post_option($post_id, 'project-gallery', array());
}

/**
 * Render a project's image gallery as a responsive grid that opens in the
 * built-in accessible lightbox. Shared by the single-project view
 * (views/content.php) and the [project_gallery] shortcode — the single source
 * of truth for gallery markup so both stay visually identical.
 *
 * Assets are enqueued by the caller (static.php / the shortcode's static.php);
 * this function only emits markup.
 *
 * @param int   $post_id Project (fw-portfolio) post ID. 0 = current post.
 * @param array $args {
 *     @type int    $columns        Desktop columns (1-6).        Default 3.
 *     @type int    $columns_tablet Tablet columns (1-4).         Default 2.
 *     @type int    $columns_mobile Mobile columns (1-2).         Default 1.
 *     @type int    $gap            Grid gap in px.               Default 16.
 *     @type string $ratio          Aspect ratio: '16-9','4-3','3-2','1-1','2-3','auto'. Default '4-3'.
 *     @type bool   $lightbox       Enable click-to-zoom lightbox. Default true.
 *     @type bool   $captions       Show caption under each thumb. Default false.
 *     @type string $image_size     Registered WP image size for the thumb. Default 'large'.
 * }
 *
 * @return string Gallery HTML, or '' when the project has no gallery images.
 */
function fw_ext_portfolio_render_gallery( $post_id = 0, $args = array() ) {
	if ( 0 === $post_id ) {
		$post_id = (int) get_the_ID();
	}
	if ( ! $post_id ) {
		return '';
	}

	$images = fw_ext_portfolio_get_gallery_images( $post_id );
	if ( empty( $images ) || ! is_array( $images ) ) {
		return '';
	}

	$defaults = array(
		'columns'        => 3,
		'columns_tablet' => 2,
		'columns_mobile' => 1,
		'gap'            => 16,
		'ratio'          => '4-3',
		'lightbox'       => true,
		'captions'       => false,
		'image_size'     => 'large',
	);
	$args = array_merge( $defaults, $args );

	$ratio_map = array(
		'16-9' => '16 / 9',
		'4-3'  => '4 / 3',
		'3-2'  => '3 / 2',
		'1-1'  => '1 / 1',
		'2-3'  => '2 / 3',
	);

	$cols   = max( 1, min( 6, (int) $args['columns'] ) );
	$colsT  = max( 1, min( 4, (int) $args['columns_tablet'] ) );
	$colsM  = max( 1, min( 2, (int) $args['columns_mobile'] ) );
	$gap    = max( 0, (int) $args['gap'] );
	$has_lb = ! empty( $args['lightbox'] );

	// Inline custom properties drive the grid — no per-instance <style> needed.
	$style  = sprintf( '--fw-pg-cols:%d;--fw-pg-cols-tablet:%d;--fw-pg-cols-mobile:%d;--fw-pg-gap:%dpx;', $cols, $colsT, $colsM, $gap );

	$grid_classes = array( 'fw-pg' );
	if ( $args['ratio'] !== 'auto' && isset( $ratio_map[ $args['ratio'] ] ) ) {
		$grid_classes[] = 'fw-pg--ratio';
		$style .= '--fw-pg-ratio:' . $ratio_map[ $args['ratio'] ] . ';';
	}

	$zoom_glyph = '<span class="fw-pg__zoom" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/></svg></span>';

	$out  = '<div class="' . esc_attr( implode( ' ', $grid_classes ) ) . '"';
	$out .= ' style="' . esc_attr( $style ) . '"';
	if ( $has_lb ) {
		$out .= ' data-fw-pg-lightbox';
	}
	$out .= '>';

	foreach ( $images as $image ) {
		if ( empty( $image['attachment_id'] ) ) {
			continue;
		}
		$att_id  = (int) $image['attachment_id'];
		$caption = trim( (string) get_the_title( $att_id ) );
		$full    = wp_get_attachment_image_url( $att_id, 'full' );
		if ( ! $full ) {
			continue;
		}

		// Responsive thumbnail — WP adds srcset, sizes, width/height + loading="lazy".
		$thumb = wp_get_attachment_image( $att_id, $args['image_size'], false, array(
			'alt'   => $caption,
			'class' => 'fw-pg__thumb',
		) );

		if ( $has_lb ) {
			$out .= '<a class="fw-pg__item" href="' . esc_url( $full ) . '"';
			$out .= ' data-caption="' . esc_attr( $caption ) . '"';
			$out .= ' aria-label="' . esc_attr( $caption !== '' ? $caption : __( 'View image', 'fw' ) ) . '">';
			$out .= $thumb . $zoom_glyph . '</a>';
		} else {
			$out .= '<figure class="fw-pg__item fw-pg__item--static">' . $thumb . '</figure>';
		}

		if ( ! empty( $args['captions'] ) && $caption !== '' ) {
			$out .= '<span class="fw-pg__caption">' . esc_html( $caption ) . '</span>';
		}
	}

	$out .= '</div>';

	return $out;
}

/**
 * @param int|array $term_ids
 *
 * @return array|WP_Error
 */
function fw_ext_portfolio_get_listing_categories( $term_ids ) {

	$args = array(
		'hide_empty'    => false
	);

	if ( is_numeric( $term_ids ) ) {
		$args['parent'] = $term_ids;
	} elseif ( is_array( $term_ids ) ) {
		$args['include'] = $term_ids;
	}

	$ext_portfolio_settings = fw()->extensions->get( 'portfolio' )->get_settings();
	$taxonomy = $ext_portfolio_settings['taxonomy_name'];

	$categories = get_terms( $taxonomy, $args );

	if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {

		if ( count( $categories ) === 1 ) {
			$categories = array_values( $categories );
			$categories = get_terms( $taxonomy, array( 'parent' => $categories[0]->term_id, 'hide_empty' => false ) );
		}

		foreach ( $categories as $key => $category ) {
			$children = get_term_children( $category->term_id, $taxonomy );
			$categories[ $key ]->children = $children;

			//remove empty categories
			if(($category->count == 0) && (is_wp_error($children) || empty($children))) {
				unset($categories[$key]);
			}
		}

		return $categories;
	}

	return  array();
}

/**
 * @param WP_Post[] $items
 * @param array $categories
 * @param string $prefix
 *
 * @return array
 */
function fw_ext_portfolio_get_sort_classes( array $items, array $categories, $prefix = 'category_' ) {

	$ext_portfolio_settings = fw()->extensions->get( 'portfolio' )->get_settings();
	$taxonomy = $ext_portfolio_settings['taxonomy_name'];
	$classes            = array();
	$categories_classes = array();
	foreach ( $items as $key => $item ) {
		$class_name = '';
		$terms      = wp_get_post_terms( $item->ID, $taxonomy );

		foreach ( $terms as $term ) {
			foreach ( $categories as $category ) {
				if ( $term->term_id == $category->term_id ) {
					$class_name .= $prefix . $category->term_id . ' ';
					$categories_classes[ $term->term_id ] = true;
				} else {
					if ( in_array( $term->term_id, $category->children, true ) ) {
						$class_name .= $prefix . $category->term_id . ' ';
						$categories_classes[ $term->term_id ] = true;
					}
				}
				$classes[ $item->ID ] = $class_name;
			}
		}
	}

	return $classes;
}
