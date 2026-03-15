<?php
/**
 * AI Review Settings Page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Review_Settings {

	const OPTION_GROUP   = 'ai_review_settings';
	const SETTINGS_PAGE  = 'ai-review-settings';
	const DEFAULT_SYSTEM_PROMPT = 'You are an excellent editor. Please correct typos and unnatural expressions. Return only the article body. Do not modify or remove any WordPress block markup, HTML tags, or shortcodes.';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'ai_review_provider',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'ai_review_model',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'ai_review_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'ai_review_system_prompt',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => self::DEFAULT_SYSTEM_PROMPT,
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'ai_review_main_section',
			__( 'LLM Settings', 'ai-review' ),
			null,
			self::SETTINGS_PAGE
		);

		add_settings_field(
			'ai_review_provider',
			__( 'LLM Provider (API Base URL)', 'ai-review' ),
			array( $this, 'render_provider_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_model',
			__( 'Model Name', 'ai-review' ),
			array( $this, 'render_model_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_api_key',
			__( 'API Key', 'ai-review' ),
			array( $this, 'render_api_key_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_system_prompt',
			__( 'System Prompt', 'ai-review' ),
			array( $this, 'render_system_prompt_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);
	}

	/**
	 * Add settings page to menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'AI Review Settings', 'ai-review' ),
			'AI Review',
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'AI Review Settings', 'ai-review' ) ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button( __( 'Save Changes', 'ai-review' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render provider field.
	 */
	public function render_provider_field() {
		$value = get_option( 'ai_review_provider', '' );
		?>
		<input type="text" name="ai_review_provider" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://api.openai.com/v1" required />
		<p class="description"><?php echo esc_html( __( 'Enter the API Base URL of your LLM provider (e.g. https://api.openai.com/v1)', 'ai-review' ) ); ?></p>
		<?php
	}

	/**
	 * Render model field.
	 */
	public function render_model_field() {
		$value = get_option( 'ai_review_model', '' );
		?>
		<input type="text" name="ai_review_model" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="gpt-4o-mini" required />
		<p class="description"><?php echo esc_html( __( 'Enter the model name to use (e.g. gpt-4o-mini)', 'ai-review' ) ); ?></p>
		<?php
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$value = get_option( 'ai_review_api_key', '' );
		?>
		<input type="password" name="ai_review_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" required />
		<p class="description"><?php echo esc_html( __( 'Enter the API key for your LLM provider', 'ai-review' ) ); ?></p>
		<?php
	}

	/**
	 * Render system prompt field.
	 */
	public function render_system_prompt_field() {
		$value = get_option( 'ai_review_system_prompt', self::DEFAULT_SYSTEM_PROMPT );
		?>
		<textarea name="ai_review_system_prompt" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( __( 'Enter the system prompt to send to the AI', 'ai-review' ) ); ?></p>
		<?php
	}

	/**
	 * Check if settings are configured.
	 */
	public static function is_configured() {
		$provider = get_option( 'ai_review_provider', '' );
		$model    = get_option( 'ai_review_model', '' );
		$api_key  = get_option( 'ai_review_api_key', '' );

		return ! empty( $provider ) && ! empty( $model ) && ! empty( $api_key );
	}
}
