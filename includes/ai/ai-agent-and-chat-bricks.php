<?php
/**
 * SNN AI Chat for Bricks Builder
 *
 * File: ai-agent-and-chat-bricks.php
 *
 * Purpose: Provides an AI-powered chat interface for Bricks Builder frontend editor.
 * Integrates with Bricks reactive state to manipulate page content in real-time.
 *
 * Features:
 * - Frontend-only chat overlay (active when /?bricks=run)
 * - Bricks toolbar button for quick access
 * - Reactive state manipulation (replace, update section, add section)
 * - AI agent integration for content generation
 * - Real-time content injection into Bricks builder
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bricks Builder Chat Overlay Class
 */
class SNN_Bricks_Chat_Overlay {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only load on frontend when Bricks builder is active
        if ( ! $this->is_bricks_builder_active() ) {
            return;
        }

        // Check if AI Agent is enabled
        $main_chat = SNN_Chat_Overlay::get_instance();
        if ( ! $main_chat->is_enabled() ) {
            return;
        }

        // Enqueue scripts and styles on frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );

        // Render overlay HTML on frontend
        add_action( 'wp_footer', array( $this, 'render_overlay' ), 999 );
    }

    /**
     * Check if Bricks builder is active (frontend editor mode)
     */
    private function is_bricks_builder_active() {
        // Check if we're on frontend and Bricks builder is running
        return ! is_admin() && isset( $_GET['bricks'] ) && $_GET['bricks'] === 'run';
    }

    /**
     * Enqueue scripts and styles for frontend
     */
    public function enqueue_assets() {
        // Load markdown.js library for chat message rendering
        wp_enqueue_script(
            'markdown-js',
            get_stylesheet_directory_uri() . '/assets/js/markdown.min.js',
            array(),
            '0.5.0',
            true
        );

        // Get configuration from main chat overlay
        $main_chat = SNN_Chat_Overlay::get_instance();
        $ai_config = function_exists( 'snn_get_ai_api_config' ) ? snn_get_ai_api_config() : array();

        // Add custom system prompt and token count
        $ai_config['systemPrompt'] = $main_chat->get_system_prompt();
        $ai_config['maxTokens'] = $main_chat->get_token_count();

        // Get Bricks page context
        $page_context = $this->get_bricks_page_context();

        wp_localize_script( 'jquery', 'snnBricksChatConfig', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'restUrl'       => rest_url( 'wp-abilities/v1/' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'agentNonce'    => wp_create_nonce( 'snn_ai_agent_nonce' ),
            'currentUserId' => get_current_user_id(),
            'userName'      => wp_get_current_user()->display_name,
            'pageContext'   => $page_context,
            'ai'            => $ai_config,
            'settings'      => array(
                'enabledAbilities'  => $main_chat->get_enabled_abilities(),
                'debugMode'         => $main_chat->is_debug_enabled(),
                'maxRetries'        => $main_chat->get_max_retries(),
                'maxHistory'        => $main_chat->get_max_history(),
            ),
        ) );

        // Inline styles
        wp_add_inline_style( 'bricks-builder', $this->get_inline_css() );
    }

    /**
     * Get Bricks page context
     */
    private function get_bricks_page_context() {
        global $post;

        $context = array(
            'type' => 'bricks_builder',
            'details' => array(
                'description' => 'Bricks Builder - Frontend Page Editor',
                'builder' => 'bricks',
                'mode' => 'frontend',
            )
        );

        if ( $post ) {
            $context['details'] = array_merge( $context['details'], array(
                'post_id' => $post->ID,
                'post_type' => $post->post_type,
                'post_title' => $post->post_title,
                'post_status' => $post->post_status,
                'edit_url' => get_permalink( $post->ID ) . '?bricks=run',
            ) );
        }

        return $context;
    }

    /**
     * Render overlay HTML
     */
    public function render_overlay() {
        $main_chat = SNN_Chat_Overlay::get_instance();
        ?>
        <div id="snn-bricks-chat-overlay" class="snn-bricks-chat-overlay" style="display: none;">
            <div class="snn-bricks-chat-container">
                <!-- Header -->
                <div class="snn-bricks-chat-header">
                    <div class="snn-bricks-chat-title">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>SNN AI Agent</span>
                        <span class="snn-bricks-agent-state-badge" id="snn-bricks-agent-state-badge"></span>
                    </div>
                    <div class="snn-bricks-chat-controls">
                        <button class="snn-bricks-chat-btn snn-bricks-chat-new" title="New chat" id="snn-bricks-chat-new-btn">
                            <span class="snn-bricks-chat-plus">+</span>
                        </button>
                        <button class="snn-bricks-chat-btn snn-bricks-chat-history" title="Chat history" id="snn-bricks-chat-history-btn">
                            <span class="dashicons dashicons-backup"></span>
                        </button>
                        <button class="snn-bricks-chat-btn snn-bricks-chat-close" title="Close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- History Dropdown -->
                <div class="snn-bricks-chat-history-dropdown" id="snn-bricks-chat-history-dropdown" style="display: none;">
                    <div class="snn-bricks-history-header">
                        <strong><?php echo esc_html__('Chat History', 'snn'); ?></strong>
                        <button class="snn-bricks-history-close" id="snn-bricks-history-close">×</button>
                    </div>
                    <div class="snn-bricks-history-list" id="snn-bricks-history-list">
                        <div class="snn-bricks-history-loading"><?php echo esc_html__('Loading...', 'snn'); ?></div>
                    </div>
                </div>

                <?php if ( ! $main_chat->is_ai_globally_enabled() ) : ?>
                <!-- AI Features Disabled Warning -->
                <div class="snn-bricks-chat-messages" id="snn-bricks-chat-messages">
                    <div class="snn-bricks-chat-ai-disabled-warning">
                        <div class="snn-bricks-warning-icon">⚠️</div>
                        <h3><?php echo esc_html__( 'AI Features Disabled', 'snn' ); ?></h3>
                        <p><?php echo esc_html__( 'The global AI Features setting is currently disabled. Please enable it to use the AI chat assistant.', 'snn' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-ai-settings' ) ); ?>" class="snn-bricks-enable-ai-btn" target="_blank">
                            <?php echo esc_html__( 'Go to AI Settings', 'snn' ); ?> →
                        </a>
                    </div>
                </div>

                <!-- Input (disabled) -->
                <div class="snn-bricks-chat-input-container">
                    <textarea
                        id="snn-bricks-chat-input"
                        class="snn-bricks-chat-input"
                        placeholder="<?php echo esc_attr__( 'AI features are disabled...', 'snn' ); ?>"
                        rows="1"
                        disabled
                    ></textarea>
                    <button id="snn-bricks-chat-send" class="snn-bricks-chat-send" title="Send message" disabled>
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <?php else : ?>
                <!-- Messages -->
                <div class="snn-bricks-chat-messages" id="snn-bricks-chat-messages">
                    <div class="snn-bricks-chat-welcome">
                        <h3>Hello, <?php echo esc_html( wp_get_current_user()->display_name ); ?>!</h3>
                        <p>I can help you design beautiful pages with Bricks Builder.</p>
                        <p><small>Describe what you want to create, and I'll generate it for you.</small></p>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="snn-bricks-chat-typing" style="display: none;">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <!-- State Indicator -->
                <div class="snn-bricks-chat-state-text" id="snn-bricks-chat-state-text"></div>

                <!-- Quick Actions -->
                <div class="snn-bricks-chat-quick-actions">
                    <button class="snn-bricks-quick-action-btn" data-message="Act as a Senior UI Architect. Design a 6-section high-end Architecture Landing Page. Call 'snn/generate-bricks-content' for each: Hero Concept: Full-viewport section. Background '#111111'. Massive 'heading' (fontSize '120', letterSpacing '-5', color '#ffffff'). Overlay a smaller 'text-basic' at bottom-left using position 'absolute'. The Manifesto: A 'section' with background '#f4f4f4' and padding '120'. Left-aligned 'heading' (size '80', color '#222'). Right-aligned 'text-basic' (maxWidth '500', fontSize '22'). Project Showcase (Asymmetric): A 3-column grid where the first 'block' spans 2 columns (gridColumn 'span 2'). Use 'image' elements with aspectRatio '16/9' and _objectFit 'cover'. Expertise List: A dark '#000000' section. Use a vertical flex 'block' with a 'gap' of '60'. Each row has a large number (h3) and a description. Interactive Philosophy: Use the 'custom-html-css-script' element to create a mouse-follow cursor effect or a grain overlay. The Studio Stats: A 4-column row with huge numbers (fontSize '90', color '#c2b280'). Footer CTA: Minimalist. Centered 'button' (background 'transparent', border '1px solid #fff', borderRadius '0'). Technical Rule: No 'px' units in JSON. Use gridTemplateColumns:mobile_landscape '1fr' for every grid.">Real Estate</button>
                    <button class="snn-bricks-quick-action-btn" data-message="Act as a Creative Director. Build a 6-section 'Bento-Box' Agency Portfolio. Hero Marquee: Dark background '#080808'. Massive 'heading' scrolling across (use 'custom-html-css-script' for animation). Neon Mint accent color '#a3e635'. Bento Work Grid: A 'section' with a complex grid. Card 1 (2x2 span): background '#18181b', borderRadius '40', padding '60'. Card 2 (1x1): background '#db2777'. Card 3 (1x1): background '#6366f1'. All cards must have backgroundHover triggers. Service Ticker: A slim, high-speed ticker section. background '#a3e635', text '#000000'. The 'Process' Slider: A flex-row 'block' with overflow 'hidden'. Each child 'block' has a 1px border and minWidth '400'. Social Proof: A centered 'container' with maxWidth '900'. Use 'text-basic' for quotes (fontSize '32', fontStyle 'italic'). Giant Footer CTA: minHeight '80vh'. Centered 'heading' (size '150', color '#ffffff'). Button with _boxShadow '0 0 50px rgba(163, 230, 53, 0.4)'. Technical Rule: Ensure all vertical 'gap' values are '40' or higher. Use hex codes for all colors.">Agency</button>
                    <button class="snn-bricks-quick-action-btn" data-message="Act as a Lead Conversion Designer. Create a 5-section 'Ethereal Wellness' sales page. Frosted Hero: background '#fdfbf7' with soft Lavender gradients in corners. Centered 'image' with borderRadius '1000' (circular) and _boxShadow '0 40px 100px rgba(0,0,0,0.05)'. Curriculum Accordion: A 'section' using a vertical stack of 'blocks'. Each 'block' has background 'rgba(255,255,255,0.4)' and _backdropFilter 'blur(20px)'. The Wall of Love: A 3-column masonry grid of 'blocks'. Each 'block' (testimonial card) has borderRadius '32' and a very soft shadow. Visual Break: A full-width 'image' with height '600', _objectFit 'cover', and a 'text-basic' centered on top with opacity '0.8'. Final Invitation: A centered 'container'. 'heading' (fontSize '64', color '#84a98c'). Pill-shaped 'button' with padding '25 60'. Technical Rule: Use lineHeight '1.6' for body text and '1.1' for headings. Define all padding as an object {top, right, bottom, left}">Wellness</button>
                    <button class="snn-bricks-quick-action-btn" data-message="Act as a Fintech UI Lead. Design a 6-section 'Deep Space' Crypto Dashboard Landing Page. Data Hero: background '#000000'. Left: Huge technical heading (font 'Rajdhani'). Right: 'custom-html-css-script' element for a floating 3D orb or chart. Live Ticker: A black '#050505' 'section'. Flex row with 6 'blocks'. Each has a small 'image' (coin icon) and a green '#34d399' heading. Glass Feature Grid: 3-column layout. Each card: background 'rgba(255,255,255,0.02)', border '1px solid rgba(255,255,255,0.1)', _backdropFilter 'blur(10px)'. Staking Calculator: A centered 'block' with maxWidth '600', padding '80', and a unique _border color. Glowing Roadmap: A vertical 'block' with a center line. Use 'heading' and 'text-basic' pairs on alternating sides. Use Neon Blurple '#6366f1' for accents. Security Badge: A split 'section'. background '#111'. 'image' on left (Shield), 'text-basic' on right explaining the encryption. Terminal CTA: 'text-basic' content using monospace font 'Share Tech Mono'. A 'button' that looks like a command-line prompt. Technical Rule: Use raw numbers for all sizing. Add fontSize:mobile_landscape overrides for all headers to ensure they don't break on small screens">Fintech</button>
                </div>

                <!-- Input -->
                <div class="snn-bricks-chat-input-container" style="flex-direction: column;">
                    <div id="snn-chat-preview-area" style="width:100%; display:none; flex-wrap:wrap; gap:5px; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #eee;"></div>
                    <div style="display:flex; width:100%; gap:8px; align-items:flex-end;">
                        <button id="snn-bricks-chat-attach-btn" class="snn-bricks-chat-btn" style="background:#f0f0f0; color:#333; width:42px; height:42px;" title="Attach image">
                            <span class="dashicons dashicons-format-image"></span>
                        </button>
                        <input type="file" id="snn-chat-file-input" accept="image/*" style="display:none;">
                        <textarea
                            id="snn-bricks-chat-input"
                            class="snn-bricks-chat-input"
                            placeholder="Describe what you want to create... (Paste images supported)"
                            rows="1"
                        ></textarea>
                        <button id="snn-bricks-chat-send" class="snn-bricks-chat-send" title="Send message">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
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
                RETRYING: 'retrying',
                RECOVERING: 'recovering',
                DONE: 'done',
                ERROR: 'error'
            };

            // Configuration from settings
            const MAX_RETRIES = snnBricksChatConfig.settings.maxRetries || 3;
            const MAX_HISTORY = snnBricksChatConfig.settings.maxHistory || 20;
            const DEBUG_MODE = snnBricksChatConfig.settings.debugMode || false;
            const ENABLED_ABILITIES = snnBricksChatConfig.settings.enabledAbilities || [];

            // Recovery configuration
            const RECOVERY_CONFIG = {
                maxRecoveryAttempts: 3,
                baseDelay: 2000,
                maxDelay: 30000,
                rateLimitDelay: 5000
            };

            // Debug console wrapper
            const debugLog = function(...args) {
                if (DEBUG_MODE) {
                    console.log('[Bricks AI]', ...args);
                }
            };

            // Chat state
            const ChatState = {
                messages: [],
                abilities: [],
                attachments: [],
                isOpen: false,
                isProcessing: false,
                abortController: null,
                currentState: AgentState.IDLE,
                currentAbility: null,
                currentSessionId: null,
                autoSaveTimer: null,
                pageContext: snnBricksChatConfig.pageContext || { type: 'bricks_builder', details: {} },
                recoveryAttempts: 0,
                lastError: null,
                pendingOperation: null,
                bricksState: null
            };

            // Bricks Builder Integration
            const BricksHelper = {
                /**
                 * Get global context (colors, variables)
                 */
                getGlobalContext() {
                    const state = this.getState();
                    if (!state) return '';
                    
                    let context = '';
                    
                    // Colors
                    if (state.colorPalette) {
                        context += "**SITE COLORS (Use these variables):**\n";
                        try {
                            const palettes = Array.isArray(state.colorPalette) ? state.colorPalette : [state.colorPalette];
                            palettes.forEach(palette => {
                                if (palette && palette.colors) {
                                     palette.colors.forEach(c => {
                                         if (c.raw) context += `- ${c.name || 'Color'}: ${c.raw} (Value: ${c.light || c.hex})\n`;
                                         else if (c.hex) context += `- ${c.name || 'Color'}: ${c.hex}\n`;
                                     });
                                }
                            });
                        } catch(e) { console.error('Error parsing colors', e); }
                        context += "\n";
                    }
                    
                    // Global Variables
                    if (state.globalVariables) {
                        context += "**GLOBAL VARIABLES (Usage: var(--name)):**\n";
                        try {
                            const vars = Array.isArray(state.globalVariables) ? state.globalVariables : Object.values(state.globalVariables);
                            // Limit to 50 variables to avoid token overflow
                            vars.slice(0, 50).forEach(v => {
                                if (v.name && v.value) {
                                    context += `- var(--${v.name}): ${v.value}\n`;
                                }
                            });
                        } catch(e) {}
                        context += "\n";
                    }
                    
                    return context;
                },

                /**
                 * Check if Bricks reactive state is available
                 */
                isAvailable() {
                    try {
                        const vueApp = document.querySelector("[data-v-app]");
                        if (!vueApp || !vueApp.__vue_app__) return false;

                        const state = vueApp.__vue_app__.config.globalProperties.$_state;
                        return state && state.content;
                    } catch (e) {
                        console.error('Bricks state not available:', e);
                        return false;
                    }
                },

                /**
                 * Get Bricks reactive state
                 */
                getState() {
                    if (ChatState.bricksState) {
                        return ChatState.bricksState;
                    }

                    try {
                        const vueApp = document.querySelector("[data-v-app]");
                        ChatState.bricksState = vueApp.__vue_app__.config.globalProperties.$_state;
                        return ChatState.bricksState;
                    } catch (e) {
                        console.error('Failed to get Bricks state:', e);
                        return null;
                    }
                },

                /**
                 * Get current page content
                 */
                getCurrentContent() {
                    const state = this.getState();
                    if (!state) return null;

                    return {
                        elements: state.content,
                        elementCount: state.content ? state.content.length : 0
                    };
                },

                /**
                 * Replace all page content
                 */
                replaceAllContent(bricksContent) {
                    const state = this.getState();
                    if (!state) {
                        throw new Error('Bricks state not available');
                    }

                    try {
                        // Parse Bricks content if it's a string
                        let contentData = typeof bricksContent === 'string'
                            ? JSON.parse(bricksContent)
                            : bricksContent;

                        // Extract content array
                        const newElements = contentData.content || contentData;

                        if (!Array.isArray(newElements)) {
                            throw new Error('Invalid content format: expected array of elements');
                        }

                        debugLog('Replacing all content with', newElements.length, 'elements');

                        // Clear existing content
                        state.content.splice(0, state.content.length);

                        // Add new elements
                        newElements.forEach(element => {
                            state.content.push(element);
                        });

                        debugLog('✅ Content replaced successfully');

                        return {
                            success: true,
                            message: `Replaced page with ${newElements.length} elements`
                        };

                    } catch (error) {
                        console.error('Replace content error:', error);
                        return {
                            success: false,
                            error: `Failed to replace content: ${error.message}`
                        };
                    }
                },

                /**
                 * Update/replace a specific section
                 */
                updateSection(sectionIdentifier, bricksContent) {
                    const state = this.getState();
                    if (!state) {
                        throw new Error('Bricks state not available');
                    }

                    try {
                        // Parse Bricks content
                        let contentData = typeof bricksContent === 'string'
                            ? JSON.parse(bricksContent)
                            : bricksContent;

                        const newElements = contentData.content || contentData;

                        if (!Array.isArray(newElements)) {
                            throw new Error('Invalid content format');
                        }

                        debugLog('Updating section:', sectionIdentifier);

                        // Find the section by looking for a heading or section element with matching text
                        const identifier = sectionIdentifier.toLowerCase();
                        let sectionIndex = -1;
                        let sectionEndIndex = -1;

                        for (let i = 0; i < state.content.length; i++) {
                            const element = state.content[i];

                            // Check heading text
                            if (element.name === 'heading' && element.settings && element.settings.text) {
                                const text = element.settings.text.replace(/<[^>]*>/g, '').toLowerCase();
                                if (text.includes(identifier) || identifier.includes(text)) {
                                    sectionIndex = i;

                                    // Find the end of this section (next heading or section element)
                                    sectionEndIndex = i;
                                    for (let j = i + 1; j < state.content.length; j++) {
                                        const nextElement = state.content[j];
                                        if (nextElement.name === 'heading' || nextElement.name === 'section') {
                                            break;
                                        }
                                        sectionEndIndex = j;
                                    }
                                    break;
                                }
                            }

                            // Check section element
                            if (element.name === 'section') {
                                // Could check section ID or other attributes
                                // For now, just mark the section
                            }
                        }

                        if (sectionIndex !== -1) {
                            // Remove old section elements
                            const removeCount = sectionEndIndex - sectionIndex + 1;
                            state.content.splice(sectionIndex, removeCount);

                            // Insert new elements at the same position
                            newElements.forEach((element, index) => {
                                state.content.splice(sectionIndex + index, 0, element);
                            });

                            debugLog(`✅ Updated section (removed ${removeCount}, added ${newElements.length})`);

                            return {
                                success: true,
                                message: `Updated section "${sectionIdentifier}" with ${newElements.length} elements`
                            };
                        } else {
                            // Section not found, append to end
                            debugLog('⚠️ Section not found, appending to end');
                            newElements.forEach(element => {
                                state.content.push(element);
                            });

                            return {
                                success: true,
                                message: `Section "${sectionIdentifier}" not found. Added ${newElements.length} elements to the end.`,
                                warning: 'Section not found - appended to end'
                            };
                        }

                    } catch (error) {
                        console.error('Update section error:', error);
                        return {
                            success: false,
                            error: `Failed to update section: ${error.message}`
                        };
                    }
                },

                /**
                 * Add new section (append or prepend)
                 */
                addSection(bricksContent, position = 'append') {
                    const state = this.getState();
                    if (!state) {
                        throw new Error('Bricks state not available');
                    }

                    try {
                        // Parse Bricks content
                        let contentData = typeof bricksContent === 'string'
                            ? JSON.parse(bricksContent)
                            : bricksContent;

                        const newElements = contentData.content || contentData;

                        if (!Array.isArray(newElements)) {
                            throw new Error('Invalid content format');
                        }

                        debugLog(`Adding ${newElements.length} elements (${position})`);

                        if (position === 'prepend') {
                            // Insert at beginning
                            newElements.reverse().forEach(element => {
                                state.content.unshift(element);
                            });
                        } else {
                            // Append to end
                            newElements.forEach(element => {
                                state.content.push(element);
                            });
                        }

                        debugLog('✅ Section added successfully');

                        return {
                            success: true,
                            message: `Added ${newElements.length} elements to ${position === 'prepend' ? 'beginning' : 'end'}`
                        };

                    } catch (error) {
                        console.error('Add section error:', error);
                        return {
                            success: false,
                            error: `Failed to add section: ${error.message}`
                        };
                    }
                }
            };

            // Initialize when DOM is ready
            $(document).ready(function() {
                // Wait for Bricks to be fully loaded
                const initInterval = setInterval(function() {
                    if (BricksHelper.isAvailable()) {
                        clearInterval(initInterval);
                        debugLog('✅ Bricks state available, initializing chat...');
                        initChat();
                        loadAbilities();
                        addToolbarButton();
                    }
                }, 500);

                // Timeout after 10 seconds
                setTimeout(function() {
                    clearInterval(initInterval);
                    if (!BricksHelper.isAvailable()) {
                        console.error('Bricks state not available after 10 seconds');
                    }
                }, 10000);
            });

            /**
             * Add chat button to Bricks toolbar
             */
            function addToolbarButton() {
                // Try multiple possible selectors for the Bricks toolbar
                const possibleSelectors = [
                    '.bricks-toolbar ul.end',
                    'ul.group-wrapper.end',
                    '.group-wrapper.end',
                    '.bricks-toolbar ul.group-wrapper.end'
                ];

                let toolbar = null;
                for (const selector of possibleSelectors) {
                    toolbar = document.querySelector(selector);
                    if (toolbar) {
                        debugLog('✅ Toolbar found with selector:', selector);
                        createToolbarButton(toolbar);
                        return;
                    }
                }

                // Toolbar doesn't exist yet, observe DOM for it
                debugLog('Toolbar not found, observing DOM...');

                const observer = new MutationObserver(function(mutations, obs) {
                    for (const selector of possibleSelectors) {
                        const toolbar = document.querySelector(selector);
                        if (toolbar) {
                            debugLog('✅ Toolbar found via observer:', selector);
                            createToolbarButton(toolbar);
                            obs.disconnect(); // Stop observing
                            return;
                        }
                    }
                });

                // Start observing the document body for added nodes
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                // Timeout after 15 seconds
                setTimeout(function() {
                    observer.disconnect();
                    if (!document.querySelector('.snn-bricks-ai-toggle')) {
                        //console.warn('Bricks toolbar not found after 15 seconds. Tried selectors:', possibleSelectors);
                    }
                }, 15000);
            }

            /**
             * Create and append toolbar button
             */
            function createToolbarButton(toolbar) {
                // Check if button already exists
                if (document.querySelector('.snn-bricks-ai-toggle')) {
                    debugLog('Toolbar button already exists');
                    return;
                }

                const button = document.createElement('li');
                button.className = 'snn-bricks-ai-toggle';
                button.setAttribute('data-balloon', 'SNN AI Agent');
                button.setAttribute('data-balloon-pos', 'bottom');
                button.setAttribute('tabindex', '0');
                button.innerHTML = `
                    <span class="snn-ai-icon" style="font-size: 25px; background: linear-gradient(45deg, #2271b1, #ffffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; position: relative; line-height: 1.2; display: inline-block; cursor: pointer;">✦</span>
                `;

                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleChat();
                });

                // Insert before the last item (second to last position)
                if (toolbar.lastElementChild) {
                    toolbar.insertBefore(button, toolbar.lastElementChild);
                } else {
                    toolbar.appendChild(button);
                }
                debugLog('✅ Toolbar button added');
            }

            /**
             * Initialize chat interface
             */
            function initChat() {
                // Toggle overlay
                $('.snn-bricks-chat-close').on('click', function(e) {
                    e.preventDefault();
                    toggleChat();
                });

                // New chat button
                $('#snn-bricks-chat-new-btn').on('click', function() {
                    clearChat();
                });

                // History button
                $('#snn-bricks-chat-history-btn').on('click', function() {
                    toggleHistoryDropdown();
                });

                $('#snn-bricks-history-close').on('click', function() {
                    $('#snn-bricks-chat-history-dropdown').hide();
                });

                // Send message logic
                $('#snn-bricks-chat-send').on('click', sendMessage);

                // Send on Enter (Shift+Enter for newline)
                $('#snn-bricks-chat-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
                
                // Image attachment handling
                $('#snn-bricks-chat-attach-btn').on('click', function() {
                    $('#snn-chat-file-input').click();
                });

                $('#snn-chat-file-input').on('change', function(e) {
                    if (this.files && this.files[0]) {
                        handleImageAttachment(this.files[0]);
                        this.value = '';
                    }
                });

                // Paste handling
                $('#snn-bricks-chat-input').on('paste', function(e) {
                    const items = (e.originalEvent || e).clipboardData.items;
                    for (let index in items) {
                        const item = items[index];
                        if (item.kind === 'file' && item.type.includes('image/')) {
                            const blob = item.getAsFile();
                            handleImageAttachment(blob);
                        }
                    }
                });

                // Auto-resize textarea
                $('#snn-bricks-chat-input').on('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Quick action buttons
                $('.snn-bricks-quick-action-btn').on('click', function() {
                    const message = $(this).data('message');
                    $('#snn-bricks-chat-input').val(message);
                    sendMessage();
                });

                // Auto-save conversation periodically
                setInterval(autoSaveConversation, 30000);
            }

            function handleImageAttachment(file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image too large. Max 5MB.');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    ChatState.attachments.push({
                        type: 'image',
                        data: e.target.result,
                        name: file.name
                    });
                    updateAttachmentPreview();
                };
                reader.readAsDataURL(file);
            }

            function updateAttachmentPreview() {
                const $preview = $('#snn-chat-preview-area');
                $preview.empty();
                
                if (ChatState.attachments.length === 0) {
                    $preview.hide();
                    return;
                }
                
                $preview.show();
                ChatState.attachments.forEach((att, index) => {
                    const $item = $(
                        '<div style="position:relative; width:60px; height:60px; border:1px solid #ddd; border-radius:4px; overflow:hidden;">' +
                            '<img src="' + att.data + '" style="width:100%; height:100%; object-fit:cover;">' +
                            '<button style="position:absolute; top:0; right:0; background:rgba(0,0,0,0.5); color:#fff; border:none; width:16px; height:16px; display:flex; align-items:center; justify-content:center; font-size:10px; cursor:pointer;" data-index="' + index + '">×</button>' +
                        '</div>'
                    );
                    $item.find('button').on('click', function() {
                        ChatState.attachments.splice($(this).data('index'), 1);
                        updateAttachmentPreview();
                    });
                    $preview.append($item);
                });
                $('#snn-bricks-chat-input').focus();
            }

            /**
             * Toggle chat overlay
             */
            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-bricks-chat-overlay').toggle();

                if (ChatState.isOpen) {
                    $('#snn-bricks-chat-input').focus();
                }
            }

            /**
             * Load available abilities from API
             */
            async function loadAbilities() {
                try {
                    const response = await fetch(snnBricksChatConfig.restUrl + 'abilities', {
                        headers: {
                            'X-WP-Nonce': snnBricksChatConfig.nonce
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const allAbilities = Array.isArray(data) ? data : [];

                        // Filter for Bricks-specific abilities and enabled abilities
                        ChatState.abilities = allAbilities.filter(a =>
                            ENABLED_ABILITIES.includes(a.name) &&
                            (a.category === 'bricks' || a.name.includes('bricks'))
                        );

                        debugLog('✅ Loaded Bricks abilities:', ChatState.abilities.length);
                        if (ChatState.abilities.length > 0) {
                            debugLog('Abilities:', ChatState.abilities.map(a => a.name).join(', '));
                        }
                    }
                } catch (error) {
                    console.error('Failed to load abilities:', error);
                }
            }

            /**
             * Send user message
             */
            async function sendMessage() {
                const input = $('#snn-bricks-chat-input');
                const message = input.val().trim();

                if ((!message && ChatState.attachments.length === 0) || ChatState.isProcessing) {
                    return;
                }

                // Add user message to UI
                let displayHtml = message ? formatMessage(message) : '';
                if (ChatState.attachments.length > 0) {
                    displayHtml += '<div style="display:flex; gap:5px; flex-wrap:wrap; margin-top:5px;">';
                    ChatState.attachments.forEach(att => {
                        displayHtml += '<img src="' + att.data + '" style="max-height:100px; max-width:100%; border-radius:4px;">';
                    });
                    displayHtml += '</div>';
                }
                
                const $messages = $('#snn-bricks-chat-messages');
                const $message = $('<div>').addClass('snn-bricks-chat-message snn-bricks-chat-message-user').html(displayHtml);
                $messages.append($message);
                scrollToBottom();

                // Capture current attachments
                const currentAttachments = [...ChatState.attachments];
                
                // Construct content object for state history
                let contentForState = message;
                if (currentAttachments.length > 0) {
                     contentForState = [];
                     if (message) contentForState.push({ type: 'text', text: message });
                     currentAttachments.forEach(att => contentForState.push({ type: 'image_url', image_url: { url: att.data } }));
                }

                // Push to history
                ChatState.messages.push({
                    role: 'user',
                    content: contentForState,
                    timestamp: Date.now()
                });

                // Clear input and state
                input.val('').css('height', 'auto');
                ChatState.attachments = [];
                $('#snn-chat-preview-area').empty().hide();

                // Process with AI
                await processWithAI(message);
            }

            /**
             * Process message with AI agent
             */
            async function processWithAI(userMessage) {
                ChatState.isProcessing = true;
                ChatState.recoveryAttempts = 0;
                showTyping();
                setAgentState(AgentState.THINKING);

                try {
                    const latestMsg = ChatState.messages[ChatState.messages.length - 1];
                    ChatState.pendingOperation = {
                        type: 'processMessage',
                        message: userMessage,
                        content: latestMsg ? latestMsg.content : userMessage,
                        timestamp: Date.now()
                    };

                    // Prepare conversation context
                    const context = ChatState.messages.slice(-MAX_HISTORY).map(m => ({
                        role: m.role === 'user' ? 'user' : 'assistant',
                        content: m.content
                    }));

                    // Build AI prompt with Bricks-specific abilities
                    const systemPrompt = buildSystemPrompt();
                    const messages = [
                        { role: 'system', content: systemPrompt },
                        ...context
                    ];

                    // Call AI API
                    const aiResponse = await callAI(messages);
                    hideTyping();

                    // Extract abilities from response
                    const abilities = extractAbilitiesFromResponse(aiResponse);

                    if (abilities.length > 0) {
                        // Show initial AI message
                        let initialMessage = aiResponse.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                        if (initialMessage) {
                            addMessage('assistant', initialMessage);
                        }

                        // Execute abilities sequentially
                        await executeAbilitiesSequentially(messages, abilities);
                        await provideFinalSummary(messages, abilities);
                    } else {
                        // No abilities, just show response
                        addMessage('assistant', aiResponse);
                    }

                    setAgentState(AgentState.DONE);
                    ChatState.pendingOperation = null;
                    autoSaveConversation();

                } catch (error) {
                    hideTyping();

                    const recovered = await attemptRecovery(error, userMessage);

                    if (!recovered) {
                        let errorMessage = 'Sorry, something went wrong: ' + error.message;
                        addMessage('error', errorMessage);
                        setAgentState(AgentState.ERROR, { error: error.message });
                    }
                } finally {
                    ChatState.isProcessing = false;
                }
            }

            /**
             * Attempt to recover from an error
             */
            async function attemptRecovery(error, userMessage) {
                ChatState.recoveryAttempts++;

                if (ChatState.recoveryAttempts > RECOVERY_CONFIG.maxRecoveryAttempts) {
                    debugLog('❌ Max recovery attempts reached');
                    return false;
                }

                debugLog(`🔄 Attempting recovery (${ChatState.recoveryAttempts}/${RECOVERY_CONFIG.maxRecoveryAttempts})...`);

                let delay = RECOVERY_CONFIG.baseDelay;

                if (error.message.includes('429') || error.message.includes('Rate limit')) {
                    delay = RECOVERY_CONFIG.rateLimitDelay * Math.pow(2, ChatState.recoveryAttempts - 1);
                } else if (error.message.includes('500') || error.message.includes('503')) {
                    delay = RECOVERY_CONFIG.baseDelay * Math.pow(2, ChatState.recoveryAttempts - 1);
                }

                delay = Math.min(delay, RECOVERY_CONFIG.maxDelay);

                setAgentState(AgentState.RECOVERING, {
                    reason: error.message,
                    delay: delay,
                    attempt: ChatState.recoveryAttempts,
                    maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                });

                showTyping();
                await sleep(delay);

                // Retry the operation...
                return false; // Simplified for now
            }

            /**
             * Build system prompt with Bricks-specific context
             */
            function buildSystemPrompt() {
                const basePrompt = snnBricksChatConfig.ai.systemPrompt || 'You are a helpful design assistant for Bricks Builder.';

                let bricksContext = `\n\n=== BRICKS BUILDER CONTEXT ===\n\n`;
                bricksContext += `You are helping the user design pages using Bricks Builder, a visual page builder for WordPress.\n`;
                bricksContext += `The user is currently editing: ${snnBricksChatConfig.pageContext.details.description || 'a page'}\n\n`;

                if (snnBricksChatConfig.pageContext.details.post_id) {
                    bricksContext += `**Currently Editing:**\n`;
                    bricksContext += `- Post ID: ${snnBricksChatConfig.pageContext.details.post_id}\n`;
                    bricksContext += `- Title: "${snnBricksChatConfig.pageContext.details.post_title}"\n`;
                    bricksContext += `- Post Type: ${snnBricksChatConfig.pageContext.details.post_type}\n\n`;
                }

                const currentContent = BricksHelper.getCurrentContent();
                if (currentContent) {
                    bricksContext += `- Current Page Elements: ${currentContent.elementCount}\n\n`;
                }

                // Inject global variables (Colors, Sizes)
                const globalContext = BricksHelper.getGlobalContext();
                if (globalContext) {
                    bricksContext += globalContext;
                }

                bricksContext += `**BRICKS CONTENT FORMAT:**\n`;
                bricksContext += `Bricks uses JSON structure with elements:\n`;
                bricksContext += `- Each element has: id, name, parent, children, settings\n`;
                bricksContext += `- Common elements: section, container, block, heading, text, image, button\n`;
                bricksContext += `- Settings contain styling and content properties\n\n`;

                bricksContext += `**DESIGN BEST PRACTICES (IMPORTANT!):**\n`;
                bricksContext += `1. **Section Padding**: ONLY use top/bottom padding on sections, NEVER left/right\n`;
                bricksContext += `   - ✅ Good: {"_padding":{"top":"100","bottom":"100"}}\n`;
                bricksContext += `   - ❌ Bad: {"_padding":{"top":"100","right":"100","bottom":"100","left":"100"}}\n`;
                bricksContext += `   - Left/right padding on sections looks amateur and unprofessional\n\n`;
                bricksContext += `2. **Container Width**: Use container _width property to control content width\n`;
                bricksContext += `   - Example: {"_width":"500"} on container element\n`;
                bricksContext += `   - This is the proper way to make sections narrower\n\n`;
                bricksContext += `3. **Responsive Design - MOBILE FIRST ALWAYS:**\n`;
                bricksContext += `   - Add responsive breakpoint values for all grid/layout properties\n`;
                bricksContext += `   - Use :mobile_landscape, :tablet_portrait, :tablet_landscape suffixes\n`;
                bricksContext += `   - Example: {"_gridTemplateColumns":"1fr 1fr 1fr","_gridTemplateColumns:mobile_landscape":"1fr"}\n`;
                bricksContext += `   - Always include mobile breakpoints for grid layouts\n`;
                bricksContext += `   - Common mobile override: "_gridTemplateColumns:mobile_landscape":"1fr" (single column)\n\n`;
                bricksContext += `4. **Image Aspect Ratio**: When using _aspectRatio on images, ALWAYS add _objectFit:"cover"\n`;
                bricksContext += `   - ✅ Good: {"_aspectRatio":"1/1","_objectFit":"cover",...}\n`;
                bricksContext += `   - This ensures images fill their container properly without distortion\n`;
                bricksContext += `   - Common aspect ratios: "1/1" (square), "16/9" (landscape), "4/3", "3/2"\n\n`;

                bricksContext += `**TYPOGRAPHY & FONTS:**\n`;
                bricksContext += `- You can use ANY Google Font in your designs\n`;
                bricksContext += `- Specify fonts in element settings using the 'typography' property\n`;
                bricksContext += `- Example: { typography: { family: "Playfair Display" } }\n`;
                bricksContext += `- Popular choices: Inter, Roboto, Poppins, Montserrat, Open Sans, Playfair Display, etc.\n`;
                bricksContext += `- Choose fonts that match the design style and brand personality\n\n`;

                bricksContext += `**AVAILABLE OPERATIONS:**\n`;
                bricksContext += `1. Replace entire page content (use for "create new page" requests)\n`;
                bricksContext += `2. Update specific section (use for "change the hero section" requests)\n`;
                bricksContext += `3. Add new section (use for "add a testimonials section" requests)\n\n`;

                if (ChatState.abilities.length === 0) {
                    return `${basePrompt}${bricksContext}\n\nNote: No Bricks abilities currently available.`;
                }

                // List available Bricks abilities
                const abilitiesList = ChatState.abilities.map(ability => {
                    return `- **${ability.name}**: ${ability.description || ability.label || 'No description'}`;
                }).join('\n');

                bricksContext += `**AVAILABLE BRICKS ABILITIES:**\n${abilitiesList}\n\n`;
                bricksContext += `Use these abilities to generate Bricks-compatible JSON content based on user requests.\n`;

                return `${basePrompt}${bricksContext}

=== HOW TO USE ABILITIES ===

When the user asks you to create or modify page designs:

1. Acknowledge the request briefly
2. Include a JSON code block with abilities to execute
3. I will execute them and inject the content into Bricks Builder

Example response:
"I'll create a hero section for you.

\`\`\`json
{
  "abilities": [
    {"name": "exact-ability-name", "input": {"description": "hero section with image"}}
  ]
}
\`\`\`"

IMPORTANT:
- Use EXACT ability names from the list above
- The abilities will return Bricks JSON content
- I will handle injecting it into the page
- Focus on describing what the user wants clearly`;
            }

            /**
             * Call AI API
             */
            async function callAI(messages, retryCount = 0) {
                const config = snnBricksChatConfig.ai;

                if (!config.apiKey || !config.apiEndpoint) {
                    throw new Error('AI API not configured');
                }

                try {
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
                            max_tokens: config.maxTokens || 4000
                        }),
                        signal: ChatState.abortController.signal
                    });

                    // Handle rate limiting
                    if (response.status === 429 && retryCount < RECOVERY_CONFIG.maxRecoveryAttempts) {
                        const delay = Math.min(
                            RECOVERY_CONFIG.rateLimitDelay * Math.pow(2, retryCount),
                            RECOVERY_CONFIG.maxDelay
                        );

                        setAgentState(AgentState.RECOVERING, {
                            reason: 'Rate limit exceeded',
                            delay: delay,
                            attempt: retryCount + 1,
                            maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                        });

                        await sleep(delay);
                        return await callAI(messages, retryCount + 1);
                    }

                    if (!response.ok) {
                        throw new Error(`AI API error: ${response.status}`);
                    }

                    const data = await response.json();
                    ChatState.recoveryAttempts = 0;

                    return data.choices[0].message.content;

                } catch (error) {
                    if (error.name === 'AbortError') throw error;
                    ChatState.lastError = error;
                    throw error;
                }
            }

            /**
             * Extract abilities from AI response
             */
            function extractAbilitiesFromResponse(response) {
                const abilities = [];
                const jsonMatch = response.match(/```json\n?([\s\S]*?)\n?```/);

                if (!jsonMatch) {
                    return abilities;
                }

                try {
                    const parsed = JSON.parse(jsonMatch[1]);
                    if (parsed.abilities && Array.isArray(parsed.abilities)) {
                        return parsed.abilities;
                    }
                } catch (error) {
                    console.error('Failed to parse abilities:', error);
                }

                return abilities;
            }

            /**
             * Execute abilities sequentially
             */
            async function executeAbilitiesSequentially(conversationMessages, abilities) {
                const totalAbilities = abilities.length;

                for (let i = 0; i < abilities.length; i++) {
                    let ability = abilities[i];
                    const current = i + 1;
                    let retryCount = 0;
                    let result = null;

                    while (retryCount <= MAX_RETRIES) {
                        showTyping();
                        setAgentState(retryCount > 0 ? AgentState.RETRYING : AgentState.THINKING);
                        await sleep(300);

                        setAgentState(AgentState.EXECUTING, {
                            abilityName: ability.name,
                            current: current,
                            total: totalAbilities,
                            retry: retryCount > 0 ? retryCount : null
                        });

                        debugLog(`Executing: ${ability.name} (${current}/${totalAbilities})`);
                        result = await executeAbility(ability.name, ability.input || {});

                        hideTyping();

                        if (result.success) {
                            // Check if this ability returns Bricks content that needs injection
                            if (result.client_command && result.client_command.type) {
                                debugLog('Executing client command:', result.client_command.type);
                                const clientResult = await executeClientCommand(result.client_command);
                                if (!clientResult.success) {
                                    result.success = false;
                                    result.error = clientResult.error;
                                }
                            }
                            break;
                        }

                        if (retryCount < MAX_RETRIES) {
                            const correctedAbility = await retryWithAI(conversationMessages, ability, result.error);
                            if (correctedAbility) {
                                ability = correctedAbility;
                                retryCount++;
                                continue;
                            } else {
                                break;
                            }
                        } else {
                            break;
                        }
                    }

                    // Show result
                    const resultHtml = formatSingleAbilityResult({
                        ability: ability.name,
                        result: result
                    });
                    addMessage('assistant', resultHtml, [{ ability: ability.name, result: result }]);

                    if (i < abilities.length - 1) {
                        await sleep(500);
                    }
                }
            }

            /**
             * Ask AI to retry with corrected input
             */
            async function retryWithAI(conversationMessages, failedAbility, errorMessage) {
                try {
                    showTyping();
                    setAgentState(AgentState.RETRYING);

                    const retryMessages = [
                        ...conversationMessages,
                        {
                            role: 'user',
                            content: `The ability "${failedAbility.name}" failed with error: "${errorMessage}"

Please provide a CORRECTED input. Respond with a JSON code block or "CANNOT_FIX".`
                        }
                    ];

                    const aiResponse = await callAI(retryMessages);
                    hideTyping();

                    if (aiResponse.includes('CANNOT_FIX')) {
                        return null;
                    }

                    const correctedAbilities = extractAbilitiesFromResponse(aiResponse);
                    if (correctedAbilities.length > 0) {
                        return correctedAbilities[0];
                    }

                    return null;
                } catch (error) {
                    console.error('Retry failed:', error);
                    hideTyping();
                    return null;
                }
            }

            /**
             * Provide final summary
             */
            async function provideFinalSummary(conversationMessages, abilities) {
                if (abilities.length <= 1) return;

                try {
                    showTyping();
                    setAgentState(AgentState.THINKING);

                    const executedList = abilities.map((a, i) =>
                        `${i + 1}. ${a.name}`
                    ).join('\n');

                    const summaryMessages = [
                        ...conversationMessages,
                        {
                            role: 'user',
                            content: `All ${abilities.length} tasks completed:\n\n${executedList}\n\nProvide a brief summary (2-3 sentences).`
                        }
                    ];

                    const summary = await callAI(summaryMessages);
                    hideTyping();

                    const cleanSummary = summary.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                    addMessage('assistant', '✅ ' + cleanSummary);

                } catch (error) {
                    console.error('Summary failed:', error);
                    hideTyping();
                }
            }

            /**
             * Execute client-side command (Bricks content injection)
             */
            async function executeClientCommand(command) {
                try {
                    if (!command || !command.type) {
                        return {
                            success: false,
                            error: 'Invalid client command'
                        };
                    }

                    debugLog('Client command type:', command.type);

                    switch (command.type) {
                        case 'bricks_replace_all':
                            return BricksHelper.replaceAllContent(command.content);

                        case 'bricks_update_section':
                            return BricksHelper.updateSection(command.section_identifier, command.content);

                        case 'bricks_add_section':
                            return BricksHelper.addSection(command.content, command.position || 'append');

                        case 'update_bricks_content':
                            // Legacy fallback - interpret action parameter
                            const action = command.action || 'append';
                            if (action === 'replace') {
                                return BricksHelper.replaceAllContent(command.content);
                            } else {
                                return BricksHelper.addSection(command.content, action === 'prepend' ? 'prepend' : 'append');
                            }

                        default:
                            return {
                                success: false,
                                error: `Unknown command type: ${command.type}`
                            };
                    }

                } catch (error) {
                    console.error('Client command error:', error);
                    return {
                        success: false,
                        error: error.message
                    };
                }
            }

            /**
             * Execute a single ability
             */
            async function executeAbility(abilityName, input) {
                try {
                    const encodedName = abilityName.split('/').map(part => encodeURIComponent(part)).join('/');
                    const apiUrl = snnBricksChatConfig.restUrl + 'abilities/' + encodedName + '/run';

                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': snnBricksChatConfig.nonce
                        },
                        body: JSON.stringify({ input: input })
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        return { success: false, error: `HTTP ${response.status}: ${errorText}` };
                    }

                    const result = await response.json();

                    // Normalize response
                    if (typeof result.success !== 'undefined') {
                        return result;
                    }

                    if (result.data !== undefined) {
                        return { success: true, data: result.data };
                    }

                    return { success: true, data: result };

                } catch (error) {
                    console.error('Execution error:', error);
                    return { success: false, error: error.message };
                }
            }

            /**
             * Format single ability result
             */
            function formatSingleAbilityResult(r) {
                const success = r.result.success;
                const status = success ? '✅' : '❌';

                let html = '<div class="ability-results">';
                html += `<div class="ability-result ${success ? 'success' : 'error'}">`;
                html += `<strong>${status} ${r.ability}</strong>`;

                if (success) {
                    html += `<div class="result-data">Content injected into page</div>`;
                } else {
                    html += `<div class="result-error">${r.result.error || 'Unknown error'}</div>`;
                }

                html += '</div></div>';
                return html;
            }

            /**
             * Helper functions
             */
            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            function showTyping() {
                $('.snn-bricks-chat-typing').show();
                scrollToBottom();
            }

            function hideTyping() {
                $('.snn-bricks-chat-typing').hide();
            }

            function setAgentState(state, metadata = null) {
                ChatState.currentState = state;
                const $stateText = $('#snn-bricks-chat-state-text');

                let stateMessage = '';
                switch(state) {
                    case AgentState.THINKING:
                        stateMessage = 'Thinking...';
                        break;
                    case AgentState.EXECUTING:
                        stateMessage = metadata && metadata.abilityName
                            ? `Executing ${metadata.abilityName}...`
                            : 'Executing...';
                        break;
                    case AgentState.RETRYING:
                        stateMessage = 'Retrying...';
                        break;
                    case AgentState.RECOVERING:
                        stateMessage = metadata ? `Recovering (${Math.ceil(metadata.delay/1000)}s)...` : 'Recovering...';
                        break;
                    case AgentState.ERROR:
                        stateMessage = 'Error occurred';
                        setTimeout(() => setAgentState(AgentState.IDLE), 3000);
                        break;
                }

                if (stateMessage) {
                    $stateText.text(stateMessage).show();
                } else {
                    $stateText.hide();
                }
            }

            function addMessage(role, content, metadata = null) {
                const message = {
                    role: role,
                    content: content,
                    metadata: metadata,
                    timestamp: Date.now()
                };

                ChatState.messages.push(message);

                const $messages = $('#snn-bricks-chat-messages');
                const $welcome = $messages.find('.snn-bricks-chat-welcome');

                if ($welcome.length) {
                    $welcome.remove();
                    $('.snn-bricks-chat-quick-actions').hide();
                }

                const $message = $('<div>')
                    .addClass('snn-bricks-chat-message')
                    .addClass('snn-bricks-chat-message-' + role)
                    .html(formatMessage(content));

                $messages.append($message);
                scrollToBottom();
            }

            function formatMessage(content) {
                if (content.includes('<div class="ability-results">')) {
                    return content;
                }

                if (typeof markdown !== 'undefined' && markdown.toHTML) {
                    try {
                        return markdown.toHTML(content);
                    } catch (e) {
                        return content.replace(/\n/g, '<br>');
                    }
                }

                return content.replace(/\n/g, '<br>');
            }

            function scrollToBottom() {
                const $messages = $('#snn-bricks-chat-messages');
                $messages.scrollTop($messages[0].scrollHeight);
            }

            function clearChat() {
                ChatState.messages = [];
                ChatState.currentSessionId = null;
                $('#snn-bricks-chat-messages').html(`
                    <div class="snn-bricks-chat-welcome">
                        <h3>Conversation cleared</h3>
                        <p>Start a new conversation.</p>
                    </div>
                `);
                $('.snn-bricks-chat-quick-actions').show();
            }

            function autoSaveConversation() {
                if (ChatState.messages.length === 0) return;

                $.ajax({
                    url: snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_save_chat_history',
                        nonce: snnBricksChatConfig.agentNonce,
                        messages: JSON.stringify(ChatState.messages),
                        session_id: ChatState.currentSessionId
                    },
                    success: function(response) {
                        if (response.success) {
                            ChatState.currentSessionId = response.data.session_id;
                            //debugLog('✅ Chat saved');
                        }
                    }
                });
            }

            function toggleHistoryDropdown() {
                const $dropdown = $('#snn-bricks-chat-history-dropdown');
                if ($dropdown.is(':visible')) {
                    $dropdown.hide();
                    return;
                }
                loadChatHistories();
                $dropdown.show();
            }

            function loadChatHistories() {
                const $list = $('#snn-bricks-history-list');
                $list.html('<div class="snn-bricks-history-loading">Loading...</div>');

                $.ajax({
                    url: snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_get_chat_histories',
                        nonce: snnBricksChatConfig.agentNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderHistoryList(response.data.histories);
                        }
                    }
                });
            }

            function renderHistoryList(histories) {
                const $list = $('#snn-bricks-history-list');

                if (histories.length === 0) {
                    $list.html('<div class="snn-bricks-history-empty">No history</div>');
                    return;
                }

                let html = '';
                histories.forEach(function(history) {
                    html += `<div class="snn-bricks-history-item" data-session-id="${history.session_id}">
                        <div class="snn-bricks-history-title">${history.title}</div>
                        <div class="snn-bricks-history-meta">${history.message_count} messages</div>
                    </div>`;
                });

                $list.html(html);

                $('.snn-bricks-history-item').on('click', function() {
                    const sessionId = $(this).data('session-id');
                    loadChatSession(sessionId);
                    $('#snn-bricks-chat-history-dropdown').hide();
                });
            }

            function loadChatSession(sessionId) {
                $.ajax({
                    url: snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_load_chat_history',
                        nonce: snnBricksChatConfig.agentNonce,
                        session_id: sessionId
                    },
                    success: function(response) {
                        if (response.success && response.data.messages) {
                            $('#snn-bricks-chat-messages').empty();
                            $('.snn-bricks-chat-quick-actions').hide();

                            ChatState.messages = response.data.messages;
                            ChatState.currentSessionId = sessionId;

                            response.data.messages.forEach(function(msg) {
                                const $message = $('<div>')
                                    .addClass('snn-bricks-chat-message')
                                    .addClass('snn-bricks-chat-message-' + msg.role)
                                    .html(formatMessage(msg.content));
                                $('#snn-bricks-chat-messages').append($message);
                            });

                            scrollToBottom();
                        }
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
/* Bricks toolbar button */
.snn-bricks-ai-toggle { cursor: pointer; }
.snn-bricks-ai-toggle a { display: flex; align-items: center; gap: 6px; }

/* Chat overlay - positioned for frontend */
.snn-bricks-chat-overlay { position: fixed; top: 0; right: 0; bottom: 0; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.snn-bricks-chat-container { width: 400px; height: 100%; background: #fff; box-shadow: -2px 0 16px rgba(0, 0, 0, 0.2); display: flex; flex-direction: column; }
.snn-bricks-chat-header { background: #161a1d; color: #fff; padding: 8px 20px; display: flex; justify-content: space-between; align-items: center; }
.snn-bricks-chat-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; }
.snn-bricks-chat-controls { display: flex; gap: 4px; }
.snn-bricks-chat-btn { background: rgba(255, 255, 255, 0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display:flex; justify-content: center; align-items: center; }
.snn-bricks-chat-btn:hover { background: rgba(255, 255, 255, 0.3); }
.snn-bricks-chat-plus { font-size: 24px; }
.snn-bricks-chat-messages { flex: 1; overflow-y: auto; padding: 16px; background: #f9f9f9; font-size:14px; }
.snn-bricks-chat-welcome { text-align: center; padding: 40px 20px; color: #666; }
.snn-bricks-chat-message { margin-bottom: 4px; padding: 4px 8px; border-radius: 12px; max-width: 95%; }
.snn-bricks-chat-message-user { background: #161a1d; color: #fff; margin-left: auto; }
.snn-bricks-chat-message-assistant { background: #fff; border: 1px solid #e0e0e0; margin-right: auto; }
.snn-bricks-chat-message-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.snn-bricks-chat-typing { padding: 8px 16px; }
.typing-dots { display: flex; gap: 4px; }
.typing-dots span { width: 8px; height: 8px; border-radius: 50%; background: #999; animation: typing 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.5; } 30% { transform: translateY(-8px); opacity: 1; } }
.snn-bricks-chat-state-text { padding: 8px 16px; background: #f0f0f0; font-size: 13px; color: #666; display: none; }
.snn-bricks-chat-quick-actions { padding: 8px 10px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 6px; flex-wrap: wrap; }
.snn-bricks-quick-action-btn { padding: 6px 12px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; cursor: pointer; }
.snn-bricks-quick-action-btn:hover { background: #161a1d; color: #fff; }
.snn-bricks-chat-input-container { padding: 12px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 8px; }
.snn-bricks-chat-input { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px; resize: none; min-height: 42px; max-height: 120px; }
.snn-bricks-chat-send { width: 42px; height: 42px; background: #161a1d; border: none; border-radius: 8px; color: #fff; cursor: pointer; display:flex; align-items: center; justify-content: center; }
.snn-bricks-chat-send:hover { background: #161a1d; }
.snn-bricks-chat-history-dropdown { position: absolute; top: 60px; left: 0; right: 0; background: #fff; border-bottom: 1px solid #ddd; max-height: 300px; overflow-y: auto; z-index: 10; }
.snn-bricks-history-header { padding: 12px 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
.snn-bricks-history-close { background: none; border: none; font-size: 24px; cursor: pointer; }
.snn-bricks-history-item { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
.snn-bricks-history-item:hover { background: #f9f9f9; }
.snn-bricks-history-title { font-weight: 600; margin-bottom: 4px; }
.snn-bricks-history-meta { font-size: 12px; color: #666; }
.ability-results {   }
.ability-result { padding: 4px 8px;   border-radius: 6px; font-size: 13px; }
.ability-result.success { background: #f0f9ff; }
.ability-result.error { background: #fef2f2; }
.result-data { color: #666; font-size: 12px; margin-top: 4px; }
.result-error { color: #dc2626; font-size: 12px; }
        ';
    }
}

// Initialize
SNN_Bricks_Chat_Overlay::get_instance();
