<?php
/**
 * AI Agent Functions
 *
 * File: ai-agent.php
 *
 * Purpose: This file provides the backend AI agent functionality with tool calling capabilities.
 * It handles multiple AI requests, tool execution, and manages the conversation flow between
 * the frontend chat interface and the AI API.
 *
 * Features:
 * - Tool calling support for various WordPress operations
 * - Multi-turn conversation handling
 * - Streaming response support
 * - WordPress integration (posts, pages, media, etc.)
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

    // Define available tools
    $tools = $use_tools ? snn_get_ai_agent_tools() : null;

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
 * Get available tools for the AI agent
 */
function snn_get_ai_agent_tools() {
    $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'create_post',
                'description' => 'Creates a new WordPress post with the given title and content',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'The title of the post'
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'The content of the post (can include HTML)'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['draft', 'publish', 'pending'],
                            'description' => 'The post status. Default is draft.'
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'Category name (optional)'
                        ]
                    ],
                    'required' => ['title', 'content']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'update_post',
                'description' => 'Updates an existing WordPress post',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'post_id' => [
                            'type' => 'integer',
                            'description' => 'The ID of the post to update'
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'The new title (optional)'
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'The new content (optional)'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['draft', 'publish', 'pending', 'trash'],
                            'description' => 'The new post status (optional)'
                        ]
                    ],
                    'required' => ['post_id']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_posts',
                'description' => 'Retrieves a list of WordPress posts with optional filters',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'post_type' => [
                            'type' => 'string',
                            'description' => 'Post type (post, page, etc.). Default is post.'
                        ],
                        'posts_per_page' => [
                            'type' => 'integer',
                            'description' => 'Number of posts to retrieve. Default is 10.'
                        ],
                        'post_status' => [
                            'type' => 'string',
                            'description' => 'Post status (publish, draft, etc.). Default is publish.'
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search term to filter posts'
                        ]
                    ]
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_post_by_id',
                'description' => 'Retrieves a specific WordPress post by its ID',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'post_id' => [
                            'type' => 'integer',
                            'description' => 'The ID of the post to retrieve'
                        ]
                    ],
                    'required' => ['post_id']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'create_page',
                'description' => 'Creates a new WordPress page',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'The title of the page'
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'The content of the page (can include HTML)'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['draft', 'publish', 'pending'],
                            'description' => 'The page status. Default is draft.'
                        ]
                    ],
                    'required' => ['title', 'content']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_site_info',
                'description' => 'Retrieves WordPress site information',
                'parameters' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_content',
                'description' => 'Searches for content across the WordPress site',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The search query'
                        ],
                        'post_type' => [
                            'type' => 'string',
                            'description' => 'Post type to search in (post, page, any). Default is any.'
                        ]
                    ],
                    'required' => ['query']
                ]
            ]
        ]
    ];

    return apply_filters('snn_ai_agent_tools', $tools);
}

/**
 * Execute tool calls from AI response
 */
