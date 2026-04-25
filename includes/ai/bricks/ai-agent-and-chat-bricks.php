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
                currentTheme: null
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
- If the user explicitly says "use existing colors", "use theme colors", "match the site", or similar \u2192 use the existing palette values above for primary/secondary/accent/background etc. Set "usedExistingTokens": true.
- If no color direction is given at all \u2192 you may use existing colors as inspiration or pick a fresh palette that fits the brief. Your call.
- If the user describes a specific new palette (e.g. "dark navy and gold") \u2192 use their description, ignore existing tokens.
- For sizes: if existing size variables are present, prefer their values for sectionPadding, containerGap, cardPadding, borderRadius where appropriate.
- When using a var() value in the spec, write it as the var() string so the designer can use it directly: e.g. "primary": "var(--color-primary)" or "primary": "#1a2b3c"
` : '';

                const systemPrompt = `You are a visual design director for a web page being built in Bricks Builder.
Given a project brief, layout plan, and any existing design tokens, output ONLY a JSON design spec \u2014 no prose, no markdown, no explanation.
${tokenContext}${tokenInstructions}
Output this exact JSON shape:
{
  "usedExistingTokens": false,
  "palette": {
    "primary":    "#hex or var(--name)",
    "secondary":  "#hex or var(--name)",
    "accent":     "#hex or var(--name)",
    "background": "#hex or var(--name)",
    "surface":    "#hex or var(--name)",
    "text":       "#hex or var(--name)",
    "textMuted":  "#hex or var(--name)"
  },
  "fonts": {
    "heading":       "Google Font Name",
    "body":          "Google Font Name",
    "headingWeight": "900",
    "bodyWeight":    "400"
  },
  "spacing": {
    "sectionPadding": "100px or var(--name)",
    "containerGap":   "32px  or var(--name)",
    "cardPadding":    "32px  or var(--name)",
    "borderRadius":   "12px  or var(--name)"
  },
  "mood":  ["bold", "premium"],
  "style": "minimal | editorial | bold | elegant | playful | technical"
}`;

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
                const systemPrompt = `You are a Bricks Builder HTML validator. Review the HTML and fix ONLY these specific issues:

