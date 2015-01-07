<?php
/**
 * Plugin Name: Simple Revisions Delete by b*web
 * Plugin URI: http://b-website.com/
 * Description: Add a discreet link in the post submitbox to let you purge the revisions of the current editing content via AJAX.
 * Author: Brice CAPOBIANCO
 * Author URI: http://b-website.com/
 * Version: 1.1
 * Domain Path: /langs
 * Text Domain: wpsrd-translate
 */


/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	exit;
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


/***************************************************************
 * Check if revisions are activated on plugin load
***************************************************************/
function wpsrd_norev_check(){
	if ( !WP_POST_REVISIONS ){
		//Keep in memory if revisions are deactivated
		set_transient('wpsrd_norev', true, 0);
	}
}
register_activation_hook( __FILE__, 'wpsrd_norev_check' );


/***************************************************************
 * Display the notice if revisions are deactivated
***************************************************************/
function wpsrd_norev_notice(){
	if ( current_user_can( 'activate_plugins' ) && 	!WP_POST_REVISIONS ){

		// Exit if no notice
		if ( ! ( get_transient( 'wpsrd_norev' ) ) )
			return;

		//Build the dismiss notice link
		$dismiss = '
			<a class="wpsrd-dismiss" href="' . admin_url( 'admin-post.php?action=wpsrd_norev_dismiss' ) . '" style="float: right; text-decoration: none;">
				' . __('Dismiss') . '<span class="dashicons dashicons-no-alt"></span>
			</a>
		';
		
		//Prepare the notice
		add_settings_error(
			'wpsrd-admin-norev',
			'wpsrd_norev',
			__( 'Revisions are deactivated on this site, the plugin "Simple Revisions Delete" has no reason to be installed.', 'wpsrd-translate' ) . ' ' . $dismiss,
			'error'
		);

		//Display the notice
		settings_errors('wpsrd-admin-norev');

	}
}
add_action( 'admin_notices', 'wpsrd_norev_notice' );


/***************************************************************
 * Dismiss the notice if revisions are deactivated
***************************************************************/
function wpsrd_norev_dismiss(){
	// Only redirect if accesed direclty & transients has already been deleted
	if ( ( get_transient( 'wpsrd_norev' ) ) ) {
		delete_transient( 'wpsrd_norev' );
	}
	
	//Redirect to previous page
	wp_safe_redirect( wp_get_referer() );
}
add_action( 'admin_post_wpsrd_norev_dismiss', 'wpsrd_norev_dismiss' );


/***************************************************************
 * Admin enqueue script
 ***************************************************************/
function wpsrd_add_admin_scripts( $page ) {
    if ( $page == 'post-new.php' || $page == 'post.php' ) {
		wp_enqueue_script( 'wpsrd_admin_js', plugin_dir_url( __FILE__ ) . 'js/wpsrd-admin-script.js', array( 'jquery' ), NULL );
    }
}
add_action( 'admin_enqueue_scripts', 'wpsrd_add_admin_scripts', 10, 1 );


/***************************************************************
 * Print Style in admin header
 ***************************************************************/
function wpsrd_add_admin_style() {
	echo '
	<style>
		#wpsrd-clear-revisions {
			display:none;
		}
		#wpsrd-clear-revisions .wpsrd-loading { 
			display:none; 
			background-image: url(' . admin_url('images/spinner-2x.gif') . '); 
			display: none; 
			width: 18px; 
			height: 18px; 
			background-size: cover; 
			margin: 0 0 -5px 4px;
		}
		#wpsrd-clear-revisions .wpsrd-link.sucess { 
			color: #444;
			font-weight: 600;
		}
		#wpsrd-clear-revisions .wpsrd-link.error { 
			display: block
			color: #a00;
			font-weight: normal;
		}
		.wpsrd-no-js:before {
			color: #888;
			content: "\f182";
			font: 400 20px/1 dashicons;
			speak: none;
			display: inline-block;
			padding: 0 2px 0 0;
			top: 0;
			left: -1px;
			position: relative;
			vertical-align: top;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			text-decoration: none!important;
		}
	</style>
	';
}
add_action( 'admin_print_styles-post-new.php', 'wpsrd_add_admin_style');
add_action( 'admin_print_styles-post.php', 'wpsrd_add_admin_style');


/***************************************************************
 * Remove revisions functions ==> voir pour moins indenter
 ***************************************************************/
