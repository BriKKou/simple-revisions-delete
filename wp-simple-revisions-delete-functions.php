<?php
/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Direct access not allowed!' );
}


/***************************************************************
 * Print Style in admin header
 ***************************************************************/
function wpsrd_add_admin_style() {
	echo '
	<style>
		#wpsrd-clear-revisions,
		.wpsrd-no-js {
			display:none;
		}
		.wpsrd-loading { 
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
		.wp-core-ui .action.wpsrd-btn {
			display: inline-block;
			margin-left: 10px;
		}
	</style>
	<noscript>
		<style>
			.wpsrd-no-js {
				display:block;
			}
		</style>
	</noscript>
	';
}
add_action( 'admin_print_styles-post-new.php', 'wpsrd_add_admin_style');
add_action( 'admin_print_styles-post.php', 'wpsrd_add_admin_style');


/***************************************************************
 * Check if revisions are activated on plugin load
***************************************************************/
function wpsrd_norev_check(){
	if ( !WP_POST_REVISIONS ){
		//Keep in memory if revisions are deactivated
		set_transient( 'wpsrd_norev', true, 0 );
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
			__( 'Revisions are deactivated on this site, the plugin "Simple Revisions Delete" has no reason to be installed.', 'simple-revisions-delete' ) . ' ' . $dismiss,
			'error'
		);

		//Display the notice
		settings_errors( 'wpsrd-admin-norev' );
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
		wp_enqueue_script( 'wpsrd_admin_js', plugin_dir_url( __FILE__ ) . 'js/wpsrd-admin-script.js', array( 'jquery' ), '1.4.1' );
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
 * Hack to prevent 'W3 Total Cache' caching the notice transient
 * Thanks to @doublesharp http://wordpress.stackexchange.com/a/123537
 ***************************************************************/
function wpsrd_disable_linked_in_cached( $value=null ){
	if( is_admin() ) {
		global $pagenow;
		if( 'edit.php' == $pagenow ) {
			global $_wp_using_ext_object_cache;
			if ( !empty( $_wp_using_ext_object_cache ) ){
				$_wp_using_ext_object_cache_prev = $_wp_using_ext_object_cache;
				$_wp_using_ext_object_cache = false;
			}
		}
	}	
	return $value;
}
add_filter( 'pre_set_transient_wpsrd_settings_errors', 'wpsrd_disable_linked_in_cached' );
add_filter( 'pre_transient_wpsrd_settings_errors', 'wpsrd_disable_linked_in_cached' );
add_action( 'delete_transient_wpsrd_settings_errors', 'wpsrd_disable_linked_in_cached' );

function wpsrd_enable_linked_in_cached( $value=null ){
	if( is_admin() ) {
		global $pagenow;
		if( 'edit.php' == $pagenow ) {
			global $_wp_using_ext_object_cache;
			if ( !empty( $_wp_using_ext_object_cache ) ){
				$_wp_using_ext_object_cache = $_wp_using_ext_object_cache_prev;
			}
		}
	}
	return $value;
}
add_action( 'set_transient_wpsrd_settings_errors', 'wpsrd_enable_linked_in_cached' );
add_filter( 'transient_wpsrd_settings_errors', 'wpsrd_enable_linked_in_cached' );
add_action( 'deleted_transient_wpsrd_settings_errors', 'wpsrd_enable_linked_in_cached' );


/***************************************************************
 * Display admin notice after purging revisions
 ***************************************************************/
function wpsrd_notice_display(){
	
	// Exit if no notice
	if ( !( $notices = get_transient( 'wpsrd_settings_errors' ) ) )
		return;
		
	$noticeCode = array( 'wpsrd_notice', 'wpsrd_notice_WP_error' );
	
	//Rebuild the notice
	foreach ( (array) $notices as $notice ) {
		if( isset( $notice[ 'code' ] ) && in_array( $notice[ 'code' ] , $noticeCode ) ) {
			add_settings_error(
				$notice[ 'setting' ],
				$notice[ 'code' ],
				$notice[ 'message' ],
				$notice[ 'type' ]
			);
		}
	}

	//Display the notice
	settings_errors( $notice[ 'setting' ] );
	
	// Remove the transient after displaying the notice
	delete_transient( 'wpsrd_settings_errors' );
	
}
add_action( 'admin_notices', 'wpsrd_notice_display', 0 );