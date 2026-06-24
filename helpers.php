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
 * Convenience accessor for the portfolio extension instance.
 *
 * @return FW_Extension_Portfolio|null
 */
function fw_ext_portfolio() {
	return function_exists( 'fw_ext' ) ? fw_ext( 'portfolio' ) : null;
}

/**
 * Read a project's "Project Details" meta (client, URL, date, services,
 * summary, featured). Returns raw saved values; rendering helpers escape.
 *
 * @param int $post_id 0 = current post.
 *
 * @return array{client:string,url:string,date:string,services:string,summary:string,featured:bool}
 */
function fw_ext_portfolio_get_project_meta( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

	$meta = array(
		'client'   => '',
		'url'      => '',
		'date'     => '',
		'services' => '',
		'summary'  => '',
		'featured' => false,
	);

	if ( ! $post_id ) {
		return $meta;
	}

	$meta['client']   = (string) fw_get_db_post_option( $post_id, 'project_client', '' );
	$meta['url']      = (string) fw_get_db_post_option( $post_id, 'project_url', '' );
	$meta['date']     = (string) fw_get_db_post_option( $post_id, 'project_date', '' );
	$meta['services'] = (string) fw_get_db_post_option( $post_id, 'project_services', '' );
	$meta['summary']  = (string) fw_get_db_post_option( $post_id, 'project_summary', '' );
	$meta['featured'] = (bool) fw_get_db_post_option( $post_id, 'project_featured', false );

	return $meta;
}

/**
 * Whether a project is flagged as featured.
 *
 * @param int $post_id
 *
 * @return bool
 */
function fw_ext_portfolio_is_featured( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

	return $post_id ? (bool) fw_get_db_post_option( $post_id, 'project_featured', false ) : false;
}

/**
 * Render a project's details as a definition list. Empty fields are skipped;
 * returns '' when there is nothing to show.
 *
 * @param int   $post_id 0 = current post.
 * @param array $args { @type string $heading Optional heading above the list. }
 *
 * @return string
 */
function fw_ext_portfolio_render_project_meta( $post_id = 0, $args = array() ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$args = array_merge( array( 'heading' => '' ), $args );
	$meta = fw_ext_portfolio_get_project_meta( $post_id );

	$rows = array();

	if ( $meta['client'] !== '' ) {
		$rows[] = array( __( 'Client', 'fw' ), esc_html( $meta['client'] ) );
	}

	if ( $meta['date'] !== '' ) {
		$ts   = strtotime( $meta['date'] );
		$date = $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $meta['date'];
		$rows[] = array( __( 'Date', 'fw' ), esc_html( $date ) );
	}

	if ( $meta['services'] !== '' ) {
		$tags = array_filter( array_map( 'trim', explode( ',', $meta['services'] ) ) );
		$tags = array_map( function ( $t ) {
			return '<span class="fw-portfolio-meta__tag">' . esc_html( $t ) . '</span>';
		}, $tags );
		$rows[] = array( __( 'Services', 'fw' ), '<span class="fw-portfolio-meta__tags">' . implode( '', $tags ) . '</span>' );
	}

	if ( $meta['url'] !== '' ) {
		$rows[] = array(
			__( 'Website', 'fw' ),
			'<a href="' . esc_url( $meta['url'] ) . '" target="_blank" rel="noopener noreferrer">' .
			esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $meta['url'] ) ) ) . '</a>',
		);
	}

	if ( empty( $rows ) ) {
		return '';
	}

	$out = '<div class="fw-portfolio-meta">';
	if ( $args['heading'] !== '' ) {
		$out .= '<h3 class="fw-portfolio-meta__heading">' . esc_html( $args['heading'] ) . '</h3>';
	}
	$out .= '<dl class="fw-portfolio-meta__list">';
	foreach ( $rows as $row ) {
		$out .= '<div class="fw-portfolio-meta__row">';
		$out .= '<dt class="fw-portfolio-meta__label">' . esc_html( $row[0] ) . '</dt>';
		$out .= '<dd class="fw-portfolio-meta__value">' . $row[1] . '</dd>';
		$out .= '</div>';
	}
	$out .= '</dl></div>';

	return $out;
}

/**
 * Query projects related to the given one (sharing a portfolio category).
 * Falls back to the most recent projects when none share a category.
 *
 * @param int $post_id
 * @param int $count
 *
 * @return WP_Post[]
 */
