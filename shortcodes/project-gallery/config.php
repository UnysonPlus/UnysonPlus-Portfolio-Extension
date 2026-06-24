<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['page_builder'] = array(
	'title'       => __( 'Project Gallery', 'fw' ),
	'description' => __( 'Show a portfolio project\'s image gallery as a responsive grid with a built-in lightbox.', 'fw' ),
	'tab'         => __( 'Media Elements', 'fw' ),
	'popup_size'  => 'medium',

	'title_template' => '
		{{ if ( o ) {
			var src  = o["project_id"] === "current" || ! o["project_id"] ? "Current project" : ("Project #" + o["project_id"]);
			var cols = o["columns"] || 3;
		}}
			<div style="margin-top:.5rem; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
				<strong>{{- src }}</strong>
				<span style="opacity:.4;">|</span>
				<em style="opacity:.7;">{{- cols }} cols</em>
			</div>
		{{ } }}
	',
);