function snn_execute_tool_calls($tool_calls) {
    $results = [];

    foreach ($tool_calls as $tool_call) {
        $function_name = $tool_call['function']['name'];
        $arguments = json_decode($tool_call['function']['arguments'], true);

        $result = snn_execute_single_tool($function_name, $arguments);

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
 * Execute a single tool function
 */
function snn_execute_single_tool($function_name, $arguments) {
    switch ($function_name) {
        case 'create_post':
            return snn_tool_create_post($arguments);

        case 'update_post':
            return snn_tool_update_post($arguments);

        case 'get_posts':
            return snn_tool_get_posts($arguments);

        case 'get_post_by_id':
            return snn_tool_get_post_by_id($arguments);

        case 'create_page':
            return snn_tool_create_page($arguments);

        case 'get_site_info':
            return snn_tool_get_site_info();

        case 'search_content':
            return snn_tool_search_content($arguments);

        default:
            return ['error' => 'Unknown tool: ' . $function_name];
    }
}

/**
 * Tool: Create Post
 */
function snn_tool_create_post($args) {
    $post_data = [
        'post_title' => sanitize_text_field($args['title']),
        'post_content' => wp_kses_post($args['content']),
        'post_status' => isset($args['status']) ? sanitize_text_field($args['status']) : 'draft',
        'post_type' => 'post'
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return ['error' => $post_id->get_error_message()];
    }

    // Handle category if provided
    if (isset($args['category']) && !empty($args['category'])) {
        $category = get_category_by_slug(sanitize_title($args['category']));
        if ($category) {
            wp_set_post_categories($post_id, [$category->term_id]);
        }
    }

    return [
        'success' => true,
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link($post_id, 'raw'),
        'view_url' => get_permalink($post_id)
    ];
}

/**
 * Tool: Update Post
 */
function snn_tool_update_post($args) {
    $post_data = ['ID' => intval($args['post_id'])];

    if (isset($args['title'])) {
        $post_data['post_title'] = sanitize_text_field($args['title']);
    }

    if (isset($args['content'])) {
        $post_data['post_content'] = wp_kses_post($args['content']);
    }

    if (isset($args['status'])) {
        $post_data['post_status'] = sanitize_text_field($args['status']);
    }

    $result = wp_update_post($post_data);

    if (is_wp_error($result)) {
        return ['error' => $result->get_error_message()];
    }

    return [
        'success' => true,
        'post_id' => $args['post_id'],
        'edit_url' => get_edit_post_link($args['post_id'], 'raw'),
        'view_url' => get_permalink($args['post_id'])
    ];
}

/**
 * Tool: Get Posts
 */
function snn_tool_get_posts($args) {
    $query_args = [
        'post_type' => isset($args['post_type']) ? sanitize_text_field($args['post_type']) : 'post',
        'posts_per_page' => isset($args['posts_per_page']) ? intval($args['posts_per_page']) : 10,
        'post_status' => isset($args['post_status']) ? sanitize_text_field($args['post_status']) : 'publish',
    ];

    if (isset($args['search'])) {
        $query_args['s'] = sanitize_text_field($args['search']);
    }

    $posts = get_posts($query_args);
    $results = [];

    foreach ($posts as $post) {
        $results[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 30),
            'status' => $post->post_status,
            'date' => $post->post_date,
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
            'view_url' => get_permalink($post->ID)
        ];
    }

    return [
        'success' => true,
        'posts' => $results,
        'count' => count($results)
    ];
}

/**
 * Tool: Get Post By ID
 */
function snn_tool_get_post_by_id($args) {
    $post = get_post(intval($args['post_id']));

    if (!$post) {
        return ['error' => 'Post not found'];
    }

    return [
        'success' => true,
        'post' => [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
            'view_url' => get_permalink($post->ID)
        ]
    ];
}

/**
 * Tool: Create Page
 */
function snn_tool_create_page($args) {
    $page_data = [
        'post_title' => sanitize_text_field($args['title']),
        'post_content' => wp_kses_post($args['content']),
        'post_status' => isset($args['status']) ? sanitize_text_field($args['status']) : 'draft',
        'post_type' => 'page'
    ];

    $page_id = wp_insert_post($page_data);

    if (is_wp_error($page_id)) {
        return ['error' => $page_id->get_error_message()];
    }

    return [
        'success' => true,
        'page_id' => $page_id,
        'edit_url' => get_edit_post_link($page_id, 'raw'),
        'view_url' => get_permalink($page_id)
    ];
}

/**
 * Tool: Get Site Info
 */
function snn_tool_get_site_info() {
    $theme = wp_get_theme();

    return [
        'success' => true,
        'site_name' => get_bloginfo('name'),
        'site_description' => get_bloginfo('description'),
        'site_url' => get_site_url(),
        'admin_email' => get_option('admin_email'),
        'wordpress_version' => get_bloginfo('version'),
        'theme' => [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version')
        ],
        'active_plugins' => count(get_option('active_plugins', [])),
        'users_count' => count_users()['total_users'],
        'posts_count' => wp_count_posts('post')->publish,
        'pages_count' => wp_count_posts('page')->publish
    ];
}

/**
 * Tool: Search Content
 */
function snn_tool_search_content($args) {
    $search_args = [
        's' => sanitize_text_field($args['query']),
        'post_type' => isset($args['post_type']) ? sanitize_text_field($args['post_type']) : 'any',
        'posts_per_page' => 20,
        'post_status' => 'publish'
    ];

    $search_query = new WP_Query($search_args);
    $results = [];

    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'excerpt' => get_the_excerpt(),
                'type' => get_post_type(),
                'url' => get_permalink(),
                'edit_url' => get_edit_post_link(get_the_ID(), 'raw')
            ];
        }
        wp_reset_postdata();
    }

    return [
        'success' => true,
        'results' => $results,
        'total_found' => $search_query->found_posts
    ];
}
