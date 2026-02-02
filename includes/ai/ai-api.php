<?php
/**
 * SNN AI API Helper 
 *
 * File: ai-api.php
 *
 * Purpose: This file acts as a centralized logic controller for preparing AI API configurations. It reads the
 * options saved from the admin settings page (managed by `ai-settings.php`) and packages them into a clean,
 * consistent array. This includes determining the correct API key, model name, and API endpoint based on the
 * user's selected provider (OpenAI, OpenRouter, or Custom). This abstraction prevents the frontend overlay
 * script from needing to contain complex PHP logic for handling different providers. The function in this file
 * is called by `ai-overlay.php` to securely pass the necessary credentials and configuration to the client-side
 * JavaScript.
 *
 * ---
 *
 * This file is part of a 3-file system:
 *
 * 1. ai-settings.php: Handles the backend WordPress admin settings UI and options saving.
 * - It saves the raw options like 'snn_ai_provider', 'snn_openai_api_key', etc., which this file reads.
 *
 * 2. ai-api.php (This file): A helper file that prepares the necessary configuration for making API calls.
 * - Key Functions: snn_get_ai_api_config().
 * - It processes the raw settings and returns a structured array of API credentials for the frontend.
 *
 * 3. ai-overlay.php: Manages the frontend user interface that appears inside the Bricks builder.
 * - It calls this file's `snn_get_ai_api_config()` function to get the configuration it needs to make
 * the `fetch` request to the correct AI provider.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves and prepares the AI configuration based on saved settings.
 *
 * This function reads the WordPress options for the AI provider, API keys, and models.
 * It then returns a structured array containing the final apiKey, model, apiEndpoint,
 * systemPrompt, actionPresets, responseFormat, and multimodal configuration settings
 * to be used by the frontend JavaScript.
 *
 * @return array An associative array containing the AI configuration.
 */
function snn_get_ai_api_config() {
    $ai_provider          = get_option('snn_ai_provider', 'openai');
    $openai_api_key       = get_option('snn_openai_api_key', '');
    $openai_model         = get_option('snn_openai_model', 'gpt-4.1-mini');
    $openrouter_api_key   = get_option('snn_openrouter_api_key', '');
    $openrouter_model     = get_option('snn_openrouter_model', '');
    $system_prompt        = get_option(
        'snn_system_prompt',
        'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Dont generate markdown. Only respond with the needed content and nothing else always!'
    );

    // Retrieve the desired response format type from settings
    $response_format_type = get_option('snn_ai_response_format_type', 'none'); // e.g., 'none', 'json_object'

    // Retrieve multimodal configuration settings
    $image_aspect_ratio = get_option('snn_ai_image_aspect_ratio', '1:1');
    $image_size         = get_option('snn_ai_image_size', '1K');

    $apiKey      = '';
    $model       = '';
    $apiEndpoint = '';

    if ($ai_provider === 'custom') {
        $apiKey      = get_option('snn_custom_api_key', '');
        $model       = get_option('snn_custom_model', '');
        $apiEndpoint = get_option('snn_custom_api_endpoint', '');
    } elseif ($ai_provider === 'openrouter') {
        $apiKey      = $openrouter_api_key;
        $model       = $openrouter_model;
        $apiEndpoint = 'https://openrouter.ai/api/v1/chat/completions';
    } else { // Default to 'openai'
        $apiKey      = $openai_api_key;
        $model       = $openai_model;
        $apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    }

    $action_presets = get_option('snn_ai_action_presets', []);
    if (!is_array($action_presets)) {
        $action_presets = [];
    }

    // Prepare the response format payload based on the setting
    $responseFormat = [];
    if ($response_format_type === 'json_object') {
        $responseFormat = ['type' => 'json_object'];
    }

    // Prepare image_config for image generation settings
    $imageConfig = [
        'aspect_ratio' => $image_aspect_ratio,
        'image_size'   => $image_size,
    ];

    // Build the configuration array
    $config = [
        'apiKey'          => $apiKey,
        'model'           => $model,
        'apiEndpoint'     => $apiEndpoint,
        'systemPrompt'    => $system_prompt,
        'actionPresets'   => array_values($action_presets),
        'responseFormat'  => $responseFormat,
        'imageConfig'     => $imageConfig,
    ];

    return $config;
}

/**
 * SECURITY FIX: Server-side AJAX proxy for AI API requests
 * This prevents API keys from being exposed to client-side JavaScript
 * 
 * Handles WordPress AJAX requests and proxies them to the AI API
 * with proper authentication and rate limiting
 */
add_action('wp_ajax_snn_ai_proxy_request', 'snn_ai_proxy_request_handler');
function snn_ai_proxy_request_handler() {
    // Verify nonce for security
    check_ajax_referer('snn_ai_proxy_nonce', 'nonce');
    
    // Verify user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized: Insufficient permissions', 403);
    }
    
    // Rate limiting: Check if user has exceeded request limit
    $user_id = get_current_user_id();
    $rate_limit_key = 'snn_ai_rate_limit_' . $user_id;
    $request_count = get_transient($rate_limit_key);
    
    if ($request_count === false) {
        set_transient($rate_limit_key, 1, HOUR_IN_SECONDS);
    } else if ($request_count >= 100) {  // 100 requests per hour limit
        wp_send_json_error('Rate limit exceeded. Please try again later.', 429);
    } else {
        set_transient($rate_limit_key, $request_count + 1, HOUR_IN_SECONDS);
    }
    
    // Get AI configuration (with API keys - kept server-side)
    $config = snn_get_ai_api_config();
    
    // Validate that we have necessary configuration
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        wp_send_json_error('API configuration is incomplete. Please check settings.');
    }
    
    // Get and validate messages from request
    $messages = isset($_POST['messages']) ? json_decode(stripslashes($_POST['messages']), true) : array();
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : $config['model'];
    
    if (empty($messages) || !is_array($messages)) {
        wp_send_json_error('Invalid messages format');
    }
    
    // Prepare request body
    $request_body = array(
        'model' => $model,
        'messages' => $messages
    );
    
    // Add response format if configured
    if (!empty($config['responseFormat'])) {
        $request_body['response_format'] = $config['responseFormat'];
    }
    
    // Make request to AI API using WordPress HTTP API
    $response = wp_remote_post($config['apiEndpoint'], array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $config['apiKey'],
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_body),
        'timeout' => 60,  // 60 second timeout for AI responses
        'sslverify' => true,  // Always verify SSL certificates
    ));
    
    // Handle errors
    if (is_wp_error($response)) {
        error_log('SNN AI Proxy Error: ' . $response->get_error_message());
        wp_send_json_error('AI API request failed: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    // Handle non-200 responses
    if ($response_code !== 200) {
        $error_data = json_decode($response_body, true);
        $error_message = 'AI API error';
        
        if (isset($error_data['error']['message'])) {
            $error_message = $error_data['error']['message'];
        }
        
        error_log(sprintf('SNN AI Proxy: API returned %d - %s', $response_code, $error_message));
        wp_send_json_error($error_message, $response_code);
    }
    
    // Parse and return successful response
    $ai_response = json_decode($response_body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('SNN AI Proxy: Invalid JSON response from API');
        wp_send_json_error('Invalid response from AI API');
    }
    
    // Log successful request (optional, for monitoring)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('SNN AI Proxy: Successful request by user %d, model: %s', $user_id, $model));
    }
    
    wp_send_json_success($ai_response);
}