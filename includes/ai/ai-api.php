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
        'You are a helpful assistant that helps with content creation or manipulation. You work inside wordpress. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Dont generate markdown. Only respond with the needed content and nothing else always!'
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