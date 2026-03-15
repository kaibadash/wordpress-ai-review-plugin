<?php
/**
 * Plugin Name: AI Review
 * Plugin URI: https://github.com/kaibadash/wordpress-ai-review-plugin
 * Description: A plugin that uses AI to revise your posts. Enter a prompt in the post editor sidebar to refine your content.
 * Version: 1.0.0
 * Author: kaibadash
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-review
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_REVIEW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_REVIEW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_REVIEW_VERSION', '1.0.0' );

require_once AI_REVIEW_PLUGIN_DIR . 'inc/admin/class-settings-page.php';
require_once AI_REVIEW_PLUGIN_DIR . 'inc/rest/class-api-endpoint.php';

/**
 * プラグイン初期化
 */
function ai_review_init() {
	$settings = new AI_Review_Settings();
	$settings->init();

	$api = new AI_Review_REST_API();
	$api->init();
}
add_action( 'plugins_loaded', 'ai_review_init' );

/**
 * Load text domain for translations.
 */
function ai_review_load_textdomain() {
	load_plugin_textdomain( 'ai-review', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'ai_review_load_textdomain' );

/**
 * Enqueue editor scripts.
 */
function ai_review_enqueue_editor_assets() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$asset_file = AI_REVIEW_PLUGIN_DIR . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'ai-review-editor',
		AI_REVIEW_PLUGIN_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_localize_script(
		'ai-review-editor',
		'aiReviewData',
		array(
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'rest_url' => rest_url( 'ai-review/v1/' ),
		)
	);

	wp_set_script_translations( 'ai-review-editor', 'ai-review', AI_REVIEW_PLUGIN_DIR . 'languages' );

	if ( file_exists( AI_REVIEW_PLUGIN_DIR . 'build/index.css' ) ) {
		wp_enqueue_style(
			'ai-review-editor-style',
			AI_REVIEW_PLUGIN_URL . 'build/index.css',
			array(),
			$asset['version']
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'ai_review_enqueue_editor_assets' );
