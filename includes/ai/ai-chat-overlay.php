<?php
/**
 * SNN AI Chat Overlay
 *
 * File: snn-chat-overlay.php
 *
 * Purpose: Provides an AI-powered chat interface accessible from anywhere in WordPress (admin and frontend).
 * Adds a button to the admin bar and displays a floating overlay that can execute WordPress abilities
 * through AI agent conversations. Uses the existing AI API configuration and integrates with the
 * SNN Abilities API for autonomous task execution.
 *
 * Features:
 * - Admin bar button for quick access
 * - Floating chat overlay with conversation history
 * - AI agent integration using existing API config
 * - WordPress abilities discovery and execution
 * - Client-side context and state management
 * - Draggable, resizable interface
 *
 * @package SNN_AI_Chat
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Chat Overlay Class
 */
class SNN_Chat_Overlay {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Add admin bar button
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_button' ), 999 );
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // Render overlay HTML
        add_action( 'wp_footer', array( $this, 'render_overlay' ), 999 );
        add_action( 'admin_footer', array( $this, 'render_overlay' ), 999 );
    }

    /**
     * Add button to WordPress admin bar
     */
    public function add_admin_bar_button( $wp_admin_bar ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'snn-ai-chat',
            'title' => '<span class="ab-icon dashicons dashicons-admin-comments"></span><span class="ab-label">AI Assistant</span>',
            'href'  => '#',
            'meta'  => array(
                'class' => 'snn-chat-toggle',
                'title' => 'Open AI Assistant',
            ),
        ) );
    }

    /**
     * Enqueue styles and scripts
     */
    public function enqueue_assets() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Inline styles
        wp_add_inline_style( 'dashicons', $this->get_inline_css() );

        // Pass configuration to JavaScript
        $ai_config = function_exists( 'snn_get_ai_api_config' ) ? snn_get_ai_api_config() : array();
        
        wp_localize_script( 'jquery', 'snnChatConfig', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'restUrl'       => rest_url( 'snn-abilities/v1/' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'currentUserId' => get_current_user_id(),
            'userName'      => wp_get_current_user()->display_name,
            'ai'            => $ai_config,
        ) );
    }

    /**
     * Render overlay HTML
     */
    public function render_overlay() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }
        ?>
        <div id="snn-chat-overlay" class="snn-chat-overlay" style="display: none;">
            <div class="snn-chat-container">
                <!-- Header -->
                <div class="snn-chat-header">
                    <div class="snn-chat-title">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>AI Assistant</span>
                    </div>
                    <div class="snn-chat-controls">
                        <button class="snn-chat-btn snn-chat-clear" title="Clear conversation">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                        <button class="snn-chat-btn snn-chat-minimize" title="Minimize">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                        <button class="snn-chat-btn snn-chat-close" title="Close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="snn-chat-messages" id="snn-chat-messages">
                    <div class="snn-chat-welcome">
                        <div class="snn-chat-welcome-icon">
                            <span class="dashicons dashicons-admin-comments"></span>
                        </div>
                        <h3>Hello, <?php echo esc_html( wp_get_current_user()->display_name ); ?>!</h3>
                        <p>I'm your AI assistant. I can help you with WordPress tasks like:</p>
                        <ul>
                            <li>Creating and editing posts</li>
                            <li>Managing content</li>
                            <li>Searching and finding information</li>
                            <li>Site configuration</li>
                        </ul>
                        <p><small>Type a message to get started.</small></p>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="snn-chat-typing" style="display: none;">
                    <span></span><span></span><span></span>
                </div>

                <!-- Input -->
                <div class="snn-chat-input-container">
                    <textarea 
                        id="snn-chat-input" 
                        class="snn-chat-input" 
                        placeholder="Ask me anything..."
                        rows="1"
                    ></textarea>
                    <button id="snn-chat-send" class="snn-chat-send" title="Send message">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>

                <!-- Status -->
                <div class="snn-chat-status" id="snn-chat-status"></div>
            </div>
        </div>

        <script>
        (function($) {
            'use strict';

            // Chat state
            const ChatState = {
                messages: [],
                abilities: [],
                isOpen: false,
                isProcessing: false,
                abortController: null
            };

            // Initialize
            $(document).ready(function() {
                initChat();
                loadAbilities();
            });

            /**
             * Initialize chat interface
             */
            function initChat() {
                // Toggle overlay
                $('.snn-chat-toggle, .snn-chat-close').on('click', function(e) {
                    e.preventDefault();
                    toggleChat();
                });

                // Minimize
                $('.snn-chat-minimize').on('click', function() {
                    $('#snn-chat-overlay').toggleClass('minimized');
                });

                // Clear chat
                $('.snn-chat-clear').on('click', function() {
                    if (confirm('Clear conversation history?')) {
                        clearChat();
                    }
                });

                // Send message
                $('#snn-chat-send').on('click', sendMessage);
                
                // Send on Enter (Shift+Enter for newline)
                $('#snn-chat-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });

                // Auto-resize textarea
                $('#snn-chat-input').on('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Make draggable
                makeDraggable();
            }

            /**
             * Toggle chat overlay
             */
            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-chat-overlay').toggle();
                
                if (ChatState.isOpen) {
                    $('#snn-chat-input').focus();
                }
            }

            /**
             * Load available abilities from API
             */
            async function loadAbilities() {
                try {
                    const response = await fetch(snnChatConfig.restUrl + 'abilities', {
                        headers: {
                            'X-WP-Nonce': snnChatConfig.nonce
                        }
                    });
                    
                    if (response.ok) {
                        ChatState.abilities = await response.json();
                        console.log('Loaded abilities:', ChatState.abilities.length);
                    }
                } catch (error) {
                    console.error('Failed to load abilities:', error);
                }
            }

            /**
             * Send user message
             */
            async function sendMessage() {
                const input = $('#snn-chat-input');
                const message = input.val().trim();

                if (!message || ChatState.isProcessing) {
                    return;
                }

                // Add user message
                addMessage('user', message);
                input.val('').css('height', 'auto');

                // Process with AI
                await processWithAI(message);
            }

            /**
             * Process message with AI agent
             */
            async function processWithAI(userMessage) {
                ChatState.isProcessing = true;
                showTyping();
                setStatus('Thinking...');

                try {
                    // Prepare conversation context
                    const context = ChatState.messages.slice(-10); // Last 10 messages
                    
                    // Build AI prompt with abilities
                    const systemPrompt = buildSystemPrompt();
                    const messages = [
                        { role: 'system', content: systemPrompt },
                        ...context.map(m => ({
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: m.content
                        }))
                    ];

                    // Call AI API
                    const aiResponse = await callAI(messages);
                    
                    hideTyping();

                    // Check if AI wants to execute abilities
                    const abilityResults = await executeAbilitiesFromResponse(aiResponse);
                    
                    // Add AI response
                    if (abilityResults.length > 0) {
                        const summary = summarizeAbilityResults(abilityResults);
                        addMessage('assistant', aiResponse + '\n\n' + summary, abilityResults);
                    } else {
                        addMessage('assistant', aiResponse);
                    }

                    setStatus('');
                } catch (error) {
                    hideTyping();
                    addMessage('error', 'Sorry, something went wrong: ' + error.message);
                    setStatus('');
                } finally {
                    ChatState.isProcessing = false;
                }
            }

            /**
             * Build system prompt with available abilities
             */
            function buildSystemPrompt() {
                const basePrompt = snnChatConfig.ai.systemPrompt || 'You are a helpful WordPress assistant.';
                
                if (ChatState.abilities.length === 0) {
                    return basePrompt;
                }

                const abilitiesDesc = ChatState.abilities.map(ability => {
                    return `- ${ability.name}: ${ability.description}`;
                }).join('\n');

                return `${basePrompt}

You have access to the following WordPress abilities:
${abilitiesDesc}

When the user asks you to perform tasks, you can use these abilities. To execute an ability, respond with a JSON block like this:
\`\`\`json
{
  "abilities": [
    {"name": "ability-name", "input": {"param": "value"}}
  ]
}
\`\`\`

You can execute multiple abilities in sequence. Always explain what you're doing in plain language along with the JSON.`;
            }

            /**
             * Call AI API
             */
            async function callAI(messages) {
                const config = snnChatConfig.ai;
                
                if (!config.apiKey || !config.apiEndpoint) {
                    throw new Error('AI API not configured. Please check settings.');
                }

                ChatState.abortController = new AbortController();

                const response = await fetch(config.apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${config.apiKey}`
                    },
                    body: JSON.stringify({
                        model: config.model,
                        messages: messages,
                        temperature: 0.7,
                        max_tokens: 2000
                    }),
                    signal: ChatState.abortController.signal
                });

                if (!response.ok) {
                    throw new Error(`AI API error: ${response.status}`);
                }

                const data = await response.json();
                return data.choices[0].message.content;
            }

            /**
             * Extract and execute abilities from AI response
             */
            async function executeAbilitiesFromResponse(response) {
                const results = [];
                
                // Look for JSON code blocks
                const jsonMatch = response.match(/```json\n?([\s\S]*?)\n?```/);
                if (!jsonMatch) {
                    return results;
                }

                try {
                    const parsed = JSON.parse(jsonMatch[1]);
                    
                    if (parsed.abilities && Array.isArray(parsed.abilities)) {
                        setStatus('Executing abilities...');
                        
                        for (const ability of parsed.abilities) {
                            const result = await executeAbility(ability.name, ability.input || {});
                            results.push({
                                ability: ability.name,
                                result: result
                            });
                        }
                    }
                } catch (error) {
                    console.error('Failed to parse ability execution:', error);
                }

                return results;
            }

            /**
             * Execute a single ability
             */
            async function executeAbility(abilityName, input) {
                try {
                    const response = await fetch(
                        snnChatConfig.restUrl + 'abilities/' + encodeURIComponent(abilityName) + '/run',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': snnChatConfig.nonce
                            },
                            body: JSON.stringify(input)
                        }
                    );

                    if (!response.ok) {
                        const error = await response.json();
                        return { success: false, error: error.message || 'Failed to execute' };
                    }

                    return await response.json();
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }

            /**
             * Summarize ability execution results
             */
            function summarizeAbilityResults(results) {
                const summary = results.map(r => {
                    const status = r.result.success ? '✓' : '✗';
                    return `${status} ${r.ability}`;
                }).join('\n');

                return `**Executed:**\n${summary}`;
            }

            /**
             * Add message to chat
             */
            function addMessage(role, content, metadata = null) {
                const message = {
                    role: role,
                    content: content,
                    metadata: metadata,
                    timestamp: Date.now()
                };

                ChatState.messages.push(message);

                const $messages = $('#snn-chat-messages');
                const $welcome = $messages.find('.snn-chat-welcome');
                
                if ($welcome.length) {
                    $welcome.remove();
                }

                const $message = $('<div>')
                    .addClass('snn-chat-message')
                    .addClass('snn-chat-message-' + role)
                    .html(formatMessage(content));

                $messages.append($message);
                scrollToBottom();
            }

            /**
             * Format message content (basic markdown)
             */
            function formatMessage(content) {
                return content
                    .replace(/```json\n?[\s\S]*?\n?```/g, '') // Remove JSON blocks
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
            }

            /**
             * Show/hide typing indicator
             */
            function showTyping() {
                $('.snn-chat-typing').show();
                scrollToBottom();
            }

            function hideTyping() {
                $('.snn-chat-typing').hide();
            }

            /**
             * Set status message
             */
            function setStatus(message) {
                $('#snn-chat-status').text(message);
            }

            /**
             * Scroll to bottom
             */
            function scrollToBottom() {
                const $messages = $('#snn-chat-messages');
                $messages.scrollTop($messages[0].scrollHeight);
            }

            /**
             * Clear chat
             */
            function clearChat() {
                ChatState.messages = [];
                $('#snn-chat-messages').html(`
                    <div class="snn-chat-welcome">
                        <div class="snn-chat-welcome-icon">
                            <span class="dashicons dashicons-admin-comments"></span>
                        </div>
                        <h3>Conversation cleared</h3>
                        <p>Start a new conversation by typing a message.</p>
                    </div>
                `);
            }

            /**
             * Make overlay draggable
             */
            function makeDraggable() {
                const $overlay = $('#snn-chat-overlay');
                const $header = $('.snn-chat-header');
                let isDragging = false;
                let currentX, currentY, initialX, initialY;

                $header.on('mousedown', function(e) {
                    if ($(e.target).closest('button').length) {
                        return;
                    }

                    isDragging = true;
                    initialX = e.clientX - $overlay.offset().left;
                    initialY = e.clientY - $overlay.offset().top;
                    $overlay.addClass('dragging');
                });

                $(document).on('mousemove', function(e) {
                    if (!isDragging) return;

                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;

                    $overlay.css({
                        left: currentX + 'px',
                        top: currentY + 'px',
                        right: 'auto',
                        bottom: 'auto'
                    });
                });

                $(document).on('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        $overlay.removeClass('dragging');
                    }
                });
            }

        })(jQuery);
        </script>
        <?php
    }

    /**
     * Get inline CSS
     */
    private function get_inline_css() {
        return '
        .snn-chat-overlay {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .snn-chat-container {
            width: 420px;
            height: 600px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .snn-chat-overlay.minimized .snn-chat-container {
            height: auto;
        }

        .snn-chat-overlay.minimized .snn-chat-messages,
        .snn-chat-overlay.minimized .snn-chat-input-container,
        .snn-chat-overlay.minimized .snn-chat-status,
        .snn-chat-overlay.minimized .snn-chat-typing {
            display: none !important;
        }

        .snn-chat-overlay.dragging {
            cursor: move;
        }

        .snn-chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
            user-select: none;
        }

        .snn-chat-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
        }

        .snn-chat-title .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .snn-chat-controls {
            display: flex;
            gap: 4px;
        }

        .snn-chat-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .snn-chat-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .snn-chat-btn .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .snn-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
        }

        .snn-chat-welcome {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .snn-chat-welcome-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .snn-chat-welcome-icon .dashicons {
            color: #fff;
            font-size: 32px;
            width: 32px;
            height: 32px;
        }

        .snn-chat-welcome h3 {
            margin: 0 0 12px;
            font-size: 20px;
            color: #333;
        }

        .snn-chat-welcome p {
            margin: 12px 0;
            line-height: 1.6;
        }

        .snn-chat-welcome ul {
            text-align: left;
            max-width: 280px;
            margin: 16px auto;
            padding-left: 20px;
        }

        .snn-chat-welcome li {
            margin: 8px 0;
            line-height: 1.5;
        }

        .snn-chat-message {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 12px;
            line-height: 1.5;
            max-width: 85%;
            word-wrap: break-word;
        }

        .snn-chat-message-user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .snn-chat-message-assistant {
            background: #fff;
            color: #333;
            border: 1px solid #e0e0e0;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }

        .snn-chat-message-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
            margin-right: auto;
        }

        .snn-chat-typing {
            padding: 12px 20px;
            background: #f9f9f9;
        }

        .snn-chat-typing span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #999;
            margin-right: 4px;
            animation: typing 1.4s infinite;
        }

        .snn-chat-typing span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .snn-chat-typing span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-8px); opacity: 1; }
        }

        .snn-chat-input-container {
            padding: 16px;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .snn-chat-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            resize: none;
            outline: none;
            font-family: inherit;
            min-height: 42px;
            max-height: 120px;
        }

        .snn-chat-input:focus {
            border-color: #667eea;
        }

        .snn-chat-send {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .snn-chat-send:hover {
            transform: scale(1.05);
        }

        .snn-chat-send:active {
            transform: scale(0.95);
        }

        .snn-chat-send .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .snn-chat-status {
            padding: 8px 20px;
            background: #f0f0f0;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
            min-height: 32px;
        }

        #wpadminbar #wp-admin-bar-snn-ai-chat .ab-icon:before {
            content: "\f125";
            top: 2px;
        }

        @media (max-width: 768px) {
            .snn-chat-container {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
            }
            
            .snn-chat-overlay {
                bottom: 0;
                right: 0;
            }
        }
        ';
    }
}

// Initialize
SNN_Chat_Overlay::get_instance();