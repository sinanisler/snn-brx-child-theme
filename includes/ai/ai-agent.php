<?php
/**
 * AI Agent Functions
 *
 * File: ai-agent.php
 *
 * Purpose: Backend AI agent functionality with WordPress Abilities integration.
 * Handles AI requests, tool execution via WordPress Abilities API, and manages
 * conversation flow between frontend chat interface and AI API.
 *
 * Features:
 * - Automatic tool generation from WordPress Abilities
 * - Multi-turn conversation handling with tool calls
 * - Proper error handling and logging
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX endpoint for AI agent
 */
add_action('wp_ajax_snn_ai_agent_chat', 'snn_ai_agent_chat_handler');

/**
 * Main AI agent chat handler
 */
function snn_ai_agent_chat_handler() {
    try {
        error_log('=== AI Agent: Request started ===');

        // Verify nonce
        check_ajax_referer('snn_ai_agent_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('AI Agent: Unauthorized access');
            wp_send_json_error(['message' => 'Unauthorized access.']);
            return;
        }

        // Get AI configuration
        if (!function_exists('snn_get_ai_api_config')) {
            wp_send_json_error(['message' => 'AI configuration not available.']);
            return;
        }

        $config = snn_get_ai_api_config();

        if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
            wp_send_json_error(['message' => 'AI API not configured properly.']);
            return;
        }

        // Get request data
        $messages = isset($_POST['messages']) ? json_decode(stripslashes($_POST['messages']), true) : [];
        $use_tools = isset($_POST['use_tools']) ? filter_var($_POST['use_tools'], FILTER_VALIDATE_BOOLEAN) : true;

        if (!is_array($messages) || empty($messages)) {
            wp_send_json_error(['message' => 'Invalid messages format.']);
            return;
        }

        error_log('AI Agent: Processing ' . count($messages) . ' messages, tools=' . ($use_tools ? 'enabled' : 'disabled'));

        // Add system message if not present
        if (!isset($messages[0]['role']) || $messages[0]['role'] !== 'system') {
            $system_content = $config['systemPrompt'] . "\n\nYou are working within a WordPress environment. " .
                            "Use the available tools to manage posts, users, and other WordPress content when needed.";

            array_unshift($messages, [
                'role' => 'system',
                'content' => $system_content
            ]);
        }

        // Get tools from WordPress Abilities
        $tools = $use_tools ? snn_get_tools_from_abilities() : null;

        if ($tools !== null) {
            error_log('AI Agent: Loaded ' . count($tools) . ' tools');
        }

        // Make API request
        $response = snn_make_ai_request($config, $messages, $tools);

        if (is_wp_error($response)) {
            error_log('AI Agent: API error - ' . $response->get_error_message());
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Get assistant message
        $assistant_message = $response['choices'][0]['message'] ?? null;

        if (!$assistant_message) {
            wp_send_json_error(['message' => 'Invalid API response structure.']);
            return;
        }

        // Check if there are tool calls
        $has_tool_calls = !empty($assistant_message['tool_calls']);

        if ($has_tool_calls) {
            error_log('AI Agent: Processing ' . count($assistant_message['tool_calls']) . ' tool calls');

            // Execute tool calls
            $tool_results = snn_execute_tool_calls($assistant_message['tool_calls']);

            // Return assistant message with tool calls and results
            wp_send_json_success([
                'message' => $assistant_message,
                'tool_results' => $tool_results,
                'requires_continuation' => true
            ]);
        } else {
            error_log('AI Agent: Final response received');

            // No tool calls - final response
            wp_send_json_success([
                'message' => $assistant_message,
                'requires_continuation' => false
            ]);
        }

    } catch (Exception $e) {
        error_log('AI Agent Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log('AI Agent Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(['message' => 'Fatal error occurred. Please check configuration.']);
    }
}

/**
 * Make API request to AI service
 */
function snn_make_ai_request($config, $messages, $tools = null) {
    $body = [
        'model' => $config['model'],
        'messages' => $messages,
    ];

    // Add tools if provided
    if ($tools !== null && !empty($tools)) {
        $body['tools'] = $tools;
        $body['tool_choice'] = 'auto';
    }

    // Add response format if specified
    if (!empty($config['responseFormat'])) {
        $body['response_format'] = $config['responseFormat'];
    }

    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $config['apiKey'],
    ];

    // OpenRouter specific headers
    if (strpos($config['apiEndpoint'], 'openrouter.ai') !== false) {
        $headers['HTTP-Referer'] = get_site_url();
        $headers['X-Title'] = get_bloginfo('name');
    }

    $response = wp_remote_post($config['apiEndpoint'], [
        'headers' => $headers,
        'body' => wp_json_encode($body),
        'timeout' => 60,
        'sslverify' => true,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log('AI Agent: API returned ' . $response_code . ' - ' . $response_body);
        return new WP_Error('api_error', 'AI API error: ' . $response_code);
    }

    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Failed to parse API response.');
    }

    if (!isset($data['choices']) || empty($data['choices'])) {
        return new WP_Error('invalid_response', 'Invalid API response structure.');
    }

    return $data;
}

