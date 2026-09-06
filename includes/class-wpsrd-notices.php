<?php
/**
 * Admin notices.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Displays the operation results and warns when revisions are disabled.
 */
class WPSRD_Notices {

	/**
	 * Option storing the dismissal of the "revisions are disabled" notice.
	 *
	 * @var string
	 */
	const DISMISS_OPTION = 'wpsrd_norev_dismissed';

	/**
	 * Registers the notice hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render_result_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_no_revision_notice' ) );
		add_action( 'admin_post_wpsrd_dismiss_norev', array( __CLASS__, 'dismiss_no_revision_notice' ) );
	}

	/**
	 * Resets the dismissed notice when the plugin is activated.
	 *
	 * @return void
	 */
	public static function on_activation() {
		delete_option( self::DISMISS_OPTION );
	}

	/**
	 * Displays the result of a purge triggered without JavaScript.
	 *
	 * @return void
	 */
	public static function render_result_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display of a redirect result.
		if ( ! isset( $_GET['wpsrd-purged'] ) && ! isset( $_GET['wpsrd-failed'] ) ) {
			return;
		}

		$purged  = isset( $_GET['wpsrd-purged'] ) ? absint( wp_unslash( $_GET['wpsrd-purged'] ) ) : 0;
		$skipped = isset( $_GET['wpsrd-skipped'] ) ? absint( wp_unslash( $_GET['wpsrd-skipped'] ) ) : 0;
		$failed  = isset( $_GET['wpsrd-failed'] ) ? absint( wp_unslash( $_GET['wpsrd-failed'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $purged > 0 ) {
			$message = sprintf(
				/* translators: %s: number of deleted revisions. */
				_n( '%s revision has been deleted.', '%s revisions have been deleted.', $purged, 'simple-revisions-delete' ),
				number_format_i18n( $purged )
			);
		} else {
			$message = __( 'No revision has been deleted.', 'simple-revisions-delete' );
		}

		if ( $skipped > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %s: number of skipped posts. */
				_n(
					'%s post was skipped because you are not allowed to delete it.',
					'%s posts were skipped because you are not allowed to delete them.',
					$skipped,
					'simple-revisions-delete'
				),
				number_format_i18n( $skipped )
			);
		}

		if ( $failed > 0 ) {
			$message .= ' ' . __( 'Some revisions could not be deleted.', 'simple-revisions-delete' );
		}

		$type = ( $purged > 0 && 0 === $failed ) ? 'success' : 'warning';

		wp_admin_notice(
			$message,
			array(
				'type'               => $type,
				'dismissible'        => true,
				'additional_classes' => array( 'wpsrd-notice' ),
			)
		);
	}

	/**
	 * Warns administrators when post revisions are disabled site wide.
	 *
	 * @return void
	 */
	public static function render_no_revision_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! defined( 'WP_POST_REVISIONS' ) || WP_POST_REVISIONS ) {
			return;
		}

		if ( get_option( self::DISMISS_OPTION ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=wpsrd_dismiss_norev' ),
			'wpsrd_dismiss_norev'
		);

		$message = sprintf(
			'%1$s <a href="%2$s">%3$s</a>',
			esc_html__( 'Post revisions are disabled on this site: the "Simple Revisions Delete" plugin has nothing to do here.', 'simple-revisions-delete' ),
			esc_url( $dismiss_url ),
			esc_html__( 'Dismiss this notice permanently', 'simple-revisions-delete' )
		);

		wp_admin_notice(
			$message,
			array(
				'type'               => 'warning',
				'paragraph_wrap'     => true,
				'additional_classes' => array( 'wpsrd-notice' ),
			)
		);
	}

	/**
	 * Stores the dismissal of the "revisions are disabled" notice.
	 *
	 * @return void
	 */
	public static function dismiss_no_revision_notice() {
		check_admin_referer( 'wpsrd_dismiss_norev' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to do this.', 'simple-revisions-delete' ),
				'',
				array( 'response' => 403 )
			);
		}

		update_option( self::DISMISS_OPTION, 1, false );

		$referer = wp_get_referer();

		wp_safe_redirect( $referer ? $referer : admin_url() );
		exit;
	}
}
