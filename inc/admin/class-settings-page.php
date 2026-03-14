<?php
/**
 * AI Review プラグイン設定ページ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Review_Settings {

	const OPTION_GROUP   = 'ai_review_settings';
	const SETTINGS_PAGE  = 'ai-review-settings';
	const DEFAULT_SYSTEM_PROMPT = 'あなたは優秀な編集者です。指示に従って記事を修正してください。修正後の記事本文のみを返してください。';

	/**
	 * 初期化
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * 設定の登録
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
			'LLM設定',
			null,
			self::SETTINGS_PAGE
		);

		add_settings_field(
			'ai_review_provider',
			'LLMプロバイダ (API Base URL)',
			array( $this, 'render_provider_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_model',
			'モデル名',
			array( $this, 'render_model_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_api_key',
			'APIキー',
			array( $this, 'render_api_key_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);

		add_settings_field(
			'ai_review_system_prompt',
			'システムプロンプト',
			array( $this, 'render_system_prompt_field' ),
			self::SETTINGS_PAGE,
			'ai_review_main_section'
		);
	}

	/**
	 * 設定ページをメニューに追加
	 */
	public function add_settings_page() {
		add_options_page(
			'AI Review 設定',
			'AI Review',
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * 設定ページのHTML描画
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>AI Review 設定</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button( '変更を保存' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * プロバイダフィールドの描画
	 */
	public function render_provider_field() {
		$value = get_option( 'ai_review_provider', '' );
		?>
		<input type="text" name="ai_review_provider" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://api.openai.com/v1" required />
		<p class="description">LLMプロバイダのAPI Base URLを入力してください（例: https://api.openai.com/v1）</p>
		<?php
	}

	/**
	 * モデルフィールドの描画
	 */
	public function render_model_field() {
		$value = get_option( 'ai_review_model', '' );
		?>
		<input type="text" name="ai_review_model" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="gpt-4o-mini" required />
		<p class="description">使用するモデル名を入力してください（例: gpt-4o-mini）</p>
		<?php
	}

	/**
	 * APIキーフィールドの描画
	 */
	public function render_api_key_field() {
		$value = get_option( 'ai_review_api_key', '' );
		?>
		<input type="password" name="ai_review_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" required />
		<p class="description">LLMプロバイダのAPIキーを入力してください</p>
		<?php
	}

	/**
	 * システムプロンプトフィールドの描画
	 */
	public function render_system_prompt_field() {
		$value = get_option( 'ai_review_system_prompt', self::DEFAULT_SYSTEM_PROMPT );
		?>
		<textarea name="ai_review_system_prompt" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">AIに送信するシステムプロンプトを入力してください</p>
		<?php
	}

	/**
	 * 設定が完了しているかチェック
	 */
	public static function is_configured() {
		$provider = get_option( 'ai_review_provider', '' );
		$model    = get_option( 'ai_review_model', '' );
		$api_key  = get_option( 'ai_review_api_key', '' );

		return ! empty( $provider ) && ! empty( $model ) && ! empty( $api_key );
	}
}
