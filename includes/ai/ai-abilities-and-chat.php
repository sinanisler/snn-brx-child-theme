<?php
/**
 * SNN AI Abilities and Chat System
 *
 * File: ai-abilities-and-chat.php
 *
 * Purpose: Implements WordPress AI abilities API with custom abilities and a chat overlay interface.
 * This file provides AI-powered chat functionality in the WordPress admin with executable abilities
 * like getting post count, user count, user list, and creating draft posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register AI Ability Categories using WordPress 6.9 Abilities API
 */
function snn_register_ai_ability_categories() {
    // Only proceed if WordPress 6.9+ with Abilities API is available
    if (!function_exists('wp_register_ability_category')) {
        return;
    }

    wp_register_ability_category(
        'snn-content-management',
        array(
            'label'       => __('SNN Content Management', 'snn'),
            'description' => __('Abilities for managing content and users in WordPress.', 'snn'),
        )
    );
}
add_action('wp_abilities_api_categories_init', 'snn_register_ai_ability_categories');

/**
 * Register AI Abilities using WordPress 6.9 Abilities API
 */
function snn_register_ai_abilities() {
    // Only proceed if WordPress 6.9+ with Abilities API is available
    if (!function_exists('wp_register_ability')) {
        return;
    }

    // Ability 1: Get Post Count
    wp_register_ability(
        'snn/get-post-count',
        array(
            'label'               => __('Get Post Count', 'snn'),
            'description'         => __('Retrieves the total number of published posts in the WordPress site.', 'snn'),
            'category'            => 'snn-content-management',
            'input_schema'        => array(
                'type'        => 'object',
                'properties'  => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => __('The post type to count (default: post).', 'snn'),
                        'default'     => 'post',
                    ),
                ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array(
                        'type'        => 'boolean',
                        'description' => __('Whether the operation was successful.', 'snn'),
                    ),
                    'data' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'published_posts' => array(
                                'type'        => 'integer',
                                'description' => __('The number of published posts.', 'snn'),
                            ),
                            'message' => array(
                                'type'        => 'string',
                                'description' => __('A human-readable message.', 'snn'),
                            ),
                        ),
                    ),
                ),
            ),
            'execute_callback'    => 'snn_ai_ability_get_post_count',
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'meta'                => array(
                'show_in_rest' => true,
            ),
        )
    );

    // Ability 2: Get User Count and List
    wp_register_ability(
        'snn/get-users-info',
        array(
            'label'               => __('Get Users Info', 'snn'),
            'description'         => __('Returns the total number of users and a list of all users with their basic information.', 'snn'),
            'category'            => 'snn-content-management',
            'input_schema'        => array(
                'type'       => 'null',
                'description' => __('No input required.', 'snn'),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array(
                        'type'        => 'boolean',
                        'description' => __('Whether the operation was successful.', 'snn'),
                    ),
                    'data' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'user_count' => array(
                                'type'        => 'integer',
                                'description' => __('The total number of users.', 'snn'),
                            ),
                            'users' => array(
                                'type'        => 'array',
                                'description' => __('List of users.', 'snn'),
                                'items'       => array(
                                    'type'       => 'object',
                                    'properties' => array(
                                        'id'         => array('type' => 'integer'),
                                        'name'       => array('type' => 'string'),
                                        'email'      => array('type' => 'string'),
                                        'registered' => array('type' => 'string'),
                                    ),
                                ),
                            ),
                            'message' => array(
                                'type'        => 'string',
                                'description' => __('A human-readable message.', 'snn'),
                            ),
                        ),
                    ),
                ),
            ),
            'execute_callback'    => 'snn_ai_ability_get_users_info',
            'permission_callback' => function() {
                return current_user_can('list_users');
            },
            'meta'                => array(
                'show_in_rest' => true,
            ),
        )
    );

    // Ability 3: Create Draft Post
    wp_register_ability(
        'snn/create-draft-post',
        array(
            'label'               => __('Create Draft Post', 'snn'),
            'description'         => __('Creates a new draft post with the provided title and content.', 'snn'),
            'category'            => 'snn-content-management',
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'title' => array(
                        'type'        => 'string',
                        'description' => __('The title of the post.', 'snn'),
                        'minLength'   => 1,
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => __('The content of the post.', 'snn'),
                        'default'     => '',
                    ),
                ),
                'required'   => array('title'),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array(
                        'type'        => 'boolean',
                        'description' => __('Whether the operation was successful.', 'snn'),
                    ),
                    'data' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'post_id' => array(
                                'type'        => 'integer',
                                'description' => __('The created post ID.', 'snn'),
                            ),
                            'edit_link' => array(
                                'type'        => 'string',
                                'description' => __('URL to edit the post.', 'snn'),
                            ),
                            'message' => array(
                                'type'        => 'string',
                                'description' => __('A human-readable message.', 'snn'),
                            ),
                        ),
                    ),
                    'error' => array(
                        'type'        => 'string',
                        'description' => __('Error message if operation failed.', 'snn'),
                    ),
                ),
            ),
            'execute_callback'    => 'snn_ai_ability_create_draft_post',
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'meta'                => array(
                'show_in_rest' => true,
            ),
        )
    );
}
add_action('wp_abilities_api_init', 'snn_register_ai_abilities');

