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
    // Enable detailed error reporting for debugging
    $detailed_error = null;

    try {
        error_log('=== AI Agent: Request started ===');
        error_log('AI Agent: POST data: ' . print_r($_POST, true));

        // Step 1: Verify nonce
        try {
            check_ajax_referer('snn_ai_agent_nonce', 'nonce');
            error_log('AI Agent: Nonce verified successfully');
        } catch (Exception $e) {
            $detailed_error = 'Nonce verification failed: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => $detailed_error]);
            return;
        }

        // Step 2: Check permissions
        if (!current_user_can('manage_options')) {
            $detailed_error = 'User lacks manage_options capability';
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Unauthorized access.']);
            return;
        }
        error_log('AI Agent: Permissions verified');

        // Step 3: Get AI configuration
        if (!function_exists('snn_get_ai_api_config')) {
            $detailed_error = 'Function snn_get_ai_api_config does not exist';
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'AI configuration function not found. Check ai-api.php is loaded.']);
            return;
        }

        try {
            $config = snn_get_ai_api_config();
            error_log('AI Agent: Config retrieved: ' . print_r(array_keys($config), true));
        } catch (Exception $e) {
            $detailed_error = 'Config retrieval failed: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Failed to get AI configuration: ' . $e->getMessage()]);
            return;
        }

        // Step 4: Validate configuration
        if (empty($config['apiKey'])) {
            $detailed_error = 'API key is empty';
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'API key not configured. Please configure in settings.']);
            return;
        }

        if (empty($config['apiEndpoint'])) {
            $detailed_error = 'API endpoint is empty';
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'API endpoint not configured. Please configure in settings.']);
            return;
        }

        error_log('AI Agent: Using endpoint: ' . $config['apiEndpoint']);
        error_log('AI Agent: Using model: ' . ($config['model'] ?? 'not set'));

        // Step 5: Parse request data
        $messages = null;
        $use_tools = true;

        try {
            if (!isset($_POST['messages'])) {
                throw new Exception('messages parameter missing from POST');
            }

            $messages_raw = stripslashes($_POST['messages']);
            $messages = json_decode($messages_raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode failed: ' . json_last_error_msg());
            }

            $use_tools = isset($_POST['use_tools']) ? filter_var($_POST['use_tools'], FILTER_VALIDATE_BOOLEAN) : true;

            error_log('AI Agent: Parsed ' . count($messages) . ' messages');

        } catch (Exception $e) {
            $detailed_error = 'Message parsing failed: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Invalid message format: ' . $e->getMessage()]);
            return;
        }

        if (!is_array($messages) || empty($messages)) {
            $detailed_error = 'Messages is not array or empty';
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Messages must be a non-empty array.']);
            return;
        }

        error_log('AI Agent: Processing ' . count($messages) . ' messages, tools=' . ($use_tools ? 'enabled' : 'disabled'));

        // Step 6: Add system message if not present
        try {
            if (!isset($messages[0]['role']) || $messages[0]['role'] !== 'system') {
                $system_content = $config['systemPrompt'] . "\n\n" .
                                "You are an AI assistant with access to WordPress tools. " .
                                "You have the following abilities:\n" .
                                "- List all posts, pages, and custom post types\n" .
                                "- Get counts of posts, pages, users, and taxonomies\n" .
                                "- Create new posts with specified title, content, and status\n" .
                                "- Analyze post content for SEO metrics\n" .
                                "- List all registered post types on the site\n\n" .
                                "When a user asks about posts, pages, or content, ALWAYS use the appropriate tool to get accurate, up-to-date information. " .
                                "Never guess or make assumptions about what content exists - always use the tools to fetch the actual data.";

                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $system_content
                ]);
                error_log('AI Agent: Added system message');
            }
        } catch (Exception $e) {
            $detailed_error = 'Failed to add system message: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => $detailed_error]);
            return;
        }

        // Step 7: Get tools from WordPress Abilities
        $tools = null;
        try {
            if ($use_tools) {
                $tools = snn_get_tools_from_abilities();
                error_log('AI Agent: Loaded ' . count($tools) . ' tools');
            } else {
                error_log('AI Agent: Tools disabled for this request');
            }
        } catch (Exception $e) {
            // Don't fail if tools can't be loaded, just log and continue without tools
            error_log('AI Agent: Warning - Failed to load tools: ' . $e->getMessage());
            $tools = null;
        }

        // Step 8: Make API request
        try {
            error_log('AI Agent: Making API request...');
            $response = snn_make_ai_request($config, $messages, $tools);

            if (is_wp_error($response)) {
                $detailed_error = 'API request failed: ' . $response->get_error_message();
                error_log('AI Agent: ' . $detailed_error);
                wp_send_json_error(['message' => 'AI API error: ' . $response->get_error_message()]);
                return;
            }

            error_log('AI Agent: API request successful');

        } catch (Exception $e) {
            $detailed_error = 'API request exception: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Failed to contact AI API: ' . $e->getMessage()]);
            return;
        }

        // Step 9: Parse response
        try {
            if (!isset($response['choices']) || !is_array($response['choices']) || empty($response['choices'])) {
                throw new Exception('Response missing choices array');
            }

            $assistant_message = $response['choices'][0]['message'] ?? null;

            if (!$assistant_message || !is_array($assistant_message)) {
                throw new Exception('Invalid message structure in response');
            }

            error_log('AI Agent: Response parsed successfully');

        } catch (Exception $e) {
            $detailed_error = 'Response parsing failed: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            error_log('AI Agent: Raw response: ' . print_r($response, true));
            wp_send_json_error(['message' => 'Invalid API response: ' . $e->getMessage()]);
            return;
        }

        // Step 10: Handle tool calls or final response
        try {
            $has_tool_calls = isset($assistant_message['tool_calls']) && is_array($assistant_message['tool_calls']) && !empty($assistant_message['tool_calls']);

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
            $detailed_error = 'Tool handling failed: ' . $e->getMessage();
            error_log('AI Agent: ' . $detailed_error);
            wp_send_json_error(['message' => 'Tool execution error: ' . $e->getMessage()]);
            return;
        }

        error_log('=== AI Agent: Request completed successfully ===');

    } catch (Exception $e) {
        $detailed_error = 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        error_log('AI Agent Exception: ' . $detailed_error);
        error_log('AI Agent Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'Server error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')']);
    } catch (Error $e) {
        $detailed_error = 'Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        error_log('AI Agent Fatal Error: ' . $detailed_error);
        error_log('AI Agent Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'Fatal error: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . basename($e->getFile())]);
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
    try {
        // Check if Abilities API is available
        if (!function_exists('wp_get_abilities')) {
            error_log('AI Agent: wp_get_abilities function not available');
            return [];
        }

        error_log('AI Agent: Calling wp_get_abilities()');
        $abilities = wp_get_abilities();

        if (!is_array($abilities)) {
            error_log('AI Agent: wp_get_abilities() returned non-array: ' . gettype($abilities));
            return [];
        }

        if (empty($abilities)) {
            error_log('AI Agent: No abilities registered');
            return [];
        }

        error_log('AI Agent: Found ' . count($abilities) . ' abilities');
        $tools = [];

        foreach ($abilities as $index => $ability) {
            try {
                // Validate ability object
                if (!is_object($ability)) {
                    error_log('AI Agent: Ability at index ' . $index . ' is not an object');
                    continue;
                }

                if (!method_exists($ability, 'check_permission')) {
                    error_log('AI Agent: Ability at index ' . $index . ' missing check_permission method');
                    continue;
                }

                // Skip if user doesn't have permission
                if (!$ability->check_permission()) {
                    error_log('AI Agent: User lacks permission for ability at index ' . $index);
                    continue;
                }

                if (!method_exists($ability, 'get_name')) {
                    error_log('AI Agent: Ability at index ' . $index . ' missing get_name method');
                    continue;
                }

                $ability_name = $ability->get_name();

                if (empty($ability_name)) {
                    error_log('AI Agent: Ability at index ' . $index . ' has empty name');
                    continue;
                }

                // Convert ability name to tool function name
                // snn/get-user-count becomes snn__get_user_count
                $function_name = str_replace(['/', '-'], ['__', '_'], $ability_name);

                $tool = [
                    'type' => 'function',
                    'function' => [
                        'name' => $function_name,
                        'description' => '',
                    ]
                ];

                // Get description
                if (method_exists($ability, 'get_description')) {
                    $description = $ability->get_description();
                    $tool['function']['description'] = $description ?: 'WordPress ability: ' . $ability_name;
                } else {
                    $tool['function']['description'] = 'WordPress ability: ' . $ability_name;
                }

                // Add input schema
                if (method_exists($ability, 'get_input_schema')) {
                    $input_schema = $ability->get_input_schema();
                    if ($input_schema && is_array($input_schema)) {
                        $tool['function']['parameters'] = $input_schema;
                    } else {
                        $tool['function']['parameters'] = [
                            'type' => 'object',
                            'properties' => [],
                        ];
                    }
                } else {
                    $tool['function']['parameters'] = [
                        'type' => 'object',
                        'properties' => [],
                    ];
                }

                $tools[] = $tool;

                error_log('AI Agent: Registered tool "' . $function_name . '" for ability "' . $ability_name . '"');

            } catch (Exception $e) {
                error_log('AI Agent: Failed to process ability at index ' . $index . ': ' . $e->getMessage());
                continue;
            }
        }

        error_log('AI Agent: Successfully registered ' . count($tools) . ' tools');
        return $tools;

    } catch (Exception $e) {
        error_log('AI Agent: Exception in snn_get_tools_from_abilities: ' . $e->getMessage());
        return [];
    }
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
    // First replace __ with /, then replace remaining _ with -
    $ability_name = str_replace('__', '/', $function_name);
    $ability_name = str_replace('_', '-', $ability_name);

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
