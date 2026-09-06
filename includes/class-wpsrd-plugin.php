<?php
/**
 * Plugin bootstrap.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires every plugin module to WordPress.
 */
final class WPSRD_Plugin {

	/**
	 * Registers the plugin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		WPSRD_REST_Controller::init();
		WPSRD_Assets::init();
		WPSRD_Classic_Editor::init();
		WPSRD_Block_Editor::init();
		WPSRD_Bulk_Actions::init();
		WPSRD_Notices::init();
	}

	/**
	 * Loads the plugin translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'simple-revisions-delete', false, dirname( WPSRD_BASENAME ) . '/langs' );
	}

	/**
	 * Adds custom links to the plugin row on the plugins list screen.
	 *
	 * @param string[] $links Plugin row meta links.
	 * @param string   $file  Plugin basename of the current row.
	 * @return string[] Filtered plugin row meta links.
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( WPSRD_BASENAME !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://b-website.com/category/plugins' ),
			esc_html__( 'More b*web plugins', 'simple-revisions-delete' )
		);

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7Z6YVM63739Y8' ),
			esc_html__( 'Donate to this plugin', 'simple-revisions-delete' )
		);

		return $links;
	}
}