/**
 * Ability Callback: Get Post Count
 */
function snn_ai_ability_get_post_count($input = null) {
    $post_type = 'post';

    // Handle input if provided
    if (is_array($input) && isset($input['post_type'])) {
        $post_type = sanitize_text_field($input['post_type']);
    }

    $count = wp_count_posts($post_type);
    $published_count = isset($count->publish) ? $count->publish : 0;

    return array(
        'success' => true,
        'data'    => array(
            'published_posts' => $published_count,
            'message'         => sprintf(__('There are %d published posts.', 'snn'), $published_count),
        ),
    );
}

/**
 * Ability Callback: Get Users Info
 */
function snn_ai_ability_get_users_info($input = null) {
    $users = get_users(array('fields' => array('ID', 'display_name', 'user_email', 'user_registered')));
    $user_count = count($users);

    $user_list = array();
    foreach ($users as $user) {
        $user_list[] = array(
            'id'         => $user->ID,
            'name'       => $user->display_name,
            'email'      => $user->user_email,
            'registered' => $user->user_registered,
        );
    }

    return array(
        'success' => true,
        'data'    => array(
            'user_count' => $user_count,
            'users'      => $user_list,
            'message'    => sprintf(__('There are %d users registered.', 'snn'), $user_count),
        ),
    );
}

/**
 * Ability Callback: Create Draft Post
 */
function snn_ai_ability_create_draft_post($input) {
    $title = isset($input['title']) ? sanitize_text_field($input['title']) : '';
    $content = isset($input['content']) ? wp_kses_post($input['content']) : '';

    if (empty($title)) {
        return new WP_Error(
            'missing_title',
            __('Title is required to create a post.', 'snn')
        );
    }

    $post_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_type'    => 'post',
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    return array(
        'success' => true,
        'data'    => array(
            'post_id'   => $post_id,
            'edit_link' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'message'   => sprintf(__('Draft post "%s" created successfully! ID: %d', 'snn'), $title, $post_id),
        ),
    );
}

/**
 * Add AI Agent to Admin Bar
 */
function snn_add_ai_agent_to_admin_bar($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $args = array(
        'id'    => 'snn_ai_agent',
        'title' => '<span class="ab-icon dashicons dashicons-admin-generic"></span>' . __('AI Agent', 'snn'),
        'href'  => '#',
        'meta'  => array(
            'class' => 'snn-ai-agent-trigger',
        ),
    );
    $wp_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'snn_add_ai_agent_to_admin_bar', 100);

/**
 * Enqueue Scripts and Styles for AI Chat
 */
