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
 * - Right-side slide-in chat panel
 * - Simple light mode design (white bg, black text)
 * - Integration with ai-agent.php for backend processing
 * - Tool calling visualization
 * - Message history management
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
        /* AI Chat Panel - Simple Light Mode */
        #snn-ai-chat-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: #ffffff;
            box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            transition: right 0.3s ease;
        }

        #snn-ai-chat-panel.active {
            right: 0;
        }

        .snn-ai-chat-header {
            background: #000000;
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
        }

        .snn-ai-chat-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .snn-ai-chat-close {
            background: transparent;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 20px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .snn-ai-chat-close:hover {
            opacity: 0.7;
        }

        .snn-ai-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #ffffff;
        }

        .snn-ai-chat-message {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .snn-ai-chat-message.user {
            align-items: flex-end;
        }

        .snn-ai-chat-message.assistant {
            align-items: flex-start;
        }

        .snn-ai-chat-message-label {
            font-size: 11px;
            color: #666666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .snn-ai-chat-message-content {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 6px;
            line-height: 1.5;
            word-wrap: break-word;
            font-size: 14px;
        }

        .snn-ai-chat-message.user .snn-ai-chat-message-content {
            background: #000000;
            color: #ffffff;
        }

        .snn-ai-chat-message.assistant .snn-ai-chat-message-content {
            background: #f5f5f5;
            color: #000000;
            border: 1px solid #e0e0e0;
        }

        .snn-ai-chat-message.tool {
            align-items: flex-start;
        }

        .snn-ai-chat-message.tool .snn-ai-chat-message-content {
            background: #fafafa;
            color: #333333;
            border: 1px solid #e0e0e0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .snn-ai-chat-message.tool .tool-name {
            font-weight: bold;
            color: #000000;
            margin-bottom: 6px;
        }

        .snn-ai-chat-message.tool .tool-result {
            background: #ffffff;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-top: 6px;
            white-space: pre-wrap;
            max-height: 150px;
            overflow-y: auto;
        }

        .snn-ai-chat-typing {
            display: none;
            padding: 10px 14px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            width: fit-content;
        }

        .snn-ai-chat-typing.active {
            display: block;
        }

        .snn-ai-chat-typing-dots {
            display: flex;
            gap: 4px;
        }

        .snn-ai-chat-typing-dots span {
            width: 6px;
            height: 6px;
            background: #666666;
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
                opacity: 0.3;
            }
            30% {
                opacity: 1;
            }
        }

        .snn-ai-chat-input-container {
            padding: 16px;
            background: #ffffff;
            border-top: 1px solid #e0e0e0;
        }

        .snn-ai-chat-input-wrapper {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .snn-ai-chat-input {
            flex: 1;
            background: #ffffff;
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 10px 12px;
            color: #000000;
            font-size: 14px;
            resize: none;
            max-height: 100px;
            min-height: 40px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .snn-ai-chat-input:focus {
            outline: none;
            border-color: #000000;
        }

        .snn-ai-chat-send {
            background: #000000;
            border: none;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            height: 40px;
            transition: opacity 0.2s ease;
        }

        .snn-ai-chat-send:hover {
            opacity: 0.8;
        }

        .snn-ai-chat-send:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .snn-ai-chat-clear {
            background: transparent;
            border: 1px solid #000000;
            color: #000000;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 8px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .snn-ai-chat-clear:hover {
            background: #000000;
            color: #ffffff;
        }

        /* Scrollbar styling */
        .snn-ai-chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb {
            background: #cccccc;
            border-radius: 3px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #999999;
        }

        /* Error message */
        .snn-ai-chat-error {
            background: #fff5f5;
            color: #c00000;
            padding: 10px 12px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #ffcccc;
            font-size: 13px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            #snn-ai-chat-panel {
                width: 100%;
                right: -100%;
            }
        }
    </style>

    <div id="snn-ai-chat-panel">
        <div class="snn-ai-chat-header">
            <h2>AI Assistant</h2>
            <button class="snn-ai-chat-close" aria-label="Close">×</button>
        </div>

        <div class="snn-ai-chat-messages" id="snn-ai-chat-messages">
            <div class="snn-ai-chat-message assistant">
                <div class="snn-ai-chat-message-label">Assistant</div>
                <div class="snn-ai-chat-message-content">
                    Hello! I'm your AI assistant. I can help you manage your WordPress site. What would you like to do?
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
                    placeholder="Type your message..."
                    rows="1"
                ></textarea>
                <button id="snn-ai-chat-send" class="snn-ai-chat-send">Send</button>
            </div>
            <button id="snn-ai-chat-clear" class="snn-ai-chat-clear">Clear Chat</button>
        </div>
    </div>

    <script>
    (function($) {
        'use strict';

        // Chat state
        let conversationMessages = [];
        let isProcessing = false;

        // DOM elements
        const panel = $('#snn-ai-chat-panel');
        const messagesContainer = $('#snn-ai-chat-messages');
        const inputField = $('#snn-ai-chat-input');
        const sendButton = $('#snn-ai-chat-send');
        const clearButton = $('#snn-ai-chat-clear');
        const typingIndicator = $('#snn-ai-chat-typing');

        // Open/Close panel
        $(document).on('click', '#wp-admin-bar-snn-ai-chat-button a, .snn-ai-chat-trigger', function(e) {
            e.preventDefault();
            panel.addClass('active');
            inputField.focus();
        });

        $('.snn-ai-chat-close').on('click', function() {
            panel.removeClass('active');
        });

        // Auto-resize textarea
        inputField.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
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
            if (confirm('Clear chat history?')) {
                conversationMessages = [];
                messagesContainer.html(
                    '<div class="snn-ai-chat-message assistant">' +
                    '<div class="snn-ai-chat-message-label">Assistant</div>' +
                    '<div class="snn-ai-chat-message-content">' +
                    'Hello! I\'m your AI assistant. I can help you manage your WordPress site. What would you like to do?' +
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
            const label = role === 'user' ? 'You' : (role === 'tool' ? 'Tool' : 'Assistant');

            let messageHTML = `<div class="snn-ai-chat-message ${role}">`;
            messageHTML += `<div class="snn-ai-chat-message-label">${label}</div>`;
            messageHTML += `<div class="snn-ai-chat-message-content">`;

            if (role === 'tool' && toolName) {
                messageHTML += `<div class="tool-name">${toolName}</div>`;
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
                '<div class="snn-ai-chat-error">' + escapeHtml(message) + '</div>'
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
