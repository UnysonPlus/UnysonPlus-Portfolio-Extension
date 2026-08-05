<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

function fw_ext_portfolio_get_gallery_images( $post_id = 0 ) {
	if ( ! $post_id ) {
		// get_the_ID() returns false outside the loop — bail cleanly then.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return array();
		}
	}

	return fw_get_db_post_option( $post_id, 'project-gallery', array() );
}

/**
 * Sanitize a heading-tag choice for the rendering helpers. Levels are chosen
 * by outline position (house rule: no skipped levels), so h2 is the default —
 * portfolio sections sit directly under the page h1 on most themes.
 *
 * @param string $tag
 * @param string $default
 *
 * @return string
 */
function fw_ext_portfolio_sanitize_heading_tag( $tag, $default = 'h2' ) {
	$allowed = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' );

	$tag = strtolower( trim( (string) $tag ) );

	return in_array( $tag, $allowed, true ) ? $tag : $default;
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
		'client'              => '',
		'url'                 => '',
		'date'                => '',
		'services'            => '',
		'role'                => '',
		'tools'               => '',
		'industry'            => '',
		'repo_url'            => '',
		'summary'             => '',
		'results'             => array(),
		'testimonial_quote'   => '',
		'testimonial_author'  => '',
		'testimonial_company' => '',
		'featured'            => false,
		'hidden'              => false,
	);

	if ( ! $post_id ) {
		return $meta;
	}

	$meta['client']              = (string) fw_get_db_post_option( $post_id, 'project_client', '' );
	$meta['url']                 = (string) fw_get_db_post_option( $post_id, 'project_url', '' );
	$meta['date']                = (string) fw_get_db_post_option( $post_id, 'project_date', '' );
	$meta['services']            = (string) fw_get_db_post_option( $post_id, 'project_services', '' );
	$meta['role']                = (string) fw_get_db_post_option( $post_id, 'project_role', '' );
	$meta['tools']               = (string) fw_get_db_post_option( $post_id, 'project_tools', '' );
	$meta['industry']            = (string) fw_get_db_post_option( $post_id, 'project_industry', '' );
	$meta['repo_url']            = (string) fw_get_db_post_option( $post_id, 'project_repo_url', '' );
	$meta['summary']             = (string) fw_get_db_post_option( $post_id, 'project_summary', '' );
	$meta['results']             = (array) fw_get_db_post_option( $post_id, 'project_results', array() );
	$meta['testimonial_quote']   = (string) fw_get_db_post_option( $post_id, 'project_testimonial_quote', '' );
	$meta['testimonial_author']  = (string) fw_get_db_post_option( $post_id, 'project_testimonial_author', '' );
	$meta['testimonial_company'] = (string) fw_get_db_post_option( $post_id, 'project_testimonial_company', '' );
	$meta['featured']            = (bool) fw_get_db_post_option( $post_id, 'project_featured', false );
	$meta['hidden']              = (bool) fw_get_db_post_option( $post_id, 'project_hidden', false );

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
 * @param array $args { @type string $heading Optional heading above the list. @type string $heading_tag h2-h6|div, default h2. }
 *
 * @return string
 */
