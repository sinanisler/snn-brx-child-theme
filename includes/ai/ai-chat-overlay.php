<?php
/**
 * AI Chat Overlay
 *
 * File: ai-chat-overlay.php
 *
 * Purpose: This file provides a frontend AI chat interface for WordPress admin.
 * It adds a button to the WordPress admin bar that opens a chat overlay where
 * users can interact with an AI agent that has access to WordPress tools.
 *
 * Features:
 * - Chat overlay UI in WordPress admin
 * - Integration with ai-agent.php for backend processing
 * - Tool calling visualization
 * - Message history management
 * - Responsive design
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Add AI Chat button to WordPress admin bar
 */
add_action('admin_bar_menu', 'snn_add_ai_chat_admin_bar_button', 100);

function snn_add_ai_chat_admin_bar_button($wp_admin_bar) {
    // Check if AI is enabled
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if AI config is available
    if (!function_exists('snn_get_ai_api_config')) {
        return;
    }

    $config = snn_get_ai_api_config();
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        return;
    }

    $args = [
        'id'    => 'snn-ai-chat-button',
        'title' => '<span class="ab-icon dashicons dashicons-format-chat"></span><span class="ab-label">AI Assistant</span>',
        'href'  => '#',
        'meta'  => [
            'class' => 'snn-ai-chat-trigger',
            'title' => 'Open AI Assistant'
        ]
    ];

    $wp_admin_bar->add_node($args);
}

/**
 * Enqueue AI Chat overlay scripts and styles
 */
add_action('admin_footer', 'snn_ai_chat_overlay_output');
add_action('wp_footer', 'snn_ai_chat_overlay_output');