1. MISSING data-bricks attributes — every structural element must have one
2. INVALID NESTING — section > container > block > content (never container inside block)
3. MISSING display:flex on flex containers — if flex-direction/align-items/gap is set, display:flex must be too
4. INLINE-FLEX usage — replace all display:inline-flex with display:flex + width:max-content
5. ORPHANED <style data-style-id> — every style block must have a matching element with that id

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

                // PASS 3: Validate and clean settings
                content.forEach(el => {
                    // Ensure numeric padding/margin values are strings
                    ['_padding', '_margin'].forEach(prop => {
                        if (el.settings[prop] && typeof el.settings[prop] === 'object') {
                            ['top', 'right', 'bottom', 'left'].forEach(side => {
                                if (el.settings[prop][side] !== undefined) {
                                    el.settings[prop][side] = String(el.settings[prop][side]);
                                }
                            });
                        }
                    });

                    // Validate typography font-size is string
                    if (el.settings._typography?.['font-size'] && typeof el.settings._typography['font-size'] !== 'string') {
                        el.settings._typography['font-size'] = String(el.settings._typography['font-size']);
                    }

                    // Wrap _cssGlobal with proper Bricks selector if not already wrapped
                    // Redirect _cssGlobal to _cssCustom as Bricks doesn't use _cssGlobal
                    if (el.settings._cssGlobal && typeof el.settings._cssGlobal === 'string') {
                        const cssGlobal = el.settings._cssGlobal.trim();
                        // Clean up
                        const cleanedCss = cssGlobal
                            .replace(/cursor:\s*pointer;?/g, '')
                            .replace(/transition:[^;]+;?/g, '')
                            .replace(/@media[^{]+\{[^}]+\}/g, '')
                            .trim();
                            
                        if (cleanedCss) {
                            // Check if already wrapped with #brxe- or %root%
                            if (!cleanedCss.includes('#brxe-') && !cleanedCss.includes('%root%')) {
                                el.settings._cssCustom = ((el.settings._cssCustom || '') + ` #brxe-${el.id} { ${cleanedCss} }`).trim();
                            } else {
                                el.settings._cssCustom = ((el.settings._cssCustom || '') + ` ${cleanedCss}`).trim();
                            }
                        }
                        delete el.settings._cssGlobal;
                    }

                    // Wrap _cssCustom with proper Bricks selector if not already wrapped
                    if (el.settings._cssCustom && typeof el.settings._cssCustom === 'string') {
                        let cssCustom = el.settings._cssCustom.trim();
                        // Only wrap if it doesn't already contain #brxe-, %root% or @keyframes
                        if (!cssCustom.includes(`#brxe-${el.id}`) && !cssCustom.includes('%root%') && !cssCustom.includes('@keyframes') && !cssCustom.includes('@media')) {
                            cssCustom = `#brxe-${el.id} { ${cssCustom} }`;
                        } else if (cssCustom.startsWith('@keyframes') || cssCustom.startsWith('@media')) {
                            // Leave keyframes/media queries outside, but ensure they are valid syntax
                        }
                        
                        // ALWAYS replace %root% with #brxe-{id} to force reactive state update
                        el.settings._cssCustom = cssCustom.replace(/%root%/g, `#brxe-${el.id}`);
                    }

                    // Remove legacy _css object (use native breakpoint suffixes instead)
                    if (el.settings._css) {
                        delete el.settings._css;
                        errors.push('Removed legacy _css from ' + el.id);
                        fixed = true;
                    }

                    // Fix gradient in _background.color.raw — convert to custom CSS
                    const bgRaw = el.settings._background?.color?.raw;
                    if (bgRaw && typeof bgRaw === 'string' && (bgRaw.includes('linear-gradient') || bgRaw.includes('radial-gradient') || bgRaw.includes('conic-gradient'))) {
                        let cssCustom = el.settings._cssCustom || '';
                        cssCustom = `#brxe-${el.id} { background: ${bgRaw}; } ${cssCustom}`.trim();
                        el.settings._cssCustom = cssCustom;
                        delete el.settings._background.color;
                        if (!Object.keys(el.settings._background).length) delete el.settings._background;
                        errors.push('Converted gradient in _background.color.raw → _cssCustom for ' + el.id);
                        fixed = true;
                    }
                });

                // INFER justify-content on flex-row children of space-between/space-around parents.
                // In Bricks, flex blocks stretch to fill available space. A right-side group inside a
                // space-between parent has no visual indication of its own justification — without
                // setting justify-content: flex-end, its own children pile up on the left edge.
                // Rule: if a flex-row element has no _justifyContent, and its parent has
                // _justifyContent: space-between or space-around, and it is NOT the first child
                // of that parent — set _justifyContent: flex-end.
                {
                    const nameMap2 = {};
                    content.forEach(el => { nameMap2[el.id] = el; });
                    content.forEach(el => {
                        if (el.settings._display === 'flex' && el.settings._direction === 'row' && !el.settings._justifyContent) {
                            const parentEl = el.parent !== 0 ? nameMap2[el.parent] : null;
                            if (parentEl) {
                                const parentJC = parentEl.settings._justifyContent;
                                if (parentJC === 'space-between' || parentJC === 'space-around' || parentJC === 'space-evenly') {
                                    // Determine position among siblings
                                    const siblings = content.filter(s => s.parent === el.parent);
                                    const myIndex = siblings.findIndex(s => s.id === el.id);
                                    if (myIndex > 0) {
                                        el.settings._justifyContent = 'flex-end';
                                        el.settings._justifyContentGrid = 'flex-end';
                                        fixed = true;
                                    } else {
                                        // First child — default to flex-start for clarity
                                        el.settings._justifyContent = 'flex-start';
                                        el.settings._justifyContentGrid = 'flex-start';
                                        fixed = true;
                                    }
                                }
                            }
                        }
                    });
                }

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
            async function compileSingleSection(sectionHtml, sectionLabel, sectionIndex) {
                // Extract Google Fonts from HTML (for future use if needed)
                const fontMatch = sectionHtml.match(/@import\s+url\(['"]([^'"]+)['"]\)/i)  || 
                                  ChatState.currentHTMLPreview.match(/@import\s+url\(['"]([^'"]+)['"]\)/i);
                const googleFonts = fontMatch ? fontMatch[1] : '';
                
                try {
                    // Use the new JavaScript compiler - instant, no API calls!
                    const bricksData = compileHtmlToBricksJson(sectionHtml, googleFonts);
                    
                    if (!bricksData || !bricksData.content || !bricksData.content.length) {
                        throw new Error('Compiler returned empty content');
                    }
                    
                    debugLog('✓ Compiled "' + sectionLabel + '" — ' + bricksData.content.length + ' elements');
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

                const sections = parseHTMLIntoSections(ChatState.currentHTMLPreview);
                const total    = sections.length;
                addMessage('assistant', '⚡ Compiling ' + total + ' section' + (total > 1 ? 's' : '') + ' with JavaScript compiler...');

                const builtImageUrls = [];
                let builtCount = 0;
                for (let i = 0; i < sections.length; i++) {
                    if (!ChatState.isProcessing) { addMessage('assistant', '⏹ Build stopped.'); break; }
                    const { label, html } = sections[i];
                    setAgentState('compiling', 'Building "' + label + '" (' + (i + 1) + '/' + total + ')...');
                    let bricksData = null;
                    try {
                        bricksData = await compileSingleSection(html, label, i + 1);
                    } catch(compileErr) {
                        // Auto-correction: ask AI to fix this section once
                        debugLog('Compilation failed for "' + label + '":', compileErr.message);
                        addMessage('assistant', '⚠️ "' + label + '" had issues. Auto-correcting...');
                        try {
                            const fixedHtml = await selfCorrectHTML(html, compileErr.message);
                            bricksData = await compileSingleSection(fixedHtml, label + ' [corrected]', i + 1);
                        } catch(retryErr) {
                            debugLog('Self-correction failed for "' + label + '":', retryErr.message);
                            if (retryErr.name !== 'AbortError') {
                                addMessage('error', '✗ "' + label + '" could not be auto-corrected: ' + retryErr.message);
                            }
                        }
                    }
                    if (bricksData && ChatState.isProcessing) {
                        const { data } = validateAndFixBricksJSON(bricksData);
                        // Collect image URLs for media library saving
                        (data.content || []).forEach(el => {
                            if (el.settings.image?.url) {
                                builtImageUrls.push(el.settings.image.url);
                                console.log('[Bricks AI] 📸 Found img URL in [' + el.id + '] (' + el.name + '):', el.settings.image.url);
                            }
                            if (el.settings._background?.image?.url) {
                                builtImageUrls.push(el.settings._background.image.url);
                                console.log('[Bricks AI] 📸 Found bg-img URL in [' + el.id + '] (' + el.name + '):', el.settings._background.image.url);
                            }
                        });
                        const result = (i === 0 && actionType === 'replace')
                            ? BricksHelper.replaceAllContent(data)
                            : BricksHelper.addSection(data, i === 0 ? actionType : 'append');
                        if (result.success) {
                            builtCount++;
                            addMessage('assistant', '✓ "' + label + '" built (' + builtCount + '/' + total + ')');
                        } else {
                            addMessage('error', '✗ "' + label + '" inject failed: ' + result.error);
                        }
                    } else if (!bricksData && ChatState.isProcessing) {
                        addMessage('error', '✗ "' + label + '" — could not compile. Skipped.');
                    }
                }

                ChatState.isProcessing = false;
                setAgentState('idle');
                updateSendButton();

                if (builtCount > 0) {
                    addMessage('assistant', '🎉 Done! ' + builtCount + '/' + total + ' sections built in Bricks.');
                    // Keep preview visible so user can still compare the HTML
                    // hideHTMLPreview();
                    removeApproveBar();
                    ChatState.previewMode = null;
                    // Keep currentHTMLPreview so toggle button remains functional
                    // ChatState.currentHTMLPreview = null;
                    // Save external images to WordPress media library
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
                let tokensSnap = '';
                if (tokens.colors.length) {
                    const colorList = tokens.colors.map(c => `  ${c.raw}${c.hex ? ' (' + c.hex + ')' : ''}`).join('\n');
                    tokensSnap += `\nTHEME COLOR VARIABLES (use these in color/background styles when user wants existing theme colors):\n${colorList}\n`;
                }
                if (tokens.sizes.length) {
                    const sizeList = tokens.sizes.map(v => `  var(${v.cssVar}) = ${v.value}  /* use as: ${v.value} OR var(${v.cssVar}) */`).join('\n');
                    tokensSnap += `\nTHEME SIZE VARIABLES (use these for padding/gap/font-size when user wants existing theme spacing):\n${sizeList}\n`;
                }

                // When a theming agent has already resolved the design spec, use it instead of
                // the raw token list — it's more precise and reduces prompt token count.
                let designSpec = tokensSnap;
                if (ChatState.currentTheme) {
                    const t = ChatState.currentTheme;
                    designSpec = '\n=== DESIGN SPEC FROM THEMING AGENT (follow exactly — do not invent new colors or fonts) ===\n' +
                        'Palette:\n' +
                        '  primary:    ' + t.palette.primary    + '\n' +
                        '  secondary:  ' + t.palette.secondary  + '\n' +
                        '  accent:     ' + t.palette.accent     + '\n' +
                        '  background: ' + t.palette.background + '\n' +
                        '  surface:    ' + t.palette.surface    + '\n' +
                        '  text:       ' + t.palette.text       + '\n' +
                        '  textMuted:  ' + t.palette.textMuted  + '\n' +
                        'Fonts:\n' +
                        '  heading: "' + t.fonts.heading + '", weight ' + t.fonts.headingWeight + '\n' +
                        '  body:    "' + t.fonts.body    + '", weight ' + t.fonts.bodyWeight    + '\n' +
                        'Spacing:\n' +
                        '  section padding: ' + t.spacing.sectionPadding + '\n' +
                        '  container gap:   ' + t.spacing.containerGap   + '\n' +
                        '  card padding:    ' + t.spacing.cardPadding    + '\n' +
                        '  border radius:   ' + t.spacing.borderRadius   + '\n' +
                        'Mood: ' + t.mood.join(', ') + ' | Style: ' + t.style + '\n' +
                        (t.usedExistingTokens ? 'These colors are from the site\'s existing Bricks global palette — use the var() names where provided.\n' : '') +
                        '=== Use ONLY these values. Every section must feel visually consistent. ===\n';
                }

                return basePrompt + `

=== BRICKS BUILDER AI — DESIGN PHASE ===
Currently editing: "${postTitle}" (${postType})
${pageSnap}${designSpec}${postTypeKeys.length ? '\nREGISTERED POST TYPES available for query loops — use the slug as data-loop value: ' + postTypeKeys.map(k => k + ' (' + postTypes[k].label + ')').join(', ') + '\n' : ''}
⚡ Your designs are compiled to Bricks using a LIGHTNING-FAST JavaScript compiler — instant conversion, zero API costs!

YOUR JOB:
Generate a complete, beautiful HTML design. Your job here is pure execution — intent is pre-classified${intent === 'refine_preview' ? ' as a REFINEMENT: incorporate the requested changes into a complete, fresh HTML output' : intent === 'add_section' ? ' as ADD SECTION: generate only the new section(s) requested' : ' as NEW DESIGN: generate the full page'}. Use:
- Google Fonts (@import in <style> tag at top of body)
- Real, production-quality content — actual headings, descriptions, CTAs (no Lorem Ipsum for main content)
- Real images via Pixabay proxy: ${ajaxUrl}?action=snn_pixabay_image&q=KEYWORDS (use different, specific keywords for each image)

OUTPUT FORMAT:
1. Write 1–2 sentences describing the design approach and color palette
2. Output the complete HTML in a \`\`\`html code block
3. IMPORTANT: YOU MUST ENCLOSE THE HTML WITHIN \`\`\`html AND \`\`\`! NEVER OUTPUT RAW HTML OUTSIDE OF THE MARKDOWN BLOCK.

🚨 CRITICAL STYLING REQUIREMENT — READ THIS FIRST:
For animations, keyframes, webkit prefixes, pseudo-elements, or ANY advanced CSS:
  ✅ CORRECT: <style data-style-id="brxe-abcdef"> @keyframes jump {...} #brxe-abcdef { animation: jump 2s; } </style>
               <div id="brxe-abcdef" data-bricks="block">
  ❌ WRONG:   <style data-style-id="brxe-xyzijk"> .mario { animation: jump 2s; } </style>  <!-- NO matching id! -->
               <div class="mario">  <!-- NO id attribute! -->

RULE: EVERY element with advanced CSS MUST have:
  1. A unique id="brxe-XXXXXX" attribute (brxe- prefix + 6 random lowercase letters, e.g. brxe-abcdef)
  2. A matching <style data-style-id="brxe-XXXXXX"> block using #brxe-XXXXXX selector
  3. If multiple elements need animation, create SEPARATE style blocks for EACH element

STYLING RULES (CRITICAL — NO SHORTCUTS):
- Use INLINE style="..." attributes for ALL standard CSS properties (padding, margin, display, flex/grid, colors, fonts, borders, shadows)
- Example: <h1 style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -0.5px; margin: 0 0 20px 0;">
- Include Google Fonts ONLY: <style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');</style>
- ⚠️ NEVER put animations, keyframes, webkit prefixes, or element-specific CSS in the global <style> tag
- ⚠️ Use <style data-style-id="brxe-XXXXXX"> blocks for advanced CSS (see CUSTOM CSS section below)
- Specify ALL visual properties: font-family, font-size, font-weight, color, line-height, letter-spacing, text-align, padding, margin, background, background-color, border, border-radius, box-shadow, opacity, display, flex properties, grid properties, width, height, max-width, object-fit, position, top, left, right, bottom, z-index, transform, transition
- Use standard CSS property names only: padding: 40px 20px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px
- Colors MUST be hex codes: #111827, #ffffff, #2563eb, rgba(0,0,0,0.1) for transparency
- All sizes MUST include units: font-size: 48px; padding: 60px 0; gap: 32px; width: 100%; max-width: 1200px
- Font stacks with fallbacks: 'Playfair Display', serif OR 'Inter', sans-serif OR 'Lato', sans-serif
- NO UTILITY CLASSES: Never use Tailwind, Bootstrap, or any utility class framework syntax
- ALL LAYOUT via inline styles: display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 24px;
- ⚠️ FLEX RULE FOR BLOCKS: ALWAYS write display:flex AND flex-direction AND any alignment/gap together on the SAME element. NEVER write align-items, justify-content, flex-direction, or gap on a block WITHOUT also writing display:flex on that same element. Missing display:flex makes ALL other flex properties invisible in Bricks.
- ⚠️ NEVER use display:inline-flex — Bricks does not support it properly. Use display:flex with width:max-content or width:auto instead to shrink-wrap a block.
- ⚠️ BRICKS WIDTH DEFAULT IS 100%, NOT AUTO: Unlike browsers, Bricks defaults all block elements to width:100%. Any layout block that should NOT stretch full width MUST explicitly declare width:auto (or width:max-content for shrink-wrap). Never assume width:auto is implicit. Examples: icon wells, badges, pill tags, inline button groups, narrow side columns, avatar boxes — all need width:auto or a fixed width. If you omit width, Bricks will force the element to 100% and break your layout.
- ALL GRID via inline styles: display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;

DESIGN QUALITY:
- Stunning, professional color palettes matching the business type
- Responsive-ready structure (mobile breakpoints will be handled by Bricks)
- Strong typography hierarchy (large bold h1, clear h2, readable body text)
- Excellent color contrast for accessibility
- Modern aesthetics: rounded corners, subtle shadows, generous whitespace, smooth transitions
- Production-ready design — not a wireframe or mockup, but a real design

HOVER & TRANSITIONS (inline style cannot handle :hover — use data attributes instead):
- To add hover background: data-hover-background="#darkred"
- To add hover transform: data-hover-transform="translateY(-4px)"
- Include a base transition in the inline style: style="... transition: all 0.3s ease;"
- Example button: <button data-bricks="button" data-hover-background="#1d4ed8" data-hover-transform="translateY(-2px)" style="background: #2563eb; color: #fff; transition: all 0.3s ease; ...">CTA</button>

IMAGES:
Use the Pixabay proxy with topic-specific, descriptive keywords for each image:
  Hero/banner:     ${ajaxUrl}?action=snn_pixabay_image&q=TOPIC+hero+background
  Team photos:     ${ajaxUrl}?action=snn_pixabay_image&q=portrait+professional+business
  Products:        ${ajaxUrl}?action=snn_pixabay_image&q=PRODUCT+photography+commercial
  Food/Restaurant: ${ajaxUrl}?action=snn_pixabay_image&q=gourmet+DISH+food+styling
  Interiors:       ${ajaxUrl}?action=snn_pixabay_image&q=PLACE+interior+design+modern
  Technology:      ${ajaxUrl}?action=snn_pixabay_image&q=technology+digital+abstract

HTML STRUCTURE RULES (CRITICAL — controls how sections are compiled):
- Output ONLY the section elements — NEVER wrap in <html>, <head>, <body> tags or add <!DOCTYPE>
- Every distinct visual section MUST be output as a top-level semantic HTML5 tag: <section>, <header>, <footer>, <nav>
- NEVER wrap sections inside <main>, <div>, or any container
- Content inside <main> is treated as ONE single section (avoid unless intended)
- MANDATORY: Add data-bricks attributes to ALL structural elements to guide compilation:
  * <section data-bricks="section"> — top-level section wrapper (use for most sections)
  * <header data-bricks="section"> — top-level header section (same as section, sets semantic tag to header)
  * <footer data-bricks="section"> — top-level footer section (same as section, sets semantic tag to footer)
  * <div data-bricks="container"> — centering wrapper. Use ONLY ONCE per section as the DIRECT child of <section>/<header>/<footer>. NEVER use for inner layouts.
  * <div data-bricks="block"> — ALL inner layouts, grids, flex columns/rows, cards, boxes. This is the universal layout element.
  * <h1 data-bricks="heading"> through <h6 data-bricks="heading"> — headings (tag attr sets h1/h2/etc.)
  * <p data-bricks="text-basic"> — body text. Can contain inline HTML: <strong>, <em>, <a>, <br>
  * <div data-bricks="text"> — Bricks Rich Text element. Use this when complex formatting, multiple paragraphs, or "Rich Text" is requested.
  * <a data-bricks="text-link"> — text link with optional icon. Set href for the link URL.
  * <button data-bricks="button"> — buttons/CTAs. Set href for link URL.
  * <img data-bricks="image"> — images (src, alt, object-fit, aspect-ratio all supported)
  * <hr> — horizontal divider. Supports border-width (height), width, border-style (solid/dashed/dotted/groove), border-color, text-align/margin for alignment.
  * <i class="fas fa-ICON-NAME"> — standalone FontAwesome icon (solid). Bricks "icon" element.
  * <i class="far fa-ICON-NAME"> or <i class="fa fa-ICON-NAME"> — FA Regular icon.
  * <i class="fab fa-ICON-NAME"> — FA Brands icon (twitter, facebook, instagram, etc.)
  * <ul data-bricks="text-basic"> or <ol data-bricks="text-basic"> — lists (rendered as native HTML inside text-basic)
  * <div data-bricks="custom-html-css-script"> — raw HTML component (ONLY for SVG animations, canvas, iframes, complex widgets)

QUERY LOOPS — POST TYPE LOOPS:
When the design needs to display a repeating list or grid of posts from a post type, use a two-block pattern: a GRID/FLEX WRAPPER block outside, and a LOOP block inside it.
  * data-loop="post_type_slug" — enables a Bricks query loop for that post type (required, e.g. data-loop="post", data-loop="codex")
  * data-loop-posts-per-page="6" — number of posts to show per page (optional, default 6)
  * data-loop-orderby="date" — orderby field: date, title, menu_order, rand (optional, default date)
  * data-loop-order="DESC" — sort direction: ASC or DESC (optional, default DESC)
  The single child of the loop block is the TEMPLATE card — Bricks repeats it for each post automatically.
  Use these Bricks dynamic tags inside template children:
    {post_title}   — post title (use in heading/text-basic text)
    {post_excerpt} — post excerpt (use in text-basic text)
    {post_date}    — publication date (use in text-basic text)
    {post_link}    — post permalink URL (use as href on <a data-bricks="block"> for a card-as-link)
    {cf_POSTTYPE_FIELDNAME} — custom field value (e.g. {cf_codex_color} for post type "codex", field "color")

  🚨 MANDATORY THREE-LAYER LOOP STRUCTURE — CRITICAL — EXACTLY 3 LAYERS, NO MORE, NO LESS:
    The grid/flex layout and the loop query CANNOT live on the same block. You MUST use exactly these three layers:

    LAYER 1 — GRID/FLEX WRAPPER block: carries display:grid (or flex), grid-template-columns, gap, width.
               This block has NO data-loop. It is purely a layout container.
               Its ONLY child is the LOOP block — nothing else sits between them.
    LAYER 2 — LOOP block (DIRECT child of wrapper, no intermediary): carries data-loop only. NO layout styling here.
               This block has ONE child — the template card.
    LAYER 3 — TEMPLATE CARD: one card element. Bricks repeats this for every post.

    ❌ WRONG — grid and loop on the same block:
      <div data-bricks="block" data-loop="post" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
        <div data-bricks="block">card template</div>
      </div>

    ❌ WRONG — extra intermediary block between wrapper and loop (NEVER do this):
      <div data-bricks="block" style="display: grid; ...">
        <div data-bricks="block" style="display: contents;">  <!-- FORBIDDEN: no extra wrapper -->
          <div data-bricks="block" data-loop="post">
            <div data-bricks="block">card template</div>
          </div>
        </div>
      </div>

    ✅ CORRECT — wrapper → loop block → card, exactly 3 layers:
      <div data-bricks="block" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; width: 100%;">
        <div data-bricks="block" data-loop="post" data-loop-posts-per-page="6">
          <div data-bricks="block">card template</div>
        </div>
      </div>

  Additional loop rules:
    - Put dynamic tags directly as text in heading/text-basic — e.g. <h3 data-bricks="heading">{post_title}</h3>
    - Design ONE template card only (Bricks handles repetition — do NOT repeat fake cards)
    - For a fully clickable card use <a data-bricks="block" href="{post_link}"> as the template root
    - GRID COLUMN COUNT must match the content: if data-loop-posts-per-page="5", use repeat(3, 1fr) not repeat(5, 1fr) — cards need readable width. Max 4 columns for cards/posts. Use 2 or 3 columns as the default. Only use 4 columns for very small thumbnail-style cards. NEVER use 5 or 6 columns for post loops.
    - NEVER add display:contents or any extra structural block between LAYER 1 and LAYER 2. The loop block must be the direct first child of the grid/flex wrapper.

  Example loop — post card grid (correct two-block pattern):
    <section data-bricks="section" style="padding-top: 80px; padding-bottom: 80px; background: #f8f8f8;">
      <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">
        <h2 data-bricks="heading" style="font-size: 40px; font-weight: 700; color: #111;">Latest Posts</h2>
        <!-- LAYER 1: grid wrapper — layout only, no data-loop -->
        <div data-bricks="block" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; width: 100%;">
          <!-- LAYER 2: loop block — query only, no grid styling -->
          <div data-bricks="block" data-loop="post" data-loop-posts-per-page="6">
            <!-- LAYER 3: template card — one card, Bricks repeats per post -->
            <a data-bricks="block" href="{post_link}" style="background: #fff; border-radius: 12px; padding: 24px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-decoration: none;">
              <h3 data-bricks="heading" style="font-size: 20px; font-weight: 600; color: #111;">{post_title}</h3>
              <p data-bricks="text-basic" style="font-size: 14px; color: #666; line-height: 1.6;">{post_excerpt}</p>
              <p data-bricks="text-basic" style="font-size: 12px; color: #999;">{post_date}</p>
            </a>
          </div>
        </div>
      </div>
    </section>

CUSTOM CSS — STYLE TAGS (MANDATORY for advanced CSS):
⚠️ CRITICAL: For ANY CSS that inline style="" cannot express, you MUST use <style data-style-id="brxe-XXXXXX"> blocks.
This includes: -webkit- prefixes, text-stroke, clip-path, filters, backdrop-filter, animations, keyframes,
pseudo-elements (:before/:after/:hover/:focus), complex transforms, gradients with clip, mask properties.

🚫 FORBIDDEN PATTERNS (will break compilation):
  ❌ WRONG: <style data-style-id="brxe-abcdef"> .my-class { ... } </style>  <!-- orphaned style block, no matching id -->
  ❌ WRONG: <style> .game-world { animation: ... } </style>  <!-- global style tag for element-specific CSS -->
  ❌ WRONG: <div class="game-world"> <!-- element with custom CSS but NO id -->

✅ MANDATORY PATTERN — EVERY element needing custom CSS MUST have a matching id:
  1. Give the element a unique id: id="brxe-XXXXXX" (brxe- prefix + 6 random lowercase letters, e.g. brxe-abcdef, brxe-mnopqr)
  2. Write <style data-style-id="brxe-XXXXXX"> IMMEDIATELY BEFORE the element
  3. Use #brxe-XXXXXX selector (converted to %root% in Bricks)
  4. Keep inline style="" for standard properties

  Example 1 — Single element with animation:
    <style data-style-id="brxe-fadinx">
      @keyframes fadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }
      #brxe-fadinx { animation: fadeIn 1s ease-out; }
    </style>
    <section id="brxe-fadinx" data-bricks="section" style="background: #000; padding-top: 100px; padding-bottom: 100px;">

  Example 2 — Multiple animated elements (EACH gets its own style block):
    <style data-style-id="brxe-wrldsc">
      @keyframes worldScroll { from { background-position: 0 0; } to { background-position: -1000px 0; } }
      #brxe-wrldsc { animation: worldScroll 10s linear infinite; }
    </style>
    <div id="brxe-wrldsc" data-bricks="block" style="position: absolute; width: 200%; height: 100%;">

      <style data-style-id="brxe-grndel">
        #brxe-grndel { background: repeating-linear-gradient(90deg, #d4af37 0, #d4af37 40px, #b8941f 40px, #b8941f 80px); }
      </style>
      <div id="brxe-grndel" data-bricks="block" style="position: absolute; bottom: 0; width: 100%; height: 60px;">

      <style data-style-id="brxe-mrioxx">
        @keyframes marioJump { 0%, 100% { transform: translateY(0); } 40% { transform: translateY(-120px); } }
        #brxe-mrioxx { animation: marioJump 3s infinite ease-in-out; }
        #brxe-mrioxx::before { content: ""; position: absolute; top: 10px; right: 8px; width: 8px; height: 8px; background: white; }
      </style>
      <div id="brxe-mrioxx" data-bricks="block" style="position: absolute; bottom: 60px; left: 100px; width: 40px; height: 60px; background: #E63946;">

      <style data-style-id="brxe-coinsp">
        @keyframes coinSpin { 0% { transform: scaleX(1); } 50% { transform: scaleX(0); } 100% { transform: scaleX(1); } }
        #brxe-coinsp { animation: coinSpin 1s infinite; }
      </style>
      <div id="brxe-coinsp" data-bricks="block" style="position: absolute; bottom: 280px; left: 310px; width: 30px; height: 30px; background: #FFD700; border-radius: 50%;">
    </div>

  Example 3 — Parent targeting child classes (child classes, parent has id + style):
    <style data-style-id="brxe-prdgrd">
      #brxe-prdgrd .product-featured { border: 2px solid #d4af37; transform: scale(1.05); }
      #brxe-prdgrd .product-card:hover { background: #1a1a1a; color: #fff; transform: translateY(-4px); }
    </style>
    <div id="brxe-prdgrd" data-bricks="block" style="display: grid; grid-template-columns: repeat(3,1fr); gap: 24px;">
      <div data-bricks="block" class="product-featured" style="padding: 24px; border-radius: 12px; background: #fff;">...</div>
      <div data-bricks="block" class="product-card" style="padding: 24px; border-radius: 12px; background: #f5f5f5;">...</div>
    </div>

  Example 4 — Text stroke / webkit effects:
    <style data-style-id="brxe-lxtitl">
      #brxe-lxtitl { -webkit-text-stroke: 2px #d4af37; color: transparent; }
    </style>
    <h1 id="brxe-lxtitl" data-bricks="heading" style="font-size: 72px; font-weight: 900;">Luxury</h1>

  Example 5 — Backdrop filters:
    <style data-style-id="brxe-glscrd">
      #brxe-glscrd { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
    </style>
    <div id="brxe-glscrd" data-bricks="block" style="background: rgba(255,255,255,0.1); padding: 32px; border-radius: 16px;">

🔑 KEY RULES:
  1. EVERY <style data-style-id="brxe-XXXXXX"> MUST have a matching element with id="brxe-XXXXXX"
  2. NEVER use class selectors at root level (\`.myclass\`) — always use #id or #id .child
  3. For multiple elements with similar effects, create SEPARATE style blocks for EACH element
  4. Parent-child pattern: parent gets id + style block with #parent-id .child-class selectors

WHEN TO USE <style data-style-id> vs inline style="":
✓ Use inline style="" for: padding, margin, display, flex/grid props, font-size, font-weight, color, background-color,
  border, border-radius, box-shadow, width, height, position, top/left/right/bottom, z-index, opacity, object-fit
✓ Use <style data-style-id> for: -webkit-* props, text-stroke, animations, @keyframes, pseudo-elements (::before/::after),
  pseudo-classes (:hover/:focus/:active), backdrop-filter, clip-path, mask, filter, complex transforms

The compiler maps the brxe-XXXXXX HTML id directly to the Bricks element id, and converts #brxe-XXXXXX → %root% in _cssCustom. Child classes are preserved as _cssClasses.
IMPORTANT: Always keep inline style="" as well for basic properties — it drives the HTML preview.

CUSTOM CSS — ATTRIBUTE (for simple single-element overrides):
  Example: <div data-bricks="block" custom-css="backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);" style="background: rgba(255,255,255,0.1); ...">
  The custom-css content is automatically wrapped with #brxe-{id} selector.
  Note: You still need inline style="" for preview rendering.

FONTAWESOME ICONS — supported libraries:
  Solid icons:   <i class="fas fa-arrow-right" style="font-size: 24px; color: #ff0000;"></i>
  Regular icons: <i class="far fa-address-card" style="font-size: 20px; color: #333;"></i>
  Brand icons:   <i class="fab fa-x-twitter" style="font-size: 20px; color: #000;"></i>
  Available FA brand icon names (most common): fa-facebook, fa-facebook-square, fa-instagram, fa-x-twitter, fa-twitter, fa-linkedin, fa-youtube, fa-tiktok, fa-pinterest, fa-github, fa-discord, fa-whatsapp
  Available FA solid icon names (examples): fa-arrow-right, fa-arrow-left, fa-check, fa-star, fa-heart, fa-phone, fa-envelope, fa-location-dot, fa-magnifying-glass, fa-bars, fa-xmark, fa-plus, fa-user, fa-cart-shopping, fa-play, fa-chevron-right, fa-bolt, fa-shield, fa-fire

  Icons inside buttons — add data-icon attribute with the FA class string:
  <button data-bricks="button" data-icon="fas fa-arrow-right" data-icon-position="right" data-icon-gap="10" style="...">Learn More</button>
  <button data-bricks="button" data-icon="fas fa-cart-shopping" data-icon-position="left" data-icon-gap="8" style="...">Add to Cart</button>

  Icons inside text-links — same data-icon approach:
  <a data-bricks="text-link" href="#" data-icon="fas fa-arrow-right" data-icon-position="right" data-icon-gap="6" style="...">Read More</a>

  Standalone icon element (uses inline style for size/color):
  <i class="fas fa-star" style="font-size: 32px; color: #f59e0b;"></i>
  <i class="fab fa-instagram" style="font-size: 24px; color: #E1306C;"></i>

COMMON STYLES — ALL BRICKS ELEMENTS SHARE THESE (apply via inline style on any element type):
  Box model:    padding, margin (shorthand and individual sides)
  Typography:   font-family, font-size, font-weight, font-style, line-height, letter-spacing,
                text-align, text-transform, text-decoration, color, white-space, word-break
  Background:   background-color, background-image, background-size (cover/contain/200px),
                background-position, background-repeat, background-attachment, background-blend-mode
  Border:       border, border-radius (all 4 corners), border-top/right/bottom/left individually,
                border-width, border-style, border-color, individual border-radius corners
  Shadow:       box-shadow
  Sizing:       width, height, min-width, max-width, min-height, max-height, aspect-ratio
  Position:     position (relative/absolute/fixed/sticky), top, right, bottom, left, z-index
  Display:      display (flex/grid/block/inline-block), overflow, opacity, visibility
  Flexbox:      flex-direction, justify-content, align-items, align-content, align-self,
                flex-wrap, flex-grow, flex-shrink, flex-basis, gap, column-gap, row-gap, order
  Grid:         grid-template-columns, grid-template-rows, grid-gap, grid-column, grid-row,
                grid-auto-flow, grid-auto-columns, grid-auto-rows, grid-area
  Image:        object-fit, object-position

RESPONSIVE BREAKPOINTS — Supported breakpoint suffixes (auto-applied by compiler for common patterns):
  :tablet_portrait — applies at tablet portrait (e.g. _padding:tablet_portrait)
  :mobile_landscape — applies at mobile landscape (e.g. _gridTemplateColumns:mobile_landscape)
  The compiler AUTOMATICALLY applies responsive rules for:
  - Large fonts (48px+) scaled down at tablet/mobile
  - 2+ column grids stacked to 1fr on mobile
  - Flex rows stacked to column direction on mobile
  - Large padding reduced on tablet and mobile

- Use clean, shallow semantic structure: <section data-bricks="section"> → <div data-bricks="container"> → content elements (blocks directly inside container). Avoid unnecessary wrapper blocks. Container itself can use flex or grid.
- ALL visual styling MUST be inline style="..." — no class-based frameworks
- CONTAINER RULE: one container per section (for width & layout). Apply display: flex/grid directly to the container to avoid extra DOM depth.

LAYOUT PATTERNS (all via inline styles + data-bricks attributes):

Centered section wrapper (ONLY ONE PER SECTION — direct child of section):
  <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px;">

Flex column layout (use block) — ALWAYS write display:flex + flex-direction + align/gap together:
  <div data-bricks="block" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">

Flex row layout (use block) — ALWAYS write display:flex + flex-direction + align/gap together:
  <div data-bricks="block" style="display: flex; flex-direction: row; gap: 40px; align-items: center; justify-content: space-between;">

⚠️ NEVER do this — missing display:flex makes direction/alignment silently ignored:
  ❌ <div data-bricks="block" style="flex-direction: row; align-items: center; gap: 24px;">
  ✅ <div data-bricks="block" style="display: flex; flex-direction: row; align-items: center; gap: 24px;">

Shrink-wrapped containers (badges, pills, tags) — use flex + width:max-content, NEVER inline-flex:
  <div data-bricks="block" style="display: flex; flex-direction: row; align-items: center; gap: 8px; width: max-content; padding: 4px 12px; border-radius: 50px;">
  (CRITICAL: Always set width: max-content or width: auto so small blocks don't stretch to 100%. NEVER use display:inline-flex — Bricks does not support it.)

Flex item with align-self (any element can have align-self):
  <div data-bricks="block" style="align-self: flex-start; flex-grow: 1;">

Grid 2 columns (can be applied directly to container or use block):
  <div data-bricks="block" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px;">

Grid 3 columns (can be applied directly to container or use block):
  <div data-bricks="block" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">

Grid 4 columns (can be applied directly to container or use block):
  <div data-bricks="block" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">

Asymmetric grid (60/40):
  <div data-bricks="block" style="display: grid; grid-template-columns: 2fr 1fr; gap: 60px; align-items: center;">

Card with padding and shadow:
  <div data-bricks="block" style="background: #ffffff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">

Background image with overlay (use block):
  <div data-bricks="block" style="background-image: url(...); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">

Individual border sides (any element):
  <div data-bricks="block" style="border-left: 4px solid #E11D48; padding-left: 24px;">
  <div data-bricks="block" style="border-bottom: 1px solid rgba(255,255,255,0.1);">

Text link element:
  <a data-bricks="text-link" href="#link" style="color: #2563eb; font-size: 16px; font-weight: 600;">Read More</a>

Horizontal dividers:
  <hr style="border-top: 2px solid #e5e7eb; width: 100%;">
  <hr style="border-top: 3px dashed #ff0000; width: 60px; text-align: center;">
  <hr style="border-top: 1px dotted #666; width: 200px; margin-left: 0;">
  <hr style="border-top: 4px groove #c9a44a; width: 80px; text-align: center;">
  Note: Use border-top-width for height, border-top-style for style (solid/dashed/dotted/groove/ridge), border-top-color for color, text-align or margin-left/right for alignment

STRICT LAYOUT RULES:
✓ KEEP DOM SHALLOW: Apply display: flex or display: grid directly to the container to arrange its children. DO NOT wrap children in an extra block unless fundamentally required for structural grouping (e.g. grouped text inside a grid cell). Example: section > container (with grid) > block (column) + image.
✓ USE CSS GRID for all side-by-side layouts (heroes, feature grids, card grids)
✓ NEVER use flex-wrap for macro layouts — causes desktop wrapping issues
✓ Use Flexbox for single-direction layouts (vertical stacks, horizontal bars, icon rows)
✓ Grid syntax: display: grid; grid-template-columns: repeat(N, 1fr); gap: 32px;
✓ For asymmetric layouts: grid-template-columns: 2fr 1fr; OR 3fr 2fr; OR 1fr 2fr;
✓ align-self works on ANY element inside a flex or grid container
✓ **CRITICAL**: When using display: flex, ALWAYS explicitly set flex-direction: row OR flex-direction: column
   (Bricks Builder defaults to column when not specified, so omitting it breaks row layouts)
✓ ALWAYS declare justify-content on EVERY flex block — never leave it implicit. Use flex-start (left-aligned), center, flex-end (right-aligned), or space-between. In Bricks, flex blocks stretch to fill available space; without explicit justify-content their children silently pile up at the left edge even when the design needs them right-aligned.
  Example — a navbar with two groups: left group → justify-content: flex-start; right group → justify-content: flex-end
✓ NO max-width / margin / padding on container: Do NOT add max-width, margin: 0 auto, padding-left, or padding-right to "container" elements. Bricks handles container width, centering, and gutter spacing via global Theme Styles — inline overrides conflict with those settings and cause double-padding.
✓ NO LEFT/RIGHT PADDING on section: Never set padding-left, padding-right, or the shorthand like padding: 80px 0 (the 0 sets left/right explicitly). Use padding-top and padding-bottom separately instead. Bricks sections inherit root gutter spacing — inline left/right values override it.
✓ EXPLICIT WIDTH ON NON-FULL-WIDTH BLOCKS: Bricks blocks default to width:100%, not width:auto. Any block that should be narrower than its parent — icon containers, stat boxes, badge/pill elements, avatar circles, inline groups, side-by-side pairs inside flex rows — MUST have an explicit width:auto, width:max-content, or a fixed px/% value in the inline style. Never leave width unset and expect it to shrink to content.

EXAMPLE COMPLETE STRUCTURE (with data-bricks attributes):
<style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');</style>

<section data-bricks="section" style="background: #0f172a; padding-top: 80px; padding-bottom: 80px;">
  <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">
    <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -1px; margin: 0;">Premium Heading</h1>
    <hr style="border-top: 2px solid rgba(255, 255, 255, 0.2); width: 60px; text-align: center;">
    <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; font-weight: 400; color: rgba(203, 213, 225, 1); line-height: 1.7; text-align: center; max-width: 700px; margin: 0;">Supporting description with readable line height and proper spacing.</p>
    <button data-bricks="button" style="background: #2563eb; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 14px 32px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.2s;">Call to Action</button>
  </div>
</section>

EXAMPLE 2-COLUMN GRID HERO (section > container > block[column]):
<section data-bricks="section" style="background: #f5f0eb; padding-top: 100px; padding-bottom: 100px;">
  <div data-bricks="container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 60px; align-items: center;">
    <div data-bricks="block" style="display: flex; flex-direction: column; gap: 24px;">
      <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 72px; font-weight: 900; color: #111827; line-height: 1.1; margin: 0;">We Make Brands People Love</h1>
      <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; color: #4b5563; line-height: 1.7; margin: 0;">Creative studio specializing in bold brand identities and digital experiences.</p>
      <button data-bricks="button" style="background: #ff6b35; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 16px 32px; border: none; border-radius: 8px; cursor: pointer;">View Our Work</button>
    </div>
    <img data-bricks="image" src="..." style="width: 100%; height: 600px; object-fit: cover; border-radius: 12px;" />
  </div>
</section>

EXAMPLE 3-ADVANCED EFFECTS (with <style data-style-id> for special effects):
⚠️ NOTE: EACH element with custom CSS has its OWN <style data-style-id="brxe-XXXXXX"> block with matching id="brxe-XXXXXX"
<style>@import url('https://fonts.googleapis.com/css2?family=Syncopate:wght@400;700&family=Space+Grotesk:wght@300;500;700&display=swap');</style>

<style data-style-id="brxe-htitlx">
  @keyframes textGlow { 0%, 100% { text-shadow: 0 0 20px rgba(230, 57, 70, 0.5); } 50% { text-shadow: 0 0 40px rgba(230, 57, 70, 0.8), 0 0 10px #fff; } }
  #brxe-htitlx { animation: textGlow 3s infinite; }
</style>

<style data-style-id="brxe-dsgntx">
  #brxe-dsgntx { color: transparent; -webkit-text-stroke: 1px #ffffff; }
</style>

<style data-style-id="brxe-glspnl">
  #brxe-glspnl { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
</style>

<section data-bricks="section" style="background: #0a0a0a; padding-top: 120px; padding-bottom: 120px; position: relative;">
  <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">
      <h1 id="brxe-htitlx" data-bricks="heading" style="font-family: 'Syncopate', sans-serif; font-size: 82px; font-weight: 700; color: #ffffff; line-height: 0.9; margin: 0; text-transform: uppercase;">
        Next Gen<br><span id="brxe-dsgntx">Design</span>
      </h1>
      <div id="brxe-glspnl" data-bricks="block" style="background: rgba(255,255,255,0.1); padding: 32px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
        <p data-bricks="text-basic" style="font-family: 'Space Grotesk', sans-serif; font-size: 18px; color: #ffffff; margin: 0;">Glass morphism panel with blur effect</p>
      </div>
  </div>
</section>

EXAMPLE 4-GAME/ANIMATION SCENE (multiple animated elements — EACH gets its own style block):
<style>@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');</style>

<style data-style-id="brxe-gmwrld">
  @keyframes worldScroll { from { background-position: 0 0; } to { background-position: -1000px 0; } }
  #brxe-gmwrld { position: absolute; width: 200%; height: 100%; animation: worldScroll 10s linear infinite; }
</style>

<style data-style-id="brxe-grndlv">
  #brxe-grndlv { position: absolute; bottom: 0; width: 100%; height: 60px; background: repeating-linear-gradient(90deg, #d4af37 0, #d4af37 40px, #b8941f 40px, #b8941f 80px); border-top: 4px solid #fff; }
</style>

<style data-style-id="brxe-mrioch">
  @keyframes marioJump { 0%, 100% { transform: translateY(0); } 40% { transform: translateY(-120px); } }
  #brxe-mrioch { position: absolute; bottom: 60px; left: 100px; width: 40px; height: 60px; background: #E63946; border: 3px solid #fff; animation: marioJump 3s infinite ease-in-out; z-index: 100; }
  #brxe-mrioch::before { content: ""; position: absolute; top: 10px; right: 8px; width: 8px; height: 8px; background: white; }
</style>

<style data-style-id="brxe-coinsn">
  @keyframes coinSpin { 0%, 100% { transform: scaleX(1); } 50% { transform: scaleX(0); } }
  #brxe-coinsn { position: absolute; bottom: 280px; left: 310px; width: 30px; height: 30px; background: #FFD700; border-radius: 50%; border: 2px solid #fff; animation: coinSpin 1s infinite; }
</style>

<section data-bricks="section" style="background: #0a0a0a; padding-top: 80px; padding-bottom: 80px;">
  <div data-bricks="container">
    <div data-bricks="block" style="position: relative; height: 500px; background: #1a1a1a; border: 4px solid #333; border-radius: 24px; overflow: hidden;">
      
      <div id="brxe-gmwrld" data-bricks="block">
        <div id="brxe-grndlv" data-bricks="block"></div>
        <div id="brxe-coinsn" data-bricks="block"></div>
        <div id="brxe-mrioch" data-bricks="block"></div>
      </div>
      
      <p data-bricks="text-basic" style="position: absolute; top: 20px; left: 20px; font-family: 'Press Start 2P', cursive; color: #FFD700; font-size: 12px; z-index: 110; margin: 0;">SCORE: 004200</p>
    </div>
  </div>
</section>

CRITICAL REMINDERS:
✓ ONLY inline styles — NO class-based styling frameworks
✓ Every visual property explicitly defined in style=\"...\"
✓ Sections as direct <body> children for independent compilation
✓ Real content, real images, production-ready design quality
✓ Semantic HTML structure with descriptive class names for structure only

OUTPUT HTML ONLY. Do not output patch blocks — patching is handled by a separate agent state.`;
            }


            // ================================================================
            // Focused Prompts — Patching & Answering states
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
                if (!cfg.apiKey || !cfg.apiEndpoint) throw new Error('AI API not configured');
                ChatState.abortController = new AbortController();
                
                // Use helper to build request body with provider routing
                const body = SNN_AI_Helpers.buildRequestBody(
                    cfg,
                    {
                        model: cfg.model,
                        messages: messages,
                        temperature: 0.7,
                        max_tokens: opts.maxTokens || cfg.maxTokens || 4000
                    }
                );
                
                debugLog('AI call:', body.model, messages.length, 'messages');
                
                const resp = await fetch(cfg.apiEndpoint, {
                    method: 'POST',
                    headers: SNN_AI_Helpers.buildHeaders(cfg.apiKey),
                    body: JSON.stringify(body),
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