function fw_ext_portfolio_render_project_meta( $post_id = 0, $args = array() ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$args = array_merge( array( 'heading' => '', 'heading_tag' => 'h2' ), $args );
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

	if ( $meta['role'] !== '' ) {
		$rows[] = array( __( 'Role', 'fw' ), esc_html( $meta['role'] ) );
	}

	if ( $meta['industry'] !== '' ) {
		$rows[] = array( __( 'Industry', 'fw' ), esc_html( $meta['industry'] ) );
	}

	if ( $meta['services'] !== '' ) {
		$tags = array_filter( array_map( 'trim', explode( ',', $meta['services'] ) ) );
		$tags = array_map( function ( $t ) {
			return '<span class="fw-portfolio-meta__tag">' . esc_html( $t ) . '</span>';
		}, $tags );
		$rows[] = array( __( 'Services', 'fw' ), '<span class="fw-portfolio-meta__tags">' . implode( '', $tags ) . '</span>' );
	}

	if ( $meta['tools'] !== '' ) {
		$tools = array_filter( array_map( 'trim', explode( ',', $meta['tools'] ) ) );
		$tools = array_map( function ( $t ) {
			return '<span class="fw-portfolio-meta__tag">' . esc_html( $t ) . '</span>';
		}, $tools );
		$rows[] = array( __( 'Tools', 'fw' ), '<span class="fw-portfolio-meta__tags">' . implode( '', $tools ) . '</span>' );
	}

	if ( $meta['repo_url'] !== '' ) {
		$rows[] = array(
			__( 'Repository', 'fw' ),
			'<a href="' . esc_url( $meta['repo_url'] ) . '" target="_blank" rel="noopener noreferrer">' .
			esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $meta['repo_url'] ) ) ) . '</a>',
		);
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
		$tag  = fw_ext_portfolio_sanitize_heading_tag( $args['heading_tag'] );
		$out .= '<' . $tag . ' class="fw-portfolio-meta__heading">' . esc_html( $args['heading'] ) . '</' . $tag . '>';
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
 * Render a project's Results / metrics band. Returns '' when no metrics are
 * filled in.
 *
 * @param int $post_id
 *
 * @return string
 */
function fw_ext_portfolio_render_results( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$meta  = fw_ext_portfolio_get_project_meta( $post_id );
	$items = array();

	foreach ( $meta['results'] as $row ) {
		$value = isset( $row['value'] ) ? trim( (string) $row['value'] ) : '';
		$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
		if ( '' === $value && '' === $label ) {
			continue;
		}
		$items[] = '<div class="fw-portfolio-results__item">'
			. '<span class="fw-portfolio-results__value">' . esc_html( $value ) . '</span>'
			. '<span class="fw-portfolio-results__label">' . esc_html( $label ) . '</span>'
			. '</div>';
	}

	if ( empty( $items ) ) {
		return '';
	}

	return '<div class="fw-portfolio-results">' . implode( '', $items ) . '</div>';
}

/**
 * Render a project's client testimonial (quote + author + company). Returns
 * '' when no quote is set.
 *
 * @param int $post_id
 *
 * @return string
 */
