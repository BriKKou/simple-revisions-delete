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
	 * WordPress only reads the bundled files when no language pack exists, so
	 * strings missing from an outdated pack would stay in English. Both are
	 * loaded: the first file holding a string wins, so the language pack keeps
	 * precedence and the bundled file fills the gaps.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		$domain = 'simple-revisions-delete';
		$locale = determine_locale();
		$mofile = $domain . '-' . $locale . '.mo';

		load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile, $locale );
		load_textdomain( $domain, WPSRD_PATH . 'langs/' . $mofile, $locale );
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
