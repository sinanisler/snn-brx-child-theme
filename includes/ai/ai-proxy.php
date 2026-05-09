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

	// Build header array for cURL ("Key: Value" strings).
	$curl_headers = [];
	foreach ( $request_headers as $k => $v ) {
		$curl_headers[] = $k . ': ' . $v;
	}

	// Non-streaming endpoints buffer the full response before sending, so cURL
	// can sit at 0 bytes/s for the entire generation window. Disable the
	// low-speed kill-switch and let the connect/transfer timeout govern instead.
	// set_time_limit(0) removes PHP's wall-clock cap for this request only so
	// a slow-but-working generation cannot be killed by the server's default limit.
	set_time_limit( 0 );

	$ch = curl_init();
	curl_setopt_array( $ch, [
		CURLOPT_URL             => $config['apiEndpoint'],
		CURLOPT_RETURNTRANSFER  => true,
		CURLOPT_POST            => true,
		CURLOPT_POSTFIELDS      => wp_json_encode( $body ),
		CURLOPT_HTTPHEADER      => $curl_headers,
		CURLOPT_CONNECTTIMEOUT  => 30,
		CURLOPT_TIMEOUT         => 180,
		CURLOPT_LOW_SPEED_LIMIT => 0,
		CURLOPT_LOW_SPEED_TIME  => 0,
		CURLOPT_SSL_VERIFYPEER  => true,
		CURLOPT_SSL_VERIFYHOST  => 2,
	] );

	$response_body = curl_exec( $ch );
	$status_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$curl_errno    = curl_errno( $ch );
	$curl_error    = curl_error( $ch );
	curl_close( $ch );

	if ( false === $response_body ) {
		http_response_code( 502 );
		wp_send_json_error( [ 'message' => 'Connection failed: cURL error ' . $curl_errno . ': ' . $curl_error ] );
		wp_die();
	}

	// 7. Transparent pass-through: relay the AI provider's exact status code and body.
	http_response_code( $status_code );
	header( 'Content-Type: application/json' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaOutput
	echo $response_body;
	wp_die();
}

// Registered only for logged-in users — no wp_ajax_nopriv_ hook intentionally.
add_action( 'wp_ajax_snn_ai_proxy', 'snn_ai_proxy_handler' );