function fw_ext_portfolio_render_testimonial( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$meta = fw_ext_portfolio_get_project_meta( $post_id );
	if ( '' === trim( $meta['testimonial_quote'] ) ) {
		return '';
	}

	$out  = '<figure class="fw-portfolio-testimonial">';
	$out .= '<blockquote class="fw-portfolio-testimonial__quote">' . esc_html( $meta['testimonial_quote'] ) . '</blockquote>';

	if ( '' !== $meta['testimonial_author'] || '' !== $meta['testimonial_company'] ) {
		$out .= '<figcaption class="fw-portfolio-testimonial__by">';
		if ( '' !== $meta['testimonial_author'] ) {
			$out .= '<span class="fw-portfolio-testimonial__author">' . esc_html( $meta['testimonial_author'] ) . '</span>';
		}
		if ( '' !== $meta['testimonial_company'] ) {
			$out .= '<span class="fw-portfolio-testimonial__company">' . esc_html( $meta['testimonial_company'] ) . '</span>';
		}
		$out .= '</figcaption>';
	}

	$out .= '</figure>';

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
		// Respect the per-project "Hide from archives" flag.
		'meta_query'          => array(
			array( 'key' => '_fw_portfolio_hidden', 'compare' => 'NOT EXISTS' ),
		),
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
 * @param array $args { @type int $count; @type string $heading; @type string $heading_tag h2-h6|div, default h2. }
 *
 * @return string
 */
function fw_ext_portfolio_render_related( $post_id = 0, $args = array() ) {
	$args = array_merge( array(
		'count'       => 3,
		'heading'     => __( 'Related Projects', 'fw' ),
		'heading_tag' => 'h2',
	), $args );

	$related = fw_ext_portfolio_get_related( $post_id, $args['count'] );
	if ( empty( $related ) ) {
		return '';
	}

	$out = '<section class="fw-portfolio-related">';
	if ( $args['heading'] !== '' ) {
		$tag  = fw_ext_portfolio_sanitize_heading_tag( $args['heading_tag'] );
		$out .= '<' . $tag . ' class="fw-portfolio-related__heading">' . esc_html( $args['heading'] ) . '</' . $tag . '>';
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
 * @param array       $args { @type bool $show_summary; @type bool $show_category; @type string $image_size; @type string $classes; }
 *
 * @return string
 */
function fw_ext_portfolio_render_card( $project, $args = array() ) {
	$project = is_object( $project ) ? $project : get_post( (int) $project );
	if ( ! $project ) {
		return '';
	}

	$args = array_merge( array(
		'show_summary'  => false,
		'show_category' => false,
		'image_size'    => 'large',
		'link_to'       => 'project',
		'classes'       => '',
	), $args );

	$pid   = (int) $project->ID;
	$link  = get_permalink( $pid );
	$title = get_the_title( $pid );

	// Dedicated card thumbnail wins over the cover image — the crop that works
	// in a grid is rarely the hero crop.
	$thumb    = '';
	$card_img = fw_get_db_post_option( $pid, 'project_card_image', array() );
	if ( is_array( $card_img ) && ! empty( $card_img['attachment_id'] ) ) {
		$thumb = wp_get_attachment_image( (int) $card_img['attachment_id'], $args['image_size'], false, array(
			'class'   => 'fw-portfolio-card__img',
			'loading' => 'lazy',
			'alt'     => $title,
		) );
	}
	if ( '' === $thumb && has_post_thumbnail( $pid ) ) {
		$thumb = get_the_post_thumbnail( $pid, $args['image_size'], array( 'class' => 'fw-portfolio-card__img', 'loading' => 'lazy' ) );
	}

	$classes = trim( 'fw-portfolio-card ' . $args['classes'] );

	// Card link mode: single project (default), the cover image in the
	// lightbox (gallery mode across the grid), or no link at all.
	$link_to    = $args['link_to'];
	$link_class = 'fw-portfolio-card__link';
	$link_open  = '';
	$link_close = '';

	if ( 'lightbox' === $link_to ) {
		$full = has_post_thumbnail( $pid ) ? wp_get_attachment_image_url( get_post_thumbnail_id( $pid ), 'full' ) : '';
		if ( $full ) {
			$link_open  = '<a class="' . $link_class . ' fw-pg__item" href="' . esc_url( $full ) . '" data-caption="' . esc_attr( $title ) . '">';
			$link_close = '</a>';
		} else {
			$link_to = 'project'; // no cover image to zoom — fall back
		}
	}

	if ( 'project' === $link_to ) {
		$link_open  = '<a class="' . $link_class . '" href="' . esc_url( $link ) . '">';
		$link_close = '</a>';
	} elseif ( 'none' === $link_to ) {
		$link_open  = '<span class="' . $link_class . ' fw-portfolio-card__link--static">';
		$link_close = '</span>';
	}

	$out  = '<article class="' . esc_attr( $classes ) . '">';
	$out .= $link_open;
	$out .= '<span class="fw-portfolio-card__media">';
	$out .= $thumb !== '' ? $thumb : '<span class="fw-portfolio-card__placeholder" aria-hidden="true"></span>';
	if ( fw_ext_portfolio_is_featured( $pid ) ) {
		$out .= '<span class="fw-portfolio-card__badge">' . esc_html__( 'Featured', 'fw' ) . '</span>';
	}
	$out .= '</span>';
	$out .= '<span class="fw-portfolio-card__body">';

	if ( ! empty( $args['show_category'] ) ) {
		$portfolio = fw_ext_portfolio();
		$terms     = $portfolio ? get_the_terms( $pid, $portfolio->get_taxonomy_name() ) : false;
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$out .= '<span class="fw-portfolio-card__cat">'
				. esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) . '</span>';
		}
	}

	$out .= '<span class="fw-portfolio-card__title">' . esc_html( $title ) . '</span>';

	if ( ! empty( $args['show_summary'] ) ) {
		$meta = fw_ext_portfolio_get_project_meta( $pid );
		if ( $meta['summary'] !== '' ) {
			$out .= '<span class="fw-portfolio-card__excerpt">' . esc_html( $meta['summary'] ) . '</span>';
		}
	}

	$out .= '</span>' . $link_close . '</article>';

	return $out;
}

/**
 * Compute the class list + inline custom-property style for a portfolio card
 * grid from its display args. Shared by the [portfolio] element's grid and
 * the archive views so both speak the same CSS contract.
 *
 * @param array $args { @type int $columns; @type int $gap; @type string $ratio 1-1|4-3|3-2|16-9|3-4|auto; @type string $hover zoom|overlay|grayscale|none; }
 *
 * @return array{class:string,style:string}
 */
function fw_ext_portfolio_grid_attrs( $args = array() ) {
	$args = array_merge( array(
		'columns' => 3,
		'gap'     => 24,
		'ratio'   => '4-3',
		'hover'   => 'zoom',
	), $args );

	$ratio_map = array(
		'1-1'  => '1 / 1',
		'4-3'  => '4 / 3',
		'3-2'  => '3 / 2',
		'16-9' => '16 / 9',
		'3-4'  => '3 / 4',
	);

	$cols  = max( 1, min( 6, (int) $args['columns'] ) );
	$gap   = max( 0, (int) $args['gap'] );
	$hover = in_array( $args['hover'], array( 'zoom', 'overlay', 'grayscale', 'none' ), true ) ? $args['hover'] : 'zoom';

	$classes = array( 'fw-portfolio-grid', 'fw-portfolio-grid--hover-' . $hover );
	$style   = sprintf( '--fw-portfolio-cols:%d;--fw-portfolio-gap:%dpx;', $cols, $gap );

	if ( 'auto' === $args['ratio'] ) {
		$classes[] = 'fw-portfolio-grid--ratio-auto';
	} elseif ( isset( $ratio_map[ $args['ratio'] ] ) ) {
		$style .= '--fw-portfolio-ratio:' . $ratio_map[ $args['ratio'] ] . ';';
	}

	return array(
		'class' => implode( ' ', $classes ),
		'style' => $style,
	);
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

	$args = fw_ext_portfolio_sanitize_grid_args( $args );

	$paginate = ( 'loadmore' === $args['pagination'] );
	if ( $paginate && $args['count'] < 1 ) {
		$args['count'] = 12; // load-more needs a page size; -1 would show everything.
	}

	$result = fw_ext_portfolio_query_projects( $args, 1 );
	if ( empty( $result['posts'] ) ) {
		return '';
	}

	$taxonomy = $portfolio->get_taxonomy_name();

	$grid_attrs = fw_ext_portfolio_grid_attrs( array(
		'columns' => $args['columns'],
		'gap'     => $args['gap'],
		'ratio'   => ( 'masonry' === $args['layout'] ) ? 'auto' : $args['ratio'],
		'hover'   => $args['hover'],
	) );
	$grid_classes = $grid_attrs['class'] . ' fw-portfolio-grid--layout-' . $args['layout'];

	// The AJAX contract: filtering + load-more re-query the server (real
	// pagination, unlike the old render-then-hide filter). The wrapper carries
	// the sanitized query so portfolio-grid.js can page/filter it.
	$wrap_attr = ' data-fw-portfolio-grid';
	if ( $paginate || $args['show_filters'] ) {
		$wrap_attr .= ' data-pf-query="' . esc_attr( wp_json_encode( fw_ext_portfolio_grid_query_export( $args ) ) ) . '"';
		$wrap_attr .= ' data-pf-page="1" data-pf-max="' . (int) $result['max'] . '"';
	}
	if ( 'lightbox' === $args['link_to'] ) {
		$wrap_attr .= ' data-fw-pg-lightbox';
	}

	$out = '<div class="fw-portfolio-grid-wrap"' . $wrap_attr . '>';

	// Filter bar — terms across the whole restriction (not just page 1).
	if ( ! empty( $args['show_filters'] ) ) {
		$t_args = array( 'taxonomy' => $taxonomy, 'hide_empty' => true );
		if ( ! empty( $args['categories'] ) ) {
			$t_args['include'] = $args['categories'];
		}
		$terms = get_terms( $t_args );

		if ( ! is_wp_error( $terms ) && count( $terms ) > 1 ) {
			$out .= '<ul class="fw-portfolio-filters" role="group" aria-label="' . esc_attr__( 'Filter projects by category', 'fw' ) . '">';
			$out .= '<li><button type="button" class="fw-portfolio-filter is-active" data-filter="0" data-slug="" aria-pressed="true">' . esc_html__( 'All', 'fw' ) . '</button></li>';
			foreach ( $terms as $term ) {
				$out .= '<li><button type="button" class="fw-portfolio-filter" data-filter="' . (int) $term->term_id . '"'
					. ' data-slug="' . esc_attr( $term->slug ) . '" aria-pressed="false">' . esc_html( $term->name ) . '</button></li>';
			}
			$out .= '</ul>';
		}
	}

	$out .= '<div class="' . esc_attr( $grid_classes ) . '" style="' . esc_attr( $grid_attrs['style'] ) . '">';
	$out .= fw_ext_portfolio_render_cards( $result['posts'], $args );
	$out .= '</div>';

	if ( $paginate ) {
		$hidden = ( $result['max'] <= 1 ) ? ' hidden' : '';
		$out .= '<div class="fw-portfolio-loadmore"' . $hidden . '>';
		$out .= '<button type="button" class="fw-portfolio-loadmore__btn">' . esc_html__( 'Load more projects', 'fw' ) . '</button>';
		$out .= '</div>';
	}

	// Screen-reader status line for AJAX updates (filter / load-more).
	$out .= '<span class="fw-portfolio-sr" role="status" aria-live="polite"></span>';

	$out .= '</div>';

	return $out;
}

/**
 * Whitelist-sanitize the grid args (shared by render time and the AJAX
 * endpoint, where the args arrive from the client and must be re-validated).
 *
 * @param array $args
 *
 * @return array
 */
function fw_ext_portfolio_sanitize_grid_args( $args ) {
	$defaults = array(
		'categories'    => array(),
		'count'         => -1,
		'columns'       => 3,
		'gap'           => 24,
		'ratio'         => '4-3',
		'hover'         => 'zoom',
		'layout'        => 'grid',
		'show_filters'  => true,
		'show_summary'  => false,
		'show_category' => false,
		'featured_only' => false,
		'orderby'       => 'date',
		'order'         => 'DESC',
		'image_size'    => 'large',
		'link_to'       => 'project',
		'pagination'    => 'none',
	);

	$args = array_merge( $defaults, array_intersect_key( (array) $args, $defaults ) );

	$bool = function ( $v ) {
		if ( is_string( $v ) ) {
			return in_array( strtolower( $v ), array( 'yes', '1', 'true', 'on' ), true );
		}
		return (bool) $v;
	};
	$enum = function ( $v, $allowed, $fallback ) {
		return in_array( $v, $allowed, true ) ? $v : $fallback;
	};

	$args['categories']    = array_values( array_filter( array_map( 'intval', (array) $args['categories'] ) ) );
	$args['count']         = (int) $args['count'];
	$args['columns']       = max( 1, min( 6, (int) $args['columns'] ) );
	$args['gap']           = max( 0, (int) $args['gap'] );
	$args['ratio']         = $enum( $args['ratio'], array( '1-1', '4-3', '3-2', '16-9', '3-4', 'auto' ), '4-3' );
	$args['hover']         = $enum( $args['hover'], array( 'zoom', 'overlay', 'grayscale', 'none' ), 'zoom' );
	$args['layout']        = $enum( $args['layout'], array( 'grid', 'masonry', 'list' ), 'grid' );
	$args['orderby']       = $enum( $args['orderby'], array( 'date', 'menu_order', 'title', 'rand' ), 'date' );
	$args['order']         = $enum( strtoupper( (string) $args['order'] ), array( 'DESC', 'ASC' ), 'DESC' );
	$args['image_size']    = $enum( $args['image_size'], array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), 'large' );
	$args['link_to']       = $enum( $args['link_to'], array( 'project', 'lightbox', 'none' ), 'project' );
	$args['pagination']    = $enum( $args['pagination'], array( 'none', 'loadmore' ), 'none' );
	$args['show_filters']  = $bool( $args['show_filters'] );
	$args['show_summary']  = $bool( $args['show_summary'] );
	$args['show_category'] = $bool( $args['show_category'] );
	$args['featured_only'] = $bool( $args['featured_only'] );

	return $args;
}

