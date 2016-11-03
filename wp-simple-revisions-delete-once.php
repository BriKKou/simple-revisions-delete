<?php
/***************************************************************
 * SECURITY : Exit if accessed directly
***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	die( 'Direct access not allowed!' );
}


/***************************************************************
 * Insert delete button if user can delete revisions
***************************************************************/
function wpsrd_single_revision_delete_button() {
	global $post, $pagenow;
	if( 'post.php' == $pagenow ) {
		$postTypeList = wpsrd_post_types_default();
		
		if ( !isset( $post->ID ) )
			return;
			
		if ( current_user_can( apply_filters( 'wpsrd_capability', 'delete_post' ), $post->ID ) && in_array( get_post_type( $post->ID ), $postTypeList ) ) {
			echo '<div id="wpsrd-btn-container" style="display:none"><a href="#delete-revision" class="action wpsrd-btn once">' . __( 'Delete' ) . '</a></div>';
		}
	}
}
add_action('admin_footer', 'wpsrd_single_revision_delete_button');


/***************************************************************
 * Delete single revision from revisions meta box
***************************************************************/
function wpsrd_single_revision_delete() {
	
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	
		//Get var from GET
		$postID = $_GET[ 'wpsrd-post_ID' ];
		$revID = $_GET[ 'revID' ];
	
		$postTypeList = wpsrd_post_types_default();

		if ( !current_user_can( apply_filters( 'wpsrd_capability', 'delete_post' ), $postID ) && !in_array( get_post_type( $postID ), $postTypeList ) ) {
	 		wp_send_json_error( __( 'You can\'t do this...', 'simple-revisions-delete' ) );
		}
	
		if ( !empty( $revID ) && $postID == wp_is_post_revision( $revID ) ) {	
		
			$revDelete = wp_delete_post_revision( $revID );
			
			if( is_wp_error( $revDelete ) ) {
				//Extra error notice if WP error return something
				$output = array( 'success' => 'error', 'data' => $revDelete->get_error_message() );
			} else {
				$output = array( 'success' => 'success', 'data' => __( 'Deleted' ) );
			}
						
			( $output['success'] == 'success' ? wp_send_json_success( $output[ 'data' ] ) : wp_send_json_error( $output[ 'data' ] ) );
		
		} else {
	 		wp_send_json_error( __( 'Something went wrong', 'simple-revisions-delete' ) );
		}
		
	}

	//If accessed directly
	 wp_die( __( 'You can\'t do this...', 'simple-revisions-delete' ) );
	 
}
add_action( 'wp_ajax_wpsrd_single_revision_delete', 'wpsrd_single_revision_delete' );