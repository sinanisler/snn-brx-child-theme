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

<div class="snn-bricks-chat-quick-actions">

    <button class="snn-bricks-quick-action-btn" data-message="The 'Artisan Bakery' E-Commerce Section 1 (Hero): {pattern_type: 'hero', content_description: 'Heading: Baked Daily With Love. Subtext: Sourdough and pastries delivered to your door. Button: Order Now.', action_type: 'replace', style_preference: 'playful'} Section 2 (About): {pattern_type: 'about', content_description: 'Our Story: 20 years of family recipes and organic local flour. Image of a bakery interior.', action_type: 'append'} Section 3 (Services): {pattern_type: 'services', layout_columns: 3, content_description: '1. Sourdough Loaves, 2. Sweet Pastries, 3. Morning Coffee.', action_type: 'append'} Section 4 (Testimonials): {pattern_type: 'testimonials', content_description: 'Reviews from local foodies about the crunch and flavor.', action_type: 'append'} Section 5 (CTA): {pattern_type: 'cta', content_description: 'Heading: Join the Bread Club. Button: Subscribe.', action_type: 'append'}">
    Bakery</button>

    <button class="snn-bricks-quick-action-btn" data-message="The 'High-Growth' Fintech App Section 1 (Hero): {pattern_type: 'hero', content_description: 'Heading: Smart Money for Everyone. Subtext: Banking, investing, and saving in one simple app. Button: Download Now.', action_type: 'replace', style_preference: 'modern'} Section 2 (Stats): {pattern_type: 'stats', content_description: '$10B Managed, 5M+ Users, 120 Countries.', action_type: 'append'} Section 3 (About): {pattern_type: 'about', content_description: 'Bank-grade security. 24/7 fraud monitoring. Your wealth, protected.', action_type: 'append'} Section 4 (Testimonials): {pattern_type: 'testimonials', content_description: 'Real stories from users who reached their saving goals.', action_type: 'append'} Section 5 (FAQ): {pattern_type: 'faq', content_description: 'Questions about interest rates, card delivery, and crypto features.', action_type: 'append'}">
    Fintech</button>

    <button class="snn-bricks-quick-action-btn" data-message="The 'Creative Studio' Portfolio Section 1 (Hero): {pattern_type: 'hero', content_description: 'Heading: We Build Digital Icons. Subtext: Branding and web design for ambitious brands. Button: See Work.', action_type: 'replace', style_preference: 'bold'} Section 2 (About): {pattern_type: 'about', content_description: 'A boutique team of 10 designers and developers working globally from London.', action_type: 'append'} Section 3 (Services): {pattern_type: 'services', layout_columns: 3, content_description: '1. Brand Strategy, 2. Web Design, 3. Motion Graphics.', action_type: 'append'} Section 4 (Team): {pattern_type: 'team', layout_columns: 4, content_description: 'The creative leads behind our award-winning projects.', action_type: 'append'} Section 5 (CTA): {pattern_type: 'cta', content_description: 'Heading: Have a bold idea? Button: Let’s Talk.', action_type: 'append'}">
    Studio</button>

    <button class="snn-bricks-quick-action-btn" data-message="The 'E-Learning' Academy Section 1 (Hero): {patterntype: 'hero', contentdescription: 'Heading: Master New Skills. Subtext: Learn from industry experts at your own pace.', actiontype: 'replace', stylepreference: 'modern'} Section 2 (Services): {patterntype: 'services', layoutcolumns: 3, contentdescription: '1. Programming, 2. Digital Marketing, 3. Graphic Design.', actiontype: 'append'} Section 3 (Stats): {patterntype: 'stats', contentdescription: '500+ Courses, 50k Students, 4.9 Avg Rating.', actiontype: 'append'} Section 4 (Testimonials): {patterntype: 'testimonials', contentdescription: 'Success stories from graduates now working at top tech firms.', actiontype: 'append'} Section 5 (CTA): {patterntype: 'cta', contentdescription: 'Heading: Start learning for free. Button: Browse Catalog.', action_type: 'append'}">
    Academy</button>

    <button class="snn-bricks-quick-action-btn" data-message="The 'Gourmet' Michelin Star Restaurant Section 1 (Hero): {type: 'section', styles: {minHeight: '100vh', background: '#000'}, children: [{type: 'container', styles: {display: 'flex', flexDirection: 'column', alignItems: 'center'}, children: [{type: 'heading', content: 'The Art of Taste', styles: {fontSize: '80', color: '#fff'}}, {type: 'button', content: 'Reserve Table'}]}]} Section 2 (Menu Tabs): {type: 'tabs-nested', children: [{type: 'block', label: 'Menu', children: [{type: 'text-basic', content: 'Dinner'}, {type: 'text-basic', content: 'Wine List'}]}, {type: 'block', label: 'Content', children: [{type: 'list', items: [{title: 'Wagyu A5', meta: '$120'}]}, {type: 'list', items: [{title: '1945 Bordeaux', meta: '$2400'}]}]}]} Section 3 (Gallery Slider): {type: 'slider-nested', styles: {perPage: '3', autoplay: true}, children: [{type: 'image', content: 'dish1.jpg'}, {type: 'image', content: 'dish2.jpg'}, {type: 'image', content: 'interior.jpg'}]} Section 4 (About): {type: 'about', content_description: 'A culinary journey led by Chef Marco Rossi.', action_type: 'append'} Section 5 (CTA): {type: 'cta', content_description: 'Heading: A Night to Remember. Button: Book Now.', action_type: 'append'}">
    Restaurant</button>