function snn_ai_chat_overlay_output() {
    // Check if AI is enabled
    $ai_enabled = get_option('snn_ai_enabled', 'no');
    if ($ai_enabled !== 'yes') {
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if AI config is available
    if (!function_exists('snn_get_ai_api_config')) {
        return;
    }

    $config = snn_get_ai_api_config();
    if (empty($config['apiKey']) || empty($config['apiEndpoint'])) {
        return;
    }

    // Get nonce for AJAX requests
    $nonce = wp_create_nonce('snn_ai_agent_nonce');
    ?>

    <style>
        /* AI Chat Overlay Styles */
        #snn-ai-chat-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            align-items: center;
            justify-content: center;
        }

        #snn-ai-chat-overlay.active {
            display: flex;
        }

        .snn-ai-chat-container {
            background: #1e1e1e;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            height: 85vh;
            max-height: 700px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .snn-ai-chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .snn-ai-chat-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .snn-ai-chat-header h2 .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .snn-ai-chat-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
            font-size: 20px;
        }

        .snn-ai-chat-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .snn-ai-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #2d2d2d;
        }

        .snn-ai-chat-message {
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .snn-ai-chat-message.user {
            flex-direction: row-reverse;
        }

        .snn-ai-chat-message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
        }

        .snn-ai-chat-message.user .snn-ai-chat-message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .snn-ai-chat-message.assistant .snn-ai-chat-message-avatar,
        .snn-ai-chat-message.tool .snn-ai-chat-message-avatar {
            background: #3a3a3a;
            color: #888;
        }

        .snn-ai-chat-message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .snn-ai-chat-message.user .snn-ai-chat-message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .snn-ai-chat-message.assistant .snn-ai-chat-message-content {
            background: #3a3a3a;
            color: #e0e0e0;
            border-bottom-left-radius: 4px;
        }

        .snn-ai-chat-message.tool .snn-ai-chat-message-content {
            background: #2a4a2a;
            color: #a8d5a8;
            border-left: 3px solid #4caf50;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .snn-ai-chat-message.tool .tool-name {
            font-weight: bold;
            color: #4caf50;
            margin-bottom: 8px;
        }

        .snn-ai-chat-message.tool .tool-result {
            background: rgba(0, 0, 0, 0.2);
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }

        .snn-ai-chat-typing {
            display: none;
            padding: 12px 16px;
            background: #3a3a3a;
            border-radius: 12px;
            width: fit-content;
            margin-left: 48px;
        }

        .snn-ai-chat-typing.active {
            display: block;
        }

        .snn-ai-chat-typing-dots {
            display: flex;
            gap: 4px;
        }

        .snn-ai-chat-typing-dots span {
            width: 8px;
            height: 8px;
            background: #888;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .snn-ai-chat-typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .snn-ai-chat-typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        .snn-ai-chat-input-container {
            padding: 20px;
            background: #1e1e1e;
            border-top: 1px solid #3a3a3a;
        }

        .snn-ai-chat-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .snn-ai-chat-input {
            flex: 1;
            background: #2d2d2d;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 12px 16px;
            color: #e0e0e0;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            min-height: 44px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .snn-ai-chat-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .snn-ai-chat-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 44px;
        }

        .snn-ai-chat-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .snn-ai-chat-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .snn-ai-chat-clear {
            background: transparent;
            border: 1px solid #3a3a3a;
            color: #888;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 8px;
            transition: all 0.2s ease;
        }

        .snn-ai-chat-clear:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Scrollbar styling */
        .snn-ai-chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-track {
            background: #1e1e1e;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb {
            background: #3a3a3a;
            border-radius: 4px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #4a4a4a;
        }

        /* Error message */
        .snn-ai-chat-error {
            background: #4a2a2a;
            color: #ff8a80;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 12px 0;
            border-left: 3px solid #f44336;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .snn-ai-chat-container {
                width: 100%;
                height: 100%;
                max-height: 100%;
                border-radius: 0;
            }

            .snn-ai-chat-message-content {
                max-width: 85%;
            }
        }
    </style>

    <div id="snn-ai-chat-overlay">
        <div class="snn-ai-chat-container">
            <div class="snn-ai-chat-header">
                <h2>
                    <span class="dashicons dashicons-format-chat"></span>
                    AI Assistant
                </h2>
                <button class="snn-ai-chat-close" aria-label="Close">×</button>
            </div>

            <div class="snn-ai-chat-messages" id="snn-ai-chat-messages">
                <div class="snn-ai-chat-message assistant">
                    <div class="snn-ai-chat-message-avatar">🤖</div>
                    <div class="snn-ai-chat-message-content">
                        Hello! I'm your AI assistant with access to WordPress tools. I can help you create posts, update content, search for information, and more. What would you like to do today?
                    </div>
                </div>
            </div>

            <div class="snn-ai-chat-typing" id="snn-ai-chat-typing">
                <div class="snn-ai-chat-typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <div class="snn-ai-chat-input-container">
                <div class="snn-ai-chat-input-wrapper">
                    <textarea
                        id="snn-ai-chat-input"
                        class="snn-ai-chat-input"
                        placeholder="Ask me anything about your WordPress site..."
                        rows="1"
                    ></textarea>
                    <button id="snn-ai-chat-send" class="snn-ai-chat-send">Send</button>
                </div>
                <button id="snn-ai-chat-clear" class="snn-ai-chat-clear">Clear Chat</button>
            </div>
        </div>
    </div>

    <script>
    (function($) {
        'use strict';

        // Chat state
        let conversationMessages = [];
        let isProcessing = false;

        // DOM elements
        const overlay = $('#snn-ai-chat-overlay');
        const messagesContainer = $('#snn-ai-chat-messages');
        const inputField = $('#snn-ai-chat-input');
        const sendButton = $('#snn-ai-chat-send');
        const clearButton = $('#snn-ai-chat-clear');
        const typingIndicator = $('#snn-ai-chat-typing');

        // Open/Close overlay
        $(document).on('click', '#wp-admin-bar-snn-ai-chat-button a, .snn-ai-chat-trigger', function(e) {
            e.preventDefault();
            overlay.addClass('active');
            inputField.focus();
        });

        $('.snn-ai-chat-close, #snn-ai-chat-overlay').on('click', function(e) {
            if (e.target === this) {
                overlay.removeClass('active');
            }
        });

        // Auto-resize textarea
        inputField.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Send message on Enter (Shift+Enter for new line)
        inputField.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Send button click
        sendButton.on('click', sendMessage);

        // Clear chat
        clearButton.on('click', function() {
            if (confirm('Are you sure you want to clear the chat history?')) {
                conversationMessages = [];
                messagesContainer.html(
                    '<div class="snn-ai-chat-message assistant">' +
                    '<div class="snn-ai-chat-message-avatar">🤖</div>' +
                    '<div class="snn-ai-chat-message-content">' +
                    'Hello! I\'m your AI assistant with access to WordPress tools. I can help you create posts, update content, search for information, and more. What would you like to do today?' +
                    '</div></div>'
                );
            }
        });

        function sendMessage() {
            const message = inputField.val().trim();

            if (!message || isProcessing) {
                return;
            }

            // Add user message to UI
            addMessageToUI('user', message);

            // Add to conversation history
            conversationMessages.push({
                role: 'user',
                content: message
            });

            // Clear input
            inputField.val('').css('height', 'auto');

            // Process message
            processMessage();
        }

        function addMessageToUI(role, content, toolName = null, toolResult = null) {
            const avatar = role === 'user' ? '👤' : (role === 'tool' ? '🔧' : '🤖');

            let messageHTML = `
                <div class="snn-ai-chat-message ${role}">
                    <div class="snn-ai-chat-message-avatar">${avatar}</div>
                    <div class="snn-ai-chat-message-content">
            `;

            if (role === 'tool' && toolName) {
                messageHTML += `<div class="tool-name">🔧 ${toolName}</div>`;
                if (toolResult) {
                    const resultText = typeof toolResult === 'object'
                        ? JSON.stringify(toolResult, null, 2)
                        : toolResult;
                    messageHTML += `<div class="tool-result">${escapeHtml(resultText)}</div>`;
                }
            } else {
                messageHTML += escapeHtml(content);
            }

            messageHTML += `</div></div>`;

            messagesContainer.append(messageHTML);
            scrollToBottom();
        }

        function showTyping() {
            typingIndicator.addClass('active');
            scrollToBottom();
        }

        function hideTyping() {
            typingIndicator.removeClass('active');
        }

        function scrollToBottom() {
            messagesContainer.animate({
                scrollTop: messagesContainer[0].scrollHeight
            }, 300);
        }

        function processMessage() {
            if (isProcessing) {
                return;
            }

            isProcessing = true;
            sendButton.prop('disabled', true);
            showTyping();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'snn_ai_agent_chat',
                    nonce: '<?php echo $nonce; ?>',
                    messages: JSON.stringify(conversationMessages),
                    use_tools: true
                },
                success: function(response) {
                    hideTyping();

                    if (response.success) {
                        const data = response.data;

                        // Add assistant message
                        if (data.message.content) {
                            addMessageToUI('assistant', data.message.content);
                            conversationMessages.push({
                                role: 'assistant',
                                content: data.message.content
                            });
                        }

                        // Handle tool calls
                        if (data.requires_continuation && data.tool_results) {
                            // Add assistant message with tool calls
                            conversationMessages.push(data.message);

                            // Add tool results to UI and conversation
                            data.tool_results.forEach(function(toolResult) {
                                const toolData = JSON.parse(toolResult.content);
                                addMessageToUI('tool', null, toolResult.name, toolData);
                                conversationMessages.push(toolResult);
                            });

                            // Continue conversation to get final response
                            setTimeout(function() {
                                processMessage();
                            }, 500);
                        } else {
                            // Conversation complete
                            isProcessing = false;
                            sendButton.prop('disabled', false);
                            inputField.focus();
                        }
                    } else {
                        showError(response.data.message || 'An error occurred. Please try again.');
                        isProcessing = false;
                        sendButton.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    hideTyping();
                    showError('Connection error: ' + error);
                    isProcessing = false;
                    sendButton.prop('disabled', false);
                }
            });
        }

        function showError(message) {
            messagesContainer.append(
                '<div class="snn-ai-chat-error">⚠️ ' + escapeHtml(message) + '</div>'
            );
            scrollToBottom();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

    })(jQuery);
    </script>

    <?php
}
