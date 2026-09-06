<?php
/**
 * Bulk action on the post list tables.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Purge revisions" bulk action to every supported post type list table.
 */
class WPSRD_Bulk_Actions {

	/**
	 * Bulk action identifier.
	 *
	 * @var string
	 */
	const ACTION = 'wpsrd-purge';

	/**
	 * Registers the bulk action hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_actions' ) );
	}

	/**
	 * Registers the bulk action on each supported post type list table.
	 *
	 * @return void
	 */
	public static function register_actions() {
		foreach ( WPSRD_Revisions::get_post_types() as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( __CLASS__, 'add_action_option' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( __CLASS__, 'handle' ), 10, 3 );
		}
	}

	/**
	 * Adds the purge option to the bulk actions dropdown.
	 *
	 * @param array<string,string> $actions Registered bulk actions.
	 * @return array<string,string> Filtered bulk actions.
	 */
	public static function add_action_option( $actions ) {
		$actions[ self::ACTION ] = __( 'Purge revisions', 'simple-revisions-delete' );

		return $actions;
	}

	/**
	 * Purges the revisions of the selected posts.
	 *
	 * @param string $redirect_to Redirect URL built by the list table.
	 * @param string $doaction    Requested bulk action.
	 * @param int[]  $post_ids    Selected post IDs.
	 * @return string Redirect URL carrying the result of the operation.
	 */
	public static function handle( $redirect_to, $doaction, $post_ids ) {
		if ( self::ACTION !== $doaction ) {
			return $redirect_to;
		}

		$deleted = 0;
		$skipped = 0;
		$failed  = 0;

		foreach ( array_map( 'absint', (array) $post_ids ) as $post_id ) {
			if ( ! WPSRD_Revisions::user_can_purge( $post_id ) ) {
				++$skipped;
				continue;
			}

			$result = WPSRD_Revisions::purge( $post_id );

			if ( is_wp_error( $result ) ) {
				++$failed;
				continue;
			}

			$deleted += $result;
		}

		return add_query_arg(
			array(
				'wpsrd-purged'  => $deleted,
				'wpsrd-skipped' => $skipped,
				'wpsrd-failed'  => $failed,
			),
			$redirect_to
		);
	}
}
