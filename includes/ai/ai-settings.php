<?php
/**
 * SNN AI Settings
 * 
 * File: ai-settings.php
 *
 * Purpose: This file manages the administrative settings for the AI features. It is responsible for creating the
 * "AI Settings" submenu within the WordPress admin dashboard, registering all necessary settings with the
 * WordPress Settings API, and rendering the settings page itself. This includes the HTML form for all options
 * like API keys, provider selection, model names, and the repeater field for custom action prompts. It also
 * contains the client-side JavaScript necessary for the settings page to function, such as toggling setting
 * visibility based on the selected provider and handling the dynamic "Action Prompts" repeater.
 *
 * ---
 *
 * This file is part of a 3-file system:
 *
 * 1. ai-settings.php (This file): Handles the backend WordPress admin settings UI and options saving.
 * - Key Functions: snn_add_ai_settings_submenu(), snn_register_ai_settings(), snn_render_ai_settings().
 * - It provides the user-configured values that the other files will use.
 *
 * 2. ai-api.php: A helper file that prepares the necessary configuration for making API calls.
 * - Key Functions: snn_get_ai_api_config().
 * - It reads the options saved by this settings file (e.g., 'snn_ai_provider', 'snn_openai_api_key') and
 * determines the correct API endpoint, key, and model to be used by the frontend overlay. This abstracts
 * the logic away from the overlay itself.
 *
 * 3. ai-overlay.php: Manages the frontend user interface that appears inside the Bricks builder.
 * - Key Functions: snn_add_ai_script_to_footer().
 * - It injects the "AI" button into builder controls, displays the AI assistant modal (both single and bulk),
 * and contains the primary client-side JavaScript for interacting with the AI. It makes the final `fetch`
 * request to the AI provider using the configuration prepared by `ai-api.php`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function snn_add_ai_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __('AI Settings', 'snn'),
        __('AI Settings', 'snn'),
        'manage_options',
        'snn-ai-settings',
        'snn_render_ai_settings'
    );
}
add_action('admin_menu', 'snn_add_ai_settings_submenu');

function snn_register_ai_settings() {
    register_setting('snn_ai_settings_group', 'snn_ai_enabled');
    register_setting('snn_ai_settings_group', 'snn_ai_provider', [
        'default' => 'openai',
    ]);
    register_setting('snn_ai_settings_group', 'snn_openrouter_api_key');
    register_setting('snn_ai_settings_group', 'snn_openrouter_model');
    register_setting('snn_ai_settings_group', 'snn_openrouter_model_provider');
    register_setting('snn_ai_settings_group', 'snn_openrouter_image_model');
    register_setting('snn_ai_settings_group', 'snn_openrouter_image_model_provider');
    register_setting('snn_ai_settings_group', 'snn_system_prompt');
    register_setting('snn_ai_settings_group', 'snn_ai_action_presets', [
        'type' => 'array',
        'default' => [],
    ]);

    // 1. Register new settings for custom provider
    register_setting('snn_ai_settings_group', 'snn_custom_api_key');
    register_setting('snn_ai_settings_group', 'snn_custom_api_endpoint');
    register_setting('snn_ai_settings_group', 'snn_custom_model');

    // 2. Register multimodal configuration settings
    register_setting('snn_ai_settings_group', 'snn_ai_image_aspect_ratio', [
        'type' => 'string',
        'default' => '16:9',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_image_size', [
        'type' => 'string',
        'default' => '1K',
    ]);

    // 3. Register generation parameter settings (shared across all providers)
    register_setting('snn_ai_settings_group', 'snn_ai_temperature', [
        'type' => 'string',
        'default' => '0.7',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_max_tokens', [
        'type' => 'string',
        'default' => '4000',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_top_p', [
        'type' => 'string',
        'default' => '1',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_frequency_penalty', [
        'type' => 'string',
        'default' => '0',
    ]);
    register_setting('snn_ai_settings_group', 'snn_ai_presence_penalty', [
        'type' => 'string',
        'default' => '0',
    ]);
}
add_action('admin_init', 'snn_register_ai_settings');

/**
 * AJAX handler for testing AI connection.
 * Works for both OpenRouter and Custom providers.
 * Sends a minimal message and reports every step with colour-coded logs.
 */
add_action('wp_ajax_snn_ai_test_connection', 'snn_ai_test_connection_handler');
function snn_ai_test_connection_handler() {
    check_ajax_referer('snn_ai_test_connection_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json(array('success' => false, 'logs' => array(
            array('type' => 'error', 'message' => 'Unauthorized.')
        )));
    }

    $logs = array();

    // Read settings
    $ai_enabled  = get_option('snn_ai_enabled', 'no');
    $ai_provider = get_option('snn_ai_provider', 'openrouter');

    if ($ai_enabled !== 'yes') {
        $logs[] = array('type' => 'error', 'message' => 'AI Features are currently DISABLED. Enable them first.');
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => array()));
    }

    $logs[] = array('type' => 'info', 'message' => 'AI Features: ENABLED');
    $logs[] = array('type' => 'info', 'message' => 'Provider: ' . strtoupper($ai_provider));

    // Get config via the central helper
    if (!function_exists('snn_get_ai_api_config')) {
        $logs[] = array('type' => 'error', 'message' => 'AI configuration helper not found.');
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => array()));
    }

    $config = snn_get_ai_api_config();

    // Build settings summary
    $settings = array(
        'Provider'      => strtoupper($ai_provider),
        'Endpoint'      => $config['apiEndpoint'] ?: '(not set)',
        'Model'         => $config['model'] ?: '(not set)',
        'API Key'       => !empty($config['apiKey']) ? '(set — ' . substr($config['apiKey'], 0, 8) . '...)' : '(not set)',
        'Temperature'   => $config['temperature'],
        'Max Tokens'    => $config['maxTokens'],
    );

    if ($ai_provider === 'openrouter' && !empty($config['modelProvider'])) {
        $settings['Model Provider'] = $config['modelProvider'];
    }

    // Validate endpoint
    if (empty($config['apiEndpoint'])) {
        $logs[] = array('type' => 'error', 'message' => 'No API endpoint configured. Please set one in the settings above.');
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings));
    }

    $logs[] = array('type' => 'info', 'message' => 'Endpoint: ' . $config['apiEndpoint']);

    // Validate model
    if (empty($config['model'])) {
        $logs[] = array('type' => 'error', 'message' => 'No model selected. Please choose a model in the settings above.');
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings));
    }

    $logs[] = array('type' => 'info', 'message' => 'Model: ' . $config['model']);

    // Check API key (optional for local models)
    if (empty($config['apiKey'])) {
        $logs[] = array('type' => 'warning', 'message' => 'No API key set. This is fine for local models (Ollama, LM Studio) but required for OpenRouter.');
    } else {
        $logs[] = array('type' => 'info', 'message' => 'API key is set (' . substr($config['apiKey'], 0, 8) . '...)');
    }

    // Build test message
    $test_messages = array(
        array('role' => 'system', 'content' => 'You are a connection tester. Reply with exactly: "OK - Connection successful." and nothing else.'),
        array('role' => 'user', 'content' => 'Test connection. Reply with the confirmation phrase.'),
    );

    $body = array(
        'model'       => $config['model'],
        'messages'    => $test_messages,
        'temperature' => floatval($config['temperature']),
        'max_tokens'  => 50, // Tiny response for testing
    );

    // Add provider routing for OpenRouter
    if ($ai_provider === 'openrouter' && !empty($config['modelProvider'])) {
        $body['provider'] = array(
            'order'           => array($config['modelProvider']),
            'allow_fallbacks' => false,
        );
    }

    $logs[] = array('type' => 'info', 'message' => 'Sending test message to API...');

    // Build cURL request
    $request_headers = array('Content-Type: application/json');
    if (!empty($config['apiKey'])) {
        $request_headers[] = 'Authorization: Bearer ' . $config['apiKey'];
    }

    set_time_limit(30);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $config['apiEndpoint'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => wp_json_encode($body),
        CURLOPT_HTTPHEADER     => $request_headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ));

    $response_body = curl_exec($ch);
    $status_code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno    = curl_errno($ch);
    $curl_error    = curl_error($ch);
    $total_time    = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    curl_close($ch);

    if (false === $response_body) {
        $logs[] = array('type' => 'error', 'message' => 'Connection failed! cURL error ' . $curl_errno . ': ' . $curl_error);
        $logs[] = array('type' => 'error', 'message' => 'Tip: Check that the endpoint URL is correct and reachable from your server.');
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings));
    }

    $logs[] = array('type' => 'success', 'message' => 'Server responded in ' . $total_time . 'ms (HTTP ' . $status_code . ')');

    // Parse response
    $response_data = json_decode($response_body, true);

    if (!$response_data) {
        $logs[] = array('type' => 'error', 'message' => 'Invalid JSON response from API. Raw response: ' . substr($response_body, 0, 300));
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings));
    }

    // Check for API-level errors
    if (isset($response_data['error'])) {
        $error_msg = is_array($response_data['error']) 
            ? ($response_data['error']['message'] ?? json_encode($response_data['error']))
            : $response_data['error'];
        $logs[] = array('type' => 'error', 'message' => 'API returned an error: ' . $error_msg);
        
        if ($status_code === 401 || $status_code === 403) {
            $logs[] = array('type' => 'error', 'message' => 'Tip: Your API key may be invalid or expired. Check your key in the settings above.');
        } elseif ($status_code === 404) {
            $logs[] = array('type' => 'error', 'message' => 'Tip: The model "' . $config['model'] . '" may not exist or the endpoint URL is wrong.');
        } elseif ($status_code === 429) {
            $logs[] = array('type' => 'error', 'message' => 'Tip: Rate limit exceeded. Wait a moment and try again.');
        }
        
        wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings));
    }

    // Extract response content
    $content = '';
    if (isset($response_data['choices'][0]['message']['content'])) {
        $content = $response_data['choices'][0]['message']['content'];
    }

    if (empty($content)) {
        $logs[] = array('type' => 'warning', 'message' => 'API responded but returned empty content. Response structure may differ from expected format.');
        $logs[] = array('type' => 'info', 'message' => 'Raw response keys: ' . implode(', ', array_keys($response_data)));
        wp_send_json(array('success' => true, 'logs' => $logs, 'settings' => $settings));
    }

    $logs[] = array('type' => 'success', 'message' => 'AI Response: "' . trim($content) . '"');
    $logs[] = array('type' => 'success', 'message' => '✅ Connection test PASSED! Your AI configuration is working correctly.');

    // Show token usage if available
    if (isset($response_data['usage'])) {
        $usage = $response_data['usage'];
        $logs[] = array('type' => 'info', 'message' => 'Token usage — Prompt: ' . ($usage['prompt_tokens'] ?? '?') . ', Completion: ' . ($usage['completion_tokens'] ?? '?') . ', Total: ' . ($usage['total_tokens'] ?? '?'));
    }

    wp_send_json(array('success' => true, 'logs' => $logs, 'settings' => $settings));
}

