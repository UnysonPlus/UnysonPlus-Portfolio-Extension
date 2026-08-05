<?php
/**
 * Portfolio category / tag archive template — identical layout to the post
 * type archive, so it simply loads archive.php (the archive header + filter
 * bar there are already taxonomy-aware).
 */

if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

require dirname( __FILE__ ) . '/archive.php';
