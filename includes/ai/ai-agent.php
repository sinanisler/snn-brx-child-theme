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
    try {
        error_log('=== AI Agent: New request started ===');

        // Verify nonce for security
        check_ajax_referer('snn_ai_agent_nonce', 'nonce');

        // Check user permissions
        if (!current_user_can('manage_options')) {
            error_log('AI Agent: Unauthorized access attempt');
            wp_send_json_error(['message' => 'Unauthorized access.']);
            return;
        }

        // Get the AI configuration
        if (!function_exists('snn_get_ai_api_config')) {
            error_log('AI Agent: snn_get_ai_api_config function not found');
            wp_send_json_error(['message' => 'AI configuration not available.']);
            return;
        }

        $config = snn_get_ai_api_config();

        if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
            error_log('AI Agent: Missing API key or endpoint');
            wp_send_json_error(['message' => 'AI API not configured properly.']);
            return;
        }

        // Get request data
        $messages = isset($_POST['messages']) ? json_decode(stripslashes($_POST['messages']), true) : [];
        $use_tools = isset($_POST['use_tools']) ? filter_var($_POST['use_tools'], FILTER_VALIDATE_BOOLEAN) : true;

        error_log('AI Agent: Received ' . count($messages) . ' messages, use_tools=' . ($use_tools ? 'true' : 'false'));

        if (empty($messages) || !is_array($messages)) {
            error_log('AI Agent: Invalid messages format - ' . print_r($messages, true));
            wp_send_json_error(['message' => 'Invalid messages format.']);
            return;
        }

        // Prepare system message for WordPress context
        $system_message = [
            'role' => 'system',
            'content' => $config['systemPrompt'] . "\n\nYou are working within a WordPress environment and have access to various tools to help manage the website. You can create, read, update posts, pages, and other WordPress content. Always use the appropriate tool when the user asks for WordPress-related operations."
        ];

        // Prepend system message ONLY if not already present
        // Check if first message is a system message
        if (!isset($messages[0]) || $messages[0]['role'] !== 'system') {
            array_unshift($messages, $system_message);
            error_log('AI Agent: Added system message to conversation');
        } else {
            error_log('AI Agent: System message already present, skipping');
        }

        // Define available tools from WordPress Abilities
        $tools = $use_tools ? snn_get_ai_tools_from_abilities() : null;

        if ($tools !== null) {
            error_log('AI Agent: Loaded ' . count($tools) . ' tools from abilities');
        }

        // Make API request
        error_log('AI Agent: Making API request to ' . $config['apiEndpoint']);
        $response = snn_make_ai_agent_request($config, $messages, $tools);

        if (is_wp_error($response)) {
            error_log('AI Agent: API request failed - ' . $response->get_error_message());
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Validate response structure
        if (!isset($response['choices'][0]['message'])) {
            error_log('AI Agent: Invalid response structure - ' . print_r($response, true));
            wp_send_json_error(['message' => 'Invalid response structure from AI API.']);
            return;
        }

        // Handle tool calls if present
        $assistant_message = $response['choices'][0]['message'];

        if (isset($assistant_message['tool_calls']) && !empty($assistant_message['tool_calls'])) {
            error_log('AI Agent: Processing ' . count($assistant_message['tool_calls']) . ' tool calls');

            // Execute tool calls
            $tool_results = snn_execute_tool_calls($assistant_message['tool_calls']);

            error_log('AI Agent: Tool execution completed, sending results');

            // Return both the assistant message and tool results
            wp_send_json_success([
                'message' => $assistant_message,
                'tool_results' => $tool_results,
                'requires_continuation' => true
            ]);
        } else {
            error_log('AI Agent: No tool calls, sending final response');

            // No tool calls, return the final response
            wp_send_json_success([
                'message' => $assistant_message,
                'requires_continuation' => false
            ]);
        }

        error_log('=== AI Agent: Request completed successfully ===');

    } catch (Exception $e) {
        error_log('AI Agent Exception: ' . $e->getMessage());
        error_log('AI Agent Exception File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('AI Agent Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'Server error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log('AI Agent Fatal Error: ' . $e->getMessage());
        error_log('AI Agent Fatal Error File: ' . $e->getFile() . ':' . $e->getLine());
        error_log('AI Agent Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'Fatal error occurred. Check error logs for details.']);
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
        error_log('AI Agent: wp_get_abilities function not found');
        return [];
    }

    $abilities = wp_get_abilities();
    $tools = [];

    foreach ($abilities as $ability) {
        // Only include abilities that the current user has permission to execute
        if (!$ability->check_permission()) {
            continue;
        }

        $ability_name = $ability->get_name();

        // Convert ability name to function name
        // Replace ONLY the first slash with double underscore to preserve other underscores
        // Example: snn/get_user_count -> snn__get_user_count (reversible!)
        $function_name = str_replace('/', '__', $ability_name);

        $tool = [
            'type' => 'function',
            'function' => [
                'name' => $function_name,
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

        error_log('AI Agent: Registered tool "' . $function_name . '" for ability "' . $ability_name . '"');
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

        error_log('AI Agent: Executing tool "' . $function_name . '" with arguments: ' . print_r($arguments, true));

        $result = snn_execute_ability_tool($function_name, $arguments);

        error_log('AI Agent: Tool "' . $function_name . '" execution result: ' . print_r($result, true));

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
    // Convert function name back to ability name (snn__get_user_count to snn/get_user_count)
    // Replace double underscore back to slash
    $ability_name = str_replace('__', '/', $function_name);

    error_log('AI Agent: Converting function name "' . $function_name . '" to ability "' . $ability_name . '"');

    // Check if WordPress Abilities API is available
    if (!function_exists('wp_get_ability')) {
        error_log('AI Agent: wp_get_ability function not found');
        return [
            'error' => 'WordPress Abilities API not available.'
        ];
    }

    // Get the ability
    $ability = wp_get_ability($ability_name);

    if (!$ability) {
        error_log('AI Agent: Ability "' . $ability_name . '" not found');
        return [
            'error' => 'Ability "' . $ability_name . '" not found.'
        ];
    }

    // Check permission
    if (!$ability->check_permission()) {
        error_log('AI Agent: Permission denied for ability "' . $ability_name . '"');
        return [
            'error' => 'Permission denied for ability "' . $ability_name . '".'
        ];
    }

    // Execute the ability
    try {
        error_log('AI Agent: Executing ability "' . $ability_name . '"');
        $result = $ability->execute($arguments);

        // Handle WP_Error
        if (is_wp_error($result)) {
            error_log('AI Agent: Ability execution returned WP_Error: ' . $result->get_error_message());
            return [
                'error' => $result->get_error_message(),
                'error_code' => $result->get_error_code(),
            ];
        }

        error_log('AI Agent: Ability "' . $ability_name . '" executed successfully');
        return $result;
    } catch (Exception $e) {
        error_log('AI Agent: Exception executing ability "' . $ability_name . '": ' . $e->getMessage());
        error_log('AI Agent: Exception trace: ' . $e->getTraceAsString());
        return [
            'error' => 'Exception executing ability: ' . $e->getMessage()
        ];
    }
}
