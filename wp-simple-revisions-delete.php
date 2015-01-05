<?php
/**
 * Plugin Name: Simple Revisions Delete by b*web
 * Plugin URI: http://b-website.com/
 * Description: Add a discreet link in the post submitbox to let you purge the revisions of the current editing content via AJAX.
 * Author: Brice CAPOBIANCO
 * Author URI: http://b-website.com/
 * Version: 1.0
 * Domain Path: /langs
 * Text Domain: wpsrd-translate
 */


/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !function_exists( 'add_action' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/***************************************************************
 * Load plugin textdomain
 ***************************************************************/
function wpsrd_load_textdomain() {
	$path = dirname( plugin_basename( __FILE__ ) ) . '/langs/';
	$loaded = load_plugin_textdomain( 'wpsrd-translate', false, $path );
}
add_action( 'init', 'wpsrd_load_textdomain' );


/***************************************************************
 * Admin enqueue script
 ***************************************************************/
function wpsrd_add_admin_scripts( $page ) {
    if ( $page == 'post-new.php' || $page == 'post.php' ) {
		wp_enqueue_script( 'wpsrd-admin-js', plugin_dir_url( __FILE__ ) . 'js/wpsrd-admin-script.js', array( 'jquery' ),  NULL);
    }
}
add_action( 'admin_enqueue_scripts', 'wpsrd_add_admin_scripts', 10, 1 );


/***************************************************************
 * Add custom meta link on plugin list page
 ***************************************************************/
function wpsrd_meta_links( $links, $file ) {
	if ( strpos( $file, 'wp-simple-revisions-delete.php' ) !== false ) {
		$links[ 0 ] = '<a href="http://b-website.com/" target="_blank"><img src="' . plugin_dir_url( __FILE__ ) . 'img/icon-bweb.svg" style="margin-bottom: -4px; width: 18px;" alt="b*web"/></a>&nbsp;&nbsp;'. $links[ 0 ];
		$links[] = '<a href="http://b-website.com/category/plugins" target="_blank" title="' . __( 'More b*web Plugins', 'wpsrd-translate' ) . '">'. __( 'More b*web Plugins', 'wpsrd-translate' ) .'</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7Z6YVM63739Y8" target="_blank" title="' . __( 'Donate', 'wpsrd-translate' ) . '"><strong>' . __( 'Donate', 'wpsrd-translate' ) . '</strong></a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'wpsrd_meta_links', 10, 2 );


/***************************************************************
 * Remove revisions functions
 ***************************************************************/
function wpsrd_remove_revisions(){
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		if ( wp_verify_nonce( $_REQUEST[ 'wpsrd-nonce' ], 'wpsrd_nonce' ) ) {
			if ( isset( $_REQUEST[ 'wpsrd-post_ID' ] ) && !empty( $_REQUEST[ 'wpsrd-post_ID' ] ) ) {
				$revisions = wp_get_post_revisions( $_REQUEST[ 'wpsrd-post_ID' ] );
				if( !empty ( $revisions ) ) {
					foreach ( $revisions as $revision ) {
						$revDelete = wp_delete_post_revision( $revision );
						if( is_wp_error( $revDelete ) ) {
							$return = array( 'success' => false,  'message' => $revDelete->get_error_message() );
						} else {
							$return = array( 'success' => true,  'message' => '<span class="dashicons dashicons-yes" style="color:#7ad03a; "></span> ' . __( 'Purged', 'wpsrd-translate' ) );
						}
					}
				} else {
					$return = array( 'success' => false,  'message' => __( 'There is no revisions for this post', 'wpsrd-translate' ) );
				}
			} else {
					$return = array( 'success' => false,  'message' => __( 'Something went wrong', 'wpsrd-translate' ) );
			}
		} else { 
			$return = array( 'success' => false,  'message' => __( 'You can\'t do this...', 'wpsrd-translate' ) );
		}
		echo json_encode( $return );
		wp_die();
	}
}
add_action( 'wp_ajax_async_wpsrd_remove_revisions', 'wpsrd_remove_revisions' );


/***************************************************************
 * Remove revisions button 
 ***************************************************************/
function wpsrd_remove_revisions_button() {
	$content = '<span id="wpsrd-clear-revisions" style="display:none;">&nbsp;&nbsp;';
	$content .= '<a href="#clear-revisions" class="wpsrd-link once" data-nonce="'. wp_create_nonce('wpsrd_nonce') .'" data-action="'. __( 'Purging', 'wpsrd-translate' ) .'" data-error="'. __( 'Something went wrong', 'wpsrd-translate' ) .'">'. __( 'Purge', 'wpsrd-translate' ) .'</a>';
	$content .= '<span class="wpsrd-loading" style="display:none; background-image: url(' . admin_url() . 'images/spinner-2x.gif); display: none; width: 18px; height: 18px; background-size: cover; margin: 0 0 -5px 4px;"></span>';
	$content .= '</span>';
	echo $content;
}
add_action( 'post_submitbox_misc_actions', 'wpsrd_remove_revisions_button', 3 );