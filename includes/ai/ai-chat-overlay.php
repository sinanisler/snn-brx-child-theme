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
                        <span class="snn-agent-state-badge" id="snn-agent-state-badge"></span>
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
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
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

            // Agent states enum
            const AgentState = {
                IDLE: 'idle',
                THINKING: 'thinking',
                EXECUTING: 'executing',
                INTERPRETING: 'interpreting',
                DONE: 'done',
                ERROR: 'error'
            };

            // Chat state
            const ChatState = {
                messages: [],
                abilities: [],
                isOpen: false,
                isProcessing: false,
                abortController: null,
                currentState: AgentState.IDLE,
                currentAbility: null
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
                        console.log('✓ Loaded abilities:', ChatState.abilities.length);
                        console.log('Abilities:', ChatState.abilities.map(a => a.name).join(', '));
                    } else {
                        console.error('Failed to load abilities:', response.status);
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
                setAgentState(AgentState.THINKING);

                try {
                    // Prepare conversation context (include last execution results)
                    const context = ChatState.messages.slice(-10).map(m => {
                        let content = m.content;

                        // If this message had ability executions, include results in context
                        if (m.metadata && m.metadata.length > 0) {
                            const resultsText = m.metadata.map(r => {
                                if (r.result.success && r.result.data) {
                                    return `[Executed ${r.ability}: ${JSON.stringify(r.result.data).substring(0, 200)}]`;
                                } else if (!r.result.success) {
                                    return `[Failed ${r.ability}: ${r.result.error || 'Unknown error'}]`;
                                }
                                return '';
                            }).filter(Boolean).join(' ');

                            if (resultsText) {
                                content = content + '\n\nExecution results: ' + resultsText;
                            }
                        }

                        return {
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: content
                        };
                    });

                    // Build AI prompt with abilities
                    const systemPrompt = buildSystemPrompt();
                    const messages = [
                        { role: 'system', content: systemPrompt },
                        ...context
                    ];

                    // Call AI API
                    const aiResponse = await callAI(messages);

                    hideTyping();

                    // Check if AI wants to execute abilities
                    const abilityResults = await executeAbilitiesFromResponse(aiResponse);

                    // Build response with results
                    let displayResponse = aiResponse;
                    let responseMetadata = null;

                    if (abilityResults.length > 0) {
                        responseMetadata = abilityResults;

                        // Remove JSON block from display
                        displayResponse = displayResponse.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();

                        // Add formatted results
                        const resultsHtml = formatAbilityResults(abilityResults);
                        displayResponse += '\n\n' + resultsHtml;

                        // Send results back to AI for interpretation if any succeeded
                        const hasSuccessful = abilityResults.some(r => r.result.success);
                        if (hasSuccessful) {
                            await interpretResults(messages, aiResponse, abilityResults);
                        }
                    }

                    // Add AI response to chat
                    addMessage('assistant', displayResponse, responseMetadata);

                    // Mark as done
                    setAgentState(AgentState.DONE);
                } catch (error) {
                    hideTyping();
                    addMessage('error', 'Sorry, something went wrong: ' + error.message);
                    setAgentState(AgentState.ERROR, { error: error.message });
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
                    const params = ability.input_schema?.properties ? 
                        Object.entries(ability.input_schema.properties).map(([key, val]) => 
                            `    - ${key} (${val.type}${ability.input_schema.required?.includes(key) ? ', required' : ''}): ${val.description || ''}`
                        ).join('\n') : '    No parameters';
                    
                    return `**${ability.name}** - ${ability.description}
  Category: ${ability.category}
  Parameters:
${params}`;
                }).join('\n\n');

                return `${basePrompt}

IMPORTANT: You are an AI assistant with the ability to execute WordPress actions through registered abilities.

=== YOUR CAPABILITIES ===

You have ${ChatState.abilities.length} abilities available. When users ask "what can you do" or similar questions, list ALL of these abilities with their descriptions:

${abilitiesDesc}

=== AVAILABLE ABILITIES (DETAILED) ===

${abilitiesDesc}

=== HOW TO USE ABILITIES ===

When the user asks you to perform a task that matches one of these abilities:

1. FIRST: Explain to the user in natural language what you're about to do
2. THEN: Include a JSON code block with the abilities to execute
3. AFTER: I will execute the abilities and show you the results

Example response format:
"I'll get the site information for you.

\`\`\`json
{
  "abilities": [
    {"name": "snn/site-info", "input": {}}
  ]
}
\`\`\`"

For abilities with parameters, include them in the input:
\`\`\`json
{
  "abilities": [
    {"name": "snn/get-posts", "input": {"post_type": "post", "posts_per_page": 5}}
  ]
}
\`\`\`

You can chain multiple abilities:
\`\`\`json
{
  "abilities": [
    {"name": "snn/site-info", "input": {}},
    {"name": "snn/get-posts", "input": {"posts_per_page": 3}}
  ]
}
\`\`\`

IMPORTANT RULES:
- Always explain what you're doing before the JSON block
- Match parameter types exactly (string, integer, boolean, etc.)
- Include all required parameters
- After execution, I'll provide results - interpret them for the user
- If you're not sure, ask the user for clarification instead of guessing`;
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
                    console.log('No JSON block found in response');
                    return results;
                }

                console.log('Found JSON block:', jsonMatch[1]);

                try {
                    const parsed = JSON.parse(jsonMatch[1]);
                    console.log('Parsed JSON:', parsed);

                    if (parsed.abilities && Array.isArray(parsed.abilities)) {
                        const totalAbilities = parsed.abilities.length;

                        for (let i = 0; i < parsed.abilities.length; i++) {
                            const ability = parsed.abilities[i];
                            const current = i + 1;

                            // Update state with current ability being executed
                            setAgentState(AgentState.EXECUTING, {
                                abilityName: ability.name,
                                current: current,
                                total: totalAbilities
                            });

                            console.log(`Executing: ${ability.name} (${current}/${totalAbilities})`, ability.input);
                            const result = await executeAbility(ability.name, ability.input || {});
                            console.log(`Result for ${ability.name}:`, result);

                            results.push({
                                ability: ability.name,
                                result: result
                            });
                        }
                    } else {
                        console.warn('JSON does not contain abilities array');
                    }
                } catch (error) {
                    console.error('Failed to parse ability execution:', error);
                    addMessage('error', 'Failed to parse ability execution: ' + error.message);
                    setAgentState(AgentState.ERROR, { error: error.message });
                }

                return results;
            }

            /**
             * Execute a single ability
             */
            async function executeAbility(abilityName, input) {
                try {
                    // Encode the ability name but keep forward slashes as-is for WordPress REST API
                    const encodedName = abilityName.split('/').map(part => encodeURIComponent(part)).join('/');
                    const apiUrl = snnChatConfig.restUrl + 'abilities/' + encodedName + '/run';
                    
                    console.log(`Calling API: ${apiUrl}`);
                    console.log('Input:', input);
                    
                    const response = await fetch(
                        apiUrl,
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
                        const errorText = await response.text();
                        console.error(`API error ${response.status}:`, errorText);
                        
                        let error;
                        try {
                            error = JSON.parse(errorText);
                        } catch (e) {
                            error = { message: errorText };
                        }
                        
                        return { success: false, error: error.message || `HTTP ${response.status}` };
                    }

                    const result = await response.json();
                    console.log('API response:', result);
                    return result;
                } catch (error) {
                    console.error('Execution error:', error);
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
             * Format ability execution results as HTML
             */
            function formatAbilityResults(results) {
                let html = '<div class="ability-results">';
                
                results.forEach(r => {
                    const status = r.result.success ? '✅' : '❌';
                    const statusClass = r.result.success ? 'success' : 'error';
                    
                    html += `<div class="ability-result ${statusClass}">`;
                    html += `<strong>${status} ${r.ability}</strong>`;
                    
                    if (r.result.success && r.result.data) {
                        // Show a preview of the data
                        const preview = formatDataPreview(r.result.data);
                        html += `<div class="result-data">${preview}</div>`;
                    } else if (!r.result.success) {
                        html += `<div class="result-error">${r.result.error || 'Unknown error'}</div>`;
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
                return html;
            }

            /**
             * Format data preview for display
             */
            function formatDataPreview(data) {
                if (Array.isArray(data)) {
                    if (data.length === 0) return 'Empty array';
                    return `Found ${data.length} item${data.length !== 1 ? 's' : ''}`;
                } else if (typeof data === 'object' && data !== null) {
                    const keys = Object.keys(data);
                    if (keys.length === 0) return 'Empty object';
                    
                    // Format object properties nicely
                    const formatted = keys.map(k => {
                        let value = data[k];
                        if (typeof value === 'string') {
                            value = value.length > 50 ? value.substring(0, 50) + '...' : value;
                        } else if (typeof value === 'object') {
                            value = Array.isArray(value) ? `[${value.length} items]` : '[object]';
                        }
                        return `<div><strong>${k}:</strong> ${value}</div>`;
                    }).join('');
                    
                    return formatted;
                }
                return String(data).substring(0, 100);
            }

            /**
             * Send results back to AI for interpretation
             */
            async function interpretResults(previousMessages, aiResponse, results) {
                try {
                    // Update state to interpreting
                    setAgentState(AgentState.INTERPRETING);
                    showTyping();

                    const resultsText = results.map(r => {
                        return `Ability: ${r.ability}\nSuccess: ${r.result.success}\nData: ${JSON.stringify(r.result.data || r.result.error, null, 2)}`;
                    }).join('\n\n');

                    const interpretMessages = [
                        ...previousMessages,
                        { role: 'assistant', content: aiResponse },
                        {
                            role: 'user',
                            content: `The abilities were executed. Here are the results:\n\n${resultsText}\n\nPlease provide a brief, natural summary of these results for the user.`
                        }
                    ];

                    const interpretation = await callAI(interpretMessages);

                    hideTyping();

                    // Add interpretation as a follow-up message
                    addMessage('assistant', interpretation);

                } catch (error) {
                    console.error('Failed to interpret results:', error);
                    hideTyping();
                    setAgentState(AgentState.ERROR, { error: 'Failed to interpret results' });
                }
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
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/`(.*?)`/g, '<code>$1</code>')
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
             * Set agent state and update UI
             */
            function setAgentState(state, metadata = null) {
                ChatState.currentState = state;

                // Log state transition
                console.log('🔄 Agent State:', state, metadata || '');

                const $badge = $('#snn-agent-state-badge');
                let statusText = '';
                let badgeText = '';
                let badgeClass = '';

                // Clear existing state classes
                $badge.removeClass('badge-idle badge-thinking badge-executing badge-interpreting badge-done badge-error');

                switch(state) {
                    case AgentState.IDLE:
                        badgeText = '';
                        badgeClass = 'badge-idle';
                        break;

                    case AgentState.THINKING:
                        statusText = '🤔 Thinking...';
                        badgeText = 'Thinking';
                        badgeClass = 'badge-thinking';
                        addStateMessage(statusText, 'thinking');
                        break;

                    case AgentState.EXECUTING:
                        if (metadata && metadata.abilityName) {
                            statusText = `⚡ Executing: ${metadata.abilityName}`;
                            if (metadata.current && metadata.total) {
                                statusText += ` (${metadata.current}/${metadata.total})`;
                            }
                        } else {
                            statusText = '⚡ Executing Ability...';
                        }
                        badgeText = 'Executing';
                        badgeClass = 'badge-executing';
                        addStateMessage(statusText, 'executing');
                        break;

                    case AgentState.INTERPRETING:
                        statusText = '📊 Interpreting Results...';
                        badgeText = 'Interpreting';
                        badgeClass = 'badge-interpreting';
                        addStateMessage(statusText, 'interpreting');
                        break;

                    case AgentState.DONE:
                        statusText = '✅ Done';
                        badgeText = 'Done';
                        badgeClass = 'badge-done';
                        addStateMessage(statusText, 'done');
                        // Auto-clear badge after 2 seconds
                        setTimeout(() => {
                            if (ChatState.currentState === AgentState.DONE) {
                                setAgentState(AgentState.IDLE);
                            }
                        }, 2000);
                        break;

                    case AgentState.ERROR:
                        statusText = metadata && metadata.error ? `❌ Error: ${metadata.error}` : '❌ Error';
                        badgeText = 'Error';
                        badgeClass = 'badge-error';
                        addStateMessage(statusText, 'error');
                        // Auto-clear badge after 3 seconds
                        setTimeout(() => {
                            if (ChatState.currentState === AgentState.ERROR) {
                                setAgentState(AgentState.IDLE);
                            }
                        }, 3000);
                        break;
                }

                $badge.addClass(badgeClass).text(badgeText);

                // Show/hide badge based on state
                if (badgeText) {
                    $badge.show();
                } else {
                    $badge.hide();
                }
            }

            /**
             * Add state message to chat history
             */
            function addStateMessage(text, stateClass) {
                const $messages = $('#snn-chat-messages');
                const $welcome = $messages.find('.snn-chat-welcome');

                if ($welcome.length) {
                    $welcome.remove();
                }

                const $stateMessage = $('<div>')
                    .addClass('snn-chat-state-message')
                    .addClass('state-' + stateClass)
                    .text(text);

                $messages.append($stateMessage);
                scrollToBottom();
            }

            /**
             * Set status message (backward compatibility wrapper)
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

        .snn-agent-state-badge {
            display: none;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .snn-agent-state-badge.badge-thinking {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            animation: badgePulse 1.5s ease-in-out infinite;
        }

        .snn-agent-state-badge.badge-executing {
            background: rgba(255, 255, 255, 0.95);
            color: #f57c00;
            animation: badgePulse 1.2s ease-in-out infinite;
        }

        .snn-agent-state-badge.badge-interpreting {
            background: rgba(255, 255, 255, 0.95);
            color: #388e3c;
            animation: badgePulse 1.5s ease-in-out infinite;
        }

        .snn-agent-state-badge.badge-done {
            background: rgba(255, 255, 255, 0.95);
            color: #2e7d32;
        }

        .snn-agent-state-badge.badge-error {
            background: rgba(255, 255, 255, 0.95);
            color: #c62828;
            animation: badgeShake 0.5s ease-in-out;
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        @keyframes badgeShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-3deg); }
            75% { transform: rotate(3deg); }
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

        .snn-chat-state-message {
            padding: 8px 14px;
            margin: 8px auto;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            max-width: 80%;
            animation: fadeInScale 0.3s ease-out;
        }

        .snn-chat-state-message.state-thinking {
            background: linear-gradient(90deg, #e3f2fd, #f3e5f5);
            color: #667eea;
            border: 1px solid #bbdefb;
        }

        .snn-chat-state-message.state-executing {
            background: linear-gradient(90deg, #fff3e0, #ffe0b2);
            color: #f57c00;
            border: 1px solid #ffcc80;
        }

        .snn-chat-state-message.state-interpreting {
            background: linear-gradient(90deg, #e8f5e9, #c8e6c9);
            color: #388e3c;
            border: 1px solid #a5d6a7;
        }

        .snn-chat-state-message.state-done {
            background: linear-gradient(90deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
            border: 1px solid #81c784;
        }

        .snn-chat-state-message.state-error {
            background: linear-gradient(90deg, #ffebee, #ffcdd2);
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .ability-results {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }

        .ability-result {
            padding: 8px 12px;
            margin: 8px 0;
            border-radius: 6px;
            font-size: 13px;
        }

        .ability-result.success {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
        }

        .ability-result.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .ability-result strong {
            display: block;
            margin-bottom: 4px;
        }

        .result-data {
            color: #666;
            font-size: 12px;
            margin-top: 6px;
            line-height: 1.6;
        }

        .result-data div {
            padding: 3px 0;
        }

        .result-data strong {
            color: #444;
            font-weight: 600;
            margin-right: 4px;
        }

        .result-error {
            color: #dc2626;
            font-size: 12px;
        }

        .snn-chat-message code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .snn-chat-typing {
            padding: 12px 20px;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #999;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
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
            font-size: 11px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            min-height: 32px;
            transition: all 0.3s ease;
            font-weight: 400;
            display: flex;
            align-items: center;
            font-style: italic;
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