/**
 * Get tools from WordPress Abilities API
 */
function snn_get_tools_from_abilities() {
    // Check if Abilities API is available
    if (!function_exists('wp_get_abilities')) {
        error_log('AI Agent: wp_get_abilities not available');
        return [];
    }

    $abilities = wp_get_abilities();

    if (!is_array($abilities) || empty($abilities)) {
        error_log('AI Agent: No abilities registered');
        return [];
    }

    $tools = [];

    foreach ($abilities as $ability) {
        // Skip if user doesn't have permission
        if (!$ability->check_permission()) {
            continue;
        }

        $ability_name = $ability->get_name();

        // Convert ability name to tool function name
        // snn/get-user-count becomes snn__get_user_count
        $function_name = str_replace(['/', '-'], ['__', '_'], $ability_name);

        $tool = [
            'type' => 'function',
            'function' => [
                'name' => $function_name,
                'description' => $ability->get_description() ?: 'WordPress ability: ' . $ability_name,
            ]
        ];

        // Add input schema
        $input_schema = $ability->get_input_schema();
        if ($input_schema && is_array($input_schema)) {
            $tool['function']['parameters'] = $input_schema;
        } else {
            $tool['function']['parameters'] = [
                'type' => 'object',
                'properties' => [],
            ];
        }

        $tools[] = $tool;

        error_log('AI Agent: Registered tool "' . $function_name . '" for ability "' . $ability_name . '"');
    }

    return $tools;
}

/**
 * Execute tool calls from AI
 */
function snn_execute_tool_calls($tool_calls) {
    if (!is_array($tool_calls)) {
        return [];
    }

    $results = [];

    foreach ($tool_calls as $tool_call) {
        $tool_id = $tool_call['id'] ?? uniqid('tool_');
        $function_name = $tool_call['function']['name'] ?? '';
        $arguments_json = $tool_call['function']['arguments'] ?? '{}';

        // Parse arguments
        $arguments = json_decode($arguments_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $arguments = [];
        }

        error_log('AI Agent: Executing tool "' . $function_name . '"');

        // Execute the tool
        $result = snn_execute_ability_tool($function_name, $arguments);

        $results[] = [
            'tool_call_id' => $tool_id,
            'role' => 'tool',
            'name' => $function_name,
            'content' => wp_json_encode($result)
        ];
    }

    return $results;
}

/**
 * Execute a single ability tool
 */
function snn_execute_ability_tool($function_name, $arguments) {
    // Convert function name back to ability name
    // snn__get_user_count becomes snn/get-user-count
    $ability_name = str_replace(['__', '_'], ['/', '-'], $function_name);

    error_log('AI Agent: Mapping "' . $function_name . '" to ability "' . $ability_name . '"');

    // Check if Abilities API is available
    if (!function_exists('wp_get_ability')) {
        return ['error' => 'WordPress Abilities API not available.'];
    }

    // Get the ability
    $ability = wp_get_ability($ability_name);

    if (!$ability) {
        error_log('AI Agent: Ability "' . $ability_name . '" not found');
        return ['error' => 'Ability "' . $ability_name . '" not found.'];
    }

    // Check permission
    if (!$ability->check_permission()) {
        error_log('AI Agent: Permission denied for "' . $ability_name . '"');
        return ['error' => 'Permission denied for this operation.'];
    }

    // Execute ability
    try {
        $result = $ability->execute($arguments);

        // Handle WP_Error
        if (is_wp_error($result)) {
            return [
                'error' => $result->get_error_message(),
                'error_code' => $result->get_error_code(),
            ];
        }

        error_log('AI Agent: Tool "' . $function_name . '" executed successfully');

        return $result;

    } catch (Exception $e) {
        error_log('AI Agent: Exception in tool execution - ' . $e->getMessage());
        return ['error' => 'Execution failed: ' . $e->getMessage()];
    }
}