</div>



                </div>

                <!-- Input -->
                <div class="snn-bricks-chat-input-container">
                    <input type="file" id="snn-bricks-chat-file-input" accept="image/*" style="display: none;" />
                    <button id="snn-bricks-chat-attach-btn" class="snn-bricks-chat-attach-btn" title="Attach image">
                        <span class="dashicons dashicons-paperclip"></span>
                    </button>
                    <div class="snn-bricks-chat-input-wrapper">
                        <div id="snn-bricks-chat-image-preview" class="snn-bricks-chat-image-preview"></div>
                        <textarea
                            id="snn-bricks-chat-input"
                            class="snn-bricks-chat-input"
                            placeholder="Describe what you want to create or paste a screenshot..."
                            rows="1"
                        ></textarea>
                    </div>
                    <button id="snn-bricks-chat-send" class="snn-bricks-chat-send" title="Send message">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
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
                bricksState: null,
                attachedImages: []
            };

            // Bricks Builder Integration
            const BricksHelper = {
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

                // Send message
                $('#snn-bricks-chat-send').on('click', sendMessage);

                // Send on Enter (Shift+Enter for newline)
                $('#snn-bricks-chat-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
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

                // Image attachment button
                $('#snn-bricks-chat-attach-btn').on('click', function() {
                    $('#snn-bricks-chat-file-input').click();
                });

                // File input change
                $('#snn-bricks-chat-file-input').on('change', function(e) {
                    handleFileSelect(e.target.files);
                });

                // Paste event for clipboard images
                $('#snn-bricks-chat-input').on('paste', function(e) {
                    handlePaste(e.originalEvent);
                });

                // Auto-save conversation periodically
                setInterval(autoSaveConversation, 30000);
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
             * Handle file selection
             */
            async function handleFileSelect(files) {
                if (!files || files.length === 0) return;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (!file.type.startsWith('image/')) continue;

                    try {
                        const base64 = await fileToBase64(file);
                        addImageAttachment(base64, file.name);
                    } catch (error) {
                        console.error('Failed to process image:', error);
                    }
                }

                // Reset file input
                $('#snn-bricks-chat-file-input').val('');
            }

            /**
             * Handle clipboard paste
             */
            async function handlePaste(event) {
                const items = event.clipboardData?.items;
                if (!items) return;

                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item.type.startsWith('image/')) {
                        event.preventDefault();
                        const file = item.getAsFile();
                        if (file) {
                            try {
                                const base64 = await fileToBase64(file);
                                addImageAttachment(base64, 'pasted-image.png');
                            } catch (error) {
                                console.error('Failed to process pasted image:', error);
                            }
                        }
                    }
                }
            }

            /**
             * Convert file to base64
             */
            function fileToBase64(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            }

            /**
             * Add image attachment
             */
            function addImageAttachment(base64Data, fileName) {
                const imageId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                ChatState.attachedImages.push({
                    id: imageId,
                    data: base64Data,
                    fileName: fileName
                });

                renderImagePreviews();
                debugLog('Image attached:', fileName);
            }

            /**
             * Remove image attachment
             */
            function removeImageAttachment(imageId) {
                ChatState.attachedImages = ChatState.attachedImages.filter(img => img.id !== imageId);
                renderImagePreviews();
            }

            /**
             * Render image previews
             */
            function renderImagePreviews() {
                const $preview = $('#snn-bricks-chat-image-preview');
                $preview.empty();

                if (ChatState.attachedImages.length === 0) {
                    $preview.hide();
                    return;
                }

                $preview.show();
                ChatState.attachedImages.forEach(img => {
                    const $imgWrapper = $('<div>').addClass('snn-image-preview-item');
                    const $img = $('<img>').attr('src', img.data).attr('alt', img.fileName);
                    const $remove = $('<button>').addClass('snn-image-preview-remove').html('×').attr('title', 'Remove image');

                    $remove.on('click', function() {
                        removeImageAttachment(img.id);
                    });

                    $imgWrapper.append($img).append($remove);
                    $preview.append($imgWrapper);
                });
            }

            /**
             * Send user message
             */
            async function sendMessage() {
                const input = $('#snn-bricks-chat-input');
                const message = input.val().trim();
                const hasImages = ChatState.attachedImages.length > 0;

                if ((!message && !hasImages) || ChatState.isProcessing) {
                    return;
                }

                // Add user message with images
                const messageContent = message || '(Image attached)';
                addMessage('user', messageContent, ChatState.attachedImages);
                
                // Save images for processing
                const imagesToProcess = [...ChatState.attachedImages];
                
                // Clear input and images
                input.val('').css('height', 'auto');
                ChatState.attachedImages = [];
                renderImagePreviews();

                // Process with AI
                await processWithAI(message, imagesToProcess);
            }

            /**
             * Process message with AI agent
             */
            async function processWithAI(userMessage, images = []) {
                ChatState.isProcessing = true;
                ChatState.recoveryAttempts = 0;
                showTyping();
                setAgentState(AgentState.THINKING);

                try {
                    ChatState.pendingOperation = {
                        type: 'processMessage',
                        message: userMessage,
                        timestamp: Date.now()
                    };

                    // Prepare conversation context
                    const context = ChatState.messages.slice(-MAX_HISTORY).map(m => {
                        const msg = {
                            role: m.role === 'user' ? 'user' : 'assistant'
                        };

                        // Handle messages with images
                        if (m.images && m.images.length > 0) {
                            msg.content = [];
                            if (m.content && m.content !== '(Image attached)') {
                                msg.content.push({
                                    type: 'text',
                                    text: m.content
                                });
                            }
                            m.images.forEach(img => {
                                msg.content.push({
                                    type: 'image_url',
                                    image_url: {
                                        url: img.data
                                    }
                                });
                            });
                        } else {
                            msg.content = m.content;
                        }

                        return msg;
                    });

                    // Build AI prompt with Bricks-specific abilities
                    const systemPrompt = buildSystemPrompt();
                    const messages = [
                        { role: 'system', content: systemPrompt },
                        ...context
                    ];

                    // Add current user message with images if any
                    if (images.length > 0) {
                        const currentMsg = {
                            role: 'user',
                            content: []
                        };

                        if (userMessage) {
                            currentMsg.content.push({
                                type: 'text',
                                text: userMessage
                            });
                        }

                        images.forEach(img => {
                            currentMsg.content.push({
                                type: 'image_url',
                                image_url: {
                                    url: img.data
                                }
                            });
                        });

                        // Only add if not already in context
                        if (messages.length === 0 || messages[messages.length - 1].role !== 'user') {
                            messages.push(currentMsg);
                        }
                    }

                    // Call AI API
                    const aiResponse = await callAI(messages);
                    hideTyping();

                    debugLog('AI Response:', aiResponse);

                    // Check for empty response
                    if (!aiResponse || aiResponse.trim() === '') {
                        throw new Error('AI returned empty response. Please try again.');
                    }

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

                bricksContext += `**IMAGE ANALYSIS & DESIGN RECREATION:**\n`;
                bricksContext += `- Users can paste screenshots or upload design images (from Figma, Adobe XD, etc.)\n`;
                bricksContext += `- When you receive an image, carefully analyze:\n`;
                bricksContext += `  * Layout structure (sections, grids, columns)\n`;
                bricksContext += `  * Typography (font styles, sizes, hierarchy)\n`;
                bricksContext += `  * Colors (background, text, accents)\n`;
                bricksContext += `  * Spacing (padding, margins, gaps)\n`;
                bricksContext += `  * Visual elements (images, icons, shapes)\n`;
                bricksContext += `- Recreate the design as faithfully as possible using Bricks elements\n`;
                bricksContext += `- If design uses custom graphics, suggest placeholder images or similar alternatives\n`;
                bricksContext += `- Maintain responsive design principles even when replicating desktop designs\n\n`;

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

                return basePrompt + bricksContext + `

=== CRITICAL: HOW TO USE ABILITIES CORRECTLY ===

**EACH ABILITY CALL = ONE DISTINCT SECTION**

When the user asks for "6 sections", you must make 6 SEPARATE ability calls, each with a UNIQUE description:

❌ WRONG (generates 6 identical sections):
` + '```json' + `
{
  "abilities": [
    {"name": "snn/generate-bricks-content", "input": {"structure": {...}}},
    {"name": "snn/generate-bricks-content", "input": {"structure": {...}}},
    {"name": "snn/generate-bricks-content", "input": {"structure": {...}}}
  ]
}
` + '```' + `

✅ CORRECT (each call describes a different section):
` + '```json' + `
{
  "abilities": [
    {
      "name": "snn/generate-bricks-content",
      "input": {
        "structure": {
          "type": "section",
          "styles": {"background": "#000000", "minHeight": "100vh", "padding": "80"},
          "children": [{
            "type": "container",
            "styles": {"display": "grid", "gridTemplateColumns": "1fr 1fr", "gap": "60"},
            "children": [
              {
                "type": "block",
                "styles": {"display": "flex", "flexDirection": "column", "gap": "24"},
                "children": [
                  {"type": "heading", "content": "Crypto Platform", "tag": "h1", "styles": {"fontSize": "120", "fontSize:mobile_landscape": "56", "fontWeight": "900", "color": "#ffffff"}}
                ]
              },
              {
                "type": "custom-html-css-script",
                "content": "<div id='orb'></div><style>#orb{width:400px;height:400px;border-radius:50%;background:radial-gradient(circle, #34d399, #000);filter:blur(40px);animation:float 6s ease-in-out infinite;}@keyframes float{0%,100%{transform:translateY(0px)}50%{transform:translateY(-30px)}}</style>"
              }
            ]
          }]
        }
      }
    },
    {
      "name": "snn/generate-bricks-content",
      "input": {
        "structure": {
          "type": "section",
          "styles": {"background": "#000000", "padding": "40"},
          "children": [{
            "type": "container",
            "children": [{
              "type": "block",
              "styles": {"display": "flex", "flexDirection": "row", "gap": "20", "overflow": "hidden"},
              "children": [
                {"type": "text-basic", "content": "🪙 BTC +5.2%", "styles": {"color": "#34d399", "fontSize": "14", "fontWeight": "700"}},
                {"type": "text-basic", "content": "🪙 ETH +3.8%", "styles": {"color": "#34d399", "fontSize": "14", "fontWeight": "700"}},
                {"type": "text-basic", "content": "🪙 SOL +8.1%", "styles": {"color": "#34d399", "fontSize": "14", "fontWeight": "700"}}
              ]
            }]
          }]
        }
      }
    }
  ]
}
` + '```' + `

**INPUT FORMAT REQUIREMENTS:**

The ability expects:
` + '```json' + `
{
  "input": {
    "structure": {
      "type": "section|container|block|heading|text|image|etc",
      "content": "text content for text elements",
      "styles": {
        "background": "#hexcolor",
        "padding": "number",
        "fontSize": "number"
      },
      "children": [
        // nested child elements following same format
      ]
    },
    "action_type": "append|prepend|replace",
    "post_id": 12345
  }
}
` + '```' + `

**COMMON MISTAKES TO AVOID:**

1. ❌ Missing "structure" wrapper: {"input": {"type": "section"}}
   ✅ Correct: {"input": {"structure": {"type": "section"}}}

2. ❌ Using "px" suffix: {"padding": "80px"}
   ✅ Correct: {"padding": "80"}

3. ❌ Missing flexDirection with display:flex
   ✅ Always specify: {"display": "flex", "flexDirection": "row"} or "column"

4. ❌ Multiple children without gap property
   ✅ Always add gap: {"gap": "24", "children": [...]}

5. ❌ Section with left/right padding
   ✅ Only top/bottom: {"padding": {"top": "80", "bottom": "80"}}

**WHEN ERRORS OCCUR:**

If an ability fails, the error message will tell you what's wrong. Common issues:
- "Invalid structure format" = missing required "structure" object
- "Invalid element type" = used wrong type name
- "Invalid style value" = used "px" or wrong format
- "Missing gap property" = container/block has multiple children but no gap

Fix the EXACT issue mentioned in the error, don't just retry the same input.

**RESPONSE FORMAT:**

You MUST respond with regular text containing a JSON code block. DO NOT use function calling or tool calling.

Your response should look like this:
` + '```json' + `
{
  "abilities": [
    {
      "name": "snn/generate-bricks-content",
      "input": {
        "structure": {...}
      }
    }
  ]
}
` + '```' + `

IMPORTANT: Always wrap your JSON in markdown code fences (` + '```json' + ` ... ` + '```' + `). Do not attempt to call functions or tools directly.`;
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

                    const requestBody = {
                        model: config.model,
                        messages: messages,
                        temperature: 0.7,
                        max_tokens: config.maxTokens || 4000
                    };

                    debugLog('Sending to AI:', {
                        model: requestBody.model,
                        messageCount: messages.length,
                        systemPromptLength: messages[0]?.content?.length || 0,
                        maxTokens: requestBody.max_tokens
                    });

                    const response = await fetch(config.apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${config.apiKey}`
                        },
                        body: JSON.stringify(requestBody),
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
                        const errorText = await response.text();
                        debugLog('API Error Response:', errorText);
                        throw new Error(`AI API error: ${response.status} - ${errorText.substring(0, 200)}`);
                    }

                    const data = await response.json();
                    debugLog('API Response Data:', data);

                    ChatState.recoveryAttempts = 0;

                    // Validate response structure
                    if (!data || !data.choices || !data.choices[0] || !data.choices[0].message) {
                        debugLog('Invalid response structure:', data);
                        throw new Error('Invalid AI API response structure');
                    }

                    const content = data.choices[0].message.content;

                    if (!content || content.trim() === '') {
                        debugLog('Empty content in response');
                        throw new Error('AI returned empty content');
                    }

                    return content;

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
                        result: result,
                        retries: retryCount
                    });
                    addMessage('assistant', resultHtml, [{ ability: ability.name, result: result, retries: retryCount }]);

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

                    // Provide detailed context about what failed
                    let errorContext = `The ability "${failedAbility.name}" failed with this error:\n"${errorMessage}"\n\n`;

                    // Add specific guidance based on error type
                    if (errorMessage.includes('structure')) {
                        errorContext += `ISSUE: The input is missing the required "structure" object.\n`;
                        errorContext += `REQUIRED FORMAT:\n{"input": {"structure": {"type": "...", "children": [...]}}}\n\n`;
                    } else if (errorMessage.includes('px')) {
                        errorContext += `ISSUE: Style values should be plain numbers, not "px" strings.\n`;
                        errorContext += `❌ Wrong: {"padding": "80px"}\n✅ Correct: {"padding": "80"}\n\n`;
                    } else if (errorMessage.includes('gap')) {
                        errorContext += `ISSUE: Containers/blocks with multiple children must have a "gap" property.\n`;
                        errorContext += `✅ Add: {"gap": "24", "children": [...]}\n\n`;
                    } else if (errorMessage.includes('flexDirection')) {
                        errorContext += `ISSUE: Elements with display:flex must specify flexDirection.\n`;
                        errorContext += `✅ Add: {"display": "flex", "flexDirection": "column"} or "row"\n\n`;
                    }

                    errorContext += `Your PREVIOUS (failed) input was:\n${JSON.stringify(failedAbility.input, null, 2)}\n\n`;
                    errorContext += `Please provide CORRECTED input that fixes the specific issue mentioned above.\n`;
                    errorContext += `Respond with ONLY a JSON code block with the corrected ability, or "CANNOT_FIX" if impossible.`;

                    const retryMessages = [
                        ...conversationMessages,
                        {
                            role: 'user',
                            content: errorContext
                        }
                    ];

                    const aiResponse = await callAI(retryMessages);
                    hideTyping();

                    if (aiResponse.includes('CANNOT_FIX')) {
                        debugLog('AI cannot fix the error');
                        return null;
                    }

                    const correctedAbilities = extractAbilitiesFromResponse(aiResponse);
                    if (correctedAbilities.length > 0) {
                        debugLog('✅ AI provided corrected input');
                        return correctedAbilities[0];
                    }

                    debugLog('⚠️ AI response did not contain valid corrected input');
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
                const retries = r.retries || 0;

                let html = '<div class="ability-results">';
                html += `<div class="ability-result ${success ? 'success' : 'error'}">`;
                html += `<strong>${status} ${r.ability}</strong>`;

                if (success) {
                    let successMsg = 'Content injected into page';
                    if (retries > 0) {
                        successMsg += ` (after ${retries} ${retries === 1 ? 'retry' : 'retries'})`;
                    }
                    html += `<div class="result-data">${successMsg}</div>`;
                } else {
                    let errorMsg = r.result.error || 'Unknown error';
                    if (retries > 0) {
                        errorMsg = `Failed after ${retries} ${retries === 1 ? 'retry' : 'retries'}: ${errorMsg}`;
                    }
                    html += `<div class="result-error">${errorMsg}</div>`;
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

            function addMessage(role, content, metadataOrImages = null) {
                const message = {
                    role: role,
                    content: content,
                    timestamp: Date.now()
                };

                // Handle images parameter (array of images) vs metadata
                if (Array.isArray(metadataOrImages) && metadataOrImages.length > 0 && metadataOrImages[0].data) {
                    message.images = metadataOrImages;
                } else {
                    message.metadata = metadataOrImages;
                }

                ChatState.messages.push(message);

                const $messages = $('#snn-bricks-chat-messages');
                const $welcome = $messages.find('.snn-bricks-chat-welcome');

                if ($welcome.length) {
                    $welcome.remove();
                    $('.snn-bricks-chat-quick-actions').hide();
                }

                const $message = $('<div>')
                    .addClass('snn-bricks-chat-message')
                    .addClass('snn-bricks-chat-message-' + role);

                // Add images if present
                if (message.images && message.images.length > 0) {
                    const $imagesDiv = $('<div>').addClass('snn-message-images');
                    message.images.forEach(img => {
                        const $img = $('<img>').attr('src', img.data).attr('alt', img.fileName || 'Attached image');
                        $imagesDiv.append($img);
                    });
                    $message.append($imagesDiv);
                }

                // Add text content
                $message.append($('<div>').html(formatMessage(content)));

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
                ChatState.attachedImages = [];
                renderImagePreviews();
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
.snn-bricks-chat-input-container { padding: 12px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 8px; align-items: flex-end; }
.snn-bricks-chat-input-wrapper { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.snn-bricks-chat-input { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px; resize: none; min-height: 70px; max-height: 120px; }
.snn-bricks-chat-attach-btn { width: 42px; height: 42px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; color: #666; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-bricks-chat-attach-btn:hover { background: #e0e0e0; }
.snn-bricks-chat-send { width: 42px; height: 42px; background: #161a1d; border: none; border-radius: 8px; color: #fff; cursor: pointer; display:flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-bricks-chat-send:hover { background: #0f1315; }
.snn-bricks-chat-image-preview { display: none; flex-wrap: wrap; gap: 8px; padding: 8px; background: #f9f9f9; border-radius: 8px; }
.snn-image-preview-item { position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; background: #fff; border: 1px solid #e0e0e0; }
.snn-image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
.snn-image-preview-remove { position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; background: rgba(0, 0, 0, 0.7); color: #fff; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; line-height: 1; padding: 0; display: flex; align-items: center; justify-content: center; }
.snn-image-preview-remove:hover { background: rgba(220, 38, 38, 0.9); }
.snn-message-images { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
.snn-message-images img { max-width: 200px; max-height: 200px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(0, 0, 0, 0.1); }
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
