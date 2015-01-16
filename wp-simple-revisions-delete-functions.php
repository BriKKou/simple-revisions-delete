<?php
/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Direct acces not allowed!' );
}


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
		wp_enqueue_script( 'wpsrd_admin_js', plugin_dir_url( __FILE__ ) . 'js/wpsrd-admin-script.js', array( 'jquery' ), '1.2' );
    }
}
add_action( 'admin_enqueue_scripts', 'wpsrd_add_admin_scripts', 10, 1 );


/***************************************************************
 * Post types supported list
 ***************************************************************/
function wpsrd_post_types_default(){
	$postTypes = array( 'post', 'page' );
	return $postTypes = apply_filters( 'wpsrd_post_types_list', $postTypes );
}

	
/***************************************************************
 * Display admin notice after purging revisions
 ***************************************************************/
function wpsrd_notice_display(){
	// Exit if no notice
	if ( ! ( $notices = get_transient( 'settings_errors' ) ) )
		return;

	$noticeCode = array( 'wpsrd_notice', 'wpsrd_notice_WP_error' );

	//Rebuild the notice
	foreach ( $notices as $notice ) {
		if( in_array( $notice[ 'code' ] , $noticeCode ) ) {
			add_settings_error(
				$notice[ 'setting' ],
				$notice[ 'code' ],
				$notice[ 'message' ],
				$notice[ 'type' ]
			);
		}
	}
	
	//Display the notice
	settings_errors( 'wpsrd-admin-notice' );
	
	// Remove the transient after displaying the notice
	delete_transient( 'settings_errors' );
}
add_action( 'admin_notices', 'wpsrd_notice_display', 0 );