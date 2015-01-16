<?php
/**
 * Plugin Name: Simple Revisions Delete by b*web
 * Plugin URI: http://b-website.com/
 * Description: Simple Revisions Delete add a discreet link within a post submit box to let you purge (delete) its revisions via AJAX. Bulk action is also available.
 * Author: Brice CAPOBIANCO
 * Author URI: http://b-website.com/
 * Version: 1.2.1
 * Domain Path: /langs
 * Text Domain: wpsrd-translate
 */


/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Direct acces not allowed!' );
}


/***************************************************************
 * Load plugin textdomain
 ***************************************************************/
function wpsrd_load_textdomain() {
	$path = dirname( plugin_basename( __FILE__ ) ) . '/langs/';
	load_plugin_textdomain( 'wpsrd-translate', false, $path );
}
add_action( 'init', 'wpsrd_load_textdomain' );


/***************************************************************
 * Load plugin files
 ***************************************************************/
$wppicFiles = array( 'functions','bulk','single' );
foreach( $wppicFiles as $wppicFile ){
	require_once( plugin_dir_path( __FILE__ ) . 'wp-simple-revisions-delete-' . $wppicFile . '.php' );
}


/***************************************************************
 * Add custom meta link on plugin list page
 ***************************************************************/
function wpsrd_meta_links( $links, $file ) {
	if ( $file === 'simple-revisions-delete/wp-simple-revisions-delete.php' ) {
		$links[] = '<a href="http://b-website.com/category/plugins" target="_blank" title="' . __( 'More b*web Plugins', 'wpsrd-translate' ) . '">'. __( 'More b*web Plugins', 'wpsrd-translate' ) .'</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7Z6YVM63739Y8" target="_blank" title="' . __( 'Donate to this plugin &#187;' ) . '"><strong>' . __( 'Donate to this plugin &#187;' ) . '</strong></a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'wpsrd_meta_links', 10, 2 );