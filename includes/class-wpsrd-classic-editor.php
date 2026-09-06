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
			array( 'wp-api-fetch', 'wp-a11y' ),
			WPSRD_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wpsrdClassic',
			array(
				'postId' => $post->ID,
				'i18n'   => array(
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
			<?php wp_nonce_field( 'wpsrd_purge_' . $post->ID, 'wpsrd_nonce' ); ?>
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

		check_admin_referer( 'wpsrd_purge_' . $post_id, 'wpsrd_nonce' );

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
}
