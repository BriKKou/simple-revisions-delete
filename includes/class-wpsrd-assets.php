<?php
/**
 * Shared admin styles.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and enqueues the stylesheet shared by both editors.
 */
class WPSRD_Assets {

	/**
	 * Stylesheet handle.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'wpsrd-admin';

	/**
	 * Registers the asset hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_on_editor' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Registers the stylesheet.
	 *
	 * @return void
	 */
	public static function register() {
		wp_register_style(
			self::STYLE_HANDLE,
			WPSRD_URL . 'assets/css/wpsrd-admin.css',
			array( 'dashicons' ),
			WPSRD_VERSION
		);
	}

	/**
	 * Enqueues the stylesheet on the post edit screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_on_editor( $hook_suffix ) {
		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the stylesheet.
	 *
	 * @return void
	 */
	public static function enqueue() {
		wp_enqueue_style( self::STYLE_HANDLE );
	}
}