/**
 * The subset of grid args the AJAX endpoint needs to re-run the query and
 * re-render cards. Display-only knobs (columns/gap/ratio/hover/layout) stay
 * client-side — appended cards inherit the grid's CSS contract.
 *
 * @param array $args Sanitized grid args.
 *
 * @return array
 */
function fw_ext_portfolio_grid_query_export( $args ) {
	return array(
		'categories'    => $args['categories'],
		'count'         => $args['count'],
		'featured_only' => $args['featured_only'],
		'orderby'       => $args['orderby'],
		'order'         => $args['order'],
		'image_size'    => $args['image_size'],
		'show_summary'  => $args['show_summary'],
		'show_category' => $args['show_category'],
		'link_to'       => $args['link_to'],
		'pagination'    => $args['pagination'],
	);
}

/**
 * Query one page of projects for the grid.
 *
 * @param array $args Sanitized grid args.
 * @param int   $page 1-based page (meaningful when count > 0).
 *
 * @return array{posts:WP_Post[],max:int}
 */
function fw_ext_portfolio_query_projects( $args, $page = 1 ) {
	$portfolio = fw_ext_portfolio();
	if ( ! $portfolio ) {
		return array( 'posts' => array(), 'max' => 0 );
	}

	$per = (int) $args['count'];

	$q_args = array(
		'post_type'           => $portfolio->get_post_type_name(),
		'post_status'         => 'publish',
		'posts_per_page'      => $per,
		'paged'               => max( 1, (int) $page ),
		'orderby'             => $args['orderby'],
		'order'               => $args['order'],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => ( $per < 1 ),
	);

	if ( ! empty( $args['categories'] ) ) {
		$q_args['tax_query'] = array(
			array( 'taxonomy' => $portfolio->get_taxonomy_name(), 'field' => 'term_id', 'terms' => $args['categories'] ),
		);
	}

	// Grids always respect the per-project "Hide from archives" flag.
	$meta_query = array(
		array( 'key' => '_fw_portfolio_hidden', 'compare' => 'NOT EXISTS' ),
	);

	if ( ! empty( $args['featured_only'] ) ) {
		$meta_query[] = array( 'key' => '_fw_portfolio_featured', 'compare' => 'EXISTS' );
	}

	$q_args['meta_query'] = $meta_query;

	$query = new WP_Query( $q_args );

	return array(
		'posts' => $query->posts,
		'max'   => ( $per < 1 ) ? 1 : max( 1, (int) $query->max_num_pages ),
	);
}

