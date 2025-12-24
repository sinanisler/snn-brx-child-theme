<?php
/**
 * AI Chat Overlay
 *
 * File: ai-chat-overlay.php
 *
 * Purpose: Frontend AI chat interface for WordPress admin.
 * Provides a slide-in chat panel for interacting with AI agent that has
 * access to WordPress Abilities as tools.
 *
 * Features:
 * - Right-side slide-in chat panel
 * - Clean light mode design
 * - Integration with ai-agent.php backend
 * - Tool calling visualization
 * - Conversation history management
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add AI Chat button to WordPress admin bar
 */
add_action('admin_bar_menu', 'snn_add_ai_chat_admin_bar_button', 100);

function snn_add_ai_chat_admin_bar_button($wp_admin_bar) {
    // Check if AI is enabled
    if (get_option('snn_ai_enabled', 'no') !== 'yes') {
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

    $wp_admin_bar->add_node([
        'id'    => 'snn-ai-chat-button',
        'title' => '<span class="ab-icon dashicons dashicons-format-chat"></span><span class="ab-label">AI Assistant</span>',
        'href'  => '#',
        'meta'  => [
            'class' => 'snn-ai-chat-trigger',
            'title' => 'Open AI Assistant'
        ]
    ]);
}

/**
 * Output AI Chat overlay HTML, CSS, and JavaScript
 */
add_action('admin_footer', 'snn_ai_chat_overlay_output');
add_action('wp_footer', 'snn_ai_chat_overlay_output');

function snn_ai_chat_overlay_output() {
    // Check if AI is enabled
    if (get_option('snn_ai_enabled', 'no') !== 'yes') {
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

    $nonce = wp_create_nonce('snn_ai_agent_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    ?>

    <style>
        /* AI Chat Panel */
        #snn-ai-chat-panel {
            position: fixed;
            top: 0;
            right: -450px;
            width: 450px;
            height: 100vh;
            background: #ffffff;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.15);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #snn-ai-chat-panel.active {
            right: 0;
        }

        /* Header */
        .snn-ai-chat-header {
            background: #000000;
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
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
            font-size: 24px;
            padding: 0;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .snn-ai-chat-close:hover {
            opacity: 0.7;
        }

        /* Messages Container */
        .snn-ai-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fafafa;
        }

        /* Message Bubble */
        .snn-ai-chat-message {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .snn-ai-chat-message.user {
            align-items: flex-end;
        }

        .snn-ai-chat-message.assistant,
        .snn-ai-chat-message.tool {
            align-items: flex-start;
        }

        .snn-ai-chat-message-label {
            font-size: 11px;
            color: #666666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .snn-ai-chat-message-content {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 8px;
            line-height: 1.6;
            word-wrap: break-word;
            font-size: 14px;
        }

        .snn-ai-chat-message.user .snn-ai-chat-message-content {
            background: #000000;
            color: #ffffff;
        }

        .snn-ai-chat-message.assistant .snn-ai-chat-message-content {
            background: #ffffff;
            color: #000000;
            border: 1px solid #e0e0e0;
        }

        .snn-ai-chat-message.tool .snn-ai-chat-message-content {
            background: #f5f5f5;
            color: #333333;
            border: 1px solid #d0d0d0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-width: 90%;
        }

        .snn-ai-chat-message.tool .tool-name {
            font-weight: bold;
            color: #000000;
            margin-bottom: 8px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .snn-ai-chat-message.tool .tool-result {
            background: #ffffff;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-top: 8px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
            font-size: 11px;
        }

        /* Typing Indicator */
        .snn-ai-chat-typing {
            display: none;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            width: fit-content;
            margin-bottom: 16px;
        }

        .snn-ai-chat-typing.active {
            display: block;
        }

        .snn-ai-chat-typing-dots {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .snn-ai-chat-typing-dots span {
            width: 8px;
            height: 8px;
            background: #666666;
            border-radius: 50%;
            animation: typing-bounce 1.4s infinite ease-in-out;
        }

        .snn-ai-chat-typing-dots span:nth-child(1) {
            animation-delay: 0s;
        }

        .snn-ai-chat-typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .snn-ai-chat-typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing-bounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            30% {
                transform: translateY(-8px);
                opacity: 1;
            }
        }

        /* Input Container */
        .snn-ai-chat-input-container {
            padding: 16px 20px;
            background: #ffffff;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        .snn-ai-chat-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .snn-ai-chat-input {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cccccc;
            border-radius: 6px;
            padding: 10px 14px;
            color: #000000;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            min-height: 44px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }

        .snn-ai-chat-input:focus {
            outline: none;
            border-color: #000000;
        }

        .snn-ai-chat-input::placeholder {
            color: #999999;
        }

        .snn-ai-chat-send {
            background: #000000;
            border: none;
            color: #ffffff;
            padding: 0 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            height: 44px;
            transition: opacity 0.2s ease;
            white-space: nowrap;
        }

        .snn-ai-chat-send:hover:not(:disabled) {
            opacity: 0.85;
        }

        .snn-ai-chat-send:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .snn-ai-chat-clear {
            background: transparent;
            border: 1px solid #cccccc;
            color: #666666;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .snn-ai-chat-clear:hover {
            background: #f5f5f5;
            border-color: #000000;
            color: #000000;
        }

        /* Scrollbar */
        .snn-ai-chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb {
            background: #cccccc;
            border-radius: 4px;
        }

        .snn-ai-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #aaaaaa;
        }

        .tool-result::-webkit-scrollbar {
            width: 6px;
        }

        .tool-result::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .tool-result::-webkit-scrollbar-thumb {
            background: #d0d0d0;
            border-radius: 3px;
        }

        /* Error Message */
        .snn-ai-chat-error {
            background: #fff5f5;
            color: #d00000;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 10px 0;
            border: 1px solid #ffcccc;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Mobile Responsive */
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
            <button class="snn-ai-chat-close" aria-label="Close">&times;</button>
        </div>

        <div class="snn-ai-chat-messages" id="snn-ai-chat-messages">
            <div class="snn-ai-chat-message assistant">
                <div class="snn-ai-chat-message-label">Assistant</div>
                <div class="snn-ai-chat-message-content">
                    Hello! I'm your AI assistant with access to WordPress tools. I can help you manage posts, users, and other site content. What would you like to do?
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

        // Configuration
        const CONFIG = {
            AJAX_URL: <?php echo wp_json_encode($ajax_url); ?>,
            NONCE: <?php echo wp_json_encode($nonce); ?>,
            RETRY_DELAY: 500
        };

        // State
        const state = {
            conversationMessages: [],
            isProcessing: false
        };

        // DOM Elements
        const elements = {
            panel: $('#snn-ai-chat-panel'),
            messagesContainer: $('#snn-ai-chat-messages'),
            inputField: $('#snn-ai-chat-input'),
            sendButton: $('#snn-ai-chat-send'),
            clearButton: $('#snn-ai-chat-clear'),
            typingIndicator: $('#snn-ai-chat-typing'),
            closeButton: $('.snn-ai-chat-close')
        };

        // Initialize
        function init() {
            bindEvents();
        }

        // Bind all event listeners
        function bindEvents() {
            // Open panel
            $(document).on('click', '#wp-admin-bar-snn-ai-chat-button a, .snn-ai-chat-trigger', function(e) {
                e.preventDefault();
                openPanel();
            });

            // Close panel
            elements.closeButton.on('click', closePanel);

            // Auto-resize textarea
            elements.inputField.on('input', autoResizeTextarea);

            // Send on Enter (Shift+Enter for new line)
            elements.inputField.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Send button
            elements.sendButton.on('click', sendMessage);

            // Clear chat
            elements.clearButton.on('click', clearChat);
        }

        // Open chat panel
        function openPanel() {
            elements.panel.addClass('active');
            elements.inputField.focus();
        }

        // Close chat panel
        function closePanel() {
            elements.panel.removeClass('active');
        }

        // Auto-resize textarea
        function autoResizeTextarea() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        }

        // Send message
        function sendMessage() {
            const message = elements.inputField.val().trim();

            if (!message || state.isProcessing) {
                return;
            }

            // Add user message to UI
            addMessageToUI('user', message);

            // Add to conversation
            state.conversationMessages.push({
                role: 'user',
                content: message
            });

            // Clear input
            elements.inputField.val('').css('height', 'auto');

            // Process the message
            processMessage();
        }

        // Process message with AI
        function processMessage() {
            if (state.isProcessing) {
                return;
            }

            state.isProcessing = true;
            elements.sendButton.prop('disabled', true);
            showTyping();

            $.ajax({
                url: CONFIG.AJAX_URL,
                type: 'POST',
                data: {
                    action: 'snn_ai_agent_chat',
                    nonce: CONFIG.NONCE,
                    messages: JSON.stringify(state.conversationMessages),
                    use_tools: true
                },
                success: handleAjaxSuccess,
                error: handleAjaxError
            });
        }

        // Handle AJAX success
        function handleAjaxSuccess(response) {
            console.log('AI Agent Response:', response);
            hideTyping();

            // Check for error response
            if (!response.success) {
                const errorMsg = response.data?.message || 'An error occurred. Please try again.';
                console.error('AI Agent Error:', errorMsg);
                showError(errorMsg);
                resetProcessing();
                return;
            }

            // Validate response data
            if (!response.data) {
                console.error('AI Agent Error: Missing response data');
                showError('Invalid response from server.');
                resetProcessing();
                return;
            }

            const data = response.data;

            try {
                // Handle tool calls
                if (data.requires_continuation && data.tool_results) {
                    handleToolCalls(data);
                } else {
                    handleFinalResponse(data);
                }
            } catch (e) {
                console.error('Error handling response:', e);
                showError('Failed to process response: ' + e.message);
                resetProcessing();
            }
        }

        // Handle tool calls
        function handleToolCalls(data) {
            try {
                console.log('Processing tool calls:', data.tool_results);

                // Validate data
                if (!data.message) {
                    throw new Error('Missing assistant message with tool calls');
                }

                // Add assistant message with tool_calls to conversation
                state.conversationMessages.push(data.message);

                // Display and add tool results
                if (Array.isArray(data.tool_results)) {
                    data.tool_results.forEach(function(toolResult, index) {
                        try {
                            if (!toolResult.content) {
                                console.warn('Tool result ' + index + ' missing content');
                                return;
                            }

                            const toolData = JSON.parse(toolResult.content);
                            addMessageToUI('tool', null, toolResult.name, toolData);
                            state.conversationMessages.push(toolResult);
                        } catch (e) {
                            console.error('Failed to parse tool result ' + index + ':', e);
                            showError('Failed to parse tool result: ' + e.message);
                        }
                    });
                } else {
                    console.warn('tool_results is not an array');
                }

                // Continue conversation
                setTimeout(processMessage, CONFIG.RETRY_DELAY);

            } catch (e) {
                console.error('Error in handleToolCalls:', e);
                showError('Tool execution error: ' + e.message);
                resetProcessing();
            }
        }

        // Handle final response
        function handleFinalResponse(data) {
            try {
                console.log('Final response:', data.message);

                if (!data.message) {
                    throw new Error('Missing message in final response');
                }

                if (data.message.content) {
                    addMessageToUI('assistant', data.message.content);
                    state.conversationMessages.push({
                        role: 'assistant',
                        content: data.message.content
                    });
                } else {
                    console.warn('Final response has no content');
                }

                resetProcessing();

            } catch (e) {
                console.error('Error in handleFinalResponse:', e);
                showError('Failed to display response: ' + e.message);
                resetProcessing();
            }
        }

        // Handle AJAX error
        function handleAjaxError(xhr, status, error) {
            console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
            hideTyping();

            let errorMsg = 'Connection error';

            if (xhr.status === 0) {
                errorMsg = 'Network error. Please check your connection.';
            } else if (xhr.status === 403) {
                errorMsg = 'Access denied. Please check your permissions.';
            } else if (xhr.status === 404) {
                errorMsg = 'Endpoint not found. Please check configuration.';
            } else if (xhr.status === 500) {
                errorMsg = 'Server error. Please try again later.';
            } else if (error) {
                errorMsg = 'Connection error: ' + error;
            }

            // Try to parse error response
            try {
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    console.log('Error response text:', xhr.responseText);
                }
            } catch (e) {
                console.error('Failed to parse error response:', e);
            }

            showError(errorMsg);
            resetProcessing();
        }

        // Reset processing state
        function resetProcessing() {
            state.isProcessing = false;
            elements.sendButton.prop('disabled', false);
            elements.inputField.focus();
        }

        // Add message to UI
        function addMessageToUI(role, content, toolName, toolResult) {
            const label = role === 'user' ? 'You' : (role === 'tool' ? 'Tool' : 'Assistant');

            let html = '<div class="snn-ai-chat-message ' + escapeAttr(role) + '">';
            html += '<div class="snn-ai-chat-message-label">' + escapeHtml(label) + '</div>';
            html += '<div class="snn-ai-chat-message-content">';

            if (role === 'tool' && toolName) {
                html += '<div class="tool-name">' + escapeHtml(toolName) + '</div>';
                if (toolResult) {
                    const resultText = typeof toolResult === 'object'
                        ? JSON.stringify(toolResult, null, 2)
                        : String(toolResult);
                    html += '<div class="tool-result">' + escapeHtml(resultText) + '</div>';
                }
            } else if (content) {
                html += escapeHtml(content);
            }

            html += '</div></div>';

            elements.messagesContainer.append(html);
            scrollToBottom();
        }

        // Show typing indicator
        function showTyping() {
            elements.typingIndicator.addClass('active');
            scrollToBottom();
        }

        // Hide typing indicator
        function hideTyping() {
            elements.typingIndicator.removeClass('active');
        }

        // Scroll to bottom
        function scrollToBottom() {
            elements.messagesContainer.animate({
                scrollTop: elements.messagesContainer[0].scrollHeight
            }, 300);
        }

        // Show error message
        function showError(message) {
            const html = '<div class="snn-ai-chat-error">' + escapeHtml(message) + '</div>';
            elements.messagesContainer.append(html);
            scrollToBottom();
        }

        // Clear chat
        function clearChat() {
            if (!confirm('Clear all chat history?')) {
                return;
            }

            state.conversationMessages = [];
            elements.messagesContainer.html(
                '<div class="snn-ai-chat-message assistant">' +
                '<div class="snn-ai-chat-message-label">Assistant</div>' +
                '<div class="snn-ai-chat-message-content">' +
                'Hello! I\'m your AI assistant with access to WordPress tools. I can help you manage posts, users, and other site content. What would you like to do?' +
                '</div></div>'
            );
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Escape HTML attribute
        function escapeAttr(text) {
            return String(text).replace(/['"<>&]/g, '');
        }

        // Initialize on DOM ready
        $(document).ready(init);

    })(jQuery);
    </script>

    <?php
}