function fw_ext_portfolio_get_related( $post_id = 0, $count = 3 ) {
	$portfolio = fw_ext_portfolio();
	if ( ! $portfolio ) {
		return array();
	}

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$count   = max( 1, (int) $count );
	if ( ! $post_id ) {
		return array();
	}

	$post_type = $portfolio->get_post_type_name();
	$taxonomy  = $portfolio->get_taxonomy_name();

	$term_ids = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

	$base_args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'rand',
	);

	$related = array();

	if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) {
		$args = $base_args;
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			),
		);
		$related = get_posts( $args );
	}

	// Top up with recent projects if the category didn't yield enough.
	if ( count( $related ) < $count ) {
		$exclude = array_merge( array( $post_id ), wp_list_pluck( $related, 'ID' ) );
		$fill    = $base_args;
		$fill['orderby']        = 'date';
		$fill['order']          = 'DESC';
		$fill['post__not_in']   = $exclude;
		$fill['posts_per_page'] = $count - count( $related );
		$related = array_merge( $related, get_posts( $fill ) );
	}

	return $related;
}

/**
 * Render a row of related-project cards.
 *
 * @param int   $post_id
 * @param array $args { @type int $count; @type string $heading; }
 *
 * @return string
 */
function fw_ext_portfolio_render_related( $post_id = 0, $args = array() ) {
	$args = array_merge( array( 'count' => 3, 'heading' => __( 'Related Projects', 'fw' ) ), $args );

	$related = fw_ext_portfolio_get_related( $post_id, $args['count'] );
	if ( empty( $related ) ) {
		return '';
	}

	$out = '<section class="fw-portfolio-related">';
	if ( $args['heading'] !== '' ) {
		$out .= '<h3 class="fw-portfolio-related__heading">' . esc_html( $args['heading'] ) . '</h3>';
	}
	$out .= '<div class="fw-portfolio-related__grid">';

	foreach ( $related as $project ) {
		$out .= fw_ext_portfolio_render_card( $project );
	}

	$out .= '</div></section>';

	return $out;
}

/**
 * Render a single project card (thumbnail + title + optional summary). Shared
 * by the related row and the [portfolio] grid so they stay identical.
 *
 * @param WP_Post|int $project
 * @param array       $args { @type bool $show_summary; @type string $image_size; @type string $classes; }
 *
 * @return string
 */
function fw_ext_portfolio_render_card( $project, $args = array() ) {
	$project = is_object( $project ) ? $project : get_post( (int) $project );
	if ( ! $project ) {
		return '';
	}

	$args = array_merge( array(
		'show_summary' => false,
		'image_size'   => 'large',
		'classes'      => '',
	), $args );

	$pid   = (int) $project->ID;
	$link  = get_permalink( $pid );
	$title = get_the_title( $pid );
	$thumb = has_post_thumbnail( $pid )
		? get_the_post_thumbnail( $pid, $args['image_size'], array( 'class' => 'fw-portfolio-card__img', 'loading' => 'lazy' ) )
		: '';

	$classes = trim( 'fw-portfolio-card ' . $args['classes'] );

	$out  = '<article class="' . esc_attr( $classes ) . '">';
	$out .= '<a class="fw-portfolio-card__link" href="' . esc_url( $link ) . '">';
	$out .= '<span class="fw-portfolio-card__media">';
	$out .= $thumb !== '' ? $thumb : '<span class="fw-portfolio-card__placeholder" aria-hidden="true"></span>';
	if ( fw_ext_portfolio_is_featured( $pid ) ) {
		$out .= '<span class="fw-portfolio-card__badge">' . esc_html__( 'Featured', 'fw' ) . '</span>';
	}
	$out .= '</span>';
	$out .= '<span class="fw-portfolio-card__body">';
	$out .= '<span class="fw-portfolio-card__title">' . esc_html( $title ) . '</span>';

	if ( ! empty( $args['show_summary'] ) ) {
		$meta = fw_ext_portfolio_get_project_meta( $pid );
		if ( $meta['summary'] !== '' ) {
			$out .= '<span class="fw-portfolio-card__excerpt">' . esc_html( $meta['summary'] ) . '</span>';
		}
	}

	$out .= '</span></a></article>';

	return $out;
}

/**
 * Render a filterable grid of portfolio projects (category filter buttons +
 * a CSS grid). Filtering is dependency-free (handled by portfolio-grid.js).
 *
 * @param array $args {
 *     @type int|int[] $categories   Restrict to these category term IDs. Empty = all.
 *     @type int       $count        Max projects (-1 = all).               Default -1.
 *     @type int       $columns      Desktop columns (1-6).                  Default 3.
 *     @type int       $gap          Grid gap (px).                          Default 24.
 *     @type bool      $show_filters Show category filter buttons.           Default true.
 *     @type bool      $show_summary Show each project's summary.            Default false.
 *     @type bool      $featured_only Only featured projects.               Default false.
 *     @type string    $orderby      WP_Query orderby.                       Default 'date'.
 *     @type string    $order        ASC|DESC.                               Default 'DESC'.
 *     @type string    $image_size   Card thumbnail size.                    Default 'large'.
 * }
 *
 * @return string
 */
