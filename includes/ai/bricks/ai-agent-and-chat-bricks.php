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

        // Strip sensitive values — API key and endpoint are handled server-side by ai-proxy.php.
        unset( $ai_config['apiKey'], $ai_config['apiEndpoint'] );

        // Get Bricks page context
        $page_context = $this->get_bricks_page_context();

        wp_localize_script( 'jquery', 'snnBricksChatConfig', array(
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'restUrl'          => rest_url( 'wp-abilities/v1/' ),
            'nonce'            => wp_create_nonce( 'wp_rest' ),
            'agentNonce'       => wp_create_nonce( 'snn_ai_agent_nonce' ),
            'pageContext'      => $page_context,
            'ai'               => $ai_config,
            'settings'         => array(
                'debugMode'        => $main_chat->is_debug_enabled(),
                'maxHistory'       => $main_chat->get_max_history(),
                'enabledAbilities' => $main_chat->get_enabled_abilities(),
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

        // Add registered public post types for query loop generation support
        $post_type_objects = get_post_types( array( 'public' => true ), 'objects' );
        $post_types = array();
        foreach ( $post_type_objects as $pt ) {
            $post_types[ $pt->name ] = array(
                'label' => $pt->label,
                'slug'  => $pt->name,
            );
        }
        $context['postTypes'] = $post_types;

        return $context;
    }

    /**
     * Render overlay HTML
     */
    public function render_overlay() {
        $main_chat = SNN_Chat_Overlay::get_instance();
        ?>
        <!-- Design Preview Pane — left side, shown when HTML preview is active -->
        <div id="snn-bricks-preview-pane" class="snn-bricks-preview-pane" style="display:none;">
            <div class="snn-bricks-preview-header">
                <div class="snn-bricks-preview-title">
                    <span>Design Preview</span>
                    <span class="snn-bricks-preview-badge">HTML + CSS</span>
                </div>
                <div class="snn-bricks-preview-controls">
                    <button id="snn-preview-approve-btn" class="snn-preview-approve-btn">&#10003; Build in Bricks</button>
                    <button id="snn-preview-close-btn" class="snn-preview-close-btn" title="Hide preview">&times;</button>
                </div>
            </div>
            <iframe id="snn-bricks-preview-iframe" class="snn-bricks-preview-iframe"></iframe>
        </div>

        <div id="snn-bricks-chat-overlay" class="snn-bricks-chat-overlay" style="display: none;">
            <div class="snn-bricks-chat-container">
                <!-- Header -->
                <div class="snn-bricks-chat-header">
                    <div class="snn-bricks-chat-title">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>SNN Agent</span>
                        <span class="snn-bricks-agent-state-badge" id="snn-bricks-agent-state-badge"></span>
                    </div>
                    <div class="snn-bricks-chat-controls">
                        <button class="snn-bricks-chat-btn snn-bricks-chat-new" title="New chat" id="snn-bricks-chat-new-btn">
                            <span class="snn-bricks-chat-plus">+</span>
                        </button>
                        <button class="snn-bricks-chat-btn snn-bricks-chat-history" title="Chat history" id="snn-bricks-chat-history-btn">
                            <span class="dashicons dashicons-backup"></span>
                        </button>
                        <button class="snn-bricks-chat-btn" id="snn-bricks-preview-toggle-btn" title="Toggle Design Preview" style="display:none;">
                            <span style="font-size:15px;">&#128247;</span>
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

                <!-- Execution Checklist -->
                <div class="snn-bricks-execution-checklist" id="snn-bricks-execution-checklist" style="display:none;"></div>

                <!-- State Indicator -->
                <div class="snn-bricks-chat-state-text" id="snn-bricks-chat-state-text"></div>

                <!-- Quick Actions -->
                <div class="snn-bricks-chat-quick-actions">



<button class="snn-bricks-quick-action-btn" data-message="Design a luxury real estate homepage for 'Noir Properties'. The mood should be exclusive and high-end, using a palette of near-black, warm white, and elegant gold accents. Please include: 1) A striking split-layout hero section with a large property image and a call-to-action to 'Live Above the Ordinary'. 2) A dark stats band showing total sales and global reach. 3) A featured properties grid showcasing current listings with minimal gold details. 4) A dramatic full-width section emphasizing curated living spaces. 5) Elegant client testimonials in a grid. 6) A strong, dark footer to schedule a private consultation. Use sophisticated serif fonts for headings and ensure a spacious, premium feel.">
Real Estate</button>

<button class="snn-bricks-quick-action-btn" data-message="Create a premium, science-backed skincare brand homepage for 'Luminos'. Use a soft and elegant palette featuring blush, charcoal, dusty rose, and pure white. The page should feel calming yet clinically proven. Sections needed: 1) A hero with a product shot, a 'Science-Backed Skincare' badge, and a 'Skin That Speaks' headline. 2) An ingredient highlights row with small icons. 3) A beautiful product collection grid with 'Add to Bag' buttons. 4) A numbered '3-Step Morning Ritual' guide alongside a lifestyle image. 5) A dark-themed 'Real Results' section with before/after elements. 6) A 'Press' or 'As Seen In' logo band. 7) A blush-toned footer to start their skin journey.">
Skincare</button>

<button class="snn-bricks-quick-action-btn" data-message="Design a bold, modern SaaS homepage for 'Flowmatic', an AI workflow automation tool. The design should be dark-themed and energetic, using deep navy backgrounds, electric blue, and vibrant green accents. Include the following: 1) A high-converting hero section with a 'Now in Beta' badge, massive headline, and dual CTA buttons above a glowing product UI mockup. 2) A subtle social proof logo band. 3) A 3-column features grid with modern icons. 4) A clean, highlighted pricing tier section. 5) A testimonials area with user avatars. 6) A vibrant gradient-backed statistics band. 7) A final call-to-action encouraging users to start for free. Use clean sans-serif typography and high-tech UI aesthetics.">
SaaS</button>

<button class="snn-bricks-quick-action-btn" data-message="Generate a high-end, minimalist creative director portfolio for 'Elena Vasquez'. The aesthetic must be striking and high-contrast, utilizing pure black, pure white, warm off-white, and sharp red-orange accents. Please design: 1) A dramatic hero section featuring massive typography and a black-and-white portrait. 2) A ticker-style horizontal service band. 3) A 'Selected Work' grid mixing tall and square project cards with image overlays. 4) An 'About' section detailing her philosophy 'I make brands feel inevitable' alongside a text-based client list. 5) A numbered services list with alternating background rows. 6) An awards/recognition band. 7) A massive 'Let's Work' footer with social links. The overall vibe should be editorial, confident, and edgy.">
Portfolio</button>

<button class="snn-bricks-quick-action-btn" data-message="Design an intense, premium fitness coaching homepage for 'FORM' by coach Marcus Reid. The vibe should be gritty but highly professional, using near-black, crimson red, and stark white. Requirements: 1) An imposing hero section with a dark gym background, a red accent line, and the headline 'Built Different'. 2) A bold red stats bar highlighting athletes trained and success rates. 3) A clear 3-column pricing/program selection grid with a highlighted popular tier. 4) A 'Real Results' client transformation section. 5) A step-by-step 'FORM Method' breakdown with large faded numbers. 6) An 'About the Coach' bio with credentials and a strong quote. 7) An urgent final CTA warning of limited coaching spots. Keep typography massive, bold, and athletic.">
Fitness</button>


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

                <!-- Support Link -->
                <div class="snn-bricks-chat-support">
                    <a href="https://sinanisler.com/github-support" target="_blank" data-balloon="If SNN-BRX saving you time and money consider supporting the project monthly." data-balloon-length="large">Consider Supporting SNN-BRX ❤</a>
                </div>

                <?php endif; ?>
            </div>
        </div>

        <script>
        (function($) {
            'use strict';

            // ================================================================
            // Configuration & State
            // ================================================================

            const MAX_HISTORY       = snnBricksChatConfig.settings.maxHistory  || 20;
            const DEBUG_MODE        = snnBricksChatConfig.settings.debugMode   || false;
            const ENABLED_ABILITIES = snnBricksChatConfig.settings.enabledAbilities || [];
            const RECOVERY_CONFIG   = { maxRecoveryAttempts: 3, baseDelay: 2000, maxDelay: 30000, rateLimitDelay: 5000 };
            const debugLog = (...a) => { if (DEBUG_MODE) console.log('[Bricks AI]', ...a); };

            const ChatState = {
                messages: [], isOpen: false, isProcessing: false,
                abortController: null, currentSessionId: null,
                pageContext: snnBricksChatConfig.pageContext || {},
                recoveryAttempts: 0, bricksState: null, attachedImages: [],
                // Abilities API
                abilities: [],
                // Two-phase workflow
                currentHTMLPreview: null, previewMode: null, previewPaneOpen: false,
                // Global ID tracker to prevent duplicates across sections
                globalUsedIds: new Set(),
                // Theming state — populated by theming agent, carried forward for add_section
                currentTheme: null,
                // Accumulated global classes across all sections in a compilation session
                accumulatedGlobalClasses: []
            };

            // ================================================================
            // Bricks Builder — Direct State Integration
            // ================================================================

            const BricksHelper = {
                isAvailable() {
                    try {
                        const a = document.querySelector('[data-v-app]');
                        if (!a || !a.__vue_app__) return false;
                        const s = a.__vue_app__.config.globalProperties.$_state;
                        return !!(s && s.content);
                    } catch(e) { return false; }
                },
                getState() {
                    if (ChatState.bricksState) return ChatState.bricksState;
                    try {
                        const a = document.querySelector('[data-v-app]');
                        ChatState.bricksState = a.__vue_app__.config.globalProperties.$_state;
                        return ChatState.bricksState;
                    } catch(e) { return null; }
                },
                getCurrentContent() {
                    const s = this.getState();
                    if (!s) return null;
                    return { elements: s.content, elementCount: s.content ? s.content.length : 0 };
                },
                replaceAllContent(data) {
                    const s = this.getState();
                    if (!s) return { success: false, error: 'Bricks state not available' };
                    try {
                        const d    = typeof data === 'string' ? JSON.parse(data) : data;
                        const els  = d.content || d;
                        if (!Array.isArray(els)) throw new Error('Invalid content format');
                        // Reassign the array reference so Vue's reactivity system picks up the
                        // change correctly and Bricks re-initialises all canvas event listeners.
                        s.content = [...els];
                        debugLog('Replaced with', els.length, 'elements');
                        // Force Bricks to re-render the canvas and re-attach drag/edit listeners
                        setTimeout(() => {
                            if (window.bricksCore?.builder?.canvas?.render) {
                                window.bricksCore.builder.canvas.render();
                                // Second render after an additional tick to catch late reactive updates
                                requestAnimationFrame(() => window.bricksCore.builder.canvas.render());
                            }
                        }, 200); // bump from 150 → 200ms
                        return { success: true, message: `Replaced page with ${els.length} elements` };
                    } catch(e) { return { success: false, error: e.message }; }
                },
                addSection(data, position = 'append') {
                    const s = this.getState();
                    if (!s) return { success: false, error: 'Bricks state not available' };
                    try {
                        const d   = typeof data === 'string' ? JSON.parse(data) : data;
                        const els = d.content || d;
                        if (!Array.isArray(els)) throw new Error('Invalid content format');
                        // Reassign the array reference (not mutate in place) so Vue reactivity
                        // fires fully and Bricks re-initialises all canvas event listeners.
                        if (position === 'prepend') {
                            s.content = [...els, ...s.content];
                        } else {
                            s.content = [...s.content, ...els];
                        }
                        debugLog('Added', els.length, 'elements', position);
                        // Force Bricks to re-render the canvas and re-attach drag/edit listeners
                        setTimeout(() => {
                            if (window.bricksCore?.builder?.canvas?.render) {
                                window.bricksCore.builder.canvas.render();
                                // Second render after an additional tick to catch late reactive updates
                                requestAnimationFrame(() => window.bricksCore.builder.canvas.render());
                            }
                        }, 200); // bump from 150 → 200ms
                        return { success: true, message: `Added ${els.length} elements (${position})` };
                    } catch(e) { return { success: false, error: e.message }; }
                },
                patchElement(cmd) {
                    const s = this.getState();
                    if (!s || !s.content) return { success: false, error: 'Bricks state not available' };
                    const { element_id, find_by, updates } = cmd;
                    let typeCounter = 0, target = null;
                    for (const el of s.content) {
                        if (element_id && el.id === element_id) { target = el; break; }
                        if (find_by) {
                            if (find_by.type === 'text_content') {
                                const raw = (el.settings && (el.settings.text || el.settings.content)) || '';
                                if (raw.replace(/<[^>]*>/g, '').trim().toLowerCase().includes(find_by.value.toLowerCase())) { target = el; break; }
                            } else if (find_by.type === 'element_type' && el.name === find_by.value) {
                                if (typeCounter === (find_by.index || 0)) { target = el; break; }
                                typeCounter++;
                            }
                        }
                    }
                    if (!target) return { success: false, error: 'Element not found' };
                    if (updates.text        != null) target.settings.text = updates.text;
                    if (updates.image_url   != null) { if (!target.settings.image) target.settings.image = {}; target.settings.image.url = updates.image_url; }
                    if (updates.bricks_settings) Object.assign(target.settings, updates.bricks_settings);
                    return { success: true, message: `Patched [${target.id}]` };
                },
                applyPatch(patchData) {
                    const s = this.getState();
                    if (!s || !s.content) return { success: false, error: 'Bricks state not available' };
                    const patches = Array.isArray(patchData.patches) ? patchData.patches : [patchData];
                    let patched = 0;
                    const errors = [];
                    patches.forEach(patch => {
                        const result = this.patchElement(patch);
                        if (result.success) patched++;
                        else errors.push(result.error);
                    });
                    if (patched > 0) {
                        s.content = [...s.content]; // Trigger Vue reactivity
                        setTimeout(() => {
                            if (window.bricksCore?.builder?.canvas?.render) {
                                window.bricksCore.builder.canvas.render();
                                // Second render after an additional tick to catch late reactive updates
                                requestAnimationFrame(() => window.bricksCore.builder.canvas.render());
                            }
                        }, 200); // bump from 150 → 200ms
                    }
                    return patched > 0
                        ? { success: true, message: `Patched ${patched} element(s)` }
                        : { success: false, error: 'No elements matched: ' + errors.join('; ') };
                },
                getDesignTokens() {
                    const s = this.getState();
                    const tokens = { colors: [], sizes: [] };
                    if (!s) return tokens;
                    try {
                        if (s.colorPalette) {
                            const palette = Array.from(s.colorPalette);
                            if (palette.length && palette[0].colors) {
                                tokens.colors = Array.from(palette[0].colors).map(c => ({ raw: c.raw, hex: c.light || '' }));
                            }
                        }
                    } catch(e) { debugLog('getDesignTokens colorPalette error:', e); }
                    try {
                        if (s.globalVariables) {
                            tokens.sizes = Array.from(s.globalVariables).map(v => ({ name: v.name, value: v.value, cssVar: '--' + v.name }));
                        }
                    } catch(e) { debugLog('getDesignTokens globalVariables error:', e); }
                    return tokens;
                },

                /**
                 * Write AI-generated global classes directly into Bricks reactive state.
                 * EXISTING classes are NEVER deleted or overwritten — data integrity first.
                 * Only ADD new classes (or update classes the AI previously created for the same section).
                 *
                 * @param {Array} newClasses - Array of { id, name, settings: { _cssCustom } }
                 * @return {number} Count of newly added classes
                 */
                writeGlobalClassesToState(newClasses) {
                    const s = this.getState();
                    if (!s) { debugLog('Bricks state not available for global class registration'); return 0; }

                    // Ensure globalClasses array exists
                    if (!Array.isArray(s.globalClasses)) {
                        s.globalClasses = [];
                    }

                    // Build sets for O(1) duplicate checking — check BOTH id AND name
                    const existingIds = new Set(s.globalClasses.map(gc => gc.id));
                    const existingNames = new Set(s.globalClasses.map(gc => gc.name));

                    let addedCount = 0;
                    newClasses.forEach(gc => {
                        // Skip if ID already exists
                        if (existingIds.has(gc.id)) return;

                        // If name already exists, append a numeric suffix to avoid conflicts
                        let finalName = gc.name;
                        let cssCustom = gc.cssCustom || (gc.settings && gc.settings._cssCustom) || '';
                        if (existingNames.has(finalName)) {
                            let suffix = 1;
                            while (existingNames.has(finalName + '-' + suffix)) suffix++;
                            finalName = finalName + '-' + suffix;
                            // CRITICAL: Rewrite CSS selectors to use the new deduplicated name
                            // ".hero { ... }" → ".hero-1 { ... }", ".hero:hover { ... }" → ".hero-1:hover { ... }"
                            const escapedOld = gc.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const selectorRegex = new RegExp('\\.' + escapedOld + '(?=\\s*[{,:])', 'g');
                            cssCustom = cssCustom.replace(selectorRegex, '.' + finalName);
                        }
                        s.globalClasses.push({
                            id: gc.id,
                            name: finalName,
                            user_id: '1',
                            modified: Math.floor(Date.now() / 1000),
                            settings: {
                                _cssCustom: cssCustom
                            }
                        });
                        existingIds.add(gc.id);
                        existingNames.add(finalName);
                        addedCount++;
                    });

                    // Force Vue reactivity to pick up the changes
                    s.globalClasses = [...s.globalClasses];

                    debugLog('✓ Added', addedCount, 'global classes to reactive state (skipped',
                             newClasses.length - addedCount, 'existing)');
                    return addedCount;
                },

                /**
                 * Write CSS color variables to Bricks color palette (first/default palette only).
                 * Each variable becomes a color entry: { id, name, raw, light }
                 * Bricks uses "light" (not "hex") as the color value field.
                 * @param {Array} colorVars - [{name: "primary", value: "#0f172a"}, ...]
                 * @return {number} Count of newly added colors
                 */
                writeColorPaletteToState(colorVars) {
                    const s = this.getState();
                    if (!s) { debugLog('Bricks state not available for color palette'); return 0; }
                    if (!colorVars || !colorVars.length) return 0;

                    // Ensure colorPalette array exists and has at least one palette
                    if (!Array.isArray(s.colorPalette)) s.colorPalette = [];
                    if (!s.colorPalette.length) {
                        s.colorPalette.push({ id: this._genShortId(), name: 'Default', colors: [] });
                    }
                    const palette = s.colorPalette[0]; // First/default palette
                    if (!Array.isArray(palette.colors)) palette.colors = [];

                    // Generate short ID helper
                    const genCid = () => {
                        const L = 'abcdefghijklmnopqrstuvwxyz';
                        let id;
                        do { id = Array.from({length:6}, () => L[Math.floor(Math.random()*26)]).join(''); }
                        while (palette.colors.some(c => c.id === id));
                        return id;
                    };

                    // Build set of existing color names for dedup
                    const existingNames = new Set(palette.colors.map(c => c.name));

                    let addedCount = 0;
                    colorVars.forEach(v => {
                        if (existingNames.has(v.name)) return;
                        // Determine light value: if it's a hex/rgb, use it; if var(), resolve if possible
                        let light = v.value;
                        if (light.match(/^var\(--/)) light = ''; // Can't resolve var() refs, leave empty
                        palette.colors.push({
                            id: genCid(),
                            name: v.name,
                            raw: 'var(--' + v.name + ')',
                            light: light   // ← Bricks uses "light", NOT "hex"
                        });
                        existingNames.add(v.name);
                        addedCount++;
                    });

                    if (addedCount) s.colorPalette = [...s.colorPalette];
                    debugLog('✓ Added', addedCount, 'colors to palette');
                    return addedCount;
                },

                /**
                 * Write CSS size/spacing variables to Bricks global variables.
                 * @param {Array} sizeVars - [{name: "section-padding", value: "100px"}, ...]
                 * @return {number} Count of newly added variables
                 */
                writeVariablesToState(sizeVars) {
                    const s = this.getState();
                    if (!s) { debugLog('Bricks state not available for variables'); return 0; }
                    if (!sizeVars || !sizeVars.length) return 0;

                    if (!Array.isArray(s.globalVariables)) s.globalVariables = [];

                    const existingNames = new Set(s.globalVariables.map(v => v.name));
                    const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
                    const genVid = () => {
                        let id;
                        do { id = Array.from({length:6}, () => LETTERS[Math.floor(Math.random()*26)]).join(''); }
                        while (s.globalVariables.some(v => v.id === id));
                        return id;
                    };

                    let addedCount = 0;
                    sizeVars.forEach(v => {
                        if (existingNames.has(v.name)) return;
                        s.globalVariables.push({
                            id: genVid(),
                            name: v.name,
                            value: v.value
                        });
                        existingNames.add(v.name);
                        addedCount++;
                    });

                    if (addedCount) s.globalVariables = [...s.globalVariables];
                    debugLog('✓ Added', addedCount, 'size variables');
                    return addedCount;
                },

                /**
                 * Write compiled elements into Bricks reactive state.
                 * Replaces or appends to bricksState.content.
                 *
                 * @param {Array} contentArray - Array of Bricks element objects
                 * @param {string} actionType - 'replace' or 'append'
                 * @return {boolean} success
                 */
                writeElementsToState(contentArray, actionType = 'append') {
                    const s = this.getState();
                    if (!s) { debugLog('Bricks state not available for element injection'); return false; }

                    if (!Array.isArray(s.content)) {
                        s.content = [];
                    }

                    if (actionType === 'replace') {
                        s.content = [...contentArray];
                    } else {
                        s.content = [...s.content, ...contentArray];
                    }

                    // Trigger canvas re-render
                    setTimeout(() => {
                        if (window.bricksCore?.builder?.canvas?.render) {
                            window.bricksCore.builder.canvas.render();
                            requestAnimationFrame(() => window.bricksCore.builder.canvas.render());
                        }
                    }, 200);

                    return true;
                }
            };

            // ================================================================
            // Init
            // ================================================================

            $(document).ready(function() {
                const iv = setInterval(function() {
                    if (BricksHelper.isAvailable()) {
                        clearInterval(iv);
                        debugLog('Bricks ready, initialising chat...');
                        initChat();
                        addToolbarButton();
                        loadAbilities();
                    }
                }, 500);
                setTimeout(() => clearInterval(iv), 10000);
            });

            // ================================================================
            // PHASE 1 — Multi-State AI Pipeline
            // Flow: analyzing → (planning →) designing / patching / answering
            // ================================================================

            async function processWithAI(userMessage, images = []) {
                ChatState.isProcessing = true;
                updateSendButton();
                showTyping();
                try {
                    // ── STATE: analyzing ─────────────────────────────────────────
                    setAgentState('analyzing');
                    let intent = 'new_design'; // safe default
                    try {
                        intent = await classifyIntent(userMessage, images);
                    } catch(e) {
                        debugLog('classifyIntent error (falling back to new_design):', e);
                    }
                    debugLog('Intent classified:', intent);

                    // ── Route by intent ──────────────────────────────────────────
                    if (intent === 'use_abilities') {
                        // ── STATE: abilities ─────────────────────────────────────
                        setAgentState('abilities');
                        await runAbilitiesFlow(userMessage, images);

                    } else if (intent === 'new_design' || intent === 'add_section') {
                        // ── STATE: planning ──────────────────────────────────────
                        setAgentState('planning');
                        let plan = '';
                        try {
                            plan = await generatePlan(userMessage, intent);
                            if (plan) addMessage('assistant', plan);
                        } catch(e) {
                            debugLog('generatePlan error (skipping plan):', e);
                        }

                        // ── STATE: theming ───────────────────────────────────────
                        showTyping();
                        setAgentState('theming');
                        try {
                            ChatState.currentTheme = await generateTheme(userMessage, plan);
                            if (ChatState.currentTheme) {
                                const t   = ChatState.currentTheme;
                                const src = t.usedExistingTokens ? '♻️ Using existing Bricks theme tokens' : '🎨 Generated fresh palette';
                                addMessage('assistant',
                                    `${src} — **${t.style}** / ${t.mood.join(', ')}\n` +
                                    `Primary: \`${t.palette.primary}\`  Accent: \`${t.palette.accent}\`\n` +
                                    `Fonts: ${t.fonts.heading} + ${t.fonts.body}`
                                );
                            }
                        } catch(e) { debugLog('theming error (skipping):', e); }

                        // ── STATE: designing ─────────────────────────────────────
                        showTyping();
                        setAgentState('designing');
                        await runDesigning(userMessage, images, plan, intent);

                        // ── STATE: reviewing ─────────────────────────────────────
                        const rawHtml = ChatState.currentHTMLPreview;
                        if (rawHtml) {
                            showTyping();
                            setAgentState('reviewing');
                            try {
                                const reviewed = await reviewDesign(rawHtml);
                                if (reviewed && reviewed !== rawHtml) {
                                    debugLog('Reviewer made fixes to HTML');
                                    ChatState.currentHTMLPreview = reviewed;
                                    showHTMLPreview(reviewed);
                                }
                            } catch(e) { debugLog('reviewing error (skipping):', e); }
                        }

                    } else if (intent === 'refine_preview') {
                        setAgentState('designing');
                        await runDesigning(userMessage, images, '', 'refine_preview');

                    } else if (intent === 'edit_patch') {
                        // ── STATE: patching ──────────────────────────────────────
                        setAgentState('patching');
                        await runPatching(userMessage, images);

                    } else {
                        // ── STATE: answering ─────────────────────────────────────
                        setAgentState('answering');
                        await runAnswering(userMessage, images);
                    }

                    autoSaveConversation();

                } catch(err) {
                    if (err.name !== 'AbortError') {
                        addMessage('error', 'Error: ' + err.message);
                        debugLog('processWithAI error:', err);
                    }
                } finally {
                    hideTyping();
                    ChatState.isProcessing = false;
                    setAgentState('idle');
                    updateSendButton();
                }
            }

            // ── Intent classification (analyzing state) ──────────────────────
            async function classifyIntent(userMessage, images = []) {
                const cc = BricksHelper.getCurrentContent();
                const hasExistingContent = cc && cc.elementCount > 0;
                const hasPreview = !!ChatState.currentHTMLPreview;
                const hasAbilities = ChatState.abilities.length > 0;
                const pageSnap = hasExistingContent
                    ? 'Page has ' + cc.elementCount + ' existing elements.'
                    : 'Page is empty.';

                const systemPrompt = `You are an intent classifier for a Bricks Builder AI assistant.
Classify the user message into exactly one intent:
  new_design    — user wants a full page or complete site designed from scratch
  add_section   — user wants a new section appended to the existing page
  edit_patch    — user wants to change/update existing page element content or styles
  question      — user is asking a question (no design or edit action requested)
  refine_preview — user wants changes to the current HTML preview (tweak colors, fonts, layout)
  use_abilities  — user wants to perform a WordPress site action: get site info, list/create/update posts, manage users, check health, etc.${hasAbilities ? '' : ' (NOTE: no abilities available, treat as question)'}

Context: ${pageSnap}${hasPreview ? ' An HTML preview is currently displayed.' : ''}${hasAbilities ? ' WordPress abilities are available.' : ''}

Routing rules:
- Empty page + design description → new_design
- Has content + "add", "include", "append", "create new section" → add_section
- Has elements + "change", "update", "fix", "make darker/bigger/different" on something specific → edit_patch
- Preview shown + user tweaks it ("darker", "bigger font", "change headline") → refine_preview
- User asks for WordPress data/actions (list posts, site info, users, health, create post) → use_abilities
- Pure question without action → question
- Ambiguous on non-empty page → add_section

Respond with ONLY valid JSON — no markdown, no explanation:
{"intent": "new_design", "reasoning": "brief"}`;

                const userContent = buildUserContent(userMessage, images);
                const response = await callAI(
                    [{ role: 'system', content: systemPrompt }, { role: 'user', content: userContent }],
                    0,
                    { maxTokens: 150 }
                );
                try {
                    const parsed = JSON.parse(response.trim());
                    return parsed.intent || 'new_design';
                } catch(e) {
                    const match = response.match(/\b(new_design|add_section|edit_patch|question|refine_preview|use_abilities)\b/);
                    return match ? match[1] : 'new_design';
                }
            }

            // ── Plan generation (planning state) ─────────────────────────────
            async function generatePlan(userMessage, intent) {
                const systemPrompt = `You are a web design layout planner for Bricks Builder.
The user wants to ${intent === 'new_design' ? 'design a full page' : 'add a new section'}.
List the sections you will create. Be brief and concrete.

Format EXACTLY like this:
📋 Planning your layout:
  1. Section Name — one-sentence description
  2. Section Name — one-sentence description

End with: "Starting design..."

Rules:
- 1–6 sections — if the user asks for a single section (e.g. "a hero", "add a pricing section"), plan EXACTLY 1 section. Do not pad to 2.
- Concise names: Hero, Features, Testimonials, Pricing, CTA, Footer etc.
- One sentence per section — no HTML, no code
- Respond with the plan only, nothing else`;

                const response = await callAI(
                    [{ role: 'system', content: systemPrompt }, { role: 'user', content: userMessage }],
                    0,
                    { maxTokens: 400 }
                );
                return response.trim();
            }

            // ── Theme generation (theming state) ─────────────────────────────
            async function generateTheme(userMessage, plan) {
                const tokens    = BricksHelper.getDesignTokens();
                const hasColors = tokens.colors.length > 0;
                const hasSizes  = tokens.sizes.length  > 0;

                let tokenContext = '';
                if (hasColors) {
                    const colorList = tokens.colors.map(c =>
                        `  ${c.raw}${c.hex ? ' \u2192 ' + c.hex : ''}`
                    ).join('\n');
                    tokenContext += `\nEXISTING THEME COLORS (Bricks global palette):\n${colorList}\n`;
                }
                if (hasSizes) {
                    const sizeList = tokens.sizes.map(v =>
                        `  var(${v.cssVar}) = ${v.value}`
                    ).join('\n');
                    tokenContext += `\nEXISTING THEME SIZES (Bricks global variables):\n${sizeList}\n`;
                }

                const tokenInstructions = (hasColors || hasSizes) ? `
EXISTING BRICKS TOKENS RULES:
- If the user explicitly says "use existing colors", "use theme colors", "match the site", or similar → use the existing palette values above for primary/secondary/accent/background etc. Set "usedExistingTokens": true.
- If no color direction is given at all → create a FRESH, original palette using concrete hex values that fits the brief. Do NOT use existing Bricks tokens — invent your own hex colors.
- If the user describes a specific new palette (e.g. "dark navy and gold") → use their description with concrete hex values, ignore existing tokens entirely.
- For sizes: if existing size variables are present, prefer their VALUES (e.g. "100px" not "var(--section-padding)") for sectionPadding, containerGap, cardPadding, borderRadius where appropriate.
- ⚠️ CRITICAL: ALWAYS output CONCRETE hex color values (#rrggbb). NEVER use var(--anything) references in palette values. var() references break the CSS pipeline because the designer agent cannot resolve them. Output raw hex like "#0f172a", "#c5a059", "#ffffff" — never "var(--bricks-color-xxx)" or "var(--secondary)".
` : '';

                const systemPrompt = `You are a visual design director for a web page being built in Bricks Builder.
Given a project brief, layout plan, and any existing design tokens, output ONLY a JSON design spec \u2014 no prose, no markdown, no explanation.
${tokenContext}${tokenInstructions}
Output this exact JSON shape (use CONCRETE HEX VALUES only, never var() references):
{
  "usedExistingTokens": false,
  "palette": {
    "primary":    "#0f172a",
    "secondary":  "#f8fafc",
    "accent":     "#c5a059",
    "background": "#0f172a",
    "surface":    "#1e293b",
    "text":       "#f8fafc",
    "textMuted":  "#94a3b8"
  },
  "fonts": {
    "heading":       "Google Font Name",
    "body":          "Google Font Name",
    "headingWeight": "900",
    "bodyWeight":    "400"
  },
  "spacing": {
    "sectionPadding": "100px",
    "containerGap":   "32px",
    "cardPadding":    "32px",
    "borderRadius":   "12px"
  },
  "mood":  ["bold", "premium"],
  "style": "minimal | editorial | bold | elegant | playful | technical"
}

⚠️ PALETTE VALUES MUST BE CONCRETE HEX (#rrggbb) OR rgb() — NEVER var(--anything). var() references are unusable and will break the design.`;

                const response = await callAI(
                    [
                        { role: 'system', content: systemPrompt },
                        { role: 'user',   content: userMessage + (plan ? '\n\nLayout plan:\n' + plan : '') }
                    ],
                    0,
                    { maxTokens: 350 }
                );

                try {
                    const cleaned = response.trim().replace(/^```json\n?/, '').replace(/\n?```$/, '');
                    return JSON.parse(cleaned);
                } catch(e) {
                    debugLog('generateTheme parse error, using defaults:', e);
                    return null;
                }
            }

            // ── Designing (designing state) ───────────────────────────────────
            async function runDesigning(userMessage, images, plan, intent) {
                const context        = buildConversationContext();
                // Prepend plan to user message so the designer knows the agreed structure
                const fullMessage = plan
                    ? userMessage + '\n\n[Agreed section plan — execute ALL sections in order:\n' + plan + ']'
                    : userMessage;
                const userMsgContent = buildUserContent(fullMessage, images);
                const response = await callAI([
                    { role: 'system', content: buildDesigningPrompt(intent) },
                    ...context,
                    { role: 'user', content: userMsgContent }
                ]);
                hideTyping();
                if (!response || !response.trim()) throw new Error('AI returned empty response.');

                const html = extractHTMLFromResponse(response);
                let textPart = response;
                if (html) {
                    if (response.match(/```(?:html)?\n?[\s\S]*?\n?```/i)) {
                        textPart = response.replace(/```(?:html)?[\s\S]*?```/gi, '').trim();
                    } else {
                        const firstTagIndex = response.search(/<(style|section|div|header|main|nav)/i);
                        if (firstTagIndex !== -1) textPart = response.substring(0, firstTagIndex).trim();
                    }
                }
                if (html) {
                    ChatState.currentHTMLPreview = html;
                    ChatState.previewMode        = 'html';
                    if (textPart) addMessage('assistant', textPart);
                    showHTMLPreview(html);
                    addApproveBar();
                } else {
                    addMessage('assistant', response);
                }
            }

            // ── Patching (patching state) ─────────────────────────────────────
            async function runPatching(userMessage, images) {
                const context        = buildConversationContext();
                const userMsgContent = buildUserContent(userMessage, images);
                const response = await callAI([
                    { role: 'system', content: buildPatchingPrompt() },
                    ...context,
                    { role: 'user', content: userMsgContent }
                ], 0, { maxTokens: 1500 });
                hideTyping();
                if (!response || !response.trim()) throw new Error('AI returned empty response.');

                const patchData = extractPatchFromResponse(response);
                if (patchData) {
                    const result   = BricksHelper.applyPatch(patchData);
                    const textPart = response.replace(/```patch[\s\S]*?```/g, '').trim();
                    if (textPart) addMessage('assistant', textPart);
                    result.success
                        ? addMessage('assistant', '✓ ' + result.message)
                        : addMessage('error', '✗ Patch failed: ' + result.error);
                } else {
                    // AI answered in prose instead of a patch block — show it
                    addMessage('assistant', response);
                }
            }

            // ── Answering (answering state) ───────────────────────────────────
            async function runAnswering(userMessage, images) {
                const context        = buildConversationContext();
                const userMsgContent = buildUserContent(userMessage, images);
                const response = await callAI([
                    { role: 'system', content: buildAnsweringPrompt() },
                    ...context,
                    { role: 'user', content: userMsgContent }
                ], 0, { maxTokens: 800 });
                hideTyping();
                if (!response || !response.trim()) throw new Error('AI returned empty response.');
                addMessage('assistant', response);
            }

            // ── Review design (reviewing state) ───────────────────────────────
            async function reviewDesign(html) {
                const systemPrompt = `You are a Bricks Builder HTML validator for a CLASS-BASED design system.
Review the HTML and fix ONLY these specific structural issues:

1. MISSING data-bricks attributes — every structural element must have one
2. INVALID NESTING — section > container > block > content (never container inside block)
3. MISSING class attributes — elements should have CSS class references (no inline styles)
4. ORPHANED CSS classes — every class used on elements must have a definition in <style>

Return ONLY the corrected HTML. If no issues found, return the HTML unchanged.
Do NOT redesign, rewrite copy, or change colors. Fix structural issues ONLY.
Output as a \`\`\`html block.`;

                const response = await callAI(
                    [{ role: 'system', content: systemPrompt },
                     { role: 'user',   content: '```html\n' + html + '\n```' }],
                    0,
                    { maxTokens: snnBricksChatConfig.ai.maxTokens || 4000 }
                );
                hideTyping();
                return extractHTMLFromResponse(response) || html; // fallback to original if parse fails
            }

            // ================================================================
            // PHASE 2 — Compile HTML Preview → Bricks JSON → Inject
            // ================================================================

            /**
             * Parse an HTML string into individual sections by landmark elements.
             * Returns [{label, html}, ...]. Falls back to one entry if no landmarks found.
             */
            function parseHTMLIntoSections(html) {
                const parser  = new DOMParser();
                const doc     = parser.parseFromString(html, 'text/html');
                const body    = doc.body;
                const semTags = new Set(['section','header','footer','nav','article']);
                const results = [];

                function getLabel(el) {
                    const h = el.querySelector('h1,h2,h3,h4');
                    return el.getAttribute('aria-label')
                        || (h ? h.textContent.trim().slice(0, 50) : '')
                        || el.tagName.charAt(0).toUpperCase() + el.tagName.slice(1).toLowerCase();
                }

                // Collect any <style data-style-id> elements immediately preceding a section element.
                // These sibling style blocks are excluded from child.outerHTML, so we prepend them
                // manually so the compiler's styleIdMap can find and apply them correctly.
                function getPrecedingStyleBlocks(el) {
                    let styles = '';
                    let prev = el.previousElementSibling;
                    while (prev) {
                        const prevTag = prev.tagName.toLowerCase();
                        if (prevTag === 'style' && prev.hasAttribute('data-style-id')) {
                            styles = prev.outerHTML + styles;
                            prev = prev.previousElementSibling;
                        } else {
                            break;
                        }
                    }
                    return styles;
                }

                for (const child of Array.from(body.children)) {
                    const tag = child.tagName.toLowerCase();
                    if (tag === 'main') {
                        // Recurse into <main> — extract its direct block children as separate sections
                        const inner = Array.from(child.children).filter(el => {
                            const t = el.tagName.toLowerCase();
                            return semTags.has(t) || (t === 'div' && el.children.length > 0);
                        });
                        if (inner.length >= 2) {
                            inner.forEach(el => results.push({ label: getLabel(el), html: el.outerHTML }));
                        } else {
                            results.push({ label: getLabel(child), html: child.outerHTML });
                        }
                    } else if (semTags.has(tag)) {
                        results.push({ label: getLabel(child), html: getPrecedingStyleBlocks(child) + child.outerHTML });
                    } else if (tag === 'div' && child.children.length > 0) {
                        // Capture meaningful top-level divs (e.g. ticker bars, announcement bands)
                        results.push({ label: getLabel(child), html: getPrecedingStyleBlocks(child) + child.outerHTML });
                    }
                }
                if (!results.length) results.push({ label: 'Page Content', html });
                return results;
            }

            /**
             * Enhanced validation and auto-fix for Bricks JSON object before injection.
             * Fixes duplicate IDs, missing parent fields, orphaned elements, missing settings, and validates structure.
             */
            function validateAndFixBricksJSON(data, globalIdSet = ChatState.globalUsedIds) {
                const content = data.content;
                if (!Array.isArray(content) || !content.length) {
                    return { valid: false, data, errors: ['Empty content array'] };
                }
                const errors  = [];
                let   fixed   = false;
                const localIds = new Set();
                const idRemap  = {};
                const LETTERS  = 'abcdefghijklmnopqrstuvwxyz';

                // Generate a Bricks-native ID: exactly 6 lowercase letters (matches Bricks' ^[a-z]{6}$ format)
                // This ensures brxe-{id} CSS classes are parsed correctly by Bricks' canvas event system.
                function genId() {
                    let id;
                    do {
                        id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
                    } while (localIds.has(id) || globalIdSet.has(id));
                    return id;
                }

                // PASS 1: Aggressively rewrite EVERY ID to a Bricks-native 6-letter ID.
                // This prevents brxe-class mismatches that silently break right-click context menus.
                content.forEach(el => {
                    const oldId = el.id || '';
                    const newId = genId();
                    idRemap[oldId] = newId;
                    el.id = newId;
                    localIds.add(newId);
                    globalIdSet.add(newId);
                    fixed = true;

                    // Ensure required baseline fields
                    if (!el.name)        { el.name = 'block'; }
                    if (!el.settings)    { el.settings = {}; }
                    if (!el.themeStyles) { el.themeStyles = []; }
                    el.children = []; // Will be rebuilt in final pass
                });

                // PASS 2: Remap parent references to new IDs
                content.forEach(el => {
                    const rawParent = el.parent;
                    if (rawParent === 0 || rawParent === '0' || rawParent === undefined || rawParent === null) {
                        el.parent = 0;
                    } else if (idRemap[rawParent]) {
                        el.parent = idRemap[rawParent];
                    } else {
                        // Parent didn't exist in this batch — rescue to root
                        errors.push('Orphan rescue: ' + el.id + ' (old parent ' + rawParent + ') → root');
                        el.parent = 0;
                    }
                });

                // PASS 2b: Nesting failsafe — convert any container that is a child of another
                // container or a child of a block into a block. 'container' must only ever be a
                // direct child of 'section' (parent === 0 is the page root; the real section
                // element will have parent 0 and name 'section').  We detect invalid containers
                // by checking whether their parent element is also a container or a block.
                {
                    const nameMap = {};
                    content.forEach(el => { nameMap[el.id] = el.name; });
                    content.forEach(el => {
                        if (el.name === 'container' && el.parent !== 0) {
                            const parentName = nameMap[el.parent];
                            if (parentName === 'container' || parentName === 'block') {
                                errors.push('Nesting fix: converted rogue container ' + el.id + ' (parent ' + el.parent + ' is ' + parentName + ') → block');
                                el.name = 'block';
                                nameMap[el.id] = 'block';
                                fixed = true;
                            }
                        }
                    });
                }

                // PASS 3: Validate _cssGlobalClasses is an array (class-based compiler guarantee)
                content.forEach(el => {
                    if (el.settings._cssGlobalClasses && !Array.isArray(el.settings._cssGlobalClasses)) {
                        el.settings._cssGlobalClasses = [el.settings._cssGlobalClasses];
                        fixed = true;
                    }
                });

                // FINAL PASS: Rebuild children arrays from parent declarations.
                // Guarantees bidirectional parent↔children consistency for Bricks' tree parser.
                const idMap = {};
                content.forEach(el => { idMap[el.id] = el; });
                content.forEach(el => {
                    if (el.parent !== 0 && el.parent !== '0') {
                        if (idMap[el.parent]) {
                            idMap[el.parent].children.push(el.id);
                        } else {
                            errors.push('Orphan final rescue: ' + el.id + ' → root');
                            el.parent = 0;
                            fixed = true;
                        }
                    }
                });

                if (fixed || errors.length) debugLog('JSON validation:', { fixed, errors: errors.length, remapped: Object.keys(idRemap).length });
                return { valid: true, fixed, data, errors };
            }

            /**
             * Compile a single HTML section to Bricks JSON via AI.
             * Performs one automatic retry on parse failure.
             */
            /**
             * Compile a single HTML section to Bricks JSON using JavaScript (NO AI).
             * This is the new LIGHTNING-FAST, 100% RELIABLE compiler.
             * 
             * @param {string} sectionHtml - The HTML string for one section
             * @param {string} sectionLabel - Human-readable label for debugging
             * @param {number} sectionIndex - Section number (1-based)
             * @return {object} - Bricks JSON {content: [...]}
             */
            async function compileSingleSection(sectionHtml, sectionLabel, sectionIndex, classNameToId = null) {
                try {
                    // CLASS-BASED compiler — classNameToId is pre-computed from full HTML <style>
                    const bricksData = compileHtmlToBricksJson(sectionHtml, classNameToId);
                    
                    if (!bricksData || !bricksData.content || !bricksData.content.length) {
                        throw new Error('Compiler returned empty content');
                    }
                    
                    debugLog('✓ Compiled "' + sectionLabel + '" — ' + bricksData.content.length + ' elements, ' +
                             (classNameToId ? Object.keys(classNameToId).length : 0) + ' class refs available');
                    return bricksData;
                    
                } catch (error) {
                    debugLog('✗ Compilation error for "' + sectionLabel + '":', error);
                    throw new Error('Failed to compile section: ' + error.message);
                }
            }

            /**
             * Main orchestrator: parses HTML into sections, compiles each
             * one individually with JavaScript (instant!), and injects them into Bricks.
             */
            async function compileSectionBySection(actionType) {
                if (!ChatState.currentHTMLPreview) return;
                ChatState.isProcessing = true;
                updateSendButton();
                setAgentState('compiling');

                // Reset global ID tracker for this compilation session
                ChatState.globalUsedIds.clear();
                ChatState.accumulatedGlobalClasses = [];

                const fullHTML = ChatState.currentHTMLPreview;
                const sections = parseHTMLIntoSections(fullHTML);
                const total    = sections.length;
                addMessage('assistant', '⚡ Compiling ' + total + ' section' + (total > 1 ? 's' : '') + ' with class-based compiler...');

                // ── PHASE 0: Extract CSS, fonts, and variables from FULL HTML <style> blocks ──
                // CRITICAL: <style> tags are children of <body>, but parseHTMLIntoSections
                // only captures semantic elements. We extract everything here BEFORE splitting.
                const fullDoc = new DOMParser().parseFromString(fullHTML, 'text/html');
                const tempClassMap = {};       // { "hero": { id: "abcxyz", css: ".hero{...}" } }
                const classNameToId = {};      // { "hero": "abcxyz" }
                const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
                const tempUsedIds = new Set();
                let allStyleCSS = '';          // Accumulate ALL <style> text for font/var extraction
                function genClassId() {
                    let id;
                    do {
                        id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
                    } while (tempUsedIds.has(id) || ChatState.globalUsedIds.has(id));
                    tempUsedIds.add(id);
                    ChatState.globalUsedIds.add(id);
                    return id;
                }
                fullDoc.querySelectorAll('style').forEach(styleEl => {
                    const css = styleEl.textContent;
                    allStyleCSS += css + '\n';
                    const rules = parseCSSRules(css);
                    for (const [className, cssBlock] of Object.entries(rules)) {
                        if (!tempClassMap[className]) {
                            const gid = genClassId();
                            tempClassMap[className] = { id: gid, css: '' };
                            classNameToId[className] = gid;
                        }
                        tempClassMap[className].css += cssBlock;
                    }
                });

                // Build global classes array from extracted CSS
                const allGlobalClasses = Object.entries(tempClassMap).map(([className, gc]) => ({
                    id: gc.id,
                    name: className,
                    cssCustom: gc.css.trim()
                }));
                debugLog('Extracted ' + allGlobalClasses.length + ' CSS classes from full HTML <style> blocks');

                // ── PHASE 0b: Build combined CSS injection element ──
                // Contains ALL non-class CSS: @import, :root, body, *, @font-face, etc.
                // This is injected via a <style> tag as a custom-html-css-script element.
                // CRITICAL: parseCSSRules only captures .class rules. Everything else
                // (body{}, :root{}, @font-face{}, etc.) must be injected directly so
                // tag-level styles and CSS custom properties survive.
                const globalCSS = extractGlobalCSS(allStyleCSS);
                debugLog('Global CSS extracted: ' + (globalCSS ? globalCSS.length + ' chars' : 'none'));

                let cssInjectionElement = null;
                if (globalCSS) {
                    const cssElId = genClassId();
                    cssInjectionElement = {
                        id: cssElId,
                        name: 'custom-html-css-script',
                        parent: 0,
                        children: [],
                        settings: { content: '<style>' + globalCSS + '</style>' },
                        themeStyles: []
                    };
                    debugLog('CSS injection element built');
                }

                // ── PHASE 0c: Best-effort: write resolvable variables to Bricks palette/vars ──
                const rootVars = extractRootVariables(allStyleCSS);
                // This supplements the :root injection above. Only write values that can
                // be resolved (hex colors, pixel sizes). var() references are skipped
                // because we can't resolve them — let the :root injection handle those.
                if (rootVars.variables.length) {
                    const colorVars = [];
                    const sizeVars = [];
                    rootVars.variables.forEach(v => {
                        const val = v.value;

                        // var(--xxx) references: can't resolve, skip (handled by :root injection)
                        if (val.match(/^var\(--/)) return;

                        // Color detection: hex, rgb/rgba, hsl/hsla, named colors
                        if (val.match(/^#[0-9a-fA-F]{3,8}$/) ||
                            val.match(/^rgb(a?)\(/) ||
                            val.match(/^hsl(a?)\(/) ||
                            val.match(/^(transparent|currentColor|inherit|initial|unset)$/i)) {
                            colorVars.push({ name: v.name, value: val });
                        }
                        // Size detection: value starts with digit or has CSS unit
                        else if (val.match(/^-?\d/) || (val.match(/[a-z]+$/i) && val.match(/px|em|rem|%|vw|vh|vmin|vmax|ch|ex|cm|mm|in|pt|pc/))) {
                            sizeVars.push({ name: v.name, value: val });
                        }
                    });
                    if (colorVars.length) {
                        const added = BricksHelper.writeColorPaletteToState(colorVars);
                        debugLog('Color variables saved to palette: ' + added);
                    }
                    if (sizeVars.length) {
                        const added = BricksHelper.writeVariablesToState(sizeVars);
                        debugLog('Size variables saved: ' + added);
                    }
                }

                // ── PHASE 1: Register ALL global classes FIRST (before any elements) ──
                if (allGlobalClasses.length) {
                    setAgentState('compiling', 'Registering ' + allGlobalClasses.length + ' CSS classes...');
                    const addedCount = BricksHelper.writeGlobalClassesToState(allGlobalClasses);
                    addMessage('assistant', '🎨 Registered ' + addedCount + ' new CSS classes as Bricks Global Classes');
                }

                // ── PHASE 1.5: Inject CSS element (fonts + :root) BEFORE any sections ──
                // This MUST happen before elements because global class CSS references
                // :root variables like var(--font-header) and var(--primary).
                // ALWAYS inject regardless of actionType — the old isFirst/replace-only
                // logic was broken and fonts/root vars were NEVER injected.
                if (cssInjectionElement) {
                    BricksHelper.writeElementsToState([cssInjectionElement], 'append');
                    debugLog('Injected CSS element (fonts + :root)');
                }

                // ── PHASE 2: Compile each section with the pre-computed classNameToId map ──
                const allCompiledData = [];
                for (let i = 0; i < sections.length; i++) {
                    if (!ChatState.isProcessing) { addMessage('assistant', '⏹ Build stopped.'); break; }
                    const { label, html } = sections[i];
                    setAgentState('compiling', 'Compiling "' + label + '" (' + (i + 1) + '/' + total + ')...');
                    let bricksData = null;
                    try {
                        bricksData = await compileSingleSection(html, label, i + 1, classNameToId);
                    } catch(compileErr) {
                        debugLog('Compilation failed for "' + label + '":', compileErr.message);
                        addMessage('assistant', '⚠️ "' + label + '" had issues. Auto-correcting...');
                        try {
                            const fixedHtml = await selfCorrectHTML(html, compileErr.message);
                            bricksData = await compileSingleSection(fixedHtml, label + ' [corrected]', i + 1, classNameToId);
                        } catch(retryErr) {
                            debugLog('Self-correction failed for "' + label + '":', retryErr.message);
                            if (retryErr.name !== 'AbortError') {
                                addMessage('error', '✗ "' + label + '" could not be auto-corrected: ' + retryErr.message);
                            }
                        }
                    }
                    if (bricksData && ChatState.isProcessing) {
                        const { data } = validateAndFixBricksJSON(bricksData);
                        allCompiledData.push({ label, data, index: i });
                        debugLog('✓ Section "' + label + '" compiled — ' + data.content.length + ' elements');
                    } else if (!bricksData && ChatState.isProcessing) {
                        addMessage('error', '✗ "' + label + '" — could not compile. Skipped.');
                    }
                }

                if (!ChatState.isProcessing) return;

                // ── PHASE 3: Inject elements into Bricks ──
                // Global classes (with full CSS in settings._cssCustom) are already registered
                // in PHASE 1. Elements reference them via _cssGlobalClasses. No per-element
                // CSS injection is needed — Bricks renders global class CSS in the canvas.

                const builtImageUrls = [];
                let builtCount = 0;
                for (const compiled of allCompiledData) {
                    if (!ChatState.isProcessing) { addMessage('assistant', '⏹ Build stopped.'); break; }
                    const { label, data, index } = compiled;
                    setAgentState('compiling', 'Building "' + label + '" (' + (builtCount + 1) + '/' + allCompiledData.length + ')...');

                    // ── Canvas rendering ──
                    // Each CSS class is already registered as a Bricks Global Class with its
                    // full CSS (including the .class-name selector) in settings._cssCustom.
                    // Bricks applies the class name to the element's HTML automatically, so
                    // the global class CSS renders correctly in the builder canvas.
                    //
                    // The previous approach wrapped each class's CSS in `%root% { .class { ... } }`,
                    // which produced INVALID nested CSS (a selector inside another selector block).
                    // That workaround is no longer needed — global classes handle canvas rendering.
                    (data.content || []).forEach(el => {
                        if (el.settings.image?.url) {
                            builtImageUrls.push(el.settings.image.url);
                        }
                    });

                    // CSS injection element (fonts + :root) is already injected in PHASE 1.5.
                    // No need to depend on isFirst/replace — it always works.
                    const isReplace = (index === 0 && actionType === 'replace');
                    const success = BricksHelper.writeElementsToState(
                        data.content,
                        isReplace ? 'replace' : 'append'
                    );
                    if (success) {
                        builtCount++;
                        addMessage('assistant', '✓ "' + label + '" built (' + builtCount + '/' + allCompiledData.length + ')');
                    } else {
                        addMessage('error', '✗ "' + label + '" inject failed');
                    }
                }

                ChatState.isProcessing = false;
                setAgentState('idle');
                updateSendButton();

                if (builtCount > 0) {
                    addMessage('assistant', '🎉 Done! ' + builtCount + '/' + allCompiledData.length + ' sections built in Bricks.');
                    removeApproveBar();
                    ChatState.previewMode = null;
                    if (builtImageUrls.length) saveImagesToWPLibrary(builtImageUrls);
                } else {
                    addMessage('error', 'No sections could be compiled. Try simplifying or rephrasing your request.');
                }
            }

            // Thin wrapper kept for backward compatibility (preview pane button, etc.)
            async function compileAndBuild(actionType) {
                await compileSectionBySection(actionType);
            }

            // ================================================================
            // Preview Pane
            // ================================================================

            function showHTMLPreview(html) {
                const iframe = document.getElementById('snn-bricks-preview-iframe');
                iframe.srcdoc = buildPreviewHTML(html);
                ChatState.previewPaneOpen = true;
                $('#snn-bricks-preview-pane').show();
                $('#snn-bricks-preview-toggle-btn').show().addClass('is-active');
            }

            function hideHTMLPreview() {
                ChatState.previewPaneOpen = false;
                $('#snn-bricks-preview-pane').hide();
                $('#snn-bricks-preview-toggle-btn').removeClass('is-active');
            }

            function togglePreviewPane() {
                if (ChatState.previewPaneOpen) {
                    hideHTMLPreview();
                } else if (ChatState.currentHTMLPreview) {
                    showHTMLPreview(ChatState.currentHTMLPreview);
                }
            }

            /**
             * Extract Bricks global CSS variables (colors + sizes) and return a <style> block
             * with a :root declaration for injection into the preview iframe.
             */
            function generateBricksRootCSS() {
                const state = BricksHelper.getState();
                if (!state) return '';
                let vars = '';

                // Colors: each entry may be a plain hex or a var(--name) reference
                if (state.colorPalette) {
                    try {
                        const palette = Array.from(state.colorPalette);
                        if (palette.length && palette[0].colors) {
                            Array.from(palette[0].colors).forEach(c => {
                                const colorVal = c.hex || c.light || c.dark || c.color || '';
                                if (c.raw && c.raw.includes('var(')) {
                                    const m = c.raw.match(/var\((--[^)]+)\)/);
                                    if (m && m[1] && colorVal) vars += `  ${m[1]}: ${colorVal};\n`;
                                }
                            });
                        }
                    } catch(e) { debugLog('generateBricksRootCSS colorPalette error:', e); }
                }

                // Sizes / spacing variables
                if (state.globalVariables) {
                    try {
                        Array.from(state.globalVariables).forEach(v => {
                            if (v.name && v.value) vars += `  --${v.name}: ${v.value};\n`;
                        });
                    } catch(e) { debugLog('generateBricksRootCSS globalVariables error:', e); }
                }

                if (!vars) return '';
                return `<style id="snn-bricks-preview-vars">:root {\n${vars}}<\/style>`;
            }

            function buildPreviewHTML(html) {
                return '<!DOCTYPE html><html lang="en"><head>' +
                    '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
                    generateBricksRootCSS() +
                    '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">' +
                    '<style>*{box-sizing:border-box;margin:0;padding:0}body{margin:0;padding:0;font-family:system-ui,-apple-system,sans-serif}<\/style>' +
                    '</head><body>' + html + '</body></html>';
            }

            function addApproveBar() {
                removeApproveBar();
                const sections = parseHTMLIntoSections(ChatState.currentHTMLPreview || '');
                const n        = sections.length;
                const sLabel   = n === 1 ? '1 section' : n + ' sections';
                const $bar = $('<div id="snn-approve-bar" class="snn-approve-bar">').html(
                    '<span class="snn-approve-label">Preview ready — <strong>' + sLabel + '</strong> detected</span>' +
                    '<button id="snn-approve-build-btn" class="snn-approve-build-btn">&#10003; Build ' + sLabel + '</button>'
                );
                $('#snn-bricks-chat-messages').after($bar);
                $('#snn-approve-build-btn').on('click', function() {
                    compileSectionBySection('append');
                });
            }

            function removeApproveBar() { $('#snn-approve-bar').remove(); }

            // ================================================================
            // System Prompts
            // ================================================================

            function buildDesigningPrompt(intent) {
                const basePrompt   = snnBricksChatConfig.ai.systemPrompt || '';
                const postTitle    = snnBricksChatConfig.pageContext?.details?.post_title || 'Unknown';
                const postType     = snnBricksChatConfig.pageContext?.details?.post_type  || 'page';
                const ajaxUrl      = snnBricksChatConfig.ajaxUrl;
                const cc           = BricksHelper.getCurrentContent();
                const tokens       = BricksHelper.getDesignTokens();
                const postTypes    = snnBricksChatConfig.pageContext?.postTypes || {};
                const postTypeKeys = Object.keys(postTypes).filter(k => !['post', 'page', 'attachment'].includes(k));

                // Build full page element snapshot (all elements, not just first 40)
                let pageSnap = '';
                if (cc && cc.elementCount > 0) {
                    const snap = (cc.elements || []).map(el => {
                        const raw = (el.settings && (el.settings.text || el.settings.content)) || '';
                        const txt = raw.replace(/<[^>]*>/g, '').trim().slice(0, 80);
                        return txt ? `  [${el.id}] ${el.name}: "${txt}"` : `  [${el.id}] ${el.name}`;
                    }).join('\n');
                    pageSnap = `\nPage currently has ${cc.elementCount} elements:\n${snap}\n`;
                }

                // Build design tokens context from Bricks global styles
                // ⚠️ These are for REFERENCE ONLY — the AI must NOT use them directly.
                // They are displayed so the AI knows what exists, but the theming
                // spec above provides the ACTUAL palette to use.
                let tokensSnap = '';

                // When a theming agent has already resolved the design spec, use it instead of
                // the raw token list — it's more precise and reduces prompt token count.
                let designSpec = tokensSnap;
                if (ChatState.currentTheme) {
                    const t = ChatState.currentTheme;
                    // Strip any var() references in palette values — they break CSS
                    const cleanVal = (v) => {
                        if (!v || typeof v !== 'string') return v;
                        // If the value is a var() reference (e.g. "var(--bricks-color-grey-900)"),
                        // flag it and keep it — the theming agent shouldn't output these.
                        // But if it does, the designer agent is warned below.
                        return v;
                    };
                    const paletteStr = Object.entries(t.palette).map(([k, v]) =>
                        `  ${k}: ${cleanVal(v)}` + (typeof v === 'string' && v.startsWith('var(') ? ' ⚠️ VAR REF — IGNORE, USE YOUR OWN HEX' : '')
                    ).join('\n');
                    designSpec = '\n=== DESIGN SPEC FROM THEMING AGENT (follow exactly — do not invent new colors or fonts) ===\n' +
                        'Palette:\n' + paletteStr + '\n' +
                        'Fonts:\n' +
                        '  heading: "' + t.fonts.heading + '", weight ' + t.fonts.headingWeight + '\n' +
                        '  body:    "' + t.fonts.body    + '", weight ' + t.fonts.bodyWeight    + '\n' +
                        'Spacing:\n' +
                        '  section padding: ' + cleanVal(t.spacing.sectionPadding) + '\n' +
                        '  container gap:   ' + cleanVal(t.spacing.containerGap)   + '\n' +
                        '  card padding:    ' + cleanVal(t.spacing.cardPadding)    + '\n' +
                        '  border radius:   ' + cleanVal(t.spacing.borderRadius)   + '\n' +
                        'Mood: ' + t.mood.join(', ') + ' | Style: ' + t.style + '\n' +
                        (t.usedExistingTokens ? 'User asked to use existing site tokens — use the hex VALUES (not var() names) where shown.\n' : '') +
                        '=== Use ONLY these values. Every section must feel visually consistent. ===\n';
                }

                return basePrompt + `

=== BRICKS BUILDER AI — DESIGN PHASE (CLASS-BASED) ===
Currently editing: "${postTitle}" (${postType})
${pageSnap}${designSpec}${postTypeKeys.length ? '\nREGISTERED POST TYPES for query loops — use the slug as data-loop value: ' + postTypeKeys.map(k => k + ' (' + postTypes[k].label + ')').join(', ') + '\n' : ''}

YOUR JOB:
Generate a complete, beautiful HTML design using standard CSS classes.${intent === 'refine_preview' ? ' REFINEMENT: incorporate the requested changes into a complete, fresh HTML output.' : intent === 'add_section' ? ' ADD SECTION: generate only the new section(s) requested.' : ' NEW DESIGN: generate the full page.'}

OUTPUT FORMAT:
1. One sentence describing the design approach and color palette
2. A \`\`\`html code block containing:
   - A <style> tag with ALL CSS class definitions AND Google Fonts @import
   - Section elements with data-bricks attributes and class references

CSS RULES:
- Define ALL styles as CSS classes in the <style> block at the top.
- Use class="..." on elements. NO inline style="" attributes.
- Write standard CSS — any property, pseudo-class (:hover), @keyframes, @media queries.
- 🔴 CRITICAL: NEVER use var(--bricks-*) or var(--secondary) or ANY Bricks internal variable in your CSS. These are site-specific tokens that may be undefined or circular. ALWAYS use concrete hex (#rrggbb), rgb(), hsl() values instead. Example: use "color: #f8fafc" NOT "color: var(--secondary)". Your :root custom properties (--primary, --accent, etc.) should also use concrete hex values.
- ⚠️ FORMAT: Write EACH CSS property on its OWN line with proper indentation:
  ✅ .hero { background: #0f172a; padding: 80px 0; }     ← WRONG (one line)
  ✅ .hero {
       background: #0f172a;
       padding: 80px 0;
     }                                                      ← CORRECT (one per line)
- Define CSS custom properties in :root for colors/spacing (ALL values must be concrete hex/rgb/px — NEVER var()):
  :root {
    --primary: #0f172a;
    --accent: #c5a059;
    --section-padding: 100px;
  }
- Google Fonts: @import in the <style> tag. Fonts will be auto-loaded.
- Class naming: one word per section, hyphenated children. Examples:
  .hero, .hero-container, .hero-heading, .hero-text, .hero-button
  .features, .features-grid, .features-card, .features-icon
  .testimonials, .testimonials-grid, .testimonials-card, .testimonials-quote
- Include responsive @media queries for mobile.
- Google Fonts: @import in the <style> tag.

HTML RULES:
- Use data-bricks attributes on every structural element:
  data-bricks="section"  — top-level section/header/footer
  data-bricks="container" — one per section, DIRECT child of section
  data-bricks="block"    — all inner layout divs
  data-bricks="heading"  — h1 through h6
  data-bricks="text-basic" — p, span, li text
  data-bricks="text"     — rich text with complex formatting
  data-bricks="button"   — buttons/CTAs (use href for link)
  data-bricks="text-link" — inline text links (<a> tags)
  data-bricks="image"    — img elements (use src for URL)
  data-bricks="icon"     — FontAwesome <i class="fas fa-icon"> (also fab, far)
  data-bricks="custom-html-css-script" — raw HTML/SVG/iframes
- Icons: <i class="fas fa-star"> or <i class="fab fa-twitter"> — style with CSS class
- Structure: section > container > content elements
- Real images via Pixabay proxy: ${ajaxUrl}?action=snn_pixabay_image&q=KEYWORDS

QUERY LOOPS (when listing posts):
- Three-layer structure:
  1. Grid wrapper block (display:grid, NO data-loop)
  2. Loop block (data-loop="post_type_slug", data-loop-posts-per-page="6")
  3. Template card (one card — Bricks repeats it)
- Dynamic tags inside template: {post_title}, {post_excerpt}, {post_date}, {post_link}, {cf_POSTTYPE_FIELDNAME}

DESIGN QUALITY:
- Real content, no Lorem Ipsum
- Professional color palettes, strong typography hierarchy
- Modern aesthetics: rounded corners, subtle shadows, generous whitespace
- Production-ready design — not a wireframe

OUTPUT THE HTML ONLY. No patch blocks, no JSON.`;
            }


            // ================================================================
            // Focused Prompts " Patching & Answering states
            // ================================================================

            function buildPatchingPrompt() {
                const cc = BricksHelper.getCurrentContent();
                let pageSnap = 'The page is empty — no elements to patch.';
                if (cc && cc.elementCount > 0) {
                    const snap = (cc.elements || []).map(el => {
                        const raw = (el.settings && (el.settings.text || el.settings.content)) || '';
                        const txt = raw.replace(/<[^>]*>/g, '').trim().slice(0, 80);
                        return txt ? `  [${el.id}] ${el.name}: "${txt}"` : `  [${el.id}] ${el.name}`;
                    }).join('\n');
                    pageSnap = `Page has ${cc.elementCount} elements:\n${snap}`;
                }
                return `You are a Bricks Builder element editor. Your ONLY job is to update existing page elements using patch blocks. Do NOT generate HTML. Do NOT create new designs.

${pageSnap}

When asked to change text, color, image, background, or any setting on the elements above, respond with a patch block.

\`\`\`patch
{
  "patches": [
    {"element_id": "EXISTING_ID", "updates": {"text": "New text content"}},
    {"find_by": {"type": "text_content", "value": "partial text to find"}, "updates": {"text": "replacement text"}},
    {"element_id": "IMG_ID", "updates": {"image_url": "https://new-image-url.jpg"}},
    {"element_id": "EL_ID", "updates": {"bricks_settings": {"_background": {"color": {"raw": "#1a1a2e"}}}}},
    {"element_id": "EL_ID", "updates": {"bricks_settings": {"_typography": {"color": {"raw": "#ffffff"}}, "_padding": {"top": "40", "bottom": "40", "left": "0", "right": "0"}}}}
  ]
}
\`\`\`

After the patch block, briefly confirm what was changed in one sentence. Do NOT produce HTML.`;
            }

            function buildAnsweringPrompt() {
                return `You are a knowledgeable Bricks Builder expert and web design consultant.
Answer the user's question concisely and helpfully.
Do NOT generate HTML. Do NOT output patch blocks. Do NOT produce designs unless explicitly asked.
Be direct and practical — 2–4 sentences unless a detailed explanation is genuinely needed.`;
            }

            // ================================================================
            // Abilities — WordPress Core Abilities API Integration
            // ================================================================

            async function loadAbilities() {
                if (!ENABLED_ABILITIES.length) {
                    debugLog('No abilities enabled in settings, skipping load.');
                    return;
                }
                try {
                    const response = await fetch(snnBricksChatConfig.restUrl + 'abilities', {
                        headers: { 'X-WP-Nonce': snnBricksChatConfig.nonce }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        const all = Array.isArray(data) ? data : [];
                        ChatState.abilities = all.filter(a => ENABLED_ABILITIES.includes(a.name));
                        debugLog('✓ Abilities loaded:', ChatState.abilities.length, ChatState.abilities.map(a => a.name));
                    } else {
                        debugLog('Failed to load abilities, status:', response.status);
                    }
                } catch(e) {
                    debugLog('loadAbilities error:', e);
                }
            }

            function buildAbilitiesSystemPrompt() {
                const basePrompt = snnBricksChatConfig.ai.systemPrompt || 'You are a helpful Bricks Builder assistant.';
                const postTitle  = snnBricksChatConfig.pageContext?.details?.post_title || 'Unknown';
                const postId     = snnBricksChatConfig.pageContext?.details?.post_id || '';

                if (!ChatState.abilities.length) {
                    return basePrompt + '\n\nNote: No WordPress abilities are currently available.';
                }

                const abilitiesList = ChatState.abilities.map(a =>
                    `- **${a.name}**: ${a.description || a.label || 'No description'} (Category: ${a.category || 'uncategorized'})`
                ).join('\n');

                const abilitiesDesc = ChatState.abilities.map(a => {
                    let params = '    (No parameters)';
                    if (a.input_schema) {
                        if (a.input_schema.properties) {
                            params = Object.entries(a.input_schema.properties).map(([key, val]) => {
                                const req = a.input_schema.required?.includes(key) ? ' (required)' : '';
                                const def = val.default !== undefined ? ` [default: ${JSON.stringify(val.default)}]` : '';
                                const enm = val.enum ? ` [options: ${val.enum.join(', ')}]` : '';
                                return `    - ${key} (${val.type}${req}): ${val.description || ''}${def}${enm}`;
                            }).join('\n');
                        } else if (a.input_schema.type) {
                            params = `    Type: ${a.input_schema.type}${a.input_schema.description ? ' - ' + a.input_schema.description : ''}`;
                        }
                    }
                    return `**${a.name}** - ${a.description || a.label || 'No description'}\n  Category: ${a.category || 'uncategorized'}\n  Parameters:\n${params}`;
                }).join('\n\n');

                return `${basePrompt}

You are a Bricks Builder AI assistant with access to WordPress Core Abilities.
Currently editing: "${postTitle}"${postId ? ` (Post ID: ${postId})` : ''}

=== AVAILABLE WORDPRESS ABILITIES (${ChatState.abilities.length} total) ===

${abilitiesList}

=== DETAILED ABILITIES ===

${abilitiesDesc}

=== HOW TO USE ABILITIES ===

When the user asks to perform a WordPress action:

1. Brief single-line acknowledgment (e.g., "I'll get the site info for you.")
2. Include a JSON code block:
\`\`\`json
{
  "abilities": [
    {"name": "exact-ability-name", "input": {}}
  ]
}
\`\`\`

IMPORTANT RULES:
- Use the EXACT ability names as listed above — copy them character by character
- The namespace prefix (snn/, core/) is part of the name — never change it
- Match parameter types exactly (string, integer, boolean, array)
- Use sensible defaults for optional parameters rather than asking
- If user asks "what can you do" → list abilities in text, do NOT execute any
- ONLY use abilities that are listed above — NEVER make up or modify ability names
- For create-post or update-post: the "content" field must have at least 1 character`;
            }

            function extractAbilitiesFromResponse(response) {
                const m = response.match(/```json\n?([\s\S]*?)\n?```/);
                if (!m) return [];
                try {
                    const parsed = JSON.parse(m[1]);
                    if (parsed.abilities && Array.isArray(parsed.abilities)) return parsed.abilities;
                } catch(e) { debugLog('extractAbilitiesFromResponse parse error:', e); }
                return [];
            }

            async function executeAbility(abilityName, input) {
                try {
                    let actualName = abilityName;
                    let abilityInfo = ChatState.abilities.find(a => a.name === abilityName);
                    // Fuzzy match: if AI used wrong namespace prefix
                    if (!abilityInfo) {
                        const suffix = abilityName.split('/').pop();
                        abilityInfo = ChatState.abilities.find(a => a.name.endsWith('/' + suffix));
                        if (abilityInfo) {
                            debugLog('Corrected ability name:', abilityName, '->', abilityInfo.name);
                            actualName = abilityInfo.name;
                        }
                    }

                    const encodedName = actualName.split('/').map(p => encodeURIComponent(p)).join('/');
                    const isReadOnly  = abilityInfo?.meta?.readonly === true;
                    const apiUrl      = snnBricksChatConfig.restUrl + 'abilities/' + encodedName + '/run';

                    const makeReq = async (method) => {
                        const opts = { headers: { 'X-WP-Nonce': snnBricksChatConfig.nonce } };
                        let url = apiUrl;
                        if (method === 'GET') {
                            opts.method = 'GET';
                            if (input && Object.keys(input).length > 0) {
                                url += '?' + new URLSearchParams({ input: JSON.stringify(input) }).toString();
                            }
                        } else {
                            opts.method = 'POST';
                            opts.headers['Content-Type'] = 'application/json';
                            opts.body = JSON.stringify({ input });
                        }
                        debugLog('Ability API call:', method, url, input);
                        return fetch(url, opts);
                    };

                    let resp = await makeReq(isReadOnly ? 'GET' : 'POST');
                    if (resp.status === 405) resp = await makeReq(isReadOnly ? 'POST' : 'GET');

                    if (!resp.ok) {
                        const errText = await resp.text();
                        let err;
                        try { err = JSON.parse(errText); } catch(e) { err = { message: errText }; }
                        return { success: false, error: err.message || `HTTP ${resp.status}` };
                    }

                    const result = await resp.json();
                    if (typeof result.success !== 'undefined') return result;
                    if (result.data !== undefined) return { success: true, data: result.data };
                    if (result.error || result.message) return { success: false, error: result.error || result.message };
                    return { success: true, data: result };
                } catch(e) {
                    return { success: false, error: e.message };
                }
            }

            function formatSingleAbilityResult(r) {
                const ok = r.result.success === true || (r.result.success !== false && !r.result.error);
                let html = `<div class="ability-results"><div class="ability-result ${ok ? 'success' : 'error'}">`;
                html += `<strong>${ok ? '✅' : '❌'} ${r.ability}</strong>`;
                if (ok) {
                    html += r.result.data
                        ? `<div class="result-data">${formatDataPreview(r.result.data)}</div>`
                        : '<div class="result-data">Completed successfully</div>';
                } else {
                    html += `<div class="result-error">${r.result.error || r.result.message || 'Unknown error'}</div>`;
                }
                html += '</div></div>';
                return html;
            }

            function formatDataPreview(data) {
                if (Array.isArray(data)) {
                    if (!data.length) return '<span class="result-meta">Empty result</span>';
                    const count = `<span class="result-meta">Found ${data.length} item${data.length !== 1 ? 's' : ''}</span>`;
                    const jsonHtml = formatJsonHighlight(data);
                    return `${count}<details class="result-details"><summary>Show data</summary><div class="json-result-container"><pre class="json-result">${jsonHtml}</pre></div></details>`;
                }
                if (typeof data === 'object' && data !== null) {
                    const id = data.ID || data.id;
                    if (id) {
                        const title  = data.post_title || data.title || '';
                        const status = data.post_status || data.status || '';
                        const summary = `<strong>ID:</strong> ${id}${title ? ' — ' + title : ''}${status ? ' <span class="result-meta">[' + status + ']</span>' : ''}`;
                        const jsonHtml = formatJsonHighlight(data);
                        return `<div class="result-inline">${summary}</div><details class="result-details"><summary>Show raw data</summary><div class="json-result-container"><pre class="json-result">${jsonHtml}</pre></div></details>`;
                    }
                    const summary = buildObjectSummary(data);
                    const jsonHtml = formatJsonHighlight(data);
                    return `${summary}<details class="result-details"><summary>Show raw data</summary><div class="json-result-container"><pre class="json-result">${jsonHtml}</pre></div></details>`;
                }
                return String(data).substring(0, 200);
            }

            function buildObjectSummary(data) {
                // Post types table (e.g. from snn/get-site-info)
                if (data.posttypes && typeof data.posttypes === 'object') {
                    const rows = Object.entries(data.posttypes).map(([slug, info]) => {
                        const pub = info.published !== undefined ? info.published : '-';
                        const drft = info.draft !== undefined ? info.draft : '-';
                        return `<tr><td>${escapeHtml(info.label || slug)}</td><td>${pub}</td><td>${drft}</td></tr>`;
                    }).join('');
                    return `<table class="result-table"><thead><tr><th>Post Type</th><th>Published</th><th>Draft</th></tr></thead><tbody>${rows}</tbody></table>`;
                }
                // WordPress site overview (top-level content block)
                if (data.wordpress && data.content) {
                    const wp = data.wordpress;
                    const c  = data.content;
                    const lines = [];
                    if (wp.sitename) lines.push(`<strong>Site:</strong> ${escapeHtml(wp.sitename)} <span class="result-meta">(WP ${escapeHtml(wp.version || '')})</span>`);
                    if (c.posts)    lines.push(`<strong>Posts:</strong> ${c.posts.published || 0} published, ${c.posts.draft || 0} draft`);
                    if (c.pages)    lines.push(`<strong>Pages:</strong> ${c.pages.published || 0} published, ${c.pages.draft || 0} draft`);
                    if (c.media)    lines.push(`<strong>Media:</strong> ${c.media.total || 0} items`);
                    if (c.comments) lines.push(`<strong>Comments:</strong> ${c.comments.approved || 0} approved`);
                    if (c.users)    lines.push(`<strong>Users:</strong> ${c.users.total || 0}`);
                    let ptTable = '';
                    if (c.posttypes) {
                        const rows = Object.entries(c.posttypes).map(([slug, info]) => {
                            return `<tr><td>${escapeHtml(info.label || slug)}</td><td>${info.published || 0}</td><td>${info.draft || 0}</td></tr>`;
                        }).join('');
                        ptTable = `<table class="result-table"><thead><tr><th>Post Type</th><th>Published</th><th>Draft</th></tr></thead><tbody>${rows}</tbody></table>`;
                    }
                    return `<div class="result-summary-block">${lines.map(l => `<div class="result-summary-row">${l}</div>`).join('')}</div>${ptTable}`;
                }
                // Generic flat object — show scalar top-level values
                const keys = Object.keys(data);
                const scalarLines = [];
                for (const k of keys) {
                    const v = data[k];
                    if (typeof v !== 'object' && scalarLines.length < 6) {
                        scalarLines.push(`<strong>${escapeHtml(k)}:</strong> ${escapeHtml(String(v)).substring(0, 80)}`);
                    }
                }
                if (scalarLines.length) {
                    return `<div class="result-summary-block">${scalarLines.map(l => `<div class="result-summary-row">${l}</div>`).join('')}</div>`;
                }
                return `<span class="result-meta">Object (${keys.length} fields)</span>`;
            }

            function escapeHtml(str) {
                return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }

            function formatJsonHighlight(data) {
                try {
                    return JSON.stringify(data, null, 2)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"([^"]+)":/g, '<span class="json-key">"$1"</span>:')
                        .replace(/: "([^"]*)"/g, ': <span class="json-string">"$1"</span>')
                        .replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>')
                        .replace(/: (null)/g, ': <span class="json-null">$1</span>')
                        .replace(/: (\d+)/g, ': <span class="json-number">$1</span>');
                } catch(e) { return String(data); }
            }

            // ── Abilities flow ────────────────────────────────────────────────
            async function runAbilitiesFlow(userMessage, images) {
                const context        = buildConversationContext();
                const userMsgContent = buildUserContent(userMessage, images);

                const response = await callAI([
                    { role: 'system', content: buildAbilitiesSystemPrompt() },
                    ...context,
                    { role: 'user', content: userMsgContent }
                ]);
                hideTyping();
                if (!response || !response.trim()) throw new Error('AI returned empty response.');

                const abilities = extractAbilitiesFromResponse(response);

                if (abilities.length > 0) {
                    // Show the AI's intro text (strip JSON block)
                    const intro = response.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                    if (intro) addMessage('assistant', intro);

                    // Execute each ability sequentially — collect results for accurate interpretation
                    const abilityResults = [];
                    for (let i = 0; i < abilities.length; i++) {
                        const ability = abilities[i];
                        setAgentState('abilities', `Running ${ability.name} (${i + 1}/${abilities.length})...`);
                        showTyping();

                        const result = await executeAbility(ability.name, ability.input || {});
                        abilityResults.push({ name: ability.name, result });
                        hideTyping();
                        addMessage('assistant', formatSingleAbilityResult({ ability: ability.name, result }));

                        await sleep(300);
                    }

                    // Ask AI to interpret the results — pass ACTUAL data so it answers accurately
                    showTyping();
                    setAgentState('answering');
                    const resultsSummary = abilityResults.map(r => {
                        const statusLabel = r.result.success !== false ? 'success' : 'error';
                        let dataStr = '';
                        if (r.result.data !== undefined) {
                            const jsonStr = JSON.stringify(r.result.data);
                            dataStr = jsonStr.length > 4000 ? jsonStr.substring(0, 4000) + '...(truncated)' : jsonStr;
                        } else if (r.result.error) {
                            dataStr = 'Error: ' + r.result.error;
                        }
                        return `- ${r.name} [${statusLabel}]:\n${dataStr}`;
                    }).join('\n\n');
                    try {
                        const interpretation = await callAI([
                            { role: 'system', content: buildAbilitiesSystemPrompt() },
                            ...context,
                            { role: 'user', content: userMessage },
                            { role: 'assistant', content: intro || 'Abilities executed.' },
                            { role: 'user', content: `All ${abilityResults.length} WordPress abilities have been executed with the following results:\n\n${resultsSummary}\n\nUsing the ACTUAL DATA above, provide a clear and accurate answer to the user's original question. Be specific with real numbers and names from the data. Do NOT invent or guess any values.` }
                        ], 0, { maxTokens: 500 });
                        hideTyping();
                        if (interpretation) {
                            const clean = interpretation.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                            if (clean) addMessage('assistant', clean);
                        }
                    } catch(e) {
                        hideTyping();
                        debugLog('Abilities interpretation error:', e);
                    }

                } else {
                    // AI gave a prose answer (e.g., listing capabilities) — just show it
                    addMessage('assistant', response);
                }
            }

            <?php include __DIR__ . '/html-to-bricks-translation.php'; ?>

            function extractHTMLFromResponse(resp) {
                let html = null;

                const m = resp.match(/```(?:html)?\n?([\s\S]*?)\n?```/i);
                if (m) html = m[1].trim();

                if (!html) {
                    const fallbackMatch = resp.match(/(?:<style[^>]*>[\s\S]*?<\/style>\s*)?(?:<section[\s\S]*|<footer[\s\S]*|<header[\s\S]*|<\/div>|<div[\s\S]*data-bricks[\s\S]*)/i);
                    if (fallbackMatch && (resp.includes('data-bricks') || resp.includes('<section') || resp.includes('<footer') || resp.includes('<header') || resp.includes('style='))) {
                        const firstTagIndex = resp.search(/<(style|section|footer|header|div|main|nav|article)/i);
                        if (firstTagIndex !== -1) {
                            html = resp.substring(firstTagIndex).trim();
                        }
                    }
                }

                if (html) {
                    // Strip <html>, <head>, <body> wrappers if the AI included them
                    html = html
                        .replace(/^<!DOCTYPE[^>]*>/i, '')
                        .replace(/^<html[^>]*>/i, '').replace(/<\/html>$/i, '')
                        .replace(/^<head>[\s\S]*?<\/head>/i, '')
                        .replace(/^<body[^>]*>/i, '').replace(/<\/body>\s*$/i, '')
                        .trim();
                }

                return html || null;
            }

            function extractBricksJSONFromResponse(resp) {
                const cleaned = resp.trim();
                try { const p = JSON.parse(cleaned); if (p.content && Array.isArray(p.content)) return p; } catch(e) { debugLog('JSON parse (direct) error:', e); }
                const m = cleaned.match(/\{[\s\S]*"content"\s*:\s*\[[\s\S]*\][\s\S]*\}/);
                if (m) { try { const p = JSON.parse(m[0]); if (p.content && Array.isArray(p.content)) return p; } catch(e) { debugLog('JSON parse (extracted) error:', e); } }
                if (DEBUG_MODE) console.warn('[Bricks AI] extractBricksJSONFromResponse: could not parse', cleaned.slice(0, 300));
                return null;
            }

            function buildConversationContext() {
                return ChatState.messages.slice(-MAX_HISTORY).map(m => {
                    const msg = { role: m.role === 'user' ? 'user' : 'assistant' };
                    if (m.images && m.images.length > 0) {
                        msg.content = [];
                        if (m.content && m.content !== '(Image attached)') msg.content.push({ type: 'text', text: m.content });
                        m.images.forEach(img => msg.content.push({ type: 'image_url', image_url: { url: img.data } }));
                    } else { msg.content = m.content; }
                    return msg;
                });
            }

            function buildUserContent(msg, imgs = []) {
                if (!imgs.length) return msg;
                const c = [];
                if (msg) c.push({ type: 'text', text: msg });
                imgs.forEach(img => c.push({ type: 'image_url', image_url: { url: img.data } }));
                return c;
            }

            // ================================================================
            // Toolbar Button
            // ================================================================

            function addToolbarButton() {
                const selectors = ['.bricks-toolbar ul.end','ul.group-wrapper.end','.group-wrapper.end'];
                for (const sel of selectors) {
                    const tb = document.querySelector(sel);
                    if (tb) { createToolbarButton(tb); return; }
                }
                const obs = new MutationObserver(function(_, o) {
                    for (const sel of selectors) {
                        const tb = document.querySelector(sel);
                        if (tb) { createToolbarButton(tb); o.disconnect(); return; }
                    }
                });
                obs.observe(document.body, { childList: true, subtree: true });
                setTimeout(() => obs.disconnect(), 15000);
            }

            function createToolbarButton(toolbar) {
                if (document.querySelector('.snn-bricks-ai-toggle')) return;
                const li = document.createElement('li');
                li.className = 'snn-bricks-ai-toggle';
                li.setAttribute('data-balloon', 'SNN Agent');
                li.setAttribute('data-balloon-pos', 'bottom');
                li.innerHTML = '<span style="font-size:22px;background:linear-gradient(45deg,#2271b1,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:inline-block;cursor:pointer;line-height:1.2">✦</span>';
                li.addEventListener('click', e => { e.preventDefault(); toggleChat(); });
                toolbar.lastElementChild ? toolbar.insertBefore(li, toolbar.lastElementChild) : toolbar.appendChild(li);
            }

            // ================================================================
            // Chat Interface
            // ================================================================

            function initChat() {
                $('.snn-bricks-chat-close').on('click', e => { e.preventDefault(); toggleChat(); });
                $('#snn-bricks-chat-new-btn').on('click', clearChat);
                $('#snn-bricks-chat-history-btn').on('click', toggleHistoryDropdown);
                $('#snn-bricks-history-close').on('click', () => $('#snn-bricks-chat-history-dropdown').hide());
                $('#snn-bricks-chat-send').on('click', sendMessage);
                $('#snn-bricks-preview-toggle-btn').on('click', togglePreviewPane);
                // Preview pane header buttons
                $('#snn-preview-approve-btn').on('click', function() { compileAndBuild('append'); });
                $('#snn-preview-close-btn').on('click', hideHTMLPreview);
                // Input
                $('#snn-bricks-chat-input').on('keydown', function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } });
                $('#snn-bricks-chat-input').on('input', function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 120) + 'px'; });
                // Quick actions
                $('.snn-bricks-quick-action-btn').on('click', function() { $('#snn-bricks-chat-input').val($(this).data('message')); sendMessage(); });
                // Images
                $('#snn-bricks-chat-attach-btn').on('click', () => $('#snn-bricks-chat-file-input').click());
                $('#snn-bricks-chat-file-input').on('change', e => handleFileSelect(e.target.files));
                $('#snn-bricks-chat-input').on('paste', e => handlePaste(e.originalEvent));
                // Message collapse toggle
                $('#snn-bricks-chat-messages').on('click', '.snn-msg-toggle', function() {
                    const $msg = $(this).closest('.snn-bricks-chat-message');
                    $msg.toggleClass('is-collapsed');
                    $(this).text($msg.hasClass('is-collapsed') ? 'Show More ▾' : 'Show Less ▴');
                });
                setInterval(autoSaveConversation, 30000);
            }

            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-bricks-chat-overlay').toggle();
                if (ChatState.isOpen) $('#snn-bricks-chat-input').focus();
            }

            async function sendMessage() {
                if (ChatState.isProcessing) { stopAgent(); return; }
                const input = $('#snn-bricks-chat-input');
                const msg   = input.val().trim();
                const imgs  = ChatState.attachedImages;
                if (!msg && !imgs.length) return;
                addMessage('user', msg || '(Image attached)', [...imgs]);
                const saved = [...imgs];
                input.val('').css('height', 'auto');
                ChatState.attachedImages = [];
                renderImagePreviews();
                await processWithAI(msg, saved);
            }

            // ================================================================
            // AI API
            // ================================================================

            async function callAI(messages, retryCount = 0, opts = {}) {
                const cfg = snnBricksChatConfig.ai;
                if (!window.snnAiProxy || !window.snnAiProxy.url) throw new Error('AI API not configured');
                ChatState.abortController = new AbortController();

                debugLog('AI call:', cfg.model, messages.length, 'messages');

                // Route through the server-side proxy — keeps API key out of the browser
                // and supports localhost models (Ollama, LM Studio) regardless of HTTPS context.
                const proxyPayload = new URLSearchParams({
                    action: 'snn_ai_proxy',
                    nonce: window.snnAiProxy.nonce,
                    request_type: 'text',
                    payload: JSON.stringify({
                        messages: messages,
                        temperature: 0.7,
                        max_tokens: opts.maxTokens || cfg.maxTokens || 4000
                    })
                });

                const resp = await fetch(window.snnAiProxy.url, {
                    method: 'POST',
                    body: proxyPayload,
                    signal: ChatState.abortController.signal
                });
                if (resp.status === 429 && retryCount < RECOVERY_CONFIG.maxRecoveryAttempts) {
                    const delay = Math.min(RECOVERY_CONFIG.rateLimitDelay * Math.pow(2, retryCount), RECOVERY_CONFIG.maxDelay);
                    setAgentState('recovering', `Rate limited — waiting ${Math.ceil(delay/1000)}s...`);
                    await sleep(delay);
                    return callAI(messages, retryCount + 1, opts);
                }
                if (!resp.ok) { const t = await resp.text(); throw new Error(`API error ${resp.status}: ${t.substring(0, 200)}`); }
                const data = await resp.json();
                if (!data?.choices?.[0]?.message?.content) throw new Error('Invalid API response');
                return data.choices[0].message.content;
            }

            // ================================================================
            // Messages
            // ================================================================

            function addMessage(role, content, images = null) {
                const m = { role, content, timestamp: Date.now() };
                if (Array.isArray(images) && images.length && images[0].data) m.images = images;
                ChatState.messages.push(m);
                const $msgs = $('#snn-bricks-chat-messages');
                $msgs.find('.snn-bricks-chat-welcome').remove();
                $('.snn-bricks-chat-quick-actions').hide();
                const $msg  = $('<div>').addClass('snn-bricks-chat-message snn-bricks-chat-message-' + role);
                const $body = $('<div>').addClass('snn-msg-body');
                if (m.images) {
                    const $imgs = $('<div>').addClass('snn-message-images');
                    m.images.forEach(img => $imgs.append($('<img>').attr('src', img.data)));
                    $body.append($imgs);
                }
                $body.append($('<div>').html(formatMessage(content)));
                $msg.append($body);
                $msgs.append($msg);
                // Auto-collapse if rendered height exceeds 70px
                setTimeout(function() {
                    if ($body[0].scrollHeight > 70) {
                        $msg.addClass('is-collapsed');
                        $msg.append($('<button class="snn-msg-toggle">Show More ▾</button>'));
                    }
                }, 0);
                scrollToBottom();
            }

            function formatMessage(c) {
                // Pre-formatted HTML (ability results) — skip the markdown renderer so
                // tables, spans, details etc. are preserved exactly as generated.
                if (typeof c === 'string' && c.trimStart().startsWith('<div class="ability-results">')) {
                    return c;
                }
                if (typeof markdown !== 'undefined' && markdown.toHTML) { try { return markdown.toHTML(c); } catch(e) {} }
                return c.replace(/\n/g, '<br>');
            }

            function scrollToBottom() { const $m = $('#snn-bricks-chat-messages'); $m.scrollTop($m[0].scrollHeight); }

            function clearChat() {
                ChatState.messages = []; ChatState.currentSessionId = null;
                ChatState.attachedImages = []; ChatState.currentHTMLPreview = null; ChatState.previewMode = null;
                ChatState.currentTheme = null; // Reset theme for new conversation
                ChatState.globalUsedIds.clear(); // Reset ID tracker
                removeApproveBar(); hideHTMLPreview(); renderImagePreviews();
                $('#snn-bricks-chat-messages').html('<div class="snn-bricks-chat-welcome"><h3>Conversation cleared</h3><p>Start a new conversation.</p></div>');
                $('.snn-bricks-chat-quick-actions').show();
            }

            function setAgentState(state, detail = '') {
                const $t = $('#snn-bricks-chat-state-text');
                const labels = {
                    analyzing:   'Understanding your request...',
                    planning:    'Planning your layout...',
                    theming:     'Choosing design language...',
                    designing:   'Designing your page...',
                    reviewing:   'Reviewing HTML structure...',
                    patching:    'Updating element...',
                    answering:   'Thinking...',
                    thinking:    'Thinking...',
                    abilities:   detail || 'Running WordPress abilities...',
                    compiling:   detail || 'Compiling to Bricks...',
                    recovering:  detail || 'Recovering...',
                    saving:      detail || 'Saving images to media library...',
                    error:       'Error',
                    idle:        ''
                };
                const lbl = labels[state] || detail || '';
                lbl ? $t.text(lbl).show() : $t.hide();
            }

            function updateSendButton() {
                const $btn = $('#snn-bricks-chat-send');
                if (ChatState.isProcessing) {
                    $btn.html('<span style="font-size:18px;line-height:1">⏹</span>').addClass('snn-chat-stop').attr('title', 'Stop agent');
                } else {
                    $btn.html('<span class="dashicons dashicons-arrow-up-alt2"></span>').removeClass('snn-chat-stop').attr('title', 'Send message');
                }
            }

            function stopAgent() {
                if (ChatState.abortController) {
                    try { ChatState.abortController.abort(); } catch(e) {}
                }
                ChatState.isProcessing = false;
                setAgentState('idle');
                hideTyping();
                updateSendButton();
                addMessage('assistant', '⏹ Agent stopped.');
                debugLog('Agent stopped by user.');
            }

            async function selfCorrectHTML(originalHtml, errorMsg) {
                setAgentState('thinking', 'Auto-correcting section...');
                const response = await callAI([
                    { role: 'system', content: 'You are a code repair assistant for Bricks Builder. Fix the HTML so it compiles correctly. Ensure all data-bricks attributes are correct (section>container>block>content nesting), and all inline styles use valid CSS. Return ONLY the corrected HTML in a ```html code block, nothing else.' },
                    { role: 'user',   content: 'This HTML failed to compile with error: "' + errorMsg + '"\nFix it:\n```html\n' + originalHtml + '\n```' }
                ]);
                const fixed = extractHTMLFromResponse(response);
                if (!fixed) throw new Error('Auto-correction returned no valid HTML block');
                return fixed;
            }

            function extractPatchFromResponse(resp) {
                const m = resp.match(/```patch\n?([\s\S]*?)\n?```/);
                if (!m) return null;
                try {
                    const parsed = JSON.parse(m[1].trim());
                    if (parsed && (Array.isArray(parsed.patches) || parsed.element_id || parsed.find_by)) return parsed;
                } catch(e) {
                    debugLog('extractPatchFromResponse parse error:', e);
                    if (DEBUG_MODE) console.warn('[Bricks AI] Patch block parse error:', e.message, m[1].slice(0, 200));
                }
                return null;
            }

            async function saveImagesToWPLibrary(imageUrls) {
                if (!imageUrls || !imageUrls.length) return;
                const hostname = window.location.hostname;

                // Log all collected URLs before filtering so we can see what was found
                console.log('[Bricks AI] 📸 All image URLs collected from compiled sections (' + imageUrls.length + '):', imageUrls);

                // Include:
                //   1. Pixabay proxy URLs (same-host admin-ajax URLs that redirect to Pixabay CDN)
                //   2. Truly external URLs (different hostname)
                // Skip: already-local WP media URLs on the same host that are NOT proxy URLs
                const external = [...new Set(imageUrls.filter(url => {
                    if (!url || !url.startsWith('http')) {
                        console.log('[Bricks AI] 📸 Skip (not http):', url);
                        return false;
                    }
                    // Pixabay proxy lives on same host but must be saved (it redirects to actual CDN image)
                    if (url.includes('action=snn_pixabay_image')) {
                        console.log('[Bricks AI] 📸 Include (Pixabay proxy):', url);
                        return true;
                    }
                    if (!url.includes(hostname)) {
                        console.log('[Bricks AI] 📸 Include (external):', url);
                        return true;
                    }
                    console.log('[Bricks AI] 📸 Skip (already local):', url);
                    return false;
                }))];

                console.log('[Bricks AI] 📸 Images to save:', external.length, external);

                if (!external.length) {
                    console.log('[Bricks AI] 📸 No images to save (all local or none found).');
                    return;
                }

                addMessage('assistant', '📸 Saving ' + external.length + ' image(s) to WordPress media library...');
                setAgentState('saving', 'Saving ' + external.length + ' image(s)...');
                let saved = 0, failed = 0;
                for (const url of external) {
                    console.log('[Bricks AI] 📸 Saving image →', url);
                    try {
                        const result = await $.ajax({
                            url: snnBricksChatConfig.ajaxUrl, type: 'POST',
                            data: { action: 'snn_save_image_to_library', nonce: snnBricksChatConfig.agentNonce, url }
                        });
                        if (result.success) {
                            saved++;
                            console.log('[Bricks AI] 📸 ✓ Saved:', url, '→', result.data.url, '(ID:', result.data.attachment_id + ')');
                            updateImageUrlInBricks(url, result.data.url);
                            debugLog('Image saved to media library:', result.data.url);
                        } else {
                            failed++;
                            console.warn('[Bricks AI] 📸 ✗ Failed:', url, '—', result.data?.message);
                            debugLog('Image save failed:', url, result.data?.message);
                        }
                    } catch(e) {
                        failed++;
                        console.error('[Bricks AI] 📸 ✗ Error saving:', url, e);
                        debugLog('Image save error:', url, e);
                    }
                }
                setAgentState('idle');
                console.log('[Bricks AI] 📸 Save complete. Saved:', saved, '/ Failed:', failed, '/ Total:', external.length);
                addMessage('assistant', saved > 0
                    ? '📸 ' + saved + '/' + external.length + ' image(s) saved to media library.' + (failed > 0 ? ' (' + failed + ' failed — external URLs kept)' : '')
                    : '⚠️ Images could not be saved to media library (' + failed + ' failed). They still appear in Bricks using external URLs.');
            }

            function updateImageUrlInBricks(oldUrl, newUrl) {
                const s = BricksHelper.getState();
                if (!s || !s.content) return;
                let updated = false;
                s.content.forEach(el => {
                    if (el.settings.image?.url === oldUrl)              { el.settings.image.url = newUrl; updated = true; }
                    if (el.settings._background?.image?.url === oldUrl) { el.settings._background.image.url = newUrl; updated = true; }
                });
                if (updated) s.content = [...s.content]; // Trigger Vue reactivity
            }

            function showTyping() { $('.snn-bricks-chat-typing').show(); scrollToBottom(); }
            function hideTyping() { $('.snn-bricks-chat-typing').hide(); }
            function sleep(ms)    { return new Promise(r => setTimeout(r, ms)); }

            // ================================================================
            // Image Attachments
            // ================================================================

            async function handleFileSelect(files) {
                if (!files || !files.length) return;
                for (const f of Array.from(files)) {
                    if (!f.type.startsWith('image/')) continue;
                    try { addImageAttachment(await fileToBase64(f), f.name); } catch(e) {}
                }
                $('#snn-bricks-chat-file-input').val('');
            }

            async function handlePaste(ev) {
                const items = ev.clipboardData?.items;
                if (!items) return;
                for (const item of Array.from(items)) {
                    if (item.type.startsWith('image/')) {
                        ev.preventDefault();
                        const f = item.getAsFile();
                        if (f) addImageAttachment(await fileToBase64(f), 'pasted.png');
                    }
                }
            }

            function fileToBase64(f) {
                return new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(r.result); r.onerror = rej; r.readAsDataURL(f); });
            }

            function addImageAttachment(data, name) {
                ChatState.attachedImages.push({ id: 'img_' + Date.now() + '_' + Math.random().toString(36).slice(2,9), data, fileName: name });
                renderImagePreviews();
            }

            function removeImageAttachment(id) {
                ChatState.attachedImages = ChatState.attachedImages.filter(i => i.id !== id);
                renderImagePreviews();
            }

            function renderImagePreviews() {
                const $p = $('#snn-bricks-chat-image-preview');
                $p.empty();
                if (!ChatState.attachedImages.length) { $p.hide(); return; }
                $p.show();
                ChatState.attachedImages.forEach(img => {
                    const $w = $('<div>').addClass('snn-image-preview-item');
                    const $r = $('<button>').addClass('snn-image-preview-remove').html('&times;').on('click', () => removeImageAttachment(img.id));
                    $w.append($('<img>').attr('src', img.data)).append($r);
                    $p.append($w);
                });
            }

            // ================================================================
            // Chat History
            // ================================================================

            function autoSaveConversation() {
                if (!ChatState.messages.length) return;
                $.ajax({ url: snnBricksChatConfig.ajaxUrl, type: 'POST',
                    data: { action: 'snn_save_chat_history', nonce: snnBricksChatConfig.agentNonce, messages: JSON.stringify(ChatState.messages), session_id: ChatState.currentSessionId },
                    success: r => { if (r.success) ChatState.currentSessionId = r.data.session_id; }
                });
            }

            function toggleHistoryDropdown() {
                const $d = $('#snn-bricks-chat-history-dropdown');
                if ($d.is(':visible')) { $d.hide(); return; }
                loadChatHistories(); $d.show();
            }

            function loadChatHistories() {
                const $l = $('#snn-bricks-history-list');
                $l.html('<div class="snn-bricks-history-loading">Loading...</div>');
                $.ajax({ url: snnBricksChatConfig.ajaxUrl, type: 'POST',
                    data: { action: 'snn_get_chat_histories', nonce: snnBricksChatConfig.agentNonce },
                    success: r => { if (r.success) renderHistoryList(r.data.histories); }
                });
            }

            function renderHistoryList(histories) {
                const $l = $('#snn-bricks-history-list');
                if (!histories || !histories.length) { $l.html('<div class="snn-bricks-history-empty">No history</div>'); return; }
                $l.html(histories.map(h =>
                    `<div class="snn-bricks-history-item" data-session-id="${h.session_id}"><div class="snn-bricks-history-title">${h.title}</div><div class="snn-bricks-history-meta">${h.message_count} messages</div></div>`
                ).join(''));
                $('.snn-bricks-history-item').on('click', function() { loadChatSession($(this).data('session-id')); $('#snn-bricks-chat-history-dropdown').hide(); });
            }

            function loadChatSession(sessionId) {
                $.ajax({ url: snnBricksChatConfig.ajaxUrl, type: 'POST',
                    data: { action: 'snn_load_chat_history', nonce: snnBricksChatConfig.agentNonce, session_id: sessionId },
                    success: function(r) {
                        if (r.success && r.data.messages) {
                            $('#snn-bricks-chat-messages').empty(); $('.snn-bricks-chat-quick-actions').hide();
                            ChatState.messages = r.data.messages; ChatState.currentSessionId = sessionId;
                            r.data.messages.forEach(function(msg) {
                                const $msg  = $('<div>').addClass('snn-bricks-chat-message snn-bricks-chat-message-' + msg.role);
                                const $body = $('<div>').addClass('snn-msg-body').html(formatMessage(msg.content));
                                $msg.append($body); $('#snn-bricks-chat-messages').append($msg);
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
.snn-bricks-chat-message { margin-bottom: 4px; padding: 4px 8px; border-radius: 12px; max-width: 95%; position: relative; }
.snn-bricks-chat-message-user { background: #161a1d; color: #fff; margin-left: auto; }
.snn-bricks-chat-message-assistant { background: #fff; border: 1px solid #e0e0e0; margin-right: auto; }
.snn-bricks-chat-message-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.snn-bricks-chat-message.is-collapsed .snn-msg-body { max-height: 70px; overflow: hidden; position: relative; }
.snn-bricks-chat-message.is-collapsed .snn-msg-body::after { content: ""; position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: linear-gradient(to bottom, transparent, var(--snn-msg-fade, #fff)); pointer-events: none; }
.snn-bricks-chat-message-user.is-collapsed .snn-msg-body::after { --snn-msg-fade: #161a1d; }
.snn-bricks-chat-message-error.is-collapsed .snn-msg-body::after { --snn-msg-fade: #fee; }
.snn-msg-toggle { display: block; width: 100%; background: none; border: none; padding: 4px 0 2px; font-size: 11px; font-weight: 600; cursor: pointer; text-align: center; opacity: 0.7; letter-spacing: 0.03em; }
.snn-bricks-chat-message-user .snn-msg-toggle { color: #ccc; }
.snn-bricks-chat-message-assistant .snn-msg-toggle { color: #555; }
.snn-bricks-chat-message-error .snn-msg-toggle { color: #c33; }
.snn-msg-toggle:hover { opacity: 1; }
.snn-bricks-chat-typing { padding: 8px 16px; }
.typing-dots { display: flex; gap: 4px; }
.typing-dots span { width: 8px; height: 8px; border-radius: 50%; background: #999; animation: typing 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.5; } 30% { transform: translateY(-8px); opacity: 1; } }
.snn-bricks-chat-state-text { padding: 8px 16px; background: #f0f0f0; font-size: 13px; color: #666; display: none; }
.snn-bricks-chat-quick-actions { padding: 5px; background: #fff;  display: flex; gap: 6px; flex-wrap: wrap; }
.snn-bricks-quick-action-btn { padding: 6px 12px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; cursor: pointer; }
.snn-bricks-quick-action-btn:hover { background: #161a1d; color: #fff; }
.snn-bricks-chat-input-container { padding: 12px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 8px; align-items: flex-end; }
.snn-bricks-chat-input-wrapper { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.snn-bricks-chat-input { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 10px; font-size: 14px; resize: none; min-height: 70px; max-height: 120px; }
.snn-bricks-chat-attach-btn { width: 42px; height: 70px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; color: #666; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-bricks-chat-attach-btn:hover { background: #e0e0e0; }
.snn-bricks-chat-send { width: 42px; height: 70px; background: #161a1d; border: none; border-radius: 8px; color: #fff; cursor: pointer; display:flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.2s; }
.snn-bricks-chat-send:hover { background: #0f1315; }
.snn-bricks-chat-send.snn-chat-stop { background: #dc2626; }
.snn-bricks-chat-send.snn-chat-stop:hover { background: #b91c1c; }
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
/* Design Preview Pane — left of chat overlay */
.snn-bricks-preview-pane { position: fixed; top: 0; left: 0; right: 400px; bottom: 0; z-index: 999998; background: #fff; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.12); }
.snn-bricks-preview-header { background: #1e293b; color: #fff; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-shrink: 0; }
.snn-bricks-preview-title { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; white-space: nowrap; }
.snn-bricks-preview-badge { background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
.snn-bricks-preview-controls { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.snn-preview-approve-btn { background: #22c55e; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.snn-preview-approve-btn:hover { background: #16a34a; }
.snn-preview-close-btn { background: rgba(255,255,255,0.1); border: none; color: #fff; width: 26px; height: 26px; border-radius: 5px; cursor: pointer; font-size: 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-preview-close-btn:hover { background: rgba(255,255,255,0.25); }
.snn-bricks-preview-iframe { flex: 1; border: none; width: 100%; }
/* Preview toggle button active state */
.snn-bricks-chat-btn.is-active { background: #22c55e !important; }
/* Approve bar inside chat */
.snn-approve-bar { background: #f0fdf4; border-top: 2px solid #22c55e; border-bottom: 1px solid #dcfce7; padding: 8px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13px; flex-shrink: 0; }
.snn-approve-label { color: #15803d; font-weight: 600; font-size: 12px; }
.snn-approve-actions { display: flex; align-items: center; gap: 6px; }
.snn-approve-build-btn { background: #16a34a; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.snn-approve-build-btn:hover { background: #15803d; }
/* Support link */
.snn-bricks-chat-support { padding: 2px 12px; background: #f9f9f9; border-top: 1px solid #e0e0e0; text-align: center; }
.snn-bricks-chat-support a { font-size: 14px; font-weight:600; color: #666; text-decoration: none; transition: color 0.2s; }
.snn-bricks-chat-support a:hover { color: #820808; }
/* Abilities API results */
.ability-results { margin-top: 4px; }
.ability-result { padding: 6px 10px; margin: 3px 0; border-radius: 5px; font-size: 13px; line-height: 1.5; }
.ability-result.success { background: #f0f9ff; border: 1px solid #bae6fd; }
.ability-result.error { background: #fef2f2; border: 1px solid #fecaca; }
.ability-result strong { display: inline; margin-right: 5px; }
.result-data { color: #444; font-size: 13px; margin-top: 4px; line-height: 1.6; }
.result-error { color: #dc2626; font-size: 12px; margin-top: 2px; }
.result-meta { color: #888; font-size: 12px; }
.result-inline { margin-bottom: 2px; }
.result-summary-block { display: flex; flex-direction: column; gap: 2px; margin-top: 4px; }
.result-summary-row { font-size: 12px; color: #444; }
.result-details { margin-top: 5px; }
.result-details summary { font-size: 11px; color: #2271b1; cursor: pointer; user-select: none; display: inline-block; padding: 1px 4px; border-radius: 3px; }
.result-details summary:hover { background: #e8f0fe; }
.result-details[open] summary { color: #1557a0; }
.result-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 4px; }
.result-table th { background: #e8f4fd; color: #1e3a5f; font-weight: 600; padding: 3px 7px; text-align: left; border: 1px solid #c8dff0; }
.result-table td { padding: 2px 7px; border: 1px solid #dde; color: #333; }
.result-table tr:nth-child(even) td { background: #f7fbff; }
.json-result-container { margin-top: 4px; max-height: 160px; overflow-y: auto; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px; }
.json-result { margin: 0; padding: 8px; font-family: Courier, monospace; font-size: 11px; line-height: 1.4; white-space: pre; overflow-x: auto; color: #333; }
.json-key { color: #0066cc; font-weight: 600; }
.json-string { color: #22863a; }
.json-number { color: #005cc5; }
.json-boolean { color: #d73a49; font-weight: 600; }
.json-null { color: #6f42c1; font-style: italic; }
        ';
    }
}

// Initialize
SNN_Bricks_Chat_Overlay::get_instance();

/**
 * Pixabay Image Proxy
 *
 * Accepts a ?q= keyword param, queries the Pixabay API, and redirects
 * to the first matching photo URL so the AI can use it as a real image src.
 * Accessible to logged-in users (Bricks builder requires login).
 */
add_action( 'wp_ajax_snn_pixabay_image', 'snn_pixabay_image_proxy_handler' );

function snn_pixabay_image_proxy_handler() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        status_header( 403 );
        exit;
    }

    $q       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : 'nature';
    $api_key = get_option( 'snn_pixabay_api_key', '' );

    $api_url = add_query_arg(
        array(
            'key'        => $api_key,
            'q'          => urlencode( $q ),
            'image_type' => 'photo',
            'safesearch' => 'true',
            'per_page'   => 5,
            'order'      => 'popular',
            'lang'       => 'en',
        ),
        'https://pixabay.com/api/'
    );

    $response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );

    if ( ! is_wp_error( $response ) ) {
        $http_code       = wp_remote_retrieve_response_code( $response );
        $rate_remaining  = (int) wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
        $rate_reset      = (int) wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );

        // Detect rate limit: HTTP 429 OR remaining quota is 0
        $is_rate_limited = ( 429 === $http_code ) ||
                           ( '' !== wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' ) && $rate_remaining <= 0 );

        if ( $is_rate_limited ) {
            // Store reset time so we can log/debug if needed
            if ( $rate_reset > 0 ) {
                set_transient( 'snn_pixabay_rate_reset', time() + $rate_reset, $rate_reset );
            }
            status_header( 429 );
            exit;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! empty( $data['hits'] ) ) {
            $hit       = $data['hits'][0];
            $image_url = ! empty( $hit['largeImageURL'] ) ? $hit['largeImageURL'] : $hit['webformatURL'];
            wp_redirect( $image_url );
            exit;
        }
    }

    // No results from Pixabay
    status_header( 404 );
    exit;
}

/**
 * Save External Image to WordPress Media Library
 *
 * Downloads an external image URL (e.g. from Pixabay proxy) and attaches it
 * to the WordPress media library so Bricks can reference it as a local attachment.
 * On success also updates the image URL in the response so the caller can swap
 * the external URL for the local one inside bricksState.
 */
add_action( 'wp_ajax_snn_save_image_to_library', 'snn_save_image_to_library_handler' );

function snn_save_image_to_library_handler() {
    check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );

    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'Insufficient permissions to upload files.' ) );
    }

    $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

    if ( empty( $url ) ) {
        wp_send_json_error( array( 'message' => 'No URL provided.' ) );
    }

    // Only allow http/https to prevent SSRF
    $parsed = wp_parse_url( $url );
    if ( ! in_array( $parsed['scheme'] ?? '', array( 'http', 'https' ), true ) ) {
        wp_send_json_error( array( 'message' => 'Only HTTP/HTTPS URLs are allowed.' ) );
    }

    // If this is a Pixabay proxy URL, resolve it directly via the Pixabay API.
    // We cannot do a loopback HTTP HEAD to admin-ajax.php (it requires auth and would return 403).
    // Instead, extract the 'q' param and call Pixabay directly — same logic as the proxy handler.
    if ( strpos( $url, 'action=snn_pixabay_image' ) !== false ) {
        $parsed_qs = array();
        wp_parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $parsed_qs );
        $q       = sanitize_text_field( $parsed_qs['q'] ?? 'nature' );
        $api_key = get_option( 'snn_pixabay_api_key', '' );

        $api_url = add_query_arg( array(
            'key'        => $api_key,
            'q'          => urlencode( $q ),
            'image_type' => 'photo',
            'safesearch' => 'true',
            'per_page'   => 5,
            'order'      => 'popular',
            'lang'       => 'en',
        ), 'https://pixabay.com/api/' );

        $api_response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );
        if ( ! is_wp_error( $api_response ) ) {
            $pix_data = json_decode( wp_remote_retrieve_body( $api_response ), true );
            if ( ! empty( $pix_data['hits'][0] ) ) {
                $hit = $pix_data['hits'][0];
                $url = ! empty( $hit['largeImageURL'] ) ? $hit['largeImageURL'] : $hit['webformatURL'];
            }
        }
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_sideload_image( $url, 0, null, 'id' );

    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
    }

    wp_send_json_success( array(
        'attachment_id' => $attachment_id,
        'url'           => wp_get_attachment_url( $attachment_id ),
        'original_url'  => $url,
    ) );
}