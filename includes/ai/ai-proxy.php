<?php
/**
 * SNN AI Proxy
 *
 * File: ai-proxy.php
 *
 * Purpose: Server-side proxy for all AI API requests. This prevents API keys from ever being
 * sent to the browser, fixes HTTPS→HTTP mixed-content issues for localhost models (Ollama,
 * LM Studio), and ensures every AI call is authenticated as a logged-in WordPress user.
 *
 * Security model:
 * 1. wp_ajax_ hook (no nopriv) — non-logged-in requests are rejected by WordPress automatically.
 * 2. Nonce verification — short-lived token tied to the user session.
 * 3. Capability check — current_user_can('edit_posts'), matching the AI feature UI gates.
 * 4. API key stays in PHP — never sent to the browser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler for proxying AI API requests.
 * Registered only for logged-in users (wp_ajax_, no wp_ajax_nopriv_).
 */
function snn_ai_proxy_handler() {

	// AI generation can take a long time — extend PHP execution limit for this handler.
	@set_time_limit( 180 );

	// 1. Nonce verification.
	if ( ! check_ajax_referer( 'snn_ai_proxy_nonce', 'nonce', false ) ) {
		http_response_code( 403 );
		wp_send_json_error( [ 'message' => 'Security check failed.' ] );
		wp_die();
	}

	// 2. Capability check — same gate used by the AI feature UIs.
	if ( ! current_user_can( 'edit_posts' ) ) {
		http_response_code( 403 );
		wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
		wp_die();
	}

	// 3. Read config server-side — API key never touches the browser.
	if ( ! function_exists( 'snn_get_ai_api_config' ) ) {
		http_response_code( 500 );
		wp_send_json_error( [ 'message' => 'AI configuration unavailable.' ] );
		wp_die();
	}

	$config = snn_get_ai_api_config();

	// API key is optional — local models (Ollama, LM Studio) don't require one.
	// Only the endpoint is mandatory.
	if ( empty( $config['apiEndpoint'] ) ) {
		http_response_code( 500 );
		wp_send_json_error( [ 'message' => 'AI API endpoint not configured. Please check AI Settings.' ] );
		wp_die();
	}

	// 4. Parse request type and payload from POST body.
	$request_type = isset( $_POST['request_type'] ) ? sanitize_text_field( wp_unslash( $_POST['request_type'] ) ) : 'text';
	$payload_raw  = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '{}';
	$payload      = json_decode( $payload_raw, true );

	if ( ! is_array( $payload ) || ! isset( $payload['messages'] ) ) {
		http_response_code( 400 );
		wp_send_json_error( [ 'message' => 'Invalid request payload.' ] );
		wp_die();
	}

	// 5. Build request body — model, provider routing, and format are set server-side.
	if ( $request_type === 'image' ) {
		$body  = array_merge( $payload, [
			'model' => ! empty( $config['imageConfig']['image_model'] ) ? $config['imageConfig']['image_model'] : $config['model'],
		] );
		$image_provider = isset( $config['imageConfig']['image_model_provider'] ) ? $config['imageConfig']['image_model_provider'] : '';
		if ( ! empty( $image_provider ) ) {
			$body['provider'] = [
				'order'           => [ $image_provider ],
				'allow_fallbacks' => false,
			];
		}
	} else {
		$body = array_merge( $payload, [
			'model' => $config['model'],
		] );
		if ( ! empty( $config['modelProvider'] ) ) {
			$body['provider'] = [
				'order'           => [ $config['modelProvider'] ],
				'allow_fallbacks' => false,
			];
		}
		if ( ! empty( $config['responseFormat'] ) ) {
			$body['response_format'] = $config['responseFormat'];
		}
	}

	// 6. Forward request to AI provider from PHP (server-side).
	// Authorization header is omitted for local models that don't require a key.
	$request_headers = [ 'Content-Type' => 'application/json' ];
	if ( ! empty( $config['apiKey'] ) ) {
		$request_headers['Authorization'] = 'Bearer ' . $config['apiKey'];
	}

	// AI models can generate slowly — disable cURL's low-speed kill switch so a
	// response that trickles in below 1024 B/s is not aborted with error 28.
	$disable_speed_limit = static function ( $handle ) {
		curl_setopt( $handle, CURLOPT_LOW_SPEED_LIMIT, 0 );
		curl_setopt( $handle, CURLOPT_LOW_SPEED_TIME, 0 );
	};
	add_action( 'http_api_curl', $disable_speed_limit );

	$ai_response = wp_remote_post( $config['apiEndpoint'], [
		'timeout'     => 180,
		'headers'     => $request_headers,
		'body'        => wp_json_encode( $body ),
		'data_format' => 'body',
	] );

	remove_action( 'http_api_curl', $disable_speed_limit );

	if ( is_wp_error( $ai_response ) ) {
		http_response_code( 502 );
		wp_send_json_error( [ 'message' => 'Connection failed: ' . $ai_response->get_error_message() ] );
		wp_die();
	}

	// 7. Transparent pass-through: relay the AI provider's exact status code and body.
	//    This preserves the response structure (choices, error format, etc.) so existing
	//    JS parsing code works without modification, and status-based retry logic (429, 5xx)
	//    continues to function correctly.
	$status_code = (int) wp_remote_retrieve_response_code( $ai_response );
	$body_raw    = wp_remote_retrieve_body( $ai_response );

	http_response_code( $status_code );
	header( 'Content-Type: application/json' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $body_raw;
	wp_die();
}

// Registered only for logged-in users — no wp_ajax_nopriv_ hook intentionally.
add_action( 'wp_ajax_snn_ai_proxy', 'snn_ai_proxy_handler' );
