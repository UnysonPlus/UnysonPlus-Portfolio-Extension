<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['has-gallery'] = true;
// Default portfolio image sizes. A theme may override these from
// framework-customizations/extensions/portfolio/config.php; this exists so
// get_image_sizes() returns something usable when none does.
$cfg['image_sizes'] = array(
	'featured-image' => array( 'width' => 223, 'height' => 139, 'crop' => true ),
	'gallery-image'  => array( 'width' => 700, 'height' => 455, 'crop' => true ),
);
