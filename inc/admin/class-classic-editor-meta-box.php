<?php
/**
 * AI Review Classic Editor Meta Box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Review_Classic_Editor {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Check if the current post uses the Classic Editor.
	 */
	private function is_classic_editor() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		if ( function_exists( 'use_block_editor_for_post' ) ) {
			return ! use_block_editor_for_post( $post );
		}
		return false;
	}

	/**
	 * Add meta box for Classic Editor.
	 */
	public function add_meta_box() {
		if ( ! $this->is_classic_editor() ) {
			return;
		}

		$post_types = get_post_types( array( 'show_ui' => true ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ai-review-meta-box',
				__( 'AI Review', 'ai-review' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box HTML.
	 */
	public function render_meta_box( $post ) {
		?>
		<div id="ai-review-classic">
			<div class="notice notice-warning inline" style="margin: 0 0 12px;">
				<p><?php esc_html_e( 'AI revisions will overwrite the current post content.', 'ai-review' ); ?></p>
			</div>

			<div id="ai-review-not-configured" style="display:none;">
				<div class="notice notice-warning inline" style="margin: 0;">
					<p><?php esc_html_e( 'Please complete the LLM settings. Go to Settings → AI Review.', 'ai-review' ); ?></p>
				</div>
			</div>

			<div id="ai-review-form">
				<p>
					<label for="ai-review-prompt"><strong><?php esc_html_e( 'Instructions (optional)', 'ai-review' ); ?></strong></label>
					<textarea id="ai-review-prompt" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Make the tone more casual', 'ai-review' ); ?>"></textarea>
					<span class="description"><?php esc_html_e( 'Optionally enter instructions for the AI. If left empty, the system prompt will be used as-is.', 'ai-review' ); ?></span>
				</p>

				<div id="ai-review-error" style="display:none;" class="notice notice-error inline">
					<p></p>
				</div>

				<p>
					<button type="button" id="ai-review-execute" class="button button-primary">
						<?php esc_html_e( 'Execute', 'ai-review' ); ?>
					</button>
					<span id="ai-review-spinner" class="spinner" style="float:none;"></span>
				</p>

				<div id="ai-review-changes" style="display:none;" class="notice notice-success inline">
					<p><strong><?php esc_html_e( 'Changes', 'ai-review' ); ?></strong></p>
					<p id="ai-review-changes-text" style="white-space:pre-wrap;"></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue Classic Editor assets.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		if ( ! $this->is_classic_editor() ) {
			return;
		}

		wp_enqueue_script(
			'ai-review-classic-editor',
			AI_REVIEW_PLUGIN_URL . 'assets/js/classic-editor.js',
			array( 'jquery' ),
			AI_REVIEW_VERSION,
			true
		);

		wp_localize_script(
			'ai-review-classic-editor',
			'aiReviewClassic',
			array(
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'rest_url' => rest_url( 'ai-review/v1/' ),
				'i18n'     => array(
					'execute'    => __( 'Execute', 'ai-review' ),
					'processing' => __( 'Processing...', 'ai-review' ),
					'error'      => __( 'An error occurred.', 'ai-review' ),
				),
			)
		);
	}
}