function snn_render_ai_settings() {
    $ai_enabled           = get_option('snn_ai_enabled', 'no');
    $ai_provider          = get_option('snn_ai_provider', 'openrouter');
    $openrouter_api_key   = get_option('snn_openrouter_api_key', '');
    $openrouter_model     = get_option('snn_openrouter_model', '');
    $openrouter_model_provider = get_option('snn_openrouter_model_provider', '');
    $openrouter_image_model = get_option('snn_openrouter_image_model', 'google/gemini-2.5-flash-image');
    $openrouter_image_model_provider = get_option('snn_openrouter_image_model_provider', '');
    $system_prompt        = get_option(
        'snn_system_prompt',
        'You are a helpful assistant that helps with content creation or manipulation. You work inside a wordpress visual builder. User usually changes a website content. Keep the content length as similar the existing content when you are editing or follow the users instructions accordingly. Only respond with the needed content and nothing else always!'
    );

    // Multimodal configuration settings
    $image_aspect_ratio = get_option('snn_ai_image_aspect_ratio', '16:9');
    $image_size         = get_option('snn_ai_image_size', '1K');

    // Generation parameters
    $temperature        = get_option('snn_ai_temperature', '0.7');
    $max_tokens         = get_option('snn_ai_max_tokens', '4000');
    $top_p              = get_option('snn_ai_top_p', '1');
    $frequency_penalty  = get_option('snn_ai_frequency_penalty', '0');
    $presence_penalty   = get_option('snn_ai_presence_penalty', '0');

    $default_presets = [
        ['name' => 'Title',    'prompt' => 'Generate a catchy title.'],
        ['name' => 'Content',  'prompt' => 'Generate engaging content.'],
        ['name' => 'Button',   'prompt' => 'Suggest a call-to-action button text.'],
        ['name' => 'Funny',    'prompt' => 'Make it funny.'],
        ['name' => 'Sad',      'prompt' => 'Make it sad.'],
        ['name' => 'Business', 'prompt' => 'Make it professional and business-like.'],
        ['name' => 'Shorter',  'prompt' => 'Make the following text significantly shorter while preserving the core meaning.'],
        ['name' => 'Longer',   'prompt' => 'Make the following text significantly longer on the following text, adding more detail or explanation.'],
        ['name' => 'CSS',      'prompt' => 'Write clean native CSS only. Always use selector %root%, no <style> tag.'],
        ['name' => 'HTML',     'prompt' => 'Write html css and js if needed and you can use cdn lib if you wish. <html> <head> or <body> not needed.'],
        ['name' => 'SEO',      'prompt' => 'Generate SEO-optimized content.'],
    ];
    $stored_action_presets = get_option('snn_ai_action_presets', false);
    if ($stored_action_presets === false) {
        $action_presets = $default_presets;
    } elseif (!is_array($stored_action_presets) || empty($stored_action_presets)) {
        $action_presets = $default_presets;
    } else {
        $action_presets = $stored_action_presets;
    }

    $action_presets = array_values($action_presets);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('AI Settings', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('snn_ai_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_ai_enabled"><?php esc_html_e('Enable AI Features', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="checkbox"
                            name="snn_ai_enabled"
                            id="snn_ai_enabled"
                            value="yes"
                            <?php checked($ai_enabled, 'yes'); ?>
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_provider"><?php esc_html_e('API Provider', 'snn'); ?></label>
                    </th>
                    <td>
                        <select name="snn_ai_provider" id="snn_ai_provider">
                            <option value="openrouter" <?php selected($ai_provider, 'openrouter'); ?>>OpenRouter</option>
                            <option value="custom" <?php selected($ai_provider, 'custom'); ?>>Custom</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('System Prompt', 'snn'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_system_prompt"><?php esc_html_e('System Prompt', 'snn'); ?></label>
                    </th>
                    <td>
                        <textarea
                            name="snn_system_prompt"
                            id="snn_system_prompt"
                            class="regular-text"
                            rows="5"
                        ><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Enter the system prompt for AI interactions.', 'snn'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <details id="snn-advanced-settings" style="margin-top: 20px;">
                <summary style="cursor: pointer; font-size: 16px; font-weight: 600; padding: 8px 0; color: #1d2327;">
                    <?php esc_html_e('⚙️ Advanced Generation Settings', 'snn'); ?>
                    <span style="font-weight: 400; font-size: 13px; color: #646970; margin-left: 8px;">
                        <?php esc_html_e('— fine-tune how the AI responds (click to expand)', 'snn'); ?>
                    </span>
                </summary>
                <p class="description" style="margin: 10px 0;">
                    <?php esc_html_e('These settings apply to all AI providers (OpenRouter and Custom). Most users don\'t need to change these — the defaults work well for general content editing.', 'snn'); ?>
                </p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snn_ai_temperature"><?php esc_html_e('Temperature', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="range"
                            name="snn_ai_temperature"
                            id="snn_ai_temperature"
                            min="0"
                            max="2"
                            step="0.1"
                            value="<?php echo esc_attr($temperature); ?>"
                            style="width: 300px; vertical-align: middle;"
                            oninput="document.getElementById('snn_ai_temperature_value').textContent = this.value"
                        />
                        <span id="snn_ai_temperature_value" style="display: inline-block; min-width: 32px; font-weight: 600; margin-left: 8px;"><?php echo esc_html($temperature); ?></span>
                        <p class="description">
                            <?php esc_html_e('Controls randomness. 0 = deterministic/factual, 1 = creative, 2 = very random. Default: 0.7', 'snn'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_max_tokens"><?php esc_html_e('Max Tokens', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="number"
                            name="snn_ai_max_tokens"
                            id="snn_ai_max_tokens"
                            value="<?php echo esc_attr($max_tokens); ?>"
                            class="small-text"
                            min="100"
                            max="128000"
                            step="100"
                        />
                        <p class="description">
                            <?php esc_html_e('Maximum response length in tokens (≈ words). Higher = longer responses but more API cost. Default: 4000', 'snn'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_top_p"><?php esc_html_e('Top P', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="range"
                            name="snn_ai_top_p"
                            id="snn_ai_top_p"
                            min="0"
                            max="1"
                            step="0.05"
                            value="<?php echo esc_attr($top_p); ?>"
                            style="width: 300px; vertical-align: middle;"
                            oninput="document.getElementById('snn_ai_top_p_value').textContent = this.value"
                        />
                        <span id="snn_ai_top_p_value" style="display: inline-block; min-width: 32px; font-weight: 600; margin-left: 8px;"><?php echo esc_html($top_p); ?></span>
                        <p class="description">
                            <?php esc_html_e('Nucleus sampling: only tokens with cumulative probability up to this value are considered. 1 = all tokens (default), 0.1 = only the most likely tokens.', 'snn'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_frequency_penalty"><?php esc_html_e('Frequency Penalty', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="range"
                            name="snn_ai_frequency_penalty"
                            id="snn_ai_frequency_penalty"
                            min="-2"
                            max="2"
                            step="0.1"
                            value="<?php echo esc_attr($frequency_penalty); ?>"
                            style="width: 300px; vertical-align: middle;"
                            oninput="document.getElementById('snn_ai_frequency_penalty_value').textContent = this.value"
                        />
                        <span id="snn_ai_frequency_penalty_value" style="display: inline-block; min-width: 32px; font-weight: 600; margin-left: 8px;"><?php echo esc_html($frequency_penalty); ?></span>
                        <p class="description">
                            <?php esc_html_e('Reduces word repetition. Positive values penalize tokens based on how often they\'ve appeared. -2 to 2, 0 = off (default).', 'snn'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snn_ai_presence_penalty"><?php esc_html_e('Presence Penalty', 'snn'); ?></label>
                    </th>
                    <td>
                        <input
                            type="range"
                            name="snn_ai_presence_penalty"
                            id="snn_ai_presence_penalty"
                            min="-2"
                            max="2"
                            step="0.1"
                            value="<?php echo esc_attr($presence_penalty); ?>"
                            style="width: 300px; vertical-align: middle;"
                            oninput="document.getElementById('snn_ai_presence_penalty_value').textContent = this.value"
                        />
                        <span id="snn_ai_presence_penalty_value" style="display: inline-block; min-width: 32px; font-weight: 600; margin-left: 8px;"><?php echo esc_html($presence_penalty); ?></span>
                        <p class="description">
                            <?php esc_html_e('Encourages topic diversity. Positive values penalize tokens that have already appeared at all. -2 to 2, 0 = off (default).', 'snn'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            </details>

            <div
                id="openrouter-settings"
                style="display: <?php echo ($ai_provider === 'openrouter' && $ai_enabled === 'yes') ? 'block' : 'none'; ?>;"
            >
                <h2><?php esc_html_e('OpenRouter API Settings', 'snn'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_api_key"><?php esc_html_e('OpenRouter API Key', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                name="snn_openrouter_api_key"
                                id="snn_openrouter_api_key"
                                value="<?php echo esc_attr($openrouter_api_key); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <p class="description"><?php esc_html_e('Enter your OpenRouter API key.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_model"><?php esc_html_e('OpenRouter Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_openrouter_model"
                                id="snn_openrouter_model"
                                class="regular-text"
                                value="<?php echo esc_attr($openrouter_model); ?>"
                                placeholder="<?php esc_attr_e('Search for model...', 'snn'); ?>"
                                list="openrouter-models"
                                autocomplete="off"
                            >
                            <datalist id="openrouter-models">
                                <option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>
                            </datalist>
                            <p class="description">
                                <?php esc_html_e('Select the OpenRouter model to use. Start typing to search.', 'snn'); ?>
                                <a href="https://openrouter.ai/models" target="_blank"><?php esc_html_e('Prices', 'snn'); ?></a>
                            </p>
                            <div id="openrouter-model-capabilities" class="model-capabilities-tags" style="margin-top: 10px; display: none;">
                                <div class="capabilities-tags" style="margin-top: 5px;"></div>
                            </div>
                            <div id="openrouter-selected-model-features" class="selected-model-features" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px; height: 220px; overflow: auto;">
                                <strong><?php esc_html_e('Selected Model Features:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_model_provider"><?php esc_html_e('Model Provider', 'snn'); ?></label>
                        </th>
                        <td>
                            <select
                                name="snn_openrouter_model_provider"
                                id="snn_openrouter_model_provider"
                                class="regular-text"
                            >
                                <option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Select a specific provider for this model. Auto lets OpenRouter choose automatically.', 'snn'); ?>
                            </p>
                            <div id="openrouter-provider-info" class="provider-info" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px;">
                                <strong><?php esc_html_e('Provider Details:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_image_model"><?php esc_html_e('OpenRouter Image Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_openrouter_image_model"
                                id="snn_openrouter_image_model"
                                class="regular-text"
                                value="<?php echo esc_attr($openrouter_image_model); ?>"
                                placeholder="<?php esc_attr_e('Search for image model...', 'snn'); ?>"
                                list="openrouter-image-models"
                                autocomplete="off"
                            >
                            <datalist id="openrouter-image-models">
                                <option value=""><?php esc_html_e('Loading image models...', 'snn'); ?></option>
                            </datalist>
                            <p class="description">
                                <?php esc_html_e('Select an OpenRouter model with image output capabilities. Start typing to search.', 'snn'); ?>
                            </p>
                            <div id="openrouter-image-model-capabilities" class="model-capabilities-tags" style="margin-top: 10px; display: none;">
                                <div class="capabilities-tags" style="margin-top: 5px;"></div>
                            </div>
                            <div id="openrouter-image-selected-model-features" class="selected-model-features" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px; height: 220px; overflow: auto;">
                                <strong><?php esc_html_e('Selected Image Model Features:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_openrouter_image_model_provider"><?php esc_html_e('Image Model Provider', 'snn'); ?></label>
                        </th>
                        <td>
                            <select
                                name="snn_openrouter_image_model_provider"
                                id="snn_openrouter_image_model_provider"
                                class="regular-text"
                            >
                                <option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Select a specific provider for this image model. Auto lets OpenRouter choose automatically.', 'snn'); ?>
                            </p>
                            <div id="openrouter-image-provider-info" class="provider-info" style="margin-top: 10px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none; max-width: 410px;">
                                <strong><?php esc_html_e('Provider Details:', 'snn'); ?></strong>
                                <ul style="list-style-type: disc; margin-left: 20px;"></ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_ai_image_aspect_ratio"><?php esc_html_e('Image Generation Settings', 'snn'); ?></label>
                        </th>
                        <td>
                            <label for="snn_ai_image_aspect_ratio" style="display: inline-block; min-width: 100px;"><?php esc_html_e('Aspect Ratio:', 'snn'); ?></label>
                            <select name="snn_ai_image_aspect_ratio" id="snn_ai_image_aspect_ratio" style="width: 150px;">
                                <option value="1:1" <?php selected($image_aspect_ratio, '1:1'); ?>>1:1 (1024×1024)</option>
                                <option value="2:3" <?php selected($image_aspect_ratio, '2:3'); ?>>2:3 (832×1248)</option>
                                <option value="3:2" <?php selected($image_aspect_ratio, '3:2'); ?>>3:2 (1248×832)</option>
                                <option value="3:4" <?php selected($image_aspect_ratio, '3:4'); ?>>3:4 (864×1184)</option>
                                <option value="4:3" <?php selected($image_aspect_ratio, '4:3'); ?>>4:3 (1184×864)</option>
                                <option value="4:5" <?php selected($image_aspect_ratio, '4:5'); ?>>4:5 (896×1152)</option>
                                <option value="5:4" <?php selected($image_aspect_ratio, '5:4'); ?>>5:4 (1152×896)</option>
                                <option value="9:16" <?php selected($image_aspect_ratio, '9:16'); ?>>9:16 (768×1344)</option>
                                <option value="16:9" <?php selected($image_aspect_ratio, '16:9'); ?>>16:9 (1344×768)</option>
                                <option value="21:9" <?php selected($image_aspect_ratio, '21:9'); ?>>21:9 (1536×672)</option>
                            </select>
                            <br><br>
                            <label for="snn_ai_image_size" style="display: inline-block; min-width: 100px;"><?php esc_html_e('Image Size:', 'snn'); ?></label>
                            <select name="snn_ai_image_size" id="snn_ai_image_size" style="width: 150px;">
                                <option value="1K" <?php selected($image_size, '1K'); ?>>1K (Standard)</option>
                                <option value="2K" <?php selected($image_size, '2K'); ?>>2K (Higher Resolution)</option>
                                <option value="4K" <?php selected($image_size, '4K'); ?>>4K (Highest Resolution)</option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Default settings for image generation. Applied when generating images with compatible models. Note: Image size options are currently only supported by Gemini models.', 'snn'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div
                id="custom-settings"
                style="display: <?php echo ($ai_provider === 'custom' && $ai_enabled === 'yes') ? 'block' : 'none'; ?>;"
            >
                <h2><?php esc_html_e('Custom API Settings', 'snn'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_api_key"><?php esc_html_e('Custom API Key', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                name="snn_custom_api_key"
                                id="snn_custom_api_key"
                                value="<?php echo esc_attr(get_option('snn_custom_api_key', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_api_endpoint"><?php esc_html_e('Custom API Endpoint', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_custom_api_endpoint"
                                id="snn_custom_api_endpoint"
                                value="<?php echo esc_attr(get_option('snn_custom_api_endpoint', '')); ?>"
                                class="regular-text"
                                placeholder="https://your-api.example.com/v1/chat/completions"
                            />
                            <p class="description">
                                <?php esc_html_e('Enter the full endpoint URL including the path. Must be an OpenAI-compatible', 'snn'); ?>
                                <code>/v1/chat/completions</code> <?php esc_html_e('endpoint.', 'snn'); ?>
                                <br>
                                <?php esc_html_e('Example:', 'snn'); ?> <code>https://your-api.example.com/v1/chat/completions</code>
                                <br><br>
                                <?php esc_html_e('You can expose your local model to the internet using', 'snn'); ?>
                                <a href="https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/" target="_blank" rel="noopener noreferrer">Cloudflare Tunnel (cloudflared)</a>.
                                <br><?php esc_html_e('Install cloudflared and run example:', 'snn'); ?>
                                <br>
                                <code>cloudflared tunnel --url http://localhost:11434</code>
                                <br>
                                <?php esc_html_e('It gives you a public HTTPS URL — append endpoint', 'snn'); ?> <code>https://example-random-url.trycloudflare.com/v1/chat/completions</code> <?php esc_html_e('and paste it here.', 'snn'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="snn_custom_model"><?php esc_html_e('Custom Model', 'snn'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="snn_custom_model"
                                id="snn_custom_model"
                                value="<?php echo esc_attr(get_option('snn_custom_model', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                </table>
            </div>

            <details id="snn-test-connection-section" style="margin-top: 15px;">
                <summary style="cursor: pointer; font-size: 16px; font-weight: 600; padding: 8px 0; color: #1d2327;">
                    <?php esc_html_e('🔌 Test Connection', 'snn'); ?>
                    <span style="font-weight: 400; font-size: 13px; color: #646970; margin-left: 8px;">
                        <?php esc_html_e('— verify your API settings are working (click to expand)', 'snn'); ?>
                    </span>
                </summary>
                <p style="margin: 10px 0;">
                    <?php esc_html_e('Send a minimal test message using your current settings. Works for both OpenRouter and Custom providers. The log below shows each step in real time with colour-coded results.', 'snn'); ?>
                </p>
                <p class="description"><?php esc_html_e('Tip: Save your settings first if you made changes, then run the test.', 'snn'); ?></p>

                <button id="snn_run_ai_test" class="button button-primary"><?php esc_html_e('Run Connection Test', 'snn'); ?></button>
                <span id="snn_ai_test_spinner" class="spinner" style="float: none; margin: 0 10px; display: none;"></span>

                <div id="snn_ai_log_wrap" style="display:none; margin-top:20px;">
                    <div id="snn_ai_log_box" style="
                        position: relative;
                        background: #1e1e1e;
                        color: #d4d4d4;
                        font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
                        font-size: 12px;
                        line-height: 1.7;
                        padding: 14px 16px;
                        border-radius: 6px;
                        max-height: 400px;
                        overflow-y: auto;
                        border: 2px solid #444;
                    ">
                        <button id="snn_ai_copy_btn" title="Copy log" style="
                            position: sticky;
                            float: right;
                            top: 0;
                            right: 0;
                            background: #3a3a3a;
                            color: #ccc;
                            border: 1px solid #555;
                            border-radius: 4px;
                            padding: 3px 10px;
                            font-size: 11px;
                            cursor: pointer;
                            z-index: 10;
                            margin-bottom: 8px;
                        "><?php esc_html_e('Copy', 'snn'); ?></button>
                        <div id="snn_ai_log_entries"></div>
                    </div>

                    <div id="snn_ai_settings_box" style="margin-top: 12px; padding: 12px 16px; border-radius: 6px; border: 2px solid #ccc; font-size: 13px;">
                        <strong><?php esc_html_e('Settings tested:', 'snn'); ?></strong>
                        <table id="snn_ai_settings_table" style="margin-top: 8px; border-collapse: collapse; width: auto;">
                        </table>
                    </div>
                </div>
            </details>

            <h2><?php esc_html_e('Prompt Presets', 'snn'); ?></h2>
            <p>
                <?php esc_html_e('Add, edit, remove, or drag-and-drop to reorder AI action prompts. These presets will be available as selectable buttons in the AI overlay.', 'snn'); ?>
            </p>

            <table class="form-table" id="snn-ai-action-presets-table">
                <tbody>
                <?php if (!empty($action_presets)) : ?>
                    <?php foreach ($action_presets as $index => $preset) : ?>
                        <tr class="snn-ai-action-preset-row" draggable="true">
                            <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                            <td style="padding:2px">
                                <input
                                    type="text"
                                    name="snn_ai_action_presets[<?php echo $index; ?>][name]"
                                    value="<?php echo esc_attr($preset['name']); ?>"
                                    placeholder="<?php esc_attr_e('Action Name', 'snn'); ?>"
                                    class="regular-text preset-name-input"
                                />
                            </td>
                            <td style="padding:2px">
                                <textarea
                                    name="snn_ai_action_presets[<?php echo $index; ?>][prompt]"
                                    rows="2"
                                    placeholder="<?php esc_attr_e('Action Prompt', 'snn'); ?>"
                                    class="regular-text preset-prompt-input"
                                ><?php echo esc_textarea($preset['prompt']); ?></textarea>
                            </td>
                            <td style="padding:2px">
                                <button type="button" class="button snn-ai-remove-preset"><?php esc_html_e('Remove', 'snn'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="snn-ai-add-preset"><?php esc_html_e('Add Preset', 'snn'); ?></button>
                <button type="button" class="button" id="snn-ai-reset-presets" style="margin-left: 10px;"><?php esc_html_e('Reset Presets', 'snn'); ?></button>
            </p>

            <div id="snn-ai-import-export-container" style="margin-top: 20px;">
                <button type="button" class="button" id="snn-ai-export-button"><?php esc_html_e('Export Presets', 'snn'); ?></button>
                <button type="button" class="button" id="snn-ai-import-button" style="margin-left: 10px;"><?php esc_html_e('Import Presets', 'snn'); ?></button>

                <div id="snn-ai-export-area" style="display: none; margin-top: 10px;">
                    <h3><?php esc_html_e('Exported Presets', 'snn'); ?></h3>
                    <p><?php esc_html_e('Copy the text below to save your presets.', 'snn'); ?></p>
                    <textarea id="snn-ai-export-textarea" rows="8" style="width: 100%; max-width: 660px;" readonly></textarea>
                </div>

                <div id="snn-ai-import-area" style="display: none; margin-top: 10px;">
                    <h3><?php esc_html_e('Import Presets', 'snn'); ?></h3>
                    <p><?php esc_html_e('Paste your previously exported presets into the text area below and click "Import". This will add the imported presets to your current list, skipping any duplicates.', 'snn'); ?></p>
                    <textarea id="snn-ai-import-textarea" rows="8" style="width: 100%; max-width: 660px;"></textarea>
                    <p>
                        <button type="button" class="button button-primary" id="snn-ai-import-apply-button"><?php esc_html_e('Import', 'snn'); ?></button>
                        <span id="snn-ai-import-status" style="margin-left: 10px; font-style: italic;"></span>
                    </p>
                </div>
            </div>

            <style>
            #snn-ai-action-presets-table {
                max-width: 660px;
            }
            #snn-ai-action-presets-table td {
                vertical-align: top;
            }
            .snn-ai-action-preset-row input.regular-text {
                max-width: 220px;
                height: 46px;
            }
            #openrouter-settings #snn_openrouter_api_key,
            #openrouter-settings #snn_openrouter_model,
            #openrouter-settings #snn_openrouter_image_model {
                width: 430px;
                max-width: 430px;
            }
            #openrouter-settings #snn_openrouter_api_key {
                margin-bottom: 10px;
            }
            .snn-drag-over-row {
                outline: 2px dashed #0073aa;
            }
            [name="snn_system_prompt"]{width:430px}
            .selected-model-features ul {
                padding-left: 20px;
                margin-top: 5px;
            }
            .selected-model-features li {
                margin-bottom: 3px;
            }
            .capabilities-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .capability-tag {
                display: inline-block;
                padding: 4px 10px;
                background-color: #0073aa;
                color: #fff;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            </style>

            <?php submit_button(__('Save AI Settings', 'snn')); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enableCheckbox = document.getElementById('snn_ai_enabled');
            const providerSelect = document.getElementById('snn_ai_provider');
            const openrouterSettingsDiv = document.getElementById('openrouter-settings');
            const customSettingsDiv = document.getElementById('custom-settings');

            // Model feature display elements
            const openrouterModelInput = document.getElementById('snn_openrouter_model');
            const openrouterFeaturesDiv = document.getElementById('openrouter-selected-model-features');
            const openrouterFeaturesList = openrouterFeaturesDiv ? openrouterFeaturesDiv.querySelector('ul') : null;
            const openrouterCapabilitiesDiv = document.getElementById('openrouter-model-capabilities');
            const openrouterCapabilitiesContainer = openrouterCapabilitiesDiv ? openrouterCapabilitiesDiv.querySelector('.capabilities-tags') : null;

            const openrouterImageModelInput = document.getElementById('snn_openrouter_image_model');
            const openrouterImageFeaturesDiv = document.getElementById('openrouter-image-selected-model-features');
            const openrouterImageFeaturesList = openrouterImageFeaturesDiv ? openrouterImageFeaturesDiv.querySelector('ul') : null;
            const openrouterImageCapabilitiesDiv = document.getElementById('openrouter-image-model-capabilities');
            const openrouterImageCapabilitiesContainer = openrouterImageCapabilitiesDiv ? openrouterImageCapabilitiesDiv.querySelector('.capabilities-tags') : null;

            let allOpenRouterModels = [];
            let allOpenRouterImageModels = [];

            function toggleSettingsVisibility() {
                const isEnabled = enableCheckbox.checked;
                openrouterSettingsDiv.style.display = 'none';
                customSettingsDiv.style.display = 'none';

                // Hide feature divs when provider changes or AI is disabled
                if (openrouterFeaturesDiv) openrouterFeaturesDiv.style.display = 'none';
                if (openrouterCapabilitiesDiv) openrouterCapabilitiesDiv.style.display = 'none';
                if (openrouterImageFeaturesDiv) openrouterImageFeaturesDiv.style.display = 'none';
                if (openrouterImageCapabilitiesDiv) openrouterImageCapabilitiesDiv.style.display = 'none';

                if (isEnabled) {
                    if (providerSelect.value === 'openrouter') {
                        openrouterSettingsDiv.style.display = 'block';
                        fetchOpenRouterModels();
                        fetchOpenRouterImageModels();
                    } else if (providerSelect.value === 'custom') {
                        customSettingsDiv.style.display = 'block';
                    }
                }
            }

            /**
             * Formats a value for display, handling booleans, numbers (including timestamps), and null/undefined/empty strings.
             * @param {*} value The value to format.
             * @returns {string} The formatted value.
             */
            function formatValue(value) {
                if (typeof value === 'boolean') {
                    return value ? 'Yes' : 'No';
                }
                if (typeof value === 'number' && !isNaN(value) && value !== null) {
                    // Check if it's a Unix timestamp (seconds or milliseconds)
                    if (String(value).length === 10 || String(value).length === 13) {
                        return new Date(value * (String(value).length === 10 ? 1000 : 1)).toLocaleString();
                    }
                    return value.toString();
                }
                if (value === null || value === undefined || value === '') {
                    return 'N/A';
                }
                return value;
            }

            /**
             * Displays all features of a given model object in a list, handling nested objects and arrays.
             * @param {HTMLElement} featuresListElement The <ul> element to append features to.
             * @param {Object} modelData The model object containing features.
             */
            function displayFeatures(featuresListElement, modelData) {
                if (!featuresListElement || !modelData) return;
                featuresListElement.innerHTML = ''; // Clear previous features

                for (const key in modelData) {
                    if (Object.prototype.hasOwnProperty.call(modelData, key)) {
                        const value = modelData[key];
                        const li = document.createElement('li');

                        if (Array.isArray(value)) {
                            // Handle arrays (e.g., supported_parameters, permissions)
                            if (value.length > 0) {
                                li.innerHTML = `<strong>${key}:</strong>`;
                                const nestedUl = document.createElement('ul');
                                value.forEach((item, index) => {
                                    const nestedLi = document.createElement('li');
                                    if (typeof item === 'object' && item !== null) {
                                        // If array contains objects (like OpenAI permissions)
                                        nestedLi.innerHTML = `<strong>Item ${index + 1}:</strong>`;
                                        const deeperNestedUl = document.createElement('ul');
                                        for (const itemKey in item) {
                                            if (Object.prototype.hasOwnProperty.call(item, itemKey)) {
                                                const deeperLi = document.createElement('li');
                                                deeperLi.textContent = `${itemKey}: ${formatValue(item[itemKey])}`;
                                                deeperNestedUl.appendChild(deeperLi);
                                            }
                                        }
                                        nestedLi.appendChild(deeperNestedUl);
                                    } else {
                                        // If array contains primitive values (like supported_parameters)
                                        nestedLi.textContent = formatValue(item);
                                    }
                                    nestedUl.appendChild(nestedLi);
                                });
                                li.appendChild(nestedUl);
                            } else {
                                li.textContent = `${key}: N/A (empty list)`;
                            }
                        } else if (typeof value === 'object' && value !== null) {
                            // Handle nested objects (e.g., architecture, pricing, top_provider)
                            li.innerHTML = `<strong>${key}:</strong>`;
                            const nestedUl = document.createElement('ul');
                            for (const nestedKey in value) {
                                if (Object.prototype.hasOwnProperty.call(value, nestedKey)) {
                                    const nestedLi = document.createElement('li');
                                    nestedLi.textContent = `${nestedKey}: ${formatValue(value[nestedKey])}`;
                                    nestedUl.appendChild(nestedLi);
                                }
                            }
                            li.appendChild(nestedUl);
                        } else {
                            // Handle primitive values
                            li.textContent = `${key}: ${formatValue(value)}`;
                        }
                        featuresListElement.appendChild(li);
                    }
                }
            }

            /**
             * Displays architecture capabilities as simple tags
             * @param {HTMLElement} container The container element for tags
             * @param {Object} architecture The architecture object from model data
             */
            function displayCapabilityTags(container, architecture) {
                if (!container || !architecture) return;
                container.innerHTML = ''; // Clear previous tags

                // Create tags for each architecture property
                const createTag = (label, value) => {
                    const tag = document.createElement('span');
                    tag.className = 'capability-tag';
                    if (Array.isArray(value)) {
                        tag.textContent = `${label}: ${value.join(', ')}`;
                    } else {
                        tag.textContent = `${label}: ${value || 'N/A'}`;
                    }
                    return tag;
                };

                // Add tags for key architecture properties
                if (architecture.modality) {
                    container.appendChild(createTag('Modality', architecture.modality));
                }
                if (architecture.input_modalities && architecture.input_modalities.length > 0) {
                    container.appendChild(createTag('Input', architecture.input_modalities));
                }
                if (architecture.output_modalities && architecture.output_modalities.length > 0) {
                    container.appendChild(createTag('Output', architecture.output_modalities));
                }
                if (architecture.tokenizer) {
                    container.appendChild(createTag('Tokenizer', architecture.tokenizer));
                }
                if (architecture.instruct_type) {
                    container.appendChild(createTag('Instruct Type', architecture.instruct_type));
                }
            }

            function displayOpenRouterModelFeatures(modelId) {
                if (!openrouterFeaturesList) return;
                openrouterFeaturesDiv.style.display = 'none';
                if (openrouterCapabilitiesDiv) openrouterCapabilitiesDiv.style.display = 'none';

                const selectedModel = allOpenRouterModels.find(model => model.id === modelId);

                if (selectedModel) {
                    // Display capability tags if architecture data exists
                    if (selectedModel.architecture && openrouterCapabilitiesContainer) {
                        openrouterCapabilitiesDiv.style.display = 'block';
                        displayCapabilityTags(openrouterCapabilitiesContainer, selectedModel.architecture);
                    }

                    // Display full features list
                    openrouterFeaturesDiv.style.display = 'block';
                    displayFeatures(openrouterFeaturesList, selectedModel);
                }
            }

            function fetchOpenRouterModels() {
                const dataListEl = document.getElementById('openrouter-models');
                if (!dataListEl) return;
                const openrouterKeyEl = document.getElementById('snn_openrouter_api_key');
                const openrouterKey = openrouterKeyEl ? openrouterKeyEl.value.trim() : '';
                if (!openrouterKey) {
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('OpenRouter key missing. Please add your key first.', 'snn'); ?></option>';
                    return;
                }
                dataListEl.innerHTML = '<option value=""><?php esc_html_e('Loading models...', 'snn'); ?></option>';
                let slowTimeout = setTimeout(function(){
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Still loading models... (this is taking longer than usual)', 'snn'); ?></option>';
                }, 3000);
                fetch('https://openrouter.ai/api/v1/models', {
                    headers: { 'Authorization': 'Bearer ' + openrouterKey }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('OpenRouter models API error: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.data) {
                        allOpenRouterModels = data.data; // Store all models
                        dataListEl.innerHTML = '';
                        data.data.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.id;
                            let priceInfo = '';
                            if (model.pricing && model.pricing.prompt && model.pricing.completion) {
                                const promptCost = (model.pricing.prompt.cost * 1000000).toFixed(6); // Per 1M tokens
                                const completionCost = (model.pricing.completion.cost * 1000000).toFixed(6); // Per 1M tokens
                                priceInfo = ` | Prompt: $${promptCost}/M, Comp: $${completionCost}/M`;
                            }
                            const providerInfo = model.top_provider ? ` | Provider: ${model.top_provider.name}` : '';
                            option.text = `${model.name} (${model.id}) | ${model.context_length} tokens${priceInfo}${providerInfo}`;
                            dataListEl.appendChild(option);
                        });
                        // Display features for the currently selected model if any
                        displayOpenRouterModelFeatures(openrouterModelInput.value);
                    } else {
                        dataListEl.innerHTML = '<option value=""><?php esc_html_e('No models found.', 'snn'); ?></option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching OpenRouter models:', error);
                    dataListEl.innerHTML = '<option value=""><?php esc_html_e('Error loading models.', 'snn'); ?></option>';
                })
                .finally(() => {
                    clearTimeout(slowTimeout);
                });
            }

            function displayOpenRouterImageModelFeatures(modelId) {
                if (!openrouterImageFeaturesList) return;
                openrouterImageFeaturesDiv.style.display = 'none';
                if (openrouterImageCapabilitiesDiv) openrouterImageCapabilitiesDiv.style.display = 'none';

                const selectedModel = allOpenRouterImageModels.find(model => model.id === modelId);

                if (selectedModel) {
                    // Display capability tags if architecture data exists
                    if (selectedModel.architecture && openrouterImageCapabilitiesContainer) {
                        openrouterImageCapabilitiesDiv.style.display = 'block';
                        displayCapabilityTags(openrouterImageCapabilitiesContainer, selectedModel.architecture);
                    }

                    // Display full features list
                    openrouterImageFeaturesDiv.style.display = 'block';
                    displayFeatures(openrouterImageFeaturesList, selectedModel);
                }
            }

            function fetchOpenRouterImageModels() {
                const dataListEl = document.getElementById('openrouter-image-models');
                if (!dataListEl) return;
                const openrouterKeyEl = document.getElementById('snn_openrouter_api_key');
                const openrouterKey = openrouterKeyEl ? openrouterKeyEl.value.trim() : '';

                // Static list of image models
                const staticImageModels = [
                    'sourceful/riverflow-v2-pro',
                    'sourceful/riverflow-v2-fast',
                    'black-forest-labs/flux.2-klein-4b',
                    'bytedance-seed/seedream-4.5',
                    'black-forest-labs/flux.2-max',
                    'sourceful/riverflow-v2-max-preview',
                    'sourceful/riverflow-v2-standard-preview',
                    'sourceful/riverflow-v2-fast-preview',
                    'black-forest-labs/flux.2-flex',
                    'black-forest-labs/flux.2-pro',
                    'google/gemini-3-pro-image-preview',
                    'openai/gpt-5-image-mini',
                    'openai/gpt-5-image',
                    'google/gemini-2.5-flash-image',
                    'google/gemini-2.5-flash-image-preview'
                ];

                // Populate datalist with static options immediately
                dataListEl.innerHTML = '';
                staticImageModels.forEach(modelId => {
                    const option = document.createElement('option');
                    option.value = modelId;
                    option.text = modelId;
                    dataListEl.appendChild(option);
                });

                // If no API key, stop here
                if (!openrouterKey) {
                    return;
                }

                // Fetch full model data in background for feature display
                fetch('https://openrouter.ai/api/v1/models', {
                    headers: { 'Authorization': 'Bearer ' + openrouterKey }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('OpenRouter models API error: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.data) {
                        // Store all models for feature lookup
                        allOpenRouterImageModels = data.data;

                        // Display features for the currently selected image model if any
                        displayOpenRouterImageModelFeatures(openrouterImageModelInput.value);
                    }
                })
                .catch(error => {
                    console.error('Error fetching OpenRouter image models data:', error);
                });
            }

            // Function to fetch available providers for a specific model
            function fetchModelProviders(modelId, isImageModel = false) {
                const providerSelectId = isImageModel ? 'snn_openrouter_image_model_provider' : 'snn_openrouter_model_provider';
                const providerInfoId = isImageModel ? 'openrouter-image-provider-info' : 'openrouter-provider-info';
                const savedProviderOption = isImageModel ? '<?php echo esc_js($openrouter_image_model_provider); ?>' : '<?php echo esc_js($openrouter_model_provider); ?>';
                
                const providerSelect = document.getElementById(providerSelectId);
                const providerInfo = document.getElementById(providerInfoId);
                
                if (!providerSelect || !modelId) return;
                
                // Reset provider dropdown
                providerSelect.innerHTML = '<option value=""><?php esc_html_e('Loading providers...', 'snn'); ?></option>';
                providerSelect.disabled = true;
                if (providerInfo) providerInfo.style.display = 'none';
                
                const openrouterKeyEl = document.getElementById('snn_openrouter_api_key');
                const openrouterKey = openrouterKeyEl ? openrouterKeyEl.value.trim() : '';
                
                if (!openrouterKey) {
                    providerSelect.innerHTML = '<option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>';
                    providerSelect.disabled = false;
                    return;
                }
                
                // Parse model ID to get author and slug
                const modelParts = modelId.split('/');
                if (modelParts.length !== 2) {
                    providerSelect.innerHTML = '<option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>';
                    providerSelect.disabled = false;
                    return;
                }
                
                const [author, slug] = modelParts;
                const endpointsUrl = `https://openrouter.ai/api/v1/models/${author}/${slug}/endpoints`;
                
                fetch(endpointsUrl, {
                    headers: { 'Authorization': 'Bearer ' + openrouterKey }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Endpoints API error: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.data && data.data.endpoints && data.data.endpoints.length > 0) {
                        const endpoints = data.data.endpoints;
                        
                        // Populate dropdown
                        providerSelect.innerHTML = '<option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>';
                        
                        endpoints.forEach(endpoint => {
                            const option = document.createElement('option');
                            option.value = endpoint.provider_name || '';
                            
                            let label = endpoint.provider_name || 'Unknown';
                            
                            // Add pricing info if available
                            if (endpoint.pricing && endpoint.pricing.prompt && endpoint.pricing.completion) {
                                const promptCost = (parseFloat(endpoint.pricing.prompt) * 1000000).toFixed(6);
                                const completionCost = (parseFloat(endpoint.pricing.completion) * 1000000).toFixed(6);
                                label += ` | Prompt: $${promptCost}/M, Comp: $${completionCost}/M`;
                            }
                            
                            // Add latency info if available
                            if (endpoint.latency_last_30m && endpoint.latency_last_30m.p50) {
                                const latencyMs = (endpoint.latency_last_30m.p50 * 1000).toFixed(0);
                                label += ` | Latency: ${latencyMs}ms`;
                            }
                            
                            // Add throughput P50 info if available
                            if (endpoint.throughput_last_30m && endpoint.throughput_last_30m.p50) {
                                label += ` | Speed: ${endpoint.throughput_last_30m.p50} tok/s`;
                            }
                            
                            option.text = label;
                            option.dataset.endpoint = JSON.stringify(endpoint);
                            providerSelect.appendChild(option);
                        });
                        
                        // Restore saved selection if available
                        if (savedProviderOption) {
                            providerSelect.value = savedProviderOption;
                        }
                        
                        providerSelect.disabled = false;
                        
                        // Store endpoints data for later use
                        providerSelect.dataset.endpoints = JSON.stringify(endpoints);
                        
                    } else {
                        providerSelect.innerHTML = '<option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>';
                        providerSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error fetching providers:', error);
                    providerSelect.innerHTML = '<option value=""><?php esc_html_e('Auto (Default)', 'snn'); ?></option>';
                    providerSelect.disabled = false;
                });
            }

            // Function to display provider details when selected
            function displayProviderInfo(providerName, isImageModel = false) {
                const providerSelectId = isImageModel ? 'snn_openrouter_image_model_provider' : 'snn_openrouter_model_provider';
                const providerInfoId = isImageModel ? 'openrouter-image-provider-info' : 'openrouter-provider-info';
                
                const providerSelect = document.getElementById(providerSelectId);
                const providerInfo = document.getElementById(providerInfoId);
                
                if (!providerSelect || !providerInfo) return;
                
                providerInfo.style.display = 'none';
                
                if (!providerName) return;
                
                const endpoints = providerSelect.dataset.endpoints ? JSON.parse(providerSelect.dataset.endpoints) : [];
                const selectedEndpoint = endpoints.find(ep => ep.provider_name === providerName);
                
                if (!selectedEndpoint) return;
                
                const infoList = providerInfo.querySelector('ul');
                if (!infoList) return;
                
                infoList.innerHTML = '';
                
                // Provider name
                infoList.innerHTML += `<li><strong><?php esc_html_e('Provider:', 'snn'); ?></strong> ${selectedEndpoint.provider_name || 'N/A'}</li>`;
                
                // Pricing
                if (selectedEndpoint.pricing) {
                    const promptCost = selectedEndpoint.pricing.prompt ? `$${(parseFloat(selectedEndpoint.pricing.prompt) * 1000000).toFixed(6)}/M` : 'N/A';
                    const completionCost = selectedEndpoint.pricing.completion ? `$${(parseFloat(selectedEndpoint.pricing.completion) * 1000000).toFixed(6)}/M` : 'N/A';
                    infoList.innerHTML += `<li><strong><?php esc_html_e('Prompt Cost:', 'snn'); ?></strong> ${promptCost}</li>`;
                    infoList.innerHTML += `<li><strong><?php esc_html_e('Completion Cost:', 'snn'); ?></strong> ${completionCost}</li>`;
                }
                
                // Latency
                if (selectedEndpoint.latency_last_30m) {
                    if (selectedEndpoint.latency_last_30m.p50) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Latency (P50):', 'snn'); ?></strong> ${(selectedEndpoint.latency_last_30m.p50 * 1000).toFixed(0)}ms</li>`;
                    }
                    if (selectedEndpoint.latency_last_30m.p90) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Latency (P90):', 'snn'); ?></strong> ${(selectedEndpoint.latency_last_30m.p90 * 1000).toFixed(0)}ms</li>`;
                    }
                    if (selectedEndpoint.latency_last_30m.p99) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Latency (P99):', 'snn'); ?></strong> ${(selectedEndpoint.latency_last_30m.p99 * 1000).toFixed(0)}ms</li>`;
                    }
                }
                
                // Throughput (tokens/second)
                if (selectedEndpoint.throughput_last_30m) {
                    if (selectedEndpoint.throughput_last_30m.p50) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Throughput (P50):', 'snn'); ?></strong> ${selectedEndpoint.throughput_last_30m.p50} tok/s</li>`;
                    }
                    if (selectedEndpoint.throughput_last_30m.p90) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Throughput (P90):', 'snn'); ?></strong> ${selectedEndpoint.throughput_last_30m.p90} tok/s</li>`;
                    }
                    if (selectedEndpoint.throughput_last_30m.p99) {
                        infoList.innerHTML += `<li><strong><?php esc_html_e('Throughput (P99):', 'snn'); ?></strong> ${selectedEndpoint.throughput_last_30m.p99} tok/s</li>`;
                    }
                }
                
                // Quantization
                if (selectedEndpoint.quantization) {
                    infoList.innerHTML += `<li><strong><?php esc_html_e('Quantization:', 'snn'); ?></strong> ${selectedEndpoint.quantization}</li>`;
                }
                
                // Supported parameters
                if (selectedEndpoint.supported_parameters && selectedEndpoint.supported_parameters.length > 0) {
                    infoList.innerHTML += `<li><strong><?php esc_html_e('Supported Parameters:', 'snn'); ?></strong> ${selectedEndpoint.supported_parameters.join(', ')}</li>`;
                }
                
                providerInfo.style.display = 'block';
            }

            if (enableCheckbox && providerSelect) {
                enableCheckbox.addEventListener('change', toggleSettingsVisibility);
                providerSelect.addEventListener('change', toggleSettingsVisibility);
                toggleSettingsVisibility();
            }

            // Add event listeners for model input changes to update features
            if (openrouterModelInput) {
                openrouterModelInput.addEventListener('input', (e) => {
                    displayOpenRouterModelFeatures(e.target.value);
                });
                // Fetch providers only when user selects/confirms a model (on blur or change)
                openrouterModelInput.addEventListener('change', (e) => {
                    if (e.target.value && e.target.value.includes('/')) {
                        fetchModelProviders(e.target.value, false);
                    }
                });
            }
            if (openrouterImageModelInput) {
                openrouterImageModelInput.addEventListener('input', (e) => {
                    displayOpenRouterImageModelFeatures(e.target.value);
                });
                // Fetch providers only when user selects/confirms a model (on blur or change)
                openrouterImageModelInput.addEventListener('change', (e) => {
                    if (e.target.value && e.target.value.includes('/')) {
                        fetchModelProviders(e.target.value, true);
                    }
                });
            }

            // Add event listeners for provider selection changes
            const providerSelectEl = document.getElementById('snn_openrouter_model_provider');
            if (providerSelectEl) {
                providerSelectEl.addEventListener('change', (e) => {
                    displayProviderInfo(e.target.value, false);
                });
            }
            
            const imageProviderSelectEl = document.getElementById('snn_openrouter_image_model_provider');
            if (imageProviderSelectEl) {
                imageProviderSelectEl.addEventListener('change', (e) => {
                    displayProviderInfo(e.target.value, true);
                });
            }

            // Fetch providers for currently selected models on page load
            if (openrouterModelInput && openrouterModelInput.value) {
                fetchModelProviders(openrouterModelInput.value, false);
            }
            if (openrouterImageModelInput && openrouterImageModelInput.value) {
                fetchModelProviders(openrouterImageModelInput.value, true);
            }


            const addPresetButton = document.getElementById('snn-ai-add-preset');
            const resetPresetButton = document.getElementById('snn-ai-reset-presets');
            const presetsTableBody = document.querySelector('#snn-ai-action-presets-table tbody');

            function updatePresetIndices() {
                if (!presetsTableBody) return;
                const rows = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row');
                rows.forEach((row, index) => {
                    const nameInput = row.querySelector('.preset-name-input');
                    const promptInput = row.querySelector('.preset-prompt-input');
                    if (nameInput) nameInput.name = `snn_ai_action_presets[${index}][name]`;
                    if (promptInput) promptInput.name = `snn_ai_action_presets[${index}][prompt]`;
                });
            }

            function createPresetRow(preset, index) {
                const row = document.createElement('tr');
                row.className = 'snn-ai-action-preset-row';
                row.setAttribute('draggable', 'true');
                row.innerHTML = `
                    <td class="snn-ai-drag-handle" style="padding:0; width:30px; text-align:center; cursor:move; font-size:30px">⬍</td>
                    <td style="padding:2px">
                        <input
                            type="text"
                            name="snn_ai_action_presets[${index}][name]"
                            value="${preset.name.replace(/"/g, '&quot;')}"
                            placeholder="<?php echo esc_js(__('Action Name', 'snn')); ?>"
                            class="regular-text preset-name-input" />
                    </td>
                    <td style="padding:2px">
                        <textarea
                            name="snn_ai_action_presets[${index}][prompt]"
                            rows="2"
                            placeholder="<?php echo esc_js(__('Action Prompt', 'snn')); ?>"
                            class="regular-text preset-prompt-input">${preset.prompt}</textarea>
                    </td>
                    <td style="padding:2px">
                        <button type="button" class="button snn-ai-remove-preset"><?php echo esc_js(__('Remove', 'snn')); ?></button>
                    </td>
                `;
                return row;
            }

            if (addPresetButton && presetsTableBody) {
                addPresetButton.addEventListener('click', function() {
                    const newIndex = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').length;
                    const newPreset = { name: '', prompt: '' };
                    const row = createPresetRow(newPreset, newIndex);
                    presetsTableBody.appendChild(row);
                    updatePresetIndices();
                });
            }

            if (resetPresetButton && presetsTableBody) {
                resetPresetButton.addEventListener('click', function() {
                    if (confirm('<?php echo esc_js(__('Are you sure you want to reset all presets to their defaults? This cannot be undone.', 'snn')); ?>')) {
                        presetsTableBody.innerHTML = '';
                        const defaultPresets = <?php echo json_encode($default_presets); ?>;
                        defaultPresets.forEach((preset, index) => {
                            const row = createPresetRow(preset, index);
                            presetsTableBody.appendChild(row);
                        });
                        updatePresetIndices();
                    }
                });
            }

            if (presetsTableBody) {
                presetsTableBody.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('snn-ai-remove-preset')) {
                        e.preventDefault();
                        const row = e.target.closest('tr.snn-ai-action-preset-row');
                        if (row) {
                            row.remove();
                            updatePresetIndices();
                        }
                    }
                });
            }

            let draggingRow = null;
            if (presetsTableBody) {
                presetsTableBody.addEventListener('dragstart', (e) => {
                    const target = e.target.closest('tr.snn-ai-action-preset-row');
                    if (target) {
                        draggingRow = target;
                        e.dataTransfer.setData('text/plain', '');
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });
                presetsTableBody.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const target = e.target.closest('tr.snn-ai-action-preset-row');
                    if (target && target !== draggingRow) {
                        const bounding = target.getBoundingClientRect();
                        const offset = bounding.y + bounding.height / 2;
                        if (e.clientY - offset > 0) {
                            if (target.nextSibling !== draggingRow) {
                                target.parentNode.insertBefore(draggingRow, target.nextSibling);
                            }
                        } else {
                            if (target !== draggingRow.nextSibling) {
                                target.parentNode.insertBefore(draggingRow, target);
                            }
                        }
                    }
                });
                presetsTableBody.addEventListener('dragend', () => {
                    draggingRow = null;
                    updatePresetIndices();
                });
            }
            updatePresetIndices();

            // Import/Export Logic
            const exportButton = document.getElementById('snn-ai-export-button');
            const importButton = document.getElementById('snn-ai-import-button');
            const exportArea = document.getElementById('snn-ai-export-area');
            const importArea = document.getElementById('snn-ai-import-area');
            const exportTextarea = document.getElementById('snn-ai-export-textarea');
            const importTextarea = document.getElementById('snn-ai-import-textarea');
            const importApplyButton = document.getElementById('snn-ai-import-apply-button');
            const importStatus = document.getElementById('snn-ai-import-status');

            if (exportButton) {
                exportButton.addEventListener('click', () => {
                    const presets = [];
                    presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').forEach(row => {
                        const name = row.querySelector('.preset-name-input').value.trim();
                        const prompt = row.querySelector('.preset-prompt-input').value.trim();
                        if (name && prompt) {
                            presets.push({ name, prompt });
                        }
                    });
                    exportTextarea.value = JSON.stringify(presets, null, 2);
                    exportArea.style.display = 'block';
                    importArea.style.display = 'none';
                    exportTextarea.select();
                });
            }

            if (importButton) {
                importButton.addEventListener('click', () => {
                    importArea.style.display = 'block';
                    exportArea.style.display = 'none';
                    importStatus.textContent = '';
                    importTextarea.value = '';
                });
            }

            if (importApplyButton) {
                importApplyButton.addEventListener('click', () => {
                    const jsonString = importTextarea.value.trim();
                    if (!jsonString) {
                        importStatus.textContent = '<?php echo esc_js(__('Textarea is empty.', 'snn')); ?>';
                        return;
                    }
                    try {
                        const importedPresets = JSON.parse(jsonString);
                        if (!Array.isArray(importedPresets)) {
                            throw new Error('<?php echo esc_js(__('Data is not a valid array.', 'snn')); ?>');
                        }

                        const existingPresets = new Set();
                        presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').forEach(row => {
                            const name = row.querySelector('.preset-name-input').value.trim().toLowerCase();
                            const prompt = row.querySelector('.preset-prompt-input').value.trim().toLowerCase();
                            existingPresets.add(`${name}|||${prompt}`);
                        });

                        let addedCount = 0;
                        let skippedCount = 0;
                        importedPresets.forEach(preset => {
                            if (preset && typeof preset.name === 'string' && typeof preset.prompt === 'string') {
                                const newName = preset.name.trim();
                                const newPrompt = preset.prompt.trim();
                                const presetKey = `${newName.toLowerCase()}|||${newPrompt.toLowerCase()}`;
                                if (newName && newPrompt && !existingPresets.has(presetKey)) {
                                    const newIndex = presetsTableBody.querySelectorAll('tr.snn-ai-action-preset-row').length;
                                    const row = createPresetRow({ name: newName, prompt: newPrompt }, newIndex);
                                    presetsTableBody.appendChild(row);
                                    existingPresets.add(presetKey);
                                    addedCount++;
                                } else {
                                    skippedCount++;
                                }
                            }
                        });

                        updatePresetIndices();
                        importStatus.textContent = `<?php echo esc_js(__('Import complete!', 'snn')); ?> ${addedCount} <?php echo esc_js(__('presets added', 'snn')); ?>, ${skippedCount} <?php echo esc_js(__('duplicates skipped.', 'snn')); ?>`;

                    } catch (error) {
                        importStatus.textContent = `<?php echo esc_js(__('Invalid JSON format.', 'snn')); ?> ${error.message}`;
                        console.error("Import error:", error);
                    }
                });
            }

            // ================================================================
            // Test Connection — Logging system (similar to SMTP test)
            // ================================================================
            const testBtn = document.getElementById('snn_run_ai_test');
            const testSpinner = document.getElementById('snn_ai_test_spinner');
            const logWrap = document.getElementById('snn_ai_log_wrap');
            const logEntries = document.getElementById('snn_ai_log_entries');
            const logBox = document.getElementById('snn_ai_log_box');
            const settingsBox = document.getElementById('snn_ai_settings_box');
            const settingsTable = document.getElementById('snn_ai_settings_table');
            const copyBtn = document.getElementById('snn_ai_copy_btn');

            let testLogData = [];

            function escHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function colorForType(type) {
                switch (type) {
                    case 'success': return '#4ec94e';
                    case 'error':   return '#f47878';
                    case 'warning': return '#f5c842';
                    default:        return '#8bbcf5';
                }
            }

            function prefixForType(type) {
                switch (type) {
                    case 'success': return '[OK]    ';
                    case 'error':   return '[ERROR] ';
                    case 'warning': return '[WARN]  ';
                    default:        return '[INFO]  ';
                }
            }

            function renderTestLog(logs) {
                testLogData = logs;
                let html = '';
                logs.forEach(function(entry) {
                    const color  = colorForType(entry.type);
                    const prefix = prefixForType(entry.type);
                    html += '<div style="color:' + color + '; white-space: pre-wrap; word-break: break-all;">'
                          + escHtml(prefix + entry.message)
                          + '</div>';
                });
                logEntries.innerHTML = html;
                if (logBox) logBox.scrollTop = logBox.scrollHeight;
            }

            function renderTestSettings(settings, success) {
                const borderColor = success ? '#4ec94e' : '#f47878';
                const bgColor     = success ? '#f0fff0' : '#fff0f0';
                settingsBox.style.borderColor = borderColor;
                settingsBox.style.background = bgColor;
                let rows = '';
                for (const key in settings) {
                    if (settings.hasOwnProperty(key)) {
                        rows += '<tr>'
                              + '<td style="padding: 2px 12px 2px 0; color: #555; font-weight: 600;">' + escHtml(key) + '</td>'
                              + '<td style="padding: 2px 0;">' + escHtml(settings[key]) + '</td>'
                              + '</tr>';
                    }
                }
                settingsTable.innerHTML = rows;
            }

            function addTestLog(type, message) {
                const entry = {type: type, message: message};
                testLogData.push(entry);
                const color  = colorForType(type);
                const prefix = prefixForType(type);
                const div = document.createElement('div');
                div.style.cssText = 'color:' + color + '; white-space: pre-wrap; word-break: break-all;';
                div.textContent = prefix + message;
                logEntries.appendChild(div);
                if (logBox) logBox.scrollTop = logBox.scrollHeight;
            }

            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    testBtn.disabled = true;
                    testSpinner.style.display = 'inline-block';
                    testLogData = [];
                    logEntries.innerHTML = '';
                    logWrap.style.display = 'block';
                    addTestLog('info', 'Initiating connection test...');
                    addTestLog('info', 'Using current saved settings (save first if you made changes).');

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'snn_ai_test_connection',
                            nonce: '<?php echo wp_create_nonce('snn_ai_test_connection_nonce'); ?>'
                        })
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        renderTestLog(data.logs);
                        if (data.settings && Object.keys(data.settings).length > 0) {
                            renderTestSettings(data.settings, data.success);
                        }
                    })
                    .catch(function(error) {
                        addTestLog('error', 'Request failed: ' + error.message);
                        addTestLog('error', 'Tip: Check your WordPress admin-ajax.php is reachable and the server is running.');
                        settingsBox.style.borderColor = '#f47878';
                        settingsBox.style.background = '#fff0f0';
                    })
                    .finally(function() {
                        testBtn.disabled = false;
                        testSpinner.style.display = 'none';
                        if (logBox) logBox.scrollTop = logBox.scrollHeight;
                    });
                });
            }

            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const text = testLogData.map(function(e) {
                        return prefixForType(e.type) + e.message;
                    }).join('\n');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function() {
                            copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'snn')); ?>';
                            setTimeout(function() { copyBtn.textContent = '<?php echo esc_js(__('Copy', 'snn')); ?>'; }, 2000);
                        });
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.cssText = 'position:fixed;opacity:0;';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'snn')); ?>';
                        setTimeout(function() { copyBtn.textContent = '<?php echo esc_js(__('Copy', 'snn')); ?>'; }, 2000);
                    }
                });
            }
        });
        </script>
    </div>
    <?php
}
