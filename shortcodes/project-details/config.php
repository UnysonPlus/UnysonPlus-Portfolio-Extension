<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['page_builder'] = array(
	'title'       => __( 'Project Details', 'fw' ),
	'description' => __( 'A project\'s details list — client, date, role, services, tools, links. Drop it anywhere to build a custom project page.', 'fw' ),
	'tab'         => __( 'Components', 'fw' ),
	'popup_size'  => 'small',
);
