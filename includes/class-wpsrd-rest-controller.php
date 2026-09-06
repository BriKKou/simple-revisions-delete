<?php
/**
 * REST API endpoints.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the revision operations to the editors through the REST API.
 */
class WPSRD_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE_V1 = 'wpsrd/v1';

	/**
	 * Registers the REST hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers the plugin routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$post_id_arg = array(
			'id' => array(
				'description'       => __( 'ID of the post owning the revisions.', 'simple-revisions-delete' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/posts/(?P<id>[\d]+)/revisions',
			array(
				'args' => $post_id_arg,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_count' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'purge' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/posts/(?P<id>[\d]+)/revisions/(?P<revision_id>[\d]+)',
			array(
				'args' => array_merge(
					$post_id_arg,
					array(
						'revision_id' => array(
							'description'       => __( 'ID of the revision to delete.', 'simple-revisions-delete' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					)
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_one' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Checks that the current user may act on the revisions of the requested post.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return true|WP_Error True when allowed, error object otherwise.
	 */
	public static function check_permission( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );

		if ( ! get_post( $post_id ) ) {
			return new WP_Error(
				'wpsrd_post_not_found',
				__( 'This post does not exist.', 'simple-revisions-delete' ),
				array( 'status' => 404 )
			);
		}

		if ( ! WPSRD_Revisions::user_can_purge( $post_id ) ) {
			return new WP_Error(
				'wpsrd_forbidden',
				__( 'You are not allowed to delete the revisions of this post.', 'simple-revisions-delete' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Returns the current revision count of a post.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response Response holding the revision count.
	 */
	public static function get_count( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'count'   => WPSRD_Revisions::count( $request['id'] ),
				'deleted' => 0,
			)
		);
	}

	/**
	 * Deletes every revision of a post.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response|WP_Error Response holding the deletion result.
	 */
	public static function purge( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$deleted = WPSRD_Revisions::purge( $post_id );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		return rest_ensure_response(
			array(
				'count'   => WPSRD_Revisions::count( $post_id ),
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * Deletes a single revision of a post.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response|WP_Error Response holding the deletion result.
	 */
	public static function delete_one( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$result  = WPSRD_Revisions::delete_one( $request['revision_id'], $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'count'   => WPSRD_Revisions::count( $post_id ),
				'deleted' => 1,
			)
		);
	}
}