/**
 * Render just the cards for a set of projects (the grid inner HTML) — used by
 * the initial render and by the AJAX filter / load-more responses.
 *
 * @param WP_Post[] $posts
 * @param array     $args Sanitized grid args (display subset).
 *
 * @return string
 */
function fw_ext_portfolio_render_cards( $posts, $args ) {
	if ( empty( $posts ) ) {
		return '';
	}

	$portfolio = fw_ext_portfolio();
	$taxonomy  = $portfolio ? $portfolio->get_taxonomy_name() : '';

	// One query warms the term cache for every card.
	if ( $portfolio ) {
		update_object_term_cache( wp_list_pluck( $posts, 'ID' ), $portfolio->get_post_type_name() );
	}

	$out = '';
	foreach ( $posts as $project ) {
		$classes = array();
		if ( $taxonomy ) {
			$terms = get_the_terms( $project->ID, $taxonomy );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$classes[] = 'cat-' . (int) $term->term_id;
				}
			}
		}

		$out .= fw_ext_portfolio_render_card( $project, array(
			'show_summary'  => ! empty( $args['show_summary'] ),
			'show_category' => ! empty( $args['show_category'] ),
			'image_size'    => $args['image_size'],
			'link_to'       => isset( $args['link_to'] ) ? $args['link_to'] : 'project',
			'classes'       => implode( ' ', $classes ),
		) );
	}

	return $out;
}

