<?php
/**
 * AI Review REST API エンドポイント
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Review_REST_API {

	const NAMESPACE = 'ai-review/v1';

	/**
	 * 初期化
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * ルート登録
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/review',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_review_request' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'args'                => array(
					'post_content' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
					'prompt'       => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_settings_status' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
			)
		);
	}

	/**
	 * 投稿編集権限チェック
	 */
	public function check_edit_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * AI修正リクエストの処理
	 */
	public function handle_review_request( WP_REST_Request $request ) {
		if ( ! AI_Review_Settings::is_configured() ) {
			return new WP_Error(
				'settings_not_configured',
				'LLMの設定が完了していません。設定画面から設定を行ってください。',
				array( 'status' => 400 )
			);
		}

		$post_content = $request->get_param( 'post_content' );
		$prompt       = $request->get_param( 'prompt' );

		if ( empty( $prompt ) ) {
			return new WP_Error(
				'missing_parameter',
				'プロンプトを入力してください。',
				array( 'status' => 400 )
			);
		}

		$provider      = get_option( 'ai_review_provider', '' );
		$model         = get_option( 'ai_review_model', '' );
		$api_key       = get_option( 'ai_review_api_key', '' );
		$system_prompt = get_option( 'ai_review_system_prompt', AI_Review_Settings::DEFAULT_SYSTEM_PROMPT );

		if ( empty( $system_prompt ) ) {
			$system_prompt = AI_Review_Settings::DEFAULT_SYSTEM_PROMPT;
		}

		$user_message = "以下の記事を修正してください。\n\n【記事内容】\n" . $post_content . "\n\n【修正指示】\n" . $prompt;

		$api_url = rtrim( $provider, '/' ) . '/chat/completions';

		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'    => $model,
						'messages' => array(
							array(
								'role'    => 'system',
								'content' => $system_prompt,
							),
							array(
								'role'    => 'user',
								'content' => $user_message,
							),
						),
					)
				),
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'llm_api_error',
				'AIサービスとの通信に失敗しました。しばらく経ってから再度お試しください。',
				array( 'status' => 502 )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new WP_Error(
				'llm_api_error',
				'AIサービスからエラーが返されました（ステータス: ' . $status_code . '）。設定を確認してください。',
				array( 'status' => 502 )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error(
				'llm_api_error',
				'AIサービスから有効な応答が得られませんでした。',
				array( 'status' => 502 )
			);
		}

		$content = $body['choices'][0]['message']['content'];

		return rest_ensure_response(
			array(
				'success' => true,
				'content' => $content,
			)
		);
	}

	/**
	 * 設定状態の取得
	 */
	public function handle_settings_status( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'configured' => AI_Review_Settings::is_configured(),
			)
		);
	}
}
