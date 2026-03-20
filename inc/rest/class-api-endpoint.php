<?php
/**
 * AI Review REST API Endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Review_REST_API {

	const NAMESPACE = 'ai-review/v1';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$review_args = array(
			'post_title'   => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'post_content' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
			),
			'prompt'       => array(
				'type'              => 'string',
				'required'          => false,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);

		register_rest_route(
			self::NAMESPACE,
			'/review',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_review_request' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'args'                => $review_args,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/review-stream',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_review_stream_request' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'args'                => $review_args,
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
	 * Check edit permission.
	 */
	public function check_edit_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Prepare common parameters for review requests.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array|WP_Error Array with api_url, api_key, model, and request_body on success.
	 */
	protected function prepare_review_params( WP_REST_Request $request ) {
		if ( ! AI_Review_Settings::is_configured() ) {
			return new WP_Error(
				'settings_not_configured',
				__( 'LLM settings are not configured. Please configure them in the settings page.', 'ai-review' ),
				array( 'status' => 400 )
			);
		}

		$post_title   = $request->get_param( 'post_title' );
		$post_content = $request->get_param( 'post_content' );
		$prompt       = $request->get_param( 'prompt' );

		$provider      = get_option( 'ai_review_provider', '' );
		$model         = get_option( 'ai_review_model', '' );
		$api_key       = get_option( 'ai_review_api_key', '' );
		$system_prompt = get_option( 'ai_review_system_prompt', AI_Review_Settings::DEFAULT_SYSTEM_PROMPT );

		if ( empty( $system_prompt ) ) {
			$system_prompt = AI_Review_Settings::DEFAULT_SYSTEM_PROMPT;
		}

		if ( ! empty( $prompt ) ) {
			/* translators: %1$s: post title, %2$s: post content, %3$s: user prompt */
			$user_message = sprintf(
				__( "Please revise the following article.\n\n[Title]\n%1\$s\n\n[Article]\n%2\$s\n\n[Instructions]\n%3\$s", 'ai-review' ),
				$post_title,
				$post_content,
				$prompt
			);
		} else {
			/* translators: %1$s: post title, %2$s: post content */
			$user_message = sprintf(
				__( "Please revise the following article.\n\n[Title]\n%1\$s\n\n[Article]\n%2\$s", 'ai-review' ),
				$post_title,
				$post_content
			);
		}

		$request_body = array(
			'model'           => $model,
			'messages'        => array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
				array(
					'role'    => 'user',
					'content' => $user_message,
				),
			),
			'response_format' => array(
				'type'        => 'json_schema',
				'json_schema' => array(
					'name'   => 'article_review',
					'strict' => true,
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'title'   => array(
								'type'        => 'string',
								'description' => 'The revised article title.',
							),
							'body'    => array(
								'type'        => 'string',
								'description' => 'The revised article body. Must preserve all WordPress block markup, HTML tags, and shortcodes.',
							),
							'changes' => array(
								'type'        => 'string',
								'description' => 'A summary of the changes made to the article.',
							),
						),
						'required'             => array( 'title', 'body', 'changes' ),
						'additionalProperties' => false,
					),
				),
			),
		);

		return array(
			'api_url'      => rtrim( $provider, '/' ) . '/chat/completions',
			'api_key'      => $api_key,
			'model'        => $model,
			'request_body' => $request_body,
		);
	}

	/**
	 * Handle AI review request (non-streaming).
	 */
	public function handle_review_request( WP_REST_Request $request ) {
		$params = $this->prepare_review_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$api_url = $params['api_url'];
		$model   = $params['model'];

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $params['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $params['request_body'] ),
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'llm_connection_error',
				__( 'Failed to communicate with the AI service.', 'ai-review' ),
				array(
					'status' => 502,
					'detail' => $response->get_error_message(),
					'url'    => $api_url,
				)
			);
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $status_code !== 200 ) {
			$error_detail = $response_body;
			$decoded      = json_decode( $response_body, true );
			if ( isset( $decoded['error']['message'] ) ) {
				$error_detail = $decoded['error']['message'];
			}

			return new WP_Error(
				'llm_api_error',
				/* translators: %d: HTTP status code */
				sprintf( __( 'The AI service returned an error (status: %d).', 'ai-review' ), $status_code ),
				array(
					'status' => 502,
					'detail' => $error_detail,
					'url'    => $api_url,
					'model'  => $model,
				)
			);
		}

		$body = json_decode( $response_body, true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error(
				'llm_empty_response',
				__( 'No valid response was received from the AI service.', 'ai-review' ),
				array(
					'status' => 502,
					'detail' => wp_json_encode( $body ),
				)
			);
		}

		$result = json_decode( $body['choices'][0]['message']['content'], true );

		if ( ! is_array( $result ) || ! isset( $result['title'], $result['body'], $result['changes'] ) ) {
			return new WP_Error(
				'llm_invalid_format',
				__( 'The AI service returned an unexpected response format.', 'ai-review' ),
				array(
					'status' => 502,
					'detail' => $body['choices'][0]['message']['content'],
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'title'   => $result['title'],
				'content' => $result['body'],
				'changes' => $result['changes'],
			)
		);
	}

	/**
	 * Handle AI review request (SSE streaming).
	 *
	 * Streams LLM API SSE chunks directly to the browser to avoid
	 * Cloudflare and other reverse proxy timeouts.
	 */
	public function handle_review_stream_request( WP_REST_Request $request ) {
		$params = $this->prepare_review_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		$params['request_body']['stream'] = true;

		// Send SSE headers before streaming.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );

		// Disable all output buffering.
		while ( ob_get_level() ) {
			ob_end_flush();
		}

		$api_url = $params['api_url'];
		$model   = $params['model'];

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
		$ch = curl_init( $api_url );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_POST           => true,
				CURLOPT_HTTPHEADER     => array(
					'Authorization: Bearer ' . $params['api_key'],
					'Content-Type: application/json',
				),
				CURLOPT_POSTFIELDS     => wp_json_encode( $params['request_body'] ),
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_TIMEOUT        => 300,
				CURLOPT_WRITEFUNCTION  => function ( $ch, $data ) {
					echo $data;
					flush();
					return strlen( $data );
				},
			)
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
		$success = curl_exec( $ch );

		if ( ! $success ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
			$error_msg = curl_error( $ch );
			echo "data: " . wp_json_encode(
				array(
					'error'   => true,
					'message' => __( 'Failed to communicate with the AI service.', 'ai-review' ),
					'detail'  => $error_msg,
					'url'     => $api_url,
					'model'   => $model,
				)
			) . "\n\n";
			flush();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
		curl_close( $ch );

		if ( $success && $http_code !== 200 ) {
			echo "data: " . wp_json_encode(
				array(
					'error'   => true,
					'message' => sprintf(
						/* translators: %d: HTTP status code */
						__( 'The AI service returned an error (status: %d).', 'ai-review' ),
						$http_code
					),
					'url'     => $api_url,
					'model'   => $model,
				)
			) . "\n\n";
			flush();
		}

		exit;
	}

	/**
	 * Get settings status.
	 */
	public function handle_settings_status( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'configured' => AI_Review_Settings::is_configured(),
			)
		);
	}
}
