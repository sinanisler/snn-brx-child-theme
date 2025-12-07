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
 * NOW SUPPORTS: Multimodal features including text, images, and PDFs via vision-capable models.
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
 * systemPrompt, actionPresets, and responseFormat to be used by the frontend JavaScript.
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

    // NEW: Retrieve the desired response format type from settings
    // You would have added 'snn_ai_response_format_type' in ai-settings.php
    $response_format_type = get_option('snn_ai_response_format_type', 'none'); // e.g., 'none', 'json_object'

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
    // You could expand this for other structured formats if needed in the future,
    // e.g., 'json_schema' if you also store a schema definition.

    // Check if the selected model supports vision/multimodal
    $supports_vision = snn_model_supports_vision($model);
    $model_capabilities = snn_get_model_capabilities($model);

    return [
        'apiKey'          => $apiKey,
        'model'           => $model,
        'apiEndpoint'     => $apiEndpoint,
        'systemPrompt'    => $system_prompt,
        'actionPresets'   => array_values($action_presets),
        'responseFormat'  => $responseFormat,
        'supportsVision'  => $supports_vision,
        'capabilities'    => $model_capabilities,
        'maxImageSize'    => 20 * 1024 * 1024, // 20MB limit for OpenRouter
        'supportedTypes'  => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
    ];
}

/**
 * Check if a model supports vision/multimodal input.
 * 
 * @param string $model The model identifier.
 * @return bool True if the model supports vision.
 */
function snn_model_supports_vision($model) {
    if (empty($model)) {
        return false;
    }

    // Get the provider to determine which metadata to check
    $ai_provider = get_option('snn_ai_provider', 'openai');
    $metadata = [];

    if ($ai_provider === 'openrouter') {
        $stored_model = get_option('snn_openrouter_model', '');
        if ($stored_model === $model) {
            $metadata_json = get_option('snn_openrouter_model_metadata', '');
            if (!empty($metadata_json)) {
                $metadata = json_decode($metadata_json, true);
                if (!is_array($metadata)) {
                    $metadata = [];
                }
            }
        }
    } elseif ($ai_provider === 'openai') {
        $stored_model = get_option('snn_openai_model', '');
        if ($stored_model === $model) {
            $metadata_json = get_option('snn_openai_model_metadata', '');
            if (!empty($metadata_json)) {
                $metadata = json_decode($metadata_json, true);
                if (!is_array($metadata)) {
                    $metadata = [];
                }
            }
        }
    }

    // Check input_modalities for image support
    if (!empty($metadata['input_modalities'])) {
        $modalities = is_array($metadata['input_modalities']) 
            ? $metadata['input_modalities'] 
            : explode(',', $metadata['input_modalities']);
        
        foreach ($modalities as $modality) {
            $mod = trim($modality);
            if (in_array($mod, ['image', 'file', 'video'], true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Get detailed capabilities of a model based on its identifier.
 * 
 * @param string $model The model identifier.
 * @return array Array of capability labels.
 */
function snn_get_model_capabilities($model) {
    if (empty($model)) {
        return ['Text Generation'];
    }

    $capabilities = [];
    
    // Get the provider to determine which metadata to check
    $ai_provider = get_option('snn_ai_provider', 'openai');
    $metadata = [];

    if ($ai_provider === 'openrouter') {
        $stored_model = get_option('snn_openrouter_model', '');
        if ($stored_model === $model) {
            $metadata_json = get_option('snn_openrouter_model_metadata', '');
            if (!empty($metadata_json)) {
                $metadata = json_decode($metadata_json, true);
                if (!is_array($metadata)) {
                    $metadata = [];
                }
            }
        }
    } elseif ($ai_provider === 'openai') {
        $stored_model = get_option('snn_openai_model', '');
        if ($stored_model === $model) {
            $metadata_json = get_option('snn_openai_model_metadata', '');
            if (!empty($metadata_json)) {
                $metadata = json_decode($metadata_json, true);
                if (!is_array($metadata)) {
                    $metadata = [];
                }
            }
        }
    }

    // Parse input_modalities
    if (!empty($metadata['input_modalities'])) {
        $modalities = is_array($metadata['input_modalities']) 
            ? $metadata['input_modalities'] 
            : explode(',', $metadata['input_modalities']);
        
        $modalities = array_map('trim', $modalities);
        
        // Text is always available
        if (in_array('text', $modalities, true)) {
            $capabilities[] = 'Text Generation';
        }
        
        if (in_array('image', $modalities, true)) {
            $capabilities[] = 'Image Analysis';
        }
        
        if (in_array('file', $modalities, true)) {
            $capabilities[] = 'File Processing';
        }
        
        if (in_array('audio', $modalities, true)) {
            $capabilities[] = 'Audio Processing';
        }
        
        if (in_array('video', $modalities, true)) {
            $capabilities[] = 'Video Analysis';
        }
    } else {
        // Fallback if no metadata
        $capabilities[] = 'Text Generation';
    }

    // Check supported_parameters for additional capabilities
    if (!empty($metadata['supported_parameters'])) {
        $params = is_array($metadata['supported_parameters']) 
            ? $metadata['supported_parameters'] 
            : explode(',', $metadata['supported_parameters']);
        
        $params = array_map('trim', $params);
        
        if (in_array('tools', $params, true) || in_array('tool_choice', $params, true)) {
            $capabilities[] = 'Function Calling';
        }
        
        if (in_array('response_format', $params, true) || in_array('structured_outputs', $params, true)) {
            $capabilities[] = 'Structured Output';
        }
        
        if (in_array('reasoning', $params, true)) {
            $capabilities[] = 'Advanced Reasoning';
        }
    }

    // Context length
    if (!empty($metadata['context_length'])) {
        $context = intval($metadata['context_length']);
        if ($context >= 1000000) {
            $capabilities[] = 'Extended Context (' . number_format($context / 1000000, 1) . 'M tokens)';
        } elseif ($context >= 100000) {
            $capabilities[] = 'Long Context (' . number_format($context / 1000, 0) . 'K tokens)';
        }
    }

    return !empty($capabilities) ? $capabilities : ['Text Generation'];
}

/**
 * AJAX handler to get model capabilities for the settings page.
 */
function snn_ajax_get_model_capabilities() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    
    if (empty($model)) {
        wp_send_json_error('No model specified');
        return;
    }

    $capabilities = snn_get_model_capabilities($model);
    wp_send_json_success($capabilities);
}
add_action('wp_ajax_snn_get_model_capabilities', 'snn_ajax_get_model_capabilities');