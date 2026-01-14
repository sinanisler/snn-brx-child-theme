<?php
/**
 * SNN AI Chat Overlay
 *
 * File: snn-chat-overlay.php
 *
 * Purpose: Provides an AI-powered chat interface accessible from anywhere in WordPress (admin and frontend).
 * Adds a button to the admin bar and displays a floating overlay that can execute WordPress abilities
 * through AI agent conversations. Uses the existing AI API configuration and integrates with the
 * WordPress Core Abilities API for autonomous task execution.
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
        // Add admin menu page
        add_action( 'admin_menu', array( $this, 'add_settings_submenu' ) );
        
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
     * Add AI Agent Settings submenu page
     */
    public function add_settings_submenu() {
        add_submenu_page(
            'snn-settings',
            __('AI Agent Settings', 'snn'),
            __('AI Agent Settings', 'snn'),
            'manage_options',
            'snn-ai-agent-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render AI Agent Settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AI Agent Settings', 'snn'); ?></h1>
            <p><?php echo esc_html__('Configure AI Agent chat overlay settings here.', 'snn'); ?></p>
            <!-- Add your settings form here -->
        </div>
        <?php
    }

    /**
     * Add button to WordPress admin bar
     */
    public function add_admin_bar_button( $wp_admin_bar ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Only show in wp-admin area
        if ( ! is_admin() ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'     => 'snn-ai-chat',
            'title'  => '<span style="font-size: 25px; background: linear-gradient(45deg, #2271b1, #e4dadd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; position: relative;  line-height: 1.2;">✦</span>',
            'href'   => '#',
            'parent' => 'top-secondary',
            'meta'   => array(
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
            'restUrl'       => rest_url( 'wp-abilities/v1/' ),
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
                        <br><p><small>Type a message to get started.</small></p>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="snn-chat-typing" style="display: none;">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <!-- State Indicator -->
                <div class="snn-chat-state-text" id="snn-chat-state-text"></div>

                <!-- Quick Actions -->
                <div class="snn-chat-quick-actions">
                    <button class="snn-quick-action-btn" data-message="List all available abilities">List Abilities</button>
                    <button class="snn-quick-action-btn" data-message="List all users">List Users</button>
                    <button class="snn-quick-action-btn" data-message="Show site details">See Site Details</button>
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

                // Quick action buttons
                $('.snn-quick-action-btn').on('click', function() {
                    const message = $(this).data('message');
                    $('#snn-chat-input').val(message);
                    sendMessage();
                });
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
                        const data = await response.json();
                        
                        // WordPress Abilities API returns an array of ability objects
                        // Each ability has properties: name, label, description, category, input_schema, output_schema, etc.
                        ChatState.abilities = Array.isArray(data) ? data : [];
                        
                        console.log('✓ Loaded abilities:', ChatState.abilities.length);
                        if (ChatState.abilities.length > 0) {
                            console.log('Abilities:', ChatState.abilities.map(a => a.name).join(', '));
                            console.log('Full abilities data:', ChatState.abilities);
                        } else {
                            console.warn('No abilities found. Make sure abilities are registered with show_in_rest => true');
                        }
                    } else {
                        console.error('Failed to load abilities:', response.status, await response.text());
                    }
                } catch (error) {
                    console.error('Failed to load abilities:', error);
                    console.error('Make sure WordPress 6.9+ is installed and Abilities API is available');
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

                    // Call AI API for initial planning
                    const aiResponse = await callAI(messages);

                    hideTyping();

                    // Extract abilities from response
                    const abilities = extractAbilitiesFromResponse(aiResponse);

                    if (abilities.length > 0) {
                        // Show initial AI message (without JSON block)
                        let initialMessage = aiResponse.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                        if (initialMessage) {
                            addMessage('assistant', initialMessage);
                        }

                        // Execute abilities sequentially with AI interpretation after each
                        await executeAbilitiesSequentially(messages, abilities);

                        // After all tasks complete, get final summary from AI
                        await provideFinalSummary(messages, abilities);
                    } else {
                        // No abilities to execute, just show AI response
                        addMessage('assistant', aiResponse);
                    }

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
                    return `${basePrompt}\n\nNote: No WordPress abilities are currently available. Make sure abilities are registered with show_in_rest enabled.`;
                }

                // Generate a list of abilities with descriptions
                const abilitiesList = ChatState.abilities.map(ability => {
                    return `- **${ability.name}**: ${ability.description || ability.label || 'No description'} (Category: ${ability.category || 'uncategorized'})`;
                }).join('\n');

                // Generate detailed ability descriptions with parameters
                const abilitiesDesc = ChatState.abilities.map(ability => {
                    let params = '    (No parameters)';
                    
                    if (ability.input_schema) {
                        if (ability.input_schema.properties) {
                            // Object type with properties
                            params = Object.entries(ability.input_schema.properties).map(([key, val]) => {
                                const isRequired = ability.input_schema.required?.includes(key) ? ' (required)' : '';
                                const defaultVal = val.default !== undefined ? ` [default: ${JSON.stringify(val.default)}]` : '';
                                const enumVals = val.enum ? ` [options: ${val.enum.join(', ')}]` : '';
                                return `    - ${key} (${val.type}${isRequired}): ${val.description || ''}${defaultVal}${enumVals}`;
                            }).join('\n');
                        } else if (ability.input_schema.type) {
                            // Simple type (string, integer, etc.)
                            params = `    Type: ${ability.input_schema.type}${ability.input_schema.description ? ' - ' + ability.input_schema.description : ''}`;
                        }
                    }
                    
                    return `**${ability.name}** - ${ability.description || ability.label || 'No description'}
  Category: ${ability.category || 'uncategorized'}
  Parameters:
${params}`;
                }).join('\n\n');

                return `${basePrompt}

IMPORTANT: You are an AI assistant with the ability to execute WordPress actions through the WordPress Core Abilities API.

=== YOUR CAPABILITIES ===

You have ${ChatState.abilities.length} WordPress Core abilities available:

${abilitiesList}

When users ask "what can you do" or "what are your capabilities", list these abilities and explain what each one does.

=== AVAILABLE ABILITIES (DETAILED) ===

${abilitiesDesc}

=== HOW TO USE ABILITIES ===

When the user asks you to perform a task that matches one of these abilities:

1. FIRST: Explain to the user in natural language what you're about to do
2. THEN: Include a JSON code block with the abilities to execute
3. AFTER: I will execute the abilities and show you the results

Example response format:
"I'll create a draft post for you.

\`\`\`json
{
  "abilities": [
    {"name": "core/create-post", "input": {"title": "My Post", "content": "Post content here", "status": "draft"}}
  ]
}
\`\`\`"

For abilities with parameters, include them in the input object:
\`\`\`json
{
  "abilities": [
    {"name": "core/get-posts", "input": {"post_type": "post", "posts_per_page": 5}}
  ]
}
\`\`\`

You can chain multiple abilities:
\`\`\`json
{
  "abilities": [
    {"name": "core/get-posts", "input": {"posts_per_page": 10}},
    {"name": "core/search-content", "input": {"query": "WordPress", "limit": 5}}
  ]
}
\`\`\`

IMPORTANT RULES:
- Always explain what you're doing before the JSON block
- Use the exact ability names as listed above (e.g., "${ChatState.abilities[0]?.name || 'core/get-posts'}")
- Match parameter types exactly (string, integer, boolean, array, etc.)
- Include all required parameters
- After execution, I'll provide results - interpret them for the user in a friendly way
- If you're not sure about parameters, ask the user for clarification instead of guessing
- Only use abilities that are listed above - don't make up ability names

VALIDATION REQUIREMENTS:
- For core/create-post and core/update-post: The "content" field MUST contain at least 1 character. If the user doesn't specify content, use a placeholder like " " (single space) or "Draft content" instead of empty string ""
- Never send empty strings ("") for required text fields - always provide at least a minimal value`;
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
             * Extract abilities from AI response (without executing)
             */
            function extractAbilitiesFromResponse(response) {
                const abilities = [];

                // Look for JSON code blocks
                const jsonMatch = response.match(/```json\n?([\s\S]*?)\n?```/);
                if (!jsonMatch) {
                    console.log('No JSON block found in response');
                    return abilities;
                }

                console.log('Found JSON block:', jsonMatch[1]);

                try {
                    const parsed = JSON.parse(jsonMatch[1]);
                    console.log('Parsed JSON:', parsed);

                    if (parsed.abilities && Array.isArray(parsed.abilities)) {
                        return parsed.abilities;
                    } else {
                        console.warn('JSON does not contain abilities array');
                    }
                } catch (error) {
                    console.error('Failed to parse abilities:', error);
                    addMessage('error', 'Failed to parse abilities: ' + error.message);
                    setAgentState(AgentState.ERROR, { error: error.message });
                }

                return abilities;
            }

            /**
             * Execute abilities one by one with AI interpretation after each
             */
            async function executeAbilitiesSequentially(conversationMessages, abilities) {
                const totalAbilities = abilities.length;

                for (let i = 0; i < abilities.length; i++) {
                    const ability = abilities[i];
                    const current = i + 1;

                    // Show thinking state before execution
                    showTyping();
                    setAgentState(AgentState.THINKING);
                    await sleep(300); // Brief pause for UX

                    // Update state to executing
                    setAgentState(AgentState.EXECUTING, {
                        abilityName: ability.name,
                        current: current,
                        total: totalAbilities
                    });

                    console.log(`Executing: ${ability.name} (${current}/${totalAbilities})`, ability.input);
                    const result = await executeAbility(ability.name, ability.input || {});
                    console.log(`Result for ${ability.name}:`, result);

                    hideTyping();

                    // Format and display this task's result
                    const resultHtml = formatSingleAbilityResult({
                        ability: ability.name,
                        result: result
                    });
                    addMessage('assistant', resultHtml, [{ ability: ability.name, result: result }]);

                    // Get AI interpretation for this specific result
                    if (result.success) {
                        await interpretSingleResult(conversationMessages, ability.name, result, current, totalAbilities);
                    } else {
                        // Show error interpretation
                        const errorMsg = `Task ${current}/${totalAbilities} (${ability.name}) failed: ${result.error || 'Unknown error'}`;
                        addMessage('assistant', errorMsg);
                    }

                    // Small delay between tasks for better UX
                    if (i < abilities.length - 1) {
                        await sleep(500);
                    }
                }
            }

            /**
             * Interpret a single ability result with AI
             */
            async function interpretSingleResult(conversationMessages, abilityName, result, current, total) {
                try {
                    showTyping();
                    setAgentState(AgentState.INTERPRETING);

                    const resultText = `Ability: ${abilityName}\nSuccess: ${result.success}\nData: ${JSON.stringify(result.data, null, 2)}`;

                    const interpretMessages = [
                        ...conversationMessages,
                        ...ChatState.messages.slice(-5).map(m => ({
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: m.content
                        })),
                        {
                            role: 'user',
                            content: `Task ${current} of ${total} completed successfully.\n\nResult:\n${resultText}\n\nProvide a brief, natural response about this result. ${total > 1 ? 'Note: This is one of multiple tasks being executed.' : ''}`
                        }
                    ];

                    const interpretation = await callAI(interpretMessages);
                    hideTyping();

                    // Strip any JSON blocks from interpretation (AI shouldn't include them, but just in case)
                    const cleanInterpretation = interpretation.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();

                    // Add interpretation as a follow-up message
                    addMessage('assistant', cleanInterpretation);

                } catch (error) {
                    console.error('Failed to interpret result:', error);
                    hideTyping();
                }
            }

            /**
             * Provide final summary after all tasks complete
             */
            async function provideFinalSummary(conversationMessages, abilities) {
                if (abilities.length <= 1) {
                    return; // No need for summary if only one task
                }

                try {
                    showTyping();
                    setAgentState(AgentState.THINKING);

                    const summaryMessages = [
                        ...conversationMessages,
                        ...ChatState.messages.slice(-15).map(m => ({
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: m.content
                        })),
                        {
                            role: 'user',
                            content: `All ${abilities.length} tasks have been completed. Provide a brief final summary of what was accomplished. Be conversational and context-aware.`
                        }
                    ];

                    const summary = await callAI(summaryMessages);
                    hideTyping();

                    // Strip any JSON blocks from summary (AI shouldn't include them, but just in case)
                    const cleanSummary = summary.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();

                    // Add summary message
                    addMessage('assistant', '✅ ' + cleanSummary);

                } catch (error) {
                    console.error('Failed to provide final summary:', error);
                    hideTyping();
                }
            }

            /**
             * Sleep utility for UX timing
             */
            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
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
                            body: JSON.stringify({ input: input })
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
                    
                    // Normalize the response - WordPress Abilities API might return different formats
                    // Check if it already has success property
                    if (typeof result.success !== 'undefined') {
                        // Already in expected format
                        return result;
                    }
                    
                    // If it has data property, it's likely successful
                    if (result.data !== undefined) {
                        return {
                            success: true,
                            data: result.data
                        };
                    }
                    
                    // If it has an error or message property indicating failure
                    if (result.error || result.message) {
                        return {
                            success: false,
                            error: result.error || result.message
                        };
                    }
                    
                    // Otherwise, treat the entire result as data (successful)
                    return {
                        success: true,
                        data: result
                    };
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
                    const success = r.result.success === true || (r.result.success !== false && !r.result.error);
                    const status = success ? '✅' : '❌';
                    const statusClass = success ? 'success' : 'error';
                    
                    html += `<div class="ability-result ${statusClass}">`;
                    html += `<strong>${status} ${r.ability}</strong>`;
                    
                    if (success) {
                        if (r.result.data) {
                            // Show a preview of the data
                            const preview = formatDataPreview(r.result.data);
                            html += `<div class="result-data">${preview}</div>`;
                        } else {
                            html += `<div class="result-data">Completed successfully</div>`;
                        }
                    } else {
                        const errorMsg = r.result.error || r.result.message || 'Unknown error';
                        html += `<div class="result-error">${errorMsg}</div>`;
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
                return html;
            }

            /**
             * Format single ability result as HTML
             */
            function formatSingleAbilityResult(r) {
                const success = r.result.success === true || (r.result.success !== false && !r.result.error);
                const status = success ? '✅' : '❌';
                const statusClass = success ? 'success' : 'error';
                
                let html = '<div class="ability-results">';
                html += `<div class="ability-result ${statusClass}">`;
                html += `<strong>${status} ${r.ability}</strong>`;
                
                if (success) {
                    if (r.result.data) {
                        // Show a preview of the data
                        const preview = formatDataPreview(r.result.data);
                        html += `<div class="result-data">${preview}</div>`;
                    } else {
                        html += `<div class="result-data">Completed successfully</div>`;
                    }
                } else {
                    const errorMsg = r.result.error || r.result.message || 'Unknown error';
                    html += `<div class="result-error">${errorMsg}</div>`;
                }
                
                html += '</div>';
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
                    
                    // Special handling for WordPress post objects
                    if (data.ID || data.id) {
                        const id = data.ID || data.id;
                        const title = data.post_title || data.title || 'Untitled';
                        const status = data.post_status || data.status || 'unknown';
                        const editUrl = `<?php echo admin_url('post.php?action=edit&post='); ?>${id}`;
                        
                        // Compact inline format
                        return `<strong>ID:</strong> ${id} | <strong>Title:</strong> ${title} | <strong>Status:</strong> ${status} | <a href="${editUrl}" target="_blank" style="color: #667eea;">Edit →</a>`;
                    }
                    
                    // Generic object formatting - compact inline format
                    const formatted = keys.slice(0, 3).map(k => {
                        let value = data[k];
                        if (typeof value === 'string') {
                            value = value.length > 30 ? value.substring(0, 30) + '...' : value;
                        } else if (typeof value === 'object') {
                            value = Array.isArray(value) ? `[${value.length} items]` : '[object]';
                        }
                        return `<strong>${k}:</strong> ${value}`;
                    }).join(' | ');
                    
                    if (keys.length > 3) {
                        return formatted + ` <em>(+${keys.length - 3} more)</em>`;
                    }
                    
                    return formatted;
                }
                return String(data).substring(0, 100);
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
                    // Hide quick actions when chat starts
                    $('.snn-chat-quick-actions').hide();
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

                const $stateText = $('#snn-chat-state-text');
                let stateMessage = '';

                switch(state) {
                    case AgentState.IDLE:
                        stateMessage = '';
                        break;

                    case AgentState.THINKING:
                        stateMessage = 'Thinking...';
                        break;

                    case AgentState.EXECUTING:
                        if (metadata && metadata.abilityName) {
                            stateMessage = `Executing ${metadata.abilityName}...`;
                            if (metadata.current && metadata.total) {
                                stateMessage = `Executing ${metadata.abilityName} (${metadata.current}/${metadata.total})...`;
                            }
                        } else {
                            stateMessage = 'Executing...';
                        }
                        break;

                    case AgentState.INTERPRETING:
                        stateMessage = 'Interpreting results...';
                        break;

                    case AgentState.DONE:
                        stateMessage = '';
                        break;

                    case AgentState.ERROR:
                        stateMessage = metadata && metadata.error ? `Error: ${metadata.error}` : 'Error occurred';
                        // Auto-clear after 3 seconds
                        setTimeout(() => {
                            if (ChatState.currentState === AgentState.ERROR) {
                                setAgentState(AgentState.IDLE);
                            }
                        }, 3000);
                        break;
                }

                // Update state text display
                if (stateMessage) {
                    $stateText.text(stateMessage).show();
                } else {
                    $stateText.hide();
                }
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
                // Show quick actions again
                $('.snn-chat-quick-actions').show();
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
.snn-chat-overlay { position: fixed; top: 32px; right: 0; bottom: 0; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.snn-chat-container { width: 400px; height: 100%; background: #fff; box-shadow: -2px 0 16px rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; }
.snn-chat-header { background: #1d2327; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; user-select: none; }
.snn-chat-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; }
.snn-chat-title .dashicons { font-size: 20px; width: 20px; height: 20px; }
.snn-agent-state-badge { display: none; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(255, 255, 255, 0.3); transition: all 0.3s ease; }
.snn-agent-state-badge.badge-thinking { background: rgba(255, 255, 255, 0.95); color: #667eea; animation: badgePulse 1.5s ease-in-out infinite; }
.snn-agent-state-badge.badge-executing { background: rgba(255, 255, 255, 0.95); color: #f57c00; animation: badgePulse 1.2s ease-in-out infinite; }
.snn-agent-state-badge.badge-interpreting { background: rgba(255, 255, 255, 0.95); color: #388e3c; animation: badgePulse 1.5s ease-in-out infinite; }
.snn-agent-state-badge.badge-done { background: rgba(255, 255, 255, 0.95); color: #2e7d32; }
.snn-agent-state-badge.badge-error { background: rgba(255, 255, 255, 0.95); color: #c62828; animation: badgeShake 0.5s ease-in-out; }
@keyframes badgePulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.05); opacity: 0.9; } }
@keyframes badgeShake { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-3deg); } 75% { transform: rotate(3deg); } }
.snn-chat-controls { display: flex; gap: 4px; }
.snn-chat-btn { background: rgba(255, 255, 255, 0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.snn-chat-btn:hover { background: rgba(255, 255, 255, 0.3); }
.snn-chat-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
.snn-chat-messages { flex: 1; overflow-y: auto; padding: 10px; background: #f9f9f9; }
.snn-chat-welcome { text-align: center; padding: 40px 20px; color: #666; }
.snn-chat-welcome-icon { width: 64px; height: 64px; margin: 0 auto 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.snn-chat-welcome-icon .dashicons { color: #fff; font-size: 32px; width: 32px; height: 32px; }
.snn-chat-welcome h3 { margin: 0 0 12px; font-size: 20px; color: #333; }
.snn-chat-welcome p { margin: 12px 0; line-height: 1.6; }
.snn-chat-welcome ul { text-align: left; max-width: 280px; margin: 16px auto; padding-left: 20px; }
.snn-chat-welcome li { margin: 8px 0; line-height: 1.5; text-align: center; }
.snn-chat-message { margin-bottom: 5px; padding: 8px; border-radius: 12px; line-height: 1.5; max-width: 95%; word-wrap: break-word; }
.snn-chat-message-user { background: #1d2327; color: #fff; margin-left: auto; border-bottom-right-radius: 4px; }
.snn-chat-message-assistant { background: #fff; color: #333; border: 1px solid #e0e0e0; margin-right: auto; border-bottom-left-radius: 4px; }
.snn-chat-message-error { background: #fee; color: #c33; border: 1px solid #fcc; margin-right: auto; }
.snn-chat-state-message { padding: 8px 14px; margin: 8px auto; border-radius: 16px; font-size: 12px; font-weight: 500; text-align: center; max-width: 80%; animation: fadeInScale 0.3s ease-out; }
.snn-chat-state-message.state-thinking { background: linear-gradient(90deg, #e3f2fd, #f3e5f5); color: #667eea; border: 1px solid #bbdefb; }
.snn-chat-state-message.state-executing { background: linear-gradient(90deg, #fff3e0, #ffe0b2); color: #f57c00; border: 1px solid #ffcc80; }
.snn-chat-state-message.state-interpreting { background: linear-gradient(90deg, #e8f5e9, #c8e6c9); color: #388e3c; border: 1px solid #a5d6a7; }
.snn-chat-state-message.state-done { background: linear-gradient(90deg, #e8f5e9, #c8e6c9); color: #2e7d32; border: 1px solid #81c784; }
.snn-chat-state-message.state-error { background: linear-gradient(90deg, #ffebee, #ffcdd2); color: #c62828; border: 1px solid #ef9a9a; }
@keyframes fadeInScale { from { opacity: 0; transform: scale(0.9) translateY(-10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.ability-results { margin-top: 0px; padding-top: 0px; }
.ability-result { padding: 6px 10px; margin: 4px 0; border-radius: 6px; font-size: 14px; line-height: 1.4; }
.ability-result.success { background: #f0f9ff; }
.ability-result.error { background: #fef2f2; border: 1px solid #fecaca; }
.ability-result strong { display: inline; margin-right: 6px; }
.result-data { color: #666; font-size: 14px; margin-top: 3px; line-height: 1.5; display: inline; }
.result-data strong { color: #444; font-weight: 600; margin-right: 2px; }
.result-error { color: #dc2626; font-size: 12px; }
.snn-chat-message code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.snn-chat-typing { padding: 12px 20px; background: #f9f9f9; display: flex; align-items: center; gap: 8px; }
.typing-dots { display: flex; gap: 4px; }
.typing-dots span { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #999; animation: typing 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.5; } 30% { transform: translateY(-8px); opacity: 1; } }
.snn-chat-state-text { display: none; padding: 8px 16px; background: #fff; font-size: 14px; color: #000; text-align: left; }
.snn-chat-quick-actions { padding: 8px 10px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 6px; flex-wrap: wrap; }
.snn-quick-action-btn { padding: 6px 12px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; color: #333; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.snn-quick-action-btn:hover { background: #1d2327; color: #fff; border-color: #1d2327; }
.snn-chat-input-container { padding: 10px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 12px; align-items: flex-end; }
.snn-chat-input { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: none; outline: none; font-family: inherit; min-height: 42px; max-height: 120px; }
.snn-chat-input:focus { border-color: #667eea; }
.snn-chat-send { width: 42px; height: 42px; background: #1d2327; border: none; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; flex-shrink: 0; }
.snn-chat-send:hover { transform: scale(1.05); }
.snn-chat-send:active { transform: scale(0.95); }
.snn-chat-send .dashicons { font-size: 20px; width: 20px; height: 20px; rotate: 90deg; }
#wpadminbar #wp-admin-bar-snn-ai-chat .ab-icon:before { content: "\f125"; top: 2px; }
@media (max-width: 768px) { .snn-chat-container { width: 100vw; height: 100%; } .snn-chat-overlay { top: 0; right: 0; } }
        ';
    }
}

// Initialize
SNN_Chat_Overlay::get_instance();