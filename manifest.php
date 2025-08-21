<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Portfolio', 'fw' );
$manifest['slug']        = 'unysonplus-portfolio';
$manifest['description'] = __( 
	'This extension will add a fully fledged portfolio module that will let you display your projects using the built in portfolio pages.',
	'fw' 
);

$manifest['version']     = '1.0.9';
$manifest['display']     = true;
$manifest['standalone']  = true;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-Portfolio-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-Portfolio-Extension';
$manifest['github_branch'] = 'master';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '5.8';
