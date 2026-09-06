<?php
/**
 * Block editor integration.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the block editor plugin that renders the purge control in the
 * post status panel.
 */
class WPSRD_Block_Editor {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'wpsrd-block-editor';

	/**
	 * Registers the block editor hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_script' ) );
	}

	/**
	 * Enqueues the block editor script.
	 *
	 * @return void
	 */
	public static function enqueue_script() {
		$post = get_post();

		if ( ! $post || ! WPSRD_Revisions::user_can_purge( $post->ID ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WPSRD_URL . 'assets/js/wpsrd-block-editor.js',
			array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-a11y', 'wp-editor' ),
			WPSRD_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wpsrdBlockEditor',
			array(
				'i18n' => array(
					'label'      => __( 'Revisions', 'simple-revisions-delete' ),
					'purge'      => __( 'Purge', 'simple-revisions-delete' ),
					'purging'    => __( 'Purging…', 'simple-revisions-delete' ),
					'none'       => __( 'No revision to delete.', 'simple-revisions-delete' ),
					'error'      => __( 'The revisions could not be deleted.', 'simple-revisions-delete' ),
					/* translators: %s: number of deleted revisions. */
					'purgedOne'  => __( '%s revision deleted.', 'simple-revisions-delete' ),
					/* translators: %s: number of deleted revisions. */
					'purgedMany' => __( '%s revisions deleted.', 'simple-revisions-delete' ),
					/* translators: %s: number of revisions. */
					'countOne'   => __( '%s revision', 'simple-revisions-delete' ),
					/* translators: %s: number of revisions. */
					'countMany'  => __( '%s revisions', 'simple-revisions-delete' ),
				),
			)
		);
	}
}