/**
 * Render the archive filter bar as REAL taxonomy links (not JS show/hide) —
 * each category filter is its own crawlable taxonomy archive URL, so the bar
 * stays correct alongside pagination and is SEO-visible. Used by the
 * extension's archive/taxonomy views. Returns '' with fewer than 2 terms.
 *
 * @return string
 */
function fw_ext_portfolio_render_archive_filter_links() {
	$portfolio = fw_ext_portfolio();
	if ( ! $portfolio ) {
		return '';
	}

	$taxonomy = $portfolio->get_taxonomy_name();

	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
	if ( is_wp_error( $terms ) || count( $terms ) < 2 ) {
		return '';
	}

	$archive_link = get_post_type_archive_link( $portfolio->get_post_type_name() );
	$current_term = is_tax( $taxonomy ) ? (int) get_queried_object_id() : 0;

	$item = function ( $url, $label, $active ) {
		return '<li><a class="fw-portfolio-filter' . ( $active ? ' is-active' : '' ) . '"'
			. ' href="' . esc_url( $url ) . '"'
			. ( $active ? ' aria-current="page"' : '' ) . '>'
			. esc_html( $label ) . '</a></li>';
	};

	$out  = '<nav class="fw-portfolio-filters fw-portfolio-filters--links" aria-label="' . esc_attr__( 'Filter projects by category', 'fw' ) . '"><ul>';
	$out .= $item( $archive_link, __( 'All', 'fw' ), ! $current_term );

	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$out .= $item( $link, $term->name, $current_term === (int) $term->term_id );
	}

	$out .= '</ul></nav>';

	return $out;
}

