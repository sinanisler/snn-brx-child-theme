<?php
/**
 * AI Agent Functions
 *
 * File: ai-agent.php
 *
 * Purpose: This file provides the backend AI agent functionality with tool calling capabilities.
 * It handles multiple AI requests, tool execution, and manages the conversation flow between
 * the frontend chat interface and the AI API. It uses WordPress Abilities API for tools.
 *
 * Features:
 * - Automatic tool generation from WordPress Abilities
 * - Multi-turn conversation handling
 * - WordPress Abilities integration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register AJAX endpoints for AI agent
 */
add_action('wp_ajax_snn_ai_agent_chat', 'snn_ai_agent_chat_handler');

/**
 * Main AI agent chat handler
 * Processes user messages, handles tool calls, and returns AI responses
 */
function snn_ai_agent_chat_handler() {
    // Verify nonce for security
    check_ajax_referer('snn_ai_agent_nonce', 'nonce');

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
        return;
    }

    // Get the AI configuration
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

    if (empty($messages) || !is_array($messages)) {
        wp_send_json_error(['message' => 'Invalid messages format.']);
        return;
    }

    // Prepare system message for WordPress context
    $system_message = [
        'role' => 'system',
        'content' => $config['systemPrompt'] . "\n\nYou are working within a WordPress environment and have access to various tools to help manage the website. You can create, read, update posts, pages, and other WordPress content. Always use the appropriate tool when the user asks for WordPress-related operations."
    ];

    // Prepend system message if not already present
    if (empty($messages) || $messages[0]['role'] !== 'system') {
        array_unshift($messages, $system_message);
    }

    // Define available tools from WordPress Abilities
    $tools = $use_tools ? snn_get_ai_tools_from_abilities() : null;

    // Make API request
    $response = snn_make_ai_agent_request($config, $messages, $tools);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
        return;
    }

    // Handle tool calls if present
    $assistant_message = $response['choices'][0]['message'];

    if (isset($assistant_message['tool_calls']) && !empty($assistant_message['tool_calls'])) {
        // Execute tool calls
        $tool_results = snn_execute_tool_calls($assistant_message['tool_calls']);

        // Return both the assistant message and tool results
        wp_send_json_success([
            'message' => $assistant_message,
            'tool_results' => $tool_results,
            'requires_continuation' => true
        ]);
    } else {
        // No tool calls, return the final response
        wp_send_json_success([
            'message' => $assistant_message,
            'requires_continuation' => false
        ]);
    }
}

/**
 * Make AI API request with tool support
 */
function snn_make_ai_agent_request($config, $messages, $tools = null) {
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

    // Add OpenRouter specific headers if using OpenRouter
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
        return new WP_Error('api_error', 'AI API returned error: ' . $response_code . ' - ' . $response_body);
    }

    $data = json_decode($response_body, true);

    if (!isset($data['choices']) || empty($data['choices'])) {
        return new WP_Error('invalid_response', 'Invalid response from AI API.');
    }

    return $data;
}

/**
 * Get AI tools from WordPress Abilities API
 */
function snn_get_ai_tools_from_abilities() {
    // Check if WordPress Abilities API is available
    if (!function_exists('wp_get_abilities')) {
        return [];
    }

    $abilities = wp_get_abilities();
    $tools = [];

    foreach ($abilities as $ability) {
        // Only include abilities that the current user has permission to execute
        if (!$ability->check_permission()) {
            continue;
        }

        $tool = [
            'type' => 'function',
            'function' => [
                'name' => str_replace('/', '_', $ability->get_name()), // Convert snn/ability to snn_ability
                'description' => $ability->get_description(),
            ]
        ];

        // Add input schema if it exists
        $input_schema = $ability->get_input_schema();
        if ($input_schema) {
            $tool['function']['parameters'] = $input_schema;
        } else {
            $tool['function']['parameters'] = [
                'type' => 'object',
                'properties' => [],
            ];
        }

        $tools[] = $tool;
    }

    return $tools;
}

/**
 * Execute tool calls from AI response
 */
function snn_execute_tool_calls($tool_calls) {
    $results = [];

    foreach ($tool_calls as $tool_call) {
        $function_name = $tool_call['function']['name'];
        $arguments = json_decode($tool_call['function']['arguments'], true);

        $result = snn_execute_ability_tool($function_name, $arguments);

        $results[] = [
            'tool_call_id' => $tool_call['id'],
            'role' => 'tool',
            'name' => $function_name,
            'content' => wp_json_encode($result)
        ];
    }

    return $results;
}

/**
 * Execute an ability tool
 */
function snn_execute_ability_tool($function_name, $arguments) {
    // Convert function name back to ability name (snn_ability to snn/ability)
    $ability_name = str_replace('_', '/', $function_name);

    // Check if WordPress Abilities API is available
    if (!function_exists('wp_get_ability')) {
        return [
            'error' => 'WordPress Abilities API not available.'
        ];
    }

    // Get the ability
    $ability = wp_get_ability($ability_name);

    if (!$ability) {
        return [
            'error' => 'Ability "' . $ability_name . '" not found.'
        ];
    }

    // Check permission
    if (!$ability->check_permission()) {
        return [
            'error' => 'Permission denied for ability "' . $ability_name . '".'
        ];
    }

    // Execute the ability
    try {
        $result = $ability->execute($arguments);

        // Handle WP_Error
        if (is_wp_error($result)) {
            return [
                'error' => $result->get_error_message(),
                'error_code' => $result->get_error_code(),
            ];
        }

        return $result;
    } catch (Exception $e) {
        return [
            'error' => 'Exception executing ability: ' . $e->getMessage()
        ];
    }
}
