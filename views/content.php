<?php
/**
 * Single-project content: a responsive gallery grid (click → accessible
 * lightbox) followed by the project content. The gallery markup is produced
 * by fw_ext_portfolio_render_gallery() so it stays identical to the
 * [project_gallery] shortcode. Assets are enqueued in static.php.
 *
 * @var string $the_content
 */

if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

echo fw_ext_portfolio_render_gallery( get_the_ID(), array(
	'columns'        => 3,
	'columns_tablet' => 2,
	'columns_mobile' => 1,
	'gap'            => 16,
	'ratio'          => '4-3',
	'lightbox'       => true,
	'captions'       => false,
	'image_size'     => 'large',
) );

echo $the_content;