/**
 * Render previous / next project navigation for the single view — thumbnail +
 * direction label + project title (the title keeps the link text descriptive
 * per the house link-text rule). Adjacency follows publish order; optionally
 * constrained to projects sharing a portfolio category.
 *
 * @param int   $post_id Unused (adjacency comes from the global post) — kept
 *                       for signature symmetry with the other renderers.
 * @param array $args { @type bool $same_category Constrain to same category. }
 *
 * @return string
 */
function fw_ext_portfolio_render_prevnext( $post_id = 0, $args = array() ) {
	$portfolio = fw_ext_portfolio();
	if ( ! $portfolio ) {
		return '';
	}

	$args     = array_merge( array( 'same_category' => false ), $args );
	$taxonomy = $portfolio->get_taxonomy_name();
	$same     = ! empty( $args['same_category'] );

	$prev = get_previous_post( $same, '', $taxonomy );
	$next = get_next_post( $same, '', $taxonomy );

	if ( ! $prev && ! $next ) {
		return '';
	}

	$render_link = function ( $project, $dir ) {
		$pid   = (int) $project->ID;
		$title = get_the_title( $pid );
		$label = ( 'prev' === $dir ) ? __( 'Previous project', 'fw' ) : __( 'Next project', 'fw' );
		$thumb = has_post_thumbnail( $pid )
			? get_the_post_thumbnail( $pid, 'thumbnail', array( 'class' => 'fw-portfolio-prevnext__thumb', 'loading' => 'lazy' ) )
			: '';

		$out  = '<a class="fw-portfolio-prevnext__link fw-portfolio-prevnext__link--' . $dir . '"';
		$out .= ' href="' . esc_url( get_permalink( $pid ) ) . '" rel="' . ( 'prev' === $dir ? 'prev' : 'next' ) . '">';
		$out .= $thumb;
		$out .= '<span class="fw-portfolio-prevnext__body">';
		$out .= '<span class="fw-portfolio-prevnext__label">' . esc_html( $label ) . '</span>';
		$out .= '<span class="fw-portfolio-prevnext__title">' . esc_html( $title ) . '</span>';
		$out .= '</span></a>';

		return $out;
	};

	$out  = '<nav class="fw-portfolio-prevnext" aria-label="' . esc_attr__( 'Project navigation', 'fw' ) . '">';
	$out .= $prev ? $render_link( $prev, 'prev' ) : '<span class="fw-portfolio-prevnext__spacer" aria-hidden="true"></span>';
	$out .= $next ? $render_link( $next, 'next' ) : '<span class="fw-portfolio-prevnext__spacer" aria-hidden="true"></span>';
	$out .= '</nav>';

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
