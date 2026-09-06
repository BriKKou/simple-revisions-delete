<?php
/**
 * Revisions service.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds every read and write operation performed on post revisions.
 *
 * This class is the single source of truth for capability checks, supported
 * post types and deletions. Every entry point (REST, form fallback, bulk
 * action) goes through it.
 */
class WPSRD_Revisions {

	/**
	 * Returns the post types the plugin operates on.
	 *
	 * @return string[] List of post type names.
	 */
	public static function get_post_types() {
		/**
		 * Filters the post types handled by the plugin.
		 *
		 * @param string[] $post_types List of post type names.
		 */
		$post_types = apply_filters( 'wpsrd_post_types_list', array( 'post', 'page' ) );

		return array_filter( array_map( 'strval', (array) $post_types ) );
	}

	/**
	 * Returns the capability required to purge revisions.
	 *
	 * @return string Capability name.
	 */
	public static function get_capability() {
		/**
		 * Filters the capability required to delete revisions.
		 *
		 * @param string $capability Capability name.
		 */
		return (string) apply_filters( 'wpsrd_capability', 'delete_post' );
	}

	/**
	 * Tells whether a post belongs to a supported post type.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when the post type is supported.
	 */
	public static function is_supported( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		return in_array( $post->post_type, self::get_post_types(), true );
	}

	/**
	 * Tells whether the current user may purge the revisions of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when the current user is allowed to purge.
	 */
	public static function user_can_purge( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! self::is_supported( $post_id ) ) {
			return false;
		}

		return current_user_can( self::get_capability(), $post_id );
	}

	/**
	 * Counts the revisions of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Number of revisions.
	 */
	public static function count( $post_id ) {
		return count( wp_get_post_revisions( absint( $post_id ), array( 'fields' => 'ids' ) ) );
	}

	/**
	 * Deletes every revision of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int|WP_Error Number of deleted revisions, or an error when a deletion failed.
	 */
	public static function purge( $post_id ) {
		$revision_ids = wp_get_post_revisions( absint( $post_id ), array( 'fields' => 'ids' ) );
		$deleted      = 0;

		foreach ( $revision_ids as $revision_id ) {
			$result = wp_delete_post_revision( $revision_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Deletes a single revision.
	 *
	 * @param int $revision_id Revision ID.
	 * @param int $post_id     ID of the post the revision must belong to.
	 * @return true|WP_Error True on success, error object otherwise.
	 */
	public static function delete_one( $revision_id, $post_id ) {
		$revision_id = absint( $revision_id );
		$post_id     = absint( $post_id );

		if ( ! $revision_id || wp_is_post_revision( $revision_id ) !== $post_id ) {
			return new WP_Error(
				'wpsrd_invalid_revision',
				__( 'This revision does not belong to this post.', 'simple-revisions-delete' ),
				array( 'status' => 400 )
			);
		}

		$result = wp_delete_post_revision( $revision_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error(
				'wpsrd_delete_failed',
				__( 'The revision could not be deleted.', 'simple-revisions-delete' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}
}
