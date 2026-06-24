<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['page_builder'] = array(
	'title'       => __( 'Portfolio Grid', 'fw' ),
	'description' => __( 'A filterable grid of portfolio projects with category filter buttons.', 'fw' ),
	'tab'         => __( 'Components', 'fw' ),
	'popup_size'  => 'large',

	'title_template' => '
		{{ if ( o ) {
			var cols = o["columns"] || 3;
			var feat = o["featured_only"] === "yes" ? "Featured" : "All";
		}}
			<div style="margin-top:.5rem; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
				<strong>{{- feat }} projects</strong>
				<span style="opacity:.4;">|</span>
				<em style="opacity:.7;">{{- cols }} cols</em>
			</div>
		{{ } }}
	',
);
