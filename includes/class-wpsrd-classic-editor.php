<?php
/**
 * Classic editor integration.
 *
 * @package Simple_Revisions_Delete
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the purge control to the classic editor submit box, the delete buttons
 * to the revisions metabox, and handles the no-JavaScript fallback.
 */
class WPSRD_Classic_Editor {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'wpsrd-classic-editor';

	/**
	 * ID of the fallback form printed in the admin footer.
	 *
	 * @var string
	 */
	const FORM_ID = 'wpsrd-purge-form';

	/**
	 * Registers the classic editor hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_purge_control' ) );
		add_action( 'admin_footer', array( __CLASS__, 'render_fallback_form' ) );
		add_action( 'admin_post_wpsrd_purge_revisions', array( __CLASS__, 'handle_form_submission' ) );
		add_action( 'wp_ajax_wpsrd_purge_revisions', array( __CLASS__, 'handle_ajax_purge' ) );
		add_action( 'wp_ajax_wpsrd_delete_revision', array( __CLASS__, 'handle_ajax_delete' ) );
	}

	/**
	 * Returns the nonce action protecting the revisions of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Nonce action.
	 */
	private static function nonce_action( $post_id ) {
		return 'wpsrd_purge_' . absint( $post_id );
	}

	/**
	 * Tells whether the current screen is the classic editor of a post whose
	 * revisions the current user may purge.
	 *
	 * @return WP_Post|null The edited post, or null when the control must not be rendered.
	 */
	private static function get_target_post() {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base || $screen->is_block_editor() ) {
			return null;
		}

		$post = get_post();

		if ( ! $post || ! WPSRD_Revisions::user_can_purge( $post->ID ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Enqueues the classic editor script.
	 *
	 * @return void
	 */
	public static function enqueue_script() {
		$post = self::get_target_post();

		if ( ! $post ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WPSRD_URL . 'assets/js/wpsrd-classic-editor.js',
			array( 'wp-a11y' ),
			WPSRD_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wpsrdClassic',
			array(
				'postId'  => $post->ID,
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::nonce_action( $post->ID ) ),
				'i18n'    => array(
					'purging'      => __( 'Purging…', 'simple-revisions-delete' ),
					'purged'       => __( 'Purged', 'simple-revisions-delete' ),
					'deleting'     => __( 'Deleting…', 'simple-revisions-delete' ),
					'deleted'      => __( 'Deleted', 'simple-revisions-delete' ),
					'error'        => __( 'Something went wrong, please reload the page.', 'simple-revisions-delete' ),
					'deleteButton' => __( 'Delete', 'simple-revisions-delete' ),
					/* translators: %s: revision label, for instance its date. */
					'deleteLabel'  => __( 'Delete the revision %s', 'simple-revisions-delete' ),
				),
			)
		);
	}

	/**
	 * Renders the purge control inside the publish metabox.
	 *
	 * The control is hidden by default: the script moves it next to the core
	 * revisions counter and reveals it. Without JavaScript it is revealed in
	 * place and submits the fallback form printed in the admin footer.
	 *
	 * @return void
	 */
	public static function render_purge_control() {
		$post = self::get_target_post();

		if ( ! $post || ! WPSRD_Revisions::count( $post->ID ) ) {
			return;
		}
		?>
		<span id="wpsrd-purge" class="wpsrd-purge" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>" hidden>
			<button type="submit" form="<?php echo esc_attr( self::FORM_ID ); ?>" class="button-link wpsrd-purge__button">
				<?php esc_html_e( 'Purge', 'simple-revisions-delete' ); ?>
			</button>
			<span class="spinner wpsrd-purge__spinner" aria-hidden="true"></span>
			<span class="wpsrd-purge__status" role="status"></span>
		</span>
		<noscript><style>#wpsrd-purge{display:inline!important;}</style></noscript>
		<?php
	}

	/**
	 * Prints the purge form outside of the post form.
	 *
	 * A form cannot be nested inside the post form, so it is printed in the
	 * footer and referenced by the button through its "form" attribute.
	 *
	 * @return void
	 */
	public static function render_fallback_form() {
		$post = self::get_target_post();

		if ( ! $post || ! WPSRD_Revisions::count( $post->ID ) ) {
			return;
		}
		?>
		<form id="<?php echo esc_attr( self::FORM_ID ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpsrd-purge__form">
			<?php wp_nonce_field( self::nonce_action( $post->ID ), 'wpsrd_nonce' ); ?>
			<input type="hidden" name="action" value="wpsrd_purge_revisions" />
			<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post->ID ); ?>" />
		</form>
		<?php
	}

	/**
	 * Handles the no-JavaScript purge form submission.
	 *
	 * @return void
	 */
	public static function handle_form_submission() {
		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		check_admin_referer( self::nonce_action( $post_id ), 'wpsrd_nonce' );

		if ( ! WPSRD_Revisions::user_can_purge( $post_id ) ) {
			wp_die(
				esc_html__( 'You are not allowed to delete the revisions of this post.', 'simple-revisions-delete' ),
				'',
				array( 'response' => 403 )
			);
		}

		$deleted  = WPSRD_Revisions::purge( $post_id );
		$redirect = get_edit_post_link( $post_id, 'raw' );

		if ( ! $redirect ) {
			$redirect = admin_url( 'edit.php' );
		}

		if ( is_wp_error( $deleted ) ) {
			$redirect = add_query_arg( 'wpsrd-failed', 1, $redirect );
		} else {
			$redirect = add_query_arg( 'wpsrd-purged', $deleted, $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Validates an AJAX request and returns the targeted post ID.
	 *
	 * Sends a JSON error and stops when the nonce or the capability check fails.
	 *
	 * @return int Post ID.
	 */
	private static function get_ajax_post_id() {
		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! check_ajax_referer( self::nonce_action( $post_id ), 'wpsrd_nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Something went wrong, please reload the page.', 'simple-revisions-delete' ) ),
				403
			);
		}

		if ( ! WPSRD_Revisions::user_can_purge( $post_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to delete the revisions of this post.', 'simple-revisions-delete' ) ),
				403
			);
		}

		return $post_id;
	}

	/**
	 * Sends the outcome of a deletion as a JSON response.
	 *
	 * @param int          $post_id Post ID.
	 * @param int|WP_Error $deleted Number of deleted revisions, or error object.
	 * @return void
	 */
	private static function send_ajax_result( $post_id, $deleted ) {
		if ( is_wp_error( $deleted ) ) {
			$data = $deleted->get_error_data();

			wp_send_json_error(
				array( 'message' => $deleted->get_error_message() ),
				isset( $data['status'] ) ? (int) $data['status'] : 500
			);
		}

		wp_send_json_success(
			array(
				'count'   => WPSRD_Revisions::count( $post_id ),
				'deleted' => (int) $deleted,
			)
		);
	}

	/**
	 * Purges every revision of a post through admin-ajax.
	 *
	 * @return void
	 */
	public static function handle_ajax_purge() {
		$post_id = self::get_ajax_post_id();

		self::send_ajax_result( $post_id, WPSRD_Revisions::purge( $post_id ) );
	}

	/**
	 * Deletes a single revision through admin-ajax.
	 *
	 * @return void
	 */
	public static function handle_ajax_delete() {
		$post_id     = self::get_ajax_post_id();
		$revision_id = isset( $_POST['revision_id'] ) ? absint( wp_unslash( $_POST['revision_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in get_ajax_post_id().
		$result      = WPSRD_Revisions::delete_one( $revision_id, $post_id );

		self::send_ajax_result( $post_id, is_wp_error( $result ) ? $result : 1 );
	}
}