function wpsrd_purge_revisions(){
	//Get var from GET
	$postID = $_GET[ 'wpsrd-post_ID' ];
	$nonce = $_GET[ 'wpsrd-nonce' ];
	
	//Nonce check
	if ( ! wp_verify_nonce( $nonce, 'delete-revisions_' . $postID ) ) {
		$output = array( 'success' => 'error', 'data' => __( 'You can\'t do this...', 'wpsrd-translate' ) );
	} else {
		$revisions = wp_get_post_revisions( $postID );
	}
	
	//Check revisions & delete them
	if( isset( $revisions ) && !empty ( $revisions ) ) {
		$output = array( 'success' => 'success', 'data' => __( 'Purged', 'wpsrd-translate' ) );
		foreach ( $revisions as $revision ) {
			$revDelete = wp_delete_post_revision( $revision );
			if( is_wp_error( $revDelete ) ) {
				$output = array( 'success' => 'error', 'data' => $revDelete->get_error_message() );
			}
		}
	} else {
		$output = array( 'success' => 'error', 'data' => __( 'There is no revisions for this post', 'wpsrd-translate' ) );
	}

	//Output for AJAX call or no JS fallback
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		( $output['success'] == 'success' ? wp_send_json_success( $output[ 'data' ] ) : wp_send_json_error( $output[ 'data' ] ) );
	} else {
		//Prepare the notice
		add_settings_error(
			'wpsrd-admin-notice',
			'wpsrd_notice',
			$output[ 'data' ],
			( $output[ 'success' ] == 'success'  ? 'updated' : 'error' )
		);
		//Store the notice for the redirection
		set_transient('settings_errors', get_settings_errors(), 30);
		
		//Build the redirection
		$redirect = add_query_arg( 'rev-purged', $output['success'], wp_get_referer() );
		
		wp_redirect( $redirect );
		exit;
	}
}
add_action( 'wp_ajax_wpsrd_purge_revisions', 'wpsrd_purge_revisions' );
add_action( 'admin_post_wpsrd_purge_revisions', 'wpsrd_purge_revisions' );


/***************************************************************
 * Display admin notice after purging revisions
 ***************************************************************/
function wpsrd_notice_display(){
	// Exit if no notice
	if ( ! ( $notices = get_transient( 'settings_errors' ) ) )
		return;
	
	//Rebuild the notice
	foreach ( $notices as $notice ) {
		if ( $notice[ 'code' ] == 'wpsrd_notice' ) {
			add_settings_error(
				$notice[ 'setting' ],
				$notice[ 'code' ],
				$notice[ 'message' ],
				$notice[ 'type' ]
			);
		}
	}
	
	//Display the notice
	settings_errors('wpsrd-admin-notice');
	
	// Remove the transient after displaying the notice
	delete_transient( 'settings_errors' );
}
add_action('admin_notices', 'wpsrd_notice_display',0);


/***************************************************************
 * Remove revisions button
 ***************************************************************/
function wpsrd_purge_revisions_button() {
	global $post;
	$revisions = wp_get_post_revisions( $post->ID );

	if( !empty ( $revisions ) ) {
		//Check if user can delete revisions
		if ( !current_user_can( 'delete_post', $post->ID ) )
			return;
		
		$nonce = wp_create_nonce( 'delete-revisions_' . $post->ID );
	
		$content = '<span id="wpsrd-clear-revisions">&nbsp;&nbsp;';
		$content .= '<a href="#clear-revisions" class="wpsrd-link once" data-nonce="' . $nonce . '" data-action="' . esc_attr__( 'Purging', 'wpsrd-translate' ) . '" data-error="' . esc_attr__( 'Something went wrong', 'wpsrd-translate' ) . '">';
		$content .= __( 'Purge', 'wpsrd-translate' );
		$content .= '</a>';
		$content .= '<span class="wpsrd-loading" ></span>';
		$content .= '</span>';
	
		$content .= '<noscript>';
		$content .= '<div class="misc-pub-section wpsrd-no-js">';
		$content .= '<a class="" href="' . admin_url( 'admin-post.php?action=wpsrd_purge_revisions&wpsrd-post_ID=' . $post->ID . '&wpsrd-nonce=' . $nonce ) . '">' . esc_attr__( 'Purge revisions', 'wpsrd-translate' ) . '</a>';
		$content .= '</div>';
		$content .= '</noscript>';

		echo $content;
	}
}
add_action( 'post_submitbox_misc_actions', 'wpsrd_purge_revisions_button', 3 );