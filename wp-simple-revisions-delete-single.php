<?php
/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Direct acces not allowed!' );
}


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
 * Remove revisions button
 ***************************************************************/
function wpsrd_purge_revisions_button() {
	global $post;
	$postTypeList = wpsrd_post_types_default();

	if ( !in_array( get_post_type( $post->ID ), $postTypeList ) )
		return;

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


/***************************************************************
 * Remove revisions functions
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