function snn_enqueue_ai_chat_assets() {
    if (!is_admin() && !is_admin_bar_showing()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    // Get AI config
    if (function_exists('snn_get_ai_api_config')) {
        $ai_config = snn_get_ai_api_config();
    } else {
        $ai_config = array(
            'apiKey'      => '',
            'model'       => '',
            'apiEndpoint' => '',
            'systemPrompt'=> 'You are a helpful WordPress AI assistant.',
        );
    }

    // Add abilities info to config
    $ai_config['abilities'] = array(
        array(
            'name'        => 'get-post-count',
            'description' => 'Get the total number of published posts',
        ),
        array(
            'name'        => 'get-users-info',
            'description' => 'Get user count and list of all users',
        ),
        array(
            'name'        => 'create-draft-post',
            'description' => 'Create a new draft post with title and content',
            'parameters'  => array(
                'title'   => 'string (required)',
                'content' => 'string (optional)',
            ),
        ),
    );

    wp_localize_script('jquery', 'snnAiChatConfig', $ai_config);
    wp_localize_script('jquery', 'snnAiChatData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('snn_ai_chat_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'snn_enqueue_ai_chat_assets');
add_action('wp_enqueue_scripts', 'snn_enqueue_ai_chat_assets');

/**
 * AJAX Handler: Execute AI Ability
 */
function snn_ajax_execute_ai_ability() {
    check_ajax_referer('snn_ai_chat_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $ability_name = isset($_POST['ability']) ? sanitize_text_field($_POST['ability']) : '';
    $params = isset($_POST['params']) ? json_decode(stripslashes($_POST['params']), true) : array();

    // Try to execute via WordPress Abilities API if available
    if (function_exists('wp_get_ability') && function_exists('wp_has_ability')) {
        if (wp_has_ability($ability_name)) {
            $ability = wp_get_ability($ability_name);
            $result = $ability->execute($params);

            // Handle WP_Error responses
            if (is_wp_error($result)) {
                wp_send_json(array(
                    'success' => false,
                    'error'   => $result->get_error_message(),
                ));
                return;
            }

            wp_send_json($result);
            return;
        }
    }

    // Fallback to direct function calls
    $result = null;

    switch ($ability_name) {
        case 'snn/get-post-count':
            $result = snn_ai_ability_get_post_count($params);
            break;
        case 'snn/get-users-info':
            $result = snn_ai_ability_get_users_info($params);
            break;
        case 'snn/create-draft-post':
            $result = snn_ai_ability_create_draft_post($params);
            break;
        default:
            $result = array(
                'success' => false,
                'error'   => 'Unknown ability',
            );
    }

    // Handle WP_Error responses
    if (is_wp_error($result)) {
        wp_send_json(array(
            'success' => false,
            'error'   => $result->get_error_message(),
        ));
        return;
    }

    wp_send_json($result);
}
add_action('wp_ajax_snn_execute_ai_ability', 'snn_ajax_execute_ai_ability');

/**
 * AJAX Handler: Chat with AI
 */
function snn_ajax_ai_chat() {
    check_ajax_referer('snn_ai_chat_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $messages = isset($_POST['messages']) ? json_decode(stripslashes($_POST['messages']), true) : array();

    if (empty($messages)) {
        wp_send_json_error(array('message' => 'No messages provided'));
    }

    // Get AI config
    if (function_exists('snn_get_ai_api_config')) {
        $config = snn_get_ai_api_config();
    } else {
        wp_send_json_error(array('message' => 'AI configuration not available'));
    }

    // Prepare the API request
    $api_messages = array();

    // Add system prompt
    $system_prompt = $config['systemPrompt'] . "\n\n" .
        "You are an AI assistant for WordPress. You have access to the following abilities:\n" .
        "1. get-post-count - Get total published posts\n" .
        "2. get-users-info - Get user count and list\n" .
        "3. create-draft-post(title, content) - Create a new draft post\n\n" .
        "When a user's request matches an ability, respond with a JSON object in this format:\n" .
        '{"action": "get-post-count", "params": {}}\n' .
        'or\n' .
        '{"action": "create-draft-post", "params": {"title": "Post Title", "content": "Post content"}}\n\n' .
        "Otherwise, respond naturally to help the user.";

    $api_messages[] = array(
        'role'    => 'system',
        'content' => $system_prompt,
    );

    // Add conversation messages
    foreach ($messages as $msg) {
        $api_messages[] = array(
            'role'    => isset($msg['role']) ? $msg['role'] : 'user',
            'content' => isset($msg['content']) ? $msg['content'] : '',
        );
    }

    // Make API request
    $response = wp_remote_post($config['apiEndpoint'], array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $config['apiKey'],
        ),
        'body' => json_encode(array(
            'model'    => $config['model'],
            'messages' => $api_messages,
        )),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['choices'][0]['message']['content'])) {
        $ai_response = $body['choices'][0]['message']['content'];
        wp_send_json_success(array('response' => $ai_response));
    } else {
        wp_send_json_error(array('message' => 'Invalid API response', 'raw' => $body));
    }
}
add_action('wp_ajax_snn_ai_chat', 'snn_ajax_ai_chat');

/**
 * Output Chat Overlay HTML, CSS, and JavaScript
 */
function snn_output_ai_chat_overlay() {
    if (!is_admin_bar_showing()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <style>
    /* AI Chat Overlay Styles */
    #snn-ai-chat-overlay {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        background: #ffffff;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        transition: right 0.3s ease;
        z-index: 999999;
        display: flex;
        flex-direction: column;
    }

    #snn-ai-chat-overlay.active {
        right: 0;
    }

    #snn-ai-chat-header {
        background: #000000;
        color: #ffffff;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #333;
    }

    #snn-ai-chat-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    #snn-ai-chat-close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    #snn-ai-chat-close:hover {
        opacity: 0.7;
    }

    #snn-ai-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #ffffff;
    }

    .snn-ai-message {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }

    .snn-ai-message.user {
        align-items: flex-end;
    }

    .snn-ai-message.assistant {
        align-items: flex-start;
    }

    .snn-ai-message-content {
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 8px;
        color: #000000;
        line-height: 1.5;
        word-wrap: break-word;
    }

    .snn-ai-message.user .snn-ai-message-content {
        background: #f0f0f0;
    }

    .snn-ai-message.assistant .snn-ai-message-content {
        background: #e8e8e8;
    }

    .snn-ai-message-label {
        font-size: 11px;
        color: #666;
        margin-bottom: 5px;
        font-weight: 500;
    }

    #snn-ai-chat-input-wrapper {
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
        background: #ffffff;
    }

    #snn-ai-chat-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        resize: vertical;
        min-height: 60px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    #snn-ai-chat-send {
        margin-top: 10px;
        width: 100%;
        padding: 10px;
        background: #000000;
        color: #ffffff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }

    #snn-ai-chat-send:hover {
        background: #333333;
    }

    #snn-ai-chat-send:disabled {
        background: #cccccc;
        cursor: not-allowed;
    }

    .snn-ai-loading {
        display: none;
        padding: 10px;
        text-align: center;
        color: #666;
        font-size: 13px;
    }

    .snn-ai-loading.active {
        display: block;
    }

    .snn-ai-ability-result {
        background: #f9f9f9;
        border-left: 3px solid #000000;
        padding: 10px;
        margin-top: 10px;
        font-size: 13px;
        border-radius: 4px;
    }

    .snn-ai-ability-result strong {
        display: block;
        margin-bottom: 5px;
    }

    /* Admin Bar Icon */
    #wp-admin-bar-snn_ai_agent .ab-icon:before {
        content: "\f333";
        top: 2px;
    }
    </style>

    <div id="snn-ai-chat-overlay">
        <div id="snn-ai-chat-header">
            <h3><?php esc_html_e('AI Agent', 'snn'); ?></h3>
            <button id="snn-ai-chat-close" aria-label="<?php esc_attr_e('Close', 'snn'); ?>">�</button>
        </div>

        <div id="snn-ai-chat-messages">
            <div class="snn-ai-message assistant">
                <div class="snn-ai-message-label"><?php esc_html_e('AI Assistant', 'snn'); ?></div>
                <div class="snn-ai-message-content">
                    <?php esc_html_e('Hello! I\'m your WordPress AI assistant. I can help you with:', 'snn'); ?>
                    <br><br>
                    " <?php esc_html_e('Getting post count', 'snn'); ?><br>
                    " <?php esc_html_e('Getting user information', 'snn'); ?><br>
                    " <?php esc_html_e('Creating draft posts', 'snn'); ?><br>
                    <br>
                    <?php esc_html_e('What would you like to do?', 'snn'); ?>
                </div>
            </div>
        </div>

        <div class="snn-ai-loading"><?php esc_html_e('AI is thinking...', 'snn'); ?></div>

        <div id="snn-ai-chat-input-wrapper">
            <textarea id="snn-ai-chat-input" placeholder="<?php esc_attr_e('Type your message...', 'snn'); ?>"></textarea>
            <button id="snn-ai-chat-send"><?php esc_html_e('Send', 'snn'); ?></button>
        </div>
    </div>

    <script>
    (function($) {
        'use strict';

        const chatOverlay = $('#snn-ai-chat-overlay');
        const chatMessages = $('#snn-ai-chat-messages');
        const chatInput = $('#snn-ai-chat-input');
        const chatSend = $('#snn-ai-chat-send');
        const chatClose = $('#snn-ai-chat-close');
        const chatLoading = $('.snn-ai-loading');

        let conversationHistory = [];

        // Toggle chat overlay
        $(document).on('click', '#wp-admin-bar-snn_ai_agent, .snn-ai-agent-trigger', function(e) {
            e.preventDefault();
            chatOverlay.toggleClass('active');
        });

        // Close chat
        chatClose.on('click', function() {
            chatOverlay.removeClass('active');
        });

        // Send message
        function sendMessage() {
            const message = chatInput.val().trim();
            if (!message) return;

            // Add user message to UI
            addMessageToUI('user', message);
            conversationHistory.push({ role: 'user', content: message });

            // Clear input
            chatInput.val('');

            // Disable send button
            chatSend.prop('disabled', true);
            chatLoading.addClass('active');

            // Send to server
            $.ajax({
                url: snnAiChatData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_ai_chat',
                    nonce: snnAiChatData.nonce,
                    messages: JSON.stringify(conversationHistory)
                },
                success: function(response) {
                    if (response.success && response.data.response) {
                        const aiResponse = response.data.response;

                        // Check if response is an ability action
                        try {
                            const parsed = JSON.parse(aiResponse);
                            if (parsed.action) {
                                executeAbility(parsed.action, parsed.params || {});
                                return;
                            }
                        } catch (e) {
                            // Not JSON, regular response
                        }

                        // Add AI response to UI
                        addMessageToUI('assistant', aiResponse);
                        conversationHistory.push({ role: 'assistant', content: aiResponse });
                    } else {
                        addMessageToUI('assistant', 'Sorry, I encountered an error: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    addMessageToUI('assistant', 'Sorry, I encountered a network error. Please try again.');
                },
                complete: function() {
                    chatSend.prop('disabled', false);
                    chatLoading.removeClass('active');
                }
            });
        }

        // Execute ability
        function executeAbility(ability, params) {
            const abilityName = 'snn/' + ability;

            $.ajax({
                url: snnAiChatData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'snn_execute_ai_ability',
                    nonce: snnAiChatData.nonce,
                    ability: abilityName,
                    params: JSON.stringify(params)
                },
                success: function(response) {
                    let resultHtml = '<div class="snn-ai-ability-result">';

                    if (response.success && response.data) {
                        resultHtml += '<strong> Action completed:</strong>';
                        resultHtml += '<div>' + escapeHtml(response.data.message || 'Success') + '</div>';

                        // Display additional data
                        if (response.data.users) {
                            resultHtml += '<br><strong>Users:</strong><ul style="margin:5px 0;padding-left:20px;">';
                            response.data.users.forEach(function(user) {
                                resultHtml += '<li>' + escapeHtml(user.name) + ' (' + escapeHtml(user.email) + ')</li>';
                            });
                            resultHtml += '</ul>';
                        }

                        if (response.data.edit_link) {
                            resultHtml += '<br><a href="' + response.data.edit_link + '" target="_blank" style="color:#000;text-decoration:underline;">Edit Post</a>';
                        }
                    } else {
                        resultHtml += '<strong> Error:</strong>';
                        resultHtml += '<div>' + escapeHtml(response.error || 'Unknown error') + '</div>';
                    }

                    resultHtml += '</div>';

                    addMessageToUI('assistant', resultHtml, true);
                },
                error: function() {
                    addMessageToUI('assistant', '<div class="snn-ai-ability-result"><strong> Error:</strong><div>Failed to execute ability</div></div>', true);
                },
                complete: function() {
                    chatSend.prop('disabled', false);
                    chatLoading.removeClass('active');
                }
            });
        }

        // Add message to UI
        function addMessageToUI(role, content, isHtml = false) {
            const label = role === 'user' ? '<?php esc_js_e('You', 'snn'); ?>' : '<?php esc_js_e('AI Assistant', 'snn'); ?>';
            const messageHtml = `
                <div class="snn-ai-message ${role}">
                    <div class="snn-ai-message-label">${label}</div>
                    <div class="snn-ai-message-content">${isHtml ? content : escapeHtml(content)}</div>
                </div>
            `;
            chatMessages.append(messageHtml);
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }

        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Event listeners
        chatSend.on('click', sendMessage);
        chatInput.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && chatOverlay.hasClass('active')) {
                chatOverlay.removeClass('active');
            }
        });

    })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'snn_output_ai_chat_overlay');
add_action('wp_footer', 'snn_output_ai_chat_overlay');