function fw_ext_portfolio_render_grid( $args = array() ) {
	$portfolio = fw_ext_portfolio();
	if ( ! $portfolio ) {
		return '';
	}

	$defaults = array(
		'categories'    => array(),
		'count'         => -1,
		'columns'       => 3,
		'gap'           => 24,
		'show_filters'  => true,
		'show_summary'  => false,
		'featured_only' => false,
		'orderby'       => 'date',
		'order'         => 'DESC',
		'image_size'    => 'large',
	);
	$args = array_merge( $defaults, $args );

	$post_type = $portfolio->get_post_type_name();
	$taxonomy  = $portfolio->get_taxonomy_name();

	$cat_ids = array();
	if ( ! empty( $args['categories'] ) ) {
		$cat_ids = array_filter( array_map( 'intval', (array) $args['categories'] ) );
	}

	$q_args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $args['count'],
		'orderby'             => $args['orderby'],
		'order'               => $args['order'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( ! empty( $cat_ids ) ) {
		$q_args['tax_query'] = array(
			array( 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $cat_ids ),
		);
	}

	if ( ! empty( $args['featured_only'] ) ) {
		$q_args['meta_query'] = array(
			array( 'key' => '_fw_portfolio_featured', 'compare' => 'EXISTS' ),
		);
	}

	$projects = get_posts( $q_args );
	if ( empty( $projects ) ) {
		return '';
	}

	// Categories present across the rendered projects, for the filter bar.
	$used_terms = array();
	$item_terms = array();
	foreach ( $projects as $project ) {
		$terms = wp_get_post_terms( $project->ID, $taxonomy );
		$slugs = array();
		foreach ( $terms as $term ) {
			if ( ! empty( $cat_ids ) && ! in_array( $term->term_id, $cat_ids, true ) ) {
				continue;
			}
			$used_terms[ $term->term_id ] = $term;
			$slugs[] = 'cat-' . $term->term_id;
		}
		$item_terms[ $project->ID ] = $slugs;
	}

	$cols = max( 1, min( 6, (int) $args['columns'] ) );
	$gap  = max( 0, (int) $args['gap'] );
	$style = sprintf( '--fw-portfolio-cols:%d;--fw-portfolio-gap:%dpx;', $cols, $gap );

	$out = '<div class="fw-portfolio-grid-wrap" data-fw-portfolio-grid>';

	// Filter bar.
	if ( ! empty( $args['show_filters'] ) && count( $used_terms ) > 1 ) {
		$out .= '<ul class="fw-portfolio-filters" role="tablist">';
		$out .= '<li><button type="button" class="fw-portfolio-filter is-active" data-filter="*">' . esc_html__( 'All', 'fw' ) . '</button></li>';
		foreach ( $used_terms as $term ) {
			$out .= '<li><button type="button" class="fw-portfolio-filter" data-filter="cat-' . (int) $term->term_id . '">' . esc_html( $term->name ) . '</button></li>';
		}
		$out .= '</ul>';
	}

	$out .= '<div class="fw-portfolio-grid" style="' . esc_attr( $style ) . '">';
	foreach ( $projects as $project ) {
		$item_classes = implode( ' ', $item_terms[ $project->ID ] );
		$out .= fw_ext_portfolio_render_card( $project, array(
			'show_summary' => ! empty( $args['show_summary'] ),
			'image_size'   => $args['image_size'],
			'classes'      => $item_classes,
		) );
	}
	$out .= '</div></div>';

	return $out;
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

	$ext_portfolio_settings = fw()->extensions->get( 'portfolio' )->get_settings();
	$taxonomy = $ext_portfolio_settings['taxonomy_name'];

	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	);

	if ( is_numeric( $term_ids ) ) {
		$args['parent'] = $term_ids;
	} elseif ( is_array( $term_ids ) ) {
		$args['include'] = $term_ids;
	}

	$categories = get_terms( $args );

	if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {

		if ( count( $categories ) === 1 ) {
			$categories = array_values( $categories );
			$categories = get_terms( array( 'taxonomy' => $taxonomy, 'parent' => $categories[0]->term_id, 'hide_empty' => false ) );
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
