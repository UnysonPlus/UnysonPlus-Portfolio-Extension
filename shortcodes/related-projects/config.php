<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['page_builder'] = array(
	'title'       => __( 'Related Projects', 'fw' ),
	'description' => __( 'A row of projects related to the current one (sharing a category), topped up with recent projects.', 'fw' ),
	'tab'         => __( 'Components', 'fw' ),
	'popup_size'  => 'small',
);
