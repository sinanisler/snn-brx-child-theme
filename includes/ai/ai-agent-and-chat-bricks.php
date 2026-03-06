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
            'agentNonce'    => wp_create_nonce( 'snn_ai_agent_nonce' ),
            'pageContext'   => $page_context,
            'ai'            => $ai_config,
            'settings'      => array(
                'debugMode'  => $main_chat->is_debug_enabled(),
                'maxHistory' => $main_chat->get_max_history(),
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
        <!-- Design Preview Pane — left side, shown when HTML preview is active -->
        <div id="snn-bricks-preview-pane" class="snn-bricks-preview-pane" style="display:none;">
            <div class="snn-bricks-preview-header">
                <div class="snn-bricks-preview-title">
                    <span>Design Preview</span>
                    <span class="snn-bricks-preview-badge">HTML / Tailwind</span>
                </div>
                <div class="snn-bricks-preview-controls">
                    <select id="snn-preview-action-type" class="snn-preview-action-select">
                        <option value="append" selected>Append Section</option>
                        <option value="replace">Replace Page</option>
                        <option value="prepend">Prepend Section</option>
                    </select>
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

    <button class="snn-bricks-quick-action-btn" data-message="Design a complete homepage for an artisan bakery called 'Baked With Love'. Include: a full-viewport hero with a warm dark background, large heading 'Baked Daily With Love', subheading about sourdough and pastries, and a prominent CTA button; a 3-column services grid featuring Sourdough Loaves, Sweet Pastries, and Morning Coffee; a 2-column about section with our 20-year story and a bakery interior image; a 2-column testimonial section with customer reviews; and a dark CTA footer 'Join the Bread Club'. Use warm earthy tones (cream, brown, terracotta).">
    Bakery</button>

    <button class="snn-bricks-quick-action-btn" data-message="Design a modern homepage for a fintech app called 'SmartPay'. Include: a dark hero section with gradient background, headline 'Smart Money for Everyone', subtext about all-in-one banking, and a Download Now CTA; a stats bar with $10B Managed, 5M+ Users, 120 Countries; a 2-column security/features section; a 2-column testimonials section with user success stories; and a newsletter/waitlist CTA section. Use a professional dark navy + electric blue color scheme.">
    Fintech</button>

    <button class="snn-bricks-quick-action-btn" data-message="Design a full creative agency homepage with a warm cream (#F5F0EB) and orange (#FF6B35) color palette. Include 6 sections: 1) Asymmetric hero — 60/40 grid, left has bold heading 'We Make Brands People Love', description, orange CTA button; right has a full-height agency photo. 2) Logo bar — white bg, 'Trusted By' text, grayscale client logos in a row. 3) Featured work — 2-column project cards with images, project names, category tags. 4) Services grid — 2x2 blocks with large orange numbers (01-04), service titles (Brand Strategy, Visual Identity, Digital Design, Motion), descriptions. 5) Team section — 4 team cards with square photos, names, quirky titles. 6) Contact — 2-column with contact info left, inquiry form right.">
    Agency</button>

    <button class="snn-bricks-quick-action-btn" data-message="Design a modern e-learning academy homepage called 'SkillForge'. Include: a bold hero with dark background, heading 'Master New Skills', subtitle about expert-led online courses, two CTA buttons (Browse Courses, Watch Demo); a 3-column feature highlights with icons (Expert Instructors, Self-Paced Learning, Certificates); a stats bar (500+ Courses, 50K Students, 4.9/5 Rating); a 3-column course cards grid with thumbnails, titles, prices; a 2-column testimonials section; and a full-width CTA banner 'Start Learning Free Today'. Use deep navy + yellow accent colors.">
    Academy</button>

    <button class="snn-bricks-quick-action-btn" data-message="Design an elegant fine-dining restaurant homepage for 'Lumiere' restaurant. Include: a dramatic full-viewport hero with dark overlay on a food photo, centered heading 'The Art of Taste' in elegant serif font, subtitle, and 'Reserve a Table' button; a 3-column highlights section (Farm to Table, Sommelier Selected, Private Dining); a 2-column about section with chef photo and story; a featured dishes section with a 3-column grid of dish cards with images, names, and prices; a 2-column testimonials with quotes; and a CTA section with reservation form. Use black, gold (#C9A84C), and deep red color scheme.">
    Restaurant</button>

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

            // ================================================================
            // Configuration & State
            // ================================================================

            const MAX_HISTORY       = snnBricksChatConfig.settings.maxHistory  || 20;
            const DEBUG_MODE        = snnBricksChatConfig.settings.debugMode   || false;
            const RECOVERY_CONFIG   = { maxRecoveryAttempts: 3, baseDelay: 2000, maxDelay: 30000, rateLimitDelay: 5000 };
            const debugLog = (...a) => { if (DEBUG_MODE) console.log('[Bricks AI]', ...a); };

            const ChatState = {
                messages: [], isOpen: false, isProcessing: false,
                abortController: null, currentSessionId: null,
                pageContext: snnBricksChatConfig.pageContext || {},
                recoveryAttempts: 0, bricksState: null, attachedImages: [],
                // Two-phase workflow
                currentHTMLPreview: null, previewMode: null, previewPaneOpen: false,
                // Global ID tracker to prevent duplicates across sections
                globalUsedIds: new Set()
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
                        s.content.splice(0, s.content.length);
                        els.forEach(el => s.content.push(el));
                        debugLog('Replaced with', els.length, 'elements');
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
                        if (position === 'prepend') {
                            [...els].reverse().forEach(el => s.content.unshift(el));
                        } else {
                            els.forEach(el => s.content.push(el));
                        }
                        debugLog('Added', els.length, 'elements', position);
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
                    }
                }, 500);
                setTimeout(() => clearInterval(iv), 10000);
            });

            // ================================================================
            // PHASE 1 — HTML / Tailwind Design Generation
            // ================================================================

            async function processWithAI(userMessage, images = []) {
                ChatState.isProcessing = true;
                showTyping();
                setAgentState('thinking');
                try {
                    const context        = buildConversationContext();
                    const userMsgContent = buildUserContent(userMessage, images);
                    const response = await callAI([
                        { role: 'system', content: buildPhase1SystemPrompt() },
                        ...context,
                        { role: 'user', content: userMsgContent }
                    ]);
                    hideTyping();
                    if (!response || !response.trim()) throw new Error('AI returned empty response.');

                    const html     = extractHTMLFromResponse(response);
                    const textPart = response.replace(/```html[\s\S]*?```/g, '').trim();

                    if (html) {
                        ChatState.currentHTMLPreview = html;
                        ChatState.previewMode        = 'html';
                        if (textPart) addMessage('assistant', textPart);
                        showHTMLPreview(html);
                        addApproveBar();
                    } else {
                        addMessage('assistant', response);
                    }
                    autoSaveConversation();
                } catch(err) {
                    hideTyping();
                    addMessage('error', 'Error: ' + err.message);
                } finally {
                    ChatState.isProcessing = false;
                    setAgentState('idle');
                }
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
                        results.push({ label: getLabel(child), html: child.outerHTML });
                    } else if (tag === 'div' && child.children.length > 0) {
                        // Capture meaningful top-level divs (e.g. ticker bars, announcement bands)
                        results.push({ label: getLabel(child), html: child.outerHTML });
                    }
                }
                if (!results.length) results.push({ label: 'Page Content', html });
                return results;
            }

            /**
             * Validate and auto-fix a Bricks JSON object before injection.
             * Fixes duplicate IDs, missing parent fields, and orphaned elements.
             */
            function validateAndFixBricksJSON(data, globalIdSet = ChatState.globalUsedIds) {
                const content = data.content;
                if (!Array.isArray(content) || !content.length) {
                    return { valid: false, data, errors: ['Empty content array'] };
                }
                const errors  = [];
                let   fixed   = false;
                const localIds = new Set();
                const idRemap = {};

                function genId() {
                    let id;
                    do { id = Math.random().toString(36).slice(2, 8); } while (localIds.has(id) || globalIdSet.has(id));
                    return id;
                }

                // Pass 1: ensure each element has a unique id (locally AND globally) and a name
                content.forEach(el => {
                    if (!el.id) {
                        el.id = genId(); localIds.add(el.id); globalIdSet.add(el.id); fixed = true;
                    } else if (localIds.has(el.id) || globalIdSet.has(el.id)) {
                        const oldId = el.id;
                        const newId = genId();
                        idRemap[el.id] = newId;
                        errors.push('Dup ID ' + oldId + '→' + newId + ' (conflict)');
                        el.id = newId; localIds.add(newId); globalIdSet.add(newId); fixed = true;
                    } else {
                        localIds.add(el.id);
                        globalIdSet.add(el.id);
                    }
                    if (!el.name)            { el.name   = 'block'; fixed = true; }
                    if (el.parent === undefined) { el.parent = 0;     fixed = true; }
                });

                // Pass 2: remap stale parent/children refs, orphan check
                content.forEach(el => {
                    if (el.parent && idRemap[el.parent]) { el.parent = idRemap[el.parent]; fixed = true; }
                    if (el.parent !== 0 && !localIds.has(el.parent)) {
                        errors.push('Orphan ' + el.id + ' (parent ' + el.parent + ')→root');
                        el.parent = 0; fixed = true;
                    }
                    if (el.children) el.children = el.children.map(c => idRemap[c] || c);
                });

                if (fixed) debugLog('JSON auto-fixed:', errors);
                return { valid: true, fixed, data, errors };
            }

            /**
             * Compile a single HTML section to Bricks JSON via AI.
             * Performs one automatic retry on parse failure.
             */
            /**
             * Compile a single HTML section to Bricks JSON via AI.
             * Performs one automatic retry on parse failure.
             */
            async function compileSingleSection(sectionHtml, sectionLabel, sectionIndex) {
                // Extract Google Fonts from HTML
                const fontMatch = sectionHtml.match(/@import\s+url\(['"]([^'"]+)['"]\)/i)  || 
                                  ChatState.currentHTMLPreview.match(/@import\s+url\(['"]([^'"]+)['"]\)/i);
                const googleFonts = fontMatch ? fontMatch[1] : '';
                
                const response = await callAI([
                    { role: 'system', content: buildPhase2SystemPrompt(sectionIndex, googleFonts) },
                    { role: 'user', content: 'Convert this ONE HTML section to Bricks Builder JSON.\nSection: "' + sectionLabel + '"\nReturn ONLY raw JSON — no markdown, no backticks, no explanation. Start with { end with }:\n\n' + sectionHtml }
                ], 0, { maxTokens: 8000 });

                let bricksData = extractBricksJSONFromResponse(response);
                if (bricksData) return bricksData;

                // One retry with stricter prompt
                const retryResp = await callAI([
                    { role: 'system', content: buildPhase2SystemPrompt(sectionIndex, googleFonts) },
                    { role: 'user', content: 'Convert to Bricks JSON:\n\n' + sectionHtml },
                    { role: 'assistant', content: response },
                    { role: 'user', content: 'Invalid JSON. Return ONLY {"content":[...]}. No markdown, no code fences. Start with { end with }.' }
                ], 0, { maxTokens: 8000 });
                return extractBricksJSONFromResponse(retryResp);
            }

            /**
             * Main Phase 2 orchestrator: parses HTML into sections, compiles each
             * one individually, and injects them into Bricks sequentially.
             */
            async function compileSectionBySection(actionType) {
                if (!ChatState.currentHTMLPreview) return;
                ChatState.isProcessing = true;
                setAgentState('compiling');

                // Reset global ID tracker for this compilation session
                ChatState.globalUsedIds.clear();

                const sections = parseHTMLIntoSections(ChatState.currentHTMLPreview);
                const total    = sections.length;
                addMessage('assistant', 'Building ' + total + ' section' + (total > 1 ? 's' : '') + ' — compiling one at a time...');

                let builtCount = 0;
                for (let i = 0; i < sections.length; i++) {
                    const { label, html } = sections[i];
                    setAgentState('compiling', 'Compiling "' + label + '" (' + (i + 1) + '/' + total + ')...');
                    showTyping();
                    try {
                        const bricksData = await compileSingleSection(html, label, i + 1);
                        hideTyping();
                        if (bricksData) {
                            const { data } = validateAndFixBricksJSON(bricksData);
                            const result   = (i === 0 && actionType === 'replace')
                                ? BricksHelper.replaceAllContent(data)
                                : BricksHelper.addSection(data, i === 0 ? actionType : 'append');
                            if (result.success) {
                                builtCount++;
                                addMessage('assistant', '✓ "' + label + '" built (' + builtCount + '/' + total + ')');
                            } else {
                                addMessage('error', '✗ "' + label + '" inject failed: ' + result.error);
                            }
                        } else {
                            addMessage('error', '✗ "' + label + '" — could not compile. Skipped.');
                        }
                    } catch(e) {
                        hideTyping();
                        addMessage('error', '✗ "' + label + '" error: ' + e.message);
                    }
                }

                ChatState.isProcessing = false;
                setAgentState('idle');

                if (builtCount > 0) {
                    addMessage('assistant', 'Done! ' + builtCount + '/' + total + ' sections built in Bricks. Scroll the canvas to review.');
                    hideHTMLPreview();
                    removeApproveBar();
                    ChatState.previewMode        = null;
                    ChatState.currentHTMLPreview = null;
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

            function buildPreviewHTML(html) {
                return '<!DOCTYPE html><html lang="en"><head>' +
                    '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
                    '<script src="https://cdn.tailwindcss.com"><\/script>' +
                    '<style>*{box-sizing:border-box}body{margin:0;padding:0}<\/style>' +
                    '</head><body>' + html + '</body></html>';
            }

            function addApproveBar() {
                removeApproveBar();
                const sections = parseHTMLIntoSections(ChatState.currentHTMLPreview || '');
                const n        = sections.length;
                const sLabel   = n === 1 ? '1 section' : n + ' sections';
                const $bar = $('<div id="snn-approve-bar" class="snn-approve-bar">').html(
                    '<span class="snn-approve-label">Preview ready — <strong>' + sLabel + '</strong> detected</span>' +
                    '<div class="snn-approve-actions">' +
                    '<select id="snn-approve-action-type" class="snn-approve-select">' +
                    '<option value="append" selected>Append</option>' +
                    '<option value="replace">Replace Page</option>' +
                    '<option value="prepend">Prepend</option>' +
                    '</select>' +
                    '<button id="snn-approve-build-btn" class="snn-approve-build-btn">&#10003; Build ' + sLabel + '</button>' +
                    '</div>'
                );
                $('#snn-bricks-chat-messages').after($bar);
                $('#snn-approve-build-btn').on('click', function() {
                    compileSectionBySection($('#snn-approve-action-type').val());
                });
            }

            function removeApproveBar() { $('#snn-approve-bar').remove(); }

            // ================================================================
            // System Prompts
            // ================================================================

            function buildPhase1SystemPrompt() {
                const basePrompt   = snnBricksChatConfig.ai.systemPrompt || '';
                const postTitle    = snnBricksChatConfig.pageContext?.details?.post_title || 'Unknown';
                const postType     = snnBricksChatConfig.pageContext?.details?.post_type  || 'page';
                const ajaxUrl      = snnBricksChatConfig.ajaxUrl;
                const cc           = BricksHelper.getCurrentContent();
                let pageSnap = '';
                if (cc && cc.elementCount > 0) {
                    const snap = (cc.elements || []).slice(0, 40).map(el => {
                        const raw = (el.settings && (el.settings.text || el.settings.content)) || '';
                        const txt = raw.replace(/<[^>]*>/g, '').trim().slice(0, 60);
                        return txt ? `  [${el.id}] ${el.name}: "${txt}"` : `  [${el.id}] ${el.name}`;
                    }).join('\n');
                    pageSnap = `\nPage currently has ${cc.elementCount} elements:\n${snap}\n`;
                }

                return basePrompt + `

=== BRICKS BUILDER AI — DESIGN PHASE ===
Currently editing: "${postTitle}" (${postType})
${pageSnap}
YOUR JOB:
When the user requests a design, layout, page or section — generate a complete, beautiful HTML mockup using:
- INLINE CSS STYLES (style="...") — NO Tailwind, NO external CSS classes
- Google Fonts (@import in <style> tag at top)
- Real content — actual headings, descriptions, CTAs (no Lorem Ipsum for headings)
- Real images via Pixabay proxy: ${ajaxUrl}?action=snn_pixabay_image&q=KEYWORDS (use different keywords for each image)

OUTPUT FORMAT:
1. Write 1–2 sentences describing the design
2. Output the complete HTML in a \`\`\`html code block

STYLING RULES (CRITICAL):
- Use INLINE style attributes on every element: <h1 style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -0.5px;">
- Include Google Fonts at top: <style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap');</style>
- Specify ALL styles explicitly: font-family, font-size, font-weight, color, line-height, letter-spacing, text-align, padding, margin, background, border-radius, etc.
- Use full CSS property names: padding: 40px 20px (not p-4)
- Colors as hex: #111827 or #ffffff
- Sizes in px: font-size: 48px, padding: 60px 0, gap: 32px
- Font families with fallbacks: 'Playfair Display', serif or 'Inter', sans-serif

DESIGN QUALITY:
- Stunning, professional color palettes matching the business type
- Responsive-ready structure (mobile will be handled by Bricks)
- Strong typography hierarchy (large bold h1, clear h2, readable body)
- Good color contrast, rounded corners, shadows, generous whitespace
- Production-ready aesthetics — not a wireframe, a real design

IMAGES:
Use the Pixabay proxy with topic-specific keywords for each image:
  Hero/banner: ${ajaxUrl}?action=snn_pixabay_image&q=TOPIC+background
  Team photos:  ${ajaxUrl}?action=snn_pixabay_image&q=portrait+professional
  Products:     ${ajaxUrl}?action=snn_pixabay_image&q=PRODUCT+photography
  Interiors:    ${ajaxUrl}?action=snn_pixabay_image&q=PLACE+interior+design

HTML STRUCTURE RULES (CRITICAL — controls how sections are compiled):
- Every distinct visual section MUST be a DIRECT child of <body> using <section>, <header>, or <footer> tags
- NEVER wrap sections inside <main> or any other container — content inside <main> is treated as ONE single section
- Use semantic structure: <section> → <div class="container"> → <div class="card"> → <h1>, <p>, <button>
- Simple class names OK for structure ("container", "card", "grid") but ALL styling must be inline
- This flat structure allows each section to be compiled independently into Bricks Builder

EXAMPLE STRUCTURE:
<style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap');</style>
<section style="background: #111827; padding: 80px 0;">
  <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 24px; display: flex; flex-direction: column; gap: 32px; align-items: center;">
    <h1 style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -0.5px;">Heading</h1>
    <p style="font-size: 18px; color: #9ca3af; line-height: 1.7; text-align: center; max-width: 700px;">Description text</p>
  </div>
</section>

WHEN NOT TO GENERATE HTML:
- User asks a question → respond in plain text only
- User says "change X to Y" (editing existing element) → explain direct Bricks edit
- User refines the preview ("make it darker / add a section") → generate FULL new replacement HTML
- Unsure → ask a quick clarifying question`;
            }

            function buildPhase2SystemPrompt(sectionIndex, googleFonts) {
                const fontContext = googleFonts ? `\nGOOGLE FONTS DETECTED:\n${googleFonts}\nUSE these font families in _typography settings.\n` : '';
                return `You are a Bricks Builder JSON compiler. You receive ONE HTML section with INLINE CSS styles and convert it to Bricks Builder JSON.

TASK: Convert the provided HTML section to Bricks Builder JSON.
- You are compiling ONE section at a time — not the whole page.
- The output must contain exactly one top-level section element with parent:0.
- Do NOT include other sections from memory or context.
- Parse ALL inline style attributes and convert them to Bricks settings.
${fontContext}
OUTPUT: Return ONLY a raw JSON object. No markdown, no backticks, no explanation.
Start with { and end with }

SCHEMA: {"content":[...elements]}

ELEMENT STRUCTURE:
{"id":"abc123","name":"type","parent":"pid_or_0","children":["id1"],"settings":{...},"label":"optional"}

IDs: 6 unique lowercase alphanumeric chars per element. Every element must have a DIFFERENT id.
IMPORTANT: Prefix all IDs with "s${sectionIndex}_" to ensure uniqueness across sections (e.g., "s${sectionIndex}_abc123").

ELEMENT TYPES & SETTINGS:

section (always parent:0, always one per output):
{"id":"s1a2b3","name":"section","parent":0,"children":["c1a2b3"],"settings":{"_padding":{"top":"80","bottom":"80"},"_background":{"color":{"hex":"#0f172a"}}},"label":"Hero"}

container (max-width wrapper OR flex/grid layout):
  column wrapper: {"name":"container","settings":{"_direction":"column","_rowGap":"24","_widthMax":"1200px","_margin":{"left":"auto","right":"auto"},"_padding":{"top":"0","right":"24","bottom":"0","left":"24"}}}
  flex row:  {"name":"container","settings":{"_direction":"row","_columnGap":"32","_alignItems":"center","_flexWrap":"wrap"}}
  css grid:  {"name":"container","settings":{"_display":"grid","_gridTemplateColumns":"1fr 1fr 1fr","_gridGap":"32"}}

block (card wrapper, nested div — padding, background, border):
{"name":"block","settings":{"_direction":"column","_rowGap":"16","_padding":{"top":"32","right":"32","bottom":"32","left":"32"},"_background":{"color":{"hex":"#ffffff"}},"_border":{"radius":{"top":"12","right":"12","bottom":"12","left":"12"},"width":{"top":"1","right":"1","bottom":"1","left":"1"},"style":"solid","color":{"hex":"#e5e7eb"}}}}

heading: {"name":"heading","settings":{"text":"Text","tag":"h1","_typography":{"font-size":"60","font-weight":"900","color":{"hex":"#ffffff"},"line-height":"1.1","text-align":"center","font-family":"Playfair Display"}}}

text-basic: {"name":"text-basic","settings":{"text":"Paragraph content here.","_typography":{"font-size":"18","line-height":"1.7","color":{"hex":"#4b5563"}}}}

button: {"name":"button","settings":{"text":"CTA Label","link":{"type":"external","url":"#"},"_background":{"color":{"hex":"#2563eb"}},"_typography":{"color":{"hex":"#ffffff"},"font-weight":"600","font-size":"16"},"_padding":{"top":"14","right":"28","bottom":"14","left":"28"},"_border":{"radius":{"top":"8","right":"8","bottom":"8","left":"8"}}}}

image: {"name":"image","settings":{"image":{"url":"https://example.com/img.jpg","size":"full"},"_aspectRatio":"16/9","_objectFit":"cover","_width":"100%","_border":{"radius":{"top":"12","right":"12","bottom":"12","left":"12"}}}}

icon: {"name":"icon","settings":{"icon":{"library":"themify","icon":"ti-star"},"_typography":{"font-size":"32","color":{"hex":"#f59e0b"}}}}

divider: {"name":"divider","settings":{"_margin":{"top":"24","bottom":"24"}}}

KEY SETTINGS REFERENCE:
_direction: "row"|"column"
_display: "grid"
_gridTemplateColumns: "1fr 1fr 1fr"
_gridGap / _columnGap / _rowGap: "32"  (strings, no px)
_justifyContent: "center"|"flex-start"|"flex-end"|"space-between"
_alignItems: "center"|"flex-start"|"flex-end"
_flexWrap: "wrap"
_width: "100%"   _widthMax: "1200px"   _minHeight: "100vh"
_padding: {"top":"40","right":"40","bottom":"40","left":"40"}
_margin: {"top":"0","right":"auto","bottom":"0","left":"auto"}
_background: {"color":{"hex":"#000000"}}
_typography: {"font-size":"20","font-weight":"700","line-height":"1.6","letter-spacing":"0.05em","text-align":"center","color":{"hex":"#ffffff"},"font-family":"Inter","text-transform":"uppercase","font-style":"italic"}
_border: {"radius":{"top":"12","right":"12","bottom":"12","left":"12"},"width":{"top":"1","right":"1","bottom":"1","left":"1"},"style":"solid","color":{"hex":"#e5e7eb"}}
_opacity: "0.8"  (string, 0–1 range)
_overflow: "hidden"

DARK SECTION BACKGROUND (bg-zinc-900, bg-gray-900, bg-slate-900):
section settings → _background: {"color":{"hex":"#111827"}}

GRADIENT BACKGROUND (bg-gradient-to-r from-blue-600 to-purple-600):
_background: {"gradient":{"type":"linear","angle":"90","stops":[{"color":{"hex":"#2563eb"},"position":"0"},{"color":{"hex":"#9333ea"},"position":"100"}]}}

BOX SHADOW (shadow-lg, shadow-xl):
_boxShadow: {"values":[{"offsetX":"0","offsetY":"10","blur":"24","spread":"-3","color":{"hex":"#000000","alpha":0.1}}]}

COL-SPAN (col-span-2 inside a 3-col grid):
On the child block/container: _gridColumn: "span 2"

RELATIVE POSITION WITH OVERFLOW HIDDEN (relative overflow-hidden):
_position: "relative"   _overflow: "hidden"

TAILWIND → BRICKS MAPPING:
LAYOUT: flex flex-col→_direction:"column" | flex/flex-row→_direction:"row" | grid grid-cols-2→_display:"grid",_gridTemplateColumns:"1fr 1fr" | grid-cols-3→"1fr 1fr 1fr" | grid-cols-4→"1fr 1fr 1fr 1fr" | items-center→_alignItems:"center" | justify-center→_justifyContent:"center" | justify-between→_justifyContent:"space-between" | flex-wrap→_flexWrap:"wrap" | col-span-2→_gridColumn:"span 2"
GAP: gap-2→"8" gap-3→"12" gap-4→"16" gap-6→"24" gap-8→"32" gap-10→"40" gap-12→"48" gap-16→"64"
SIZING: max-w-7xl→"1280px" max-w-6xl→"1152px" max-w-5xl→"1024px" max-w-4xl→"896px" max-w-3xl→"768px" max-w-2xl→"672px" max-w-xl→"576px" | min-h-screen→_minHeight:"100vh" | w-full→_width:"100%" | w-24→_width:"96px" | h-48→_height:"192px" h-64→_height:"256px" h-96→_height:"384px"
PADDING: p-2→"8" p-3→"12" p-4→"16" p-6→"24" p-8→"32" p-10→"40" p-12→"48" p-16→"64" | px-4→l/r"16" px-6→"24" px-8→"32" px-16→"64" | py-4→t/b"16" py-8→"32" py-12→"48" py-16→"64" py-20→"80" py-24→"96" py-32→"128"
MARGIN: mx-auto→left/right"auto" | mt-4→top"16" mt-6→"24" mt-8→"32" mb-4→bottom"16" mb-6→"24" mb-8→"32"
TYPOGRAPHY: text-xs→"12" text-sm→"14" text-base→"16" text-lg→"18" text-xl→"20" text-2xl→"24" text-3xl→"30" text-4xl→"36" text-5xl→"48" text-6xl→"60" text-7xl→"72" text-8xl→"96" | font-medium→"500" font-semibold→"600" font-bold→"700" font-extrabold→"800" font-black→"900" | text-center→text-align:"center" text-right→"right" | leading-none→"1" leading-tight→"1.25" leading-snug→"1.375" leading-normal→"1.5" leading-relaxed→"1.625" leading-loose→"2" | tracking-tight→"-0.025em" tracking-wide→"0.05em" tracking-wider→"0.1em" tracking-widest→"0.25em" | uppercase→text-transform:"uppercase" | italic→font-style:"italic"
COLORS: bg-white→"#ffffff" bg-black→"#000000" bg-gray-50→"#f9fafb" bg-gray-100→"#f3f4f6" bg-gray-200→"#e5e7eb" bg-gray-800→"#1f2937" bg-gray-900→"#111827" bg-slate-800→"#1e293b" bg-slate-900→"#0f172a" bg-zinc-900→"#18181b" bg-stone-100→"#f5f5f4" bg-stone-900→"#1c1917" bg-red-600→"#dc2626" bg-red-700→"#b91c1c" bg-blue-600→"#2563eb" bg-indigo-600→"#4f46e5" bg-green-500→"#22c55e" bg-yellow-400→"#facc15" bg-amber-500→"#f59e0b" bg-orange-500→"#f97316" bg-purple-600→"#9333ea" bg-pink-600→"#db2777" | text-white→"#ffffff" text-black→"#000000" text-gray-400→"#9ca3af" text-gray-500→"#6b7280" text-gray-600→"#4b5563" text-gray-700→"#374151" text-gray-900→"#111827" text-red-600→"#dc2626" text-red-700→"#b91c1c" text-blue-600→"#2563eb" text-indigo-600→"#4f46e5" text-green-600→"#16a34a" text-amber-500→"#f59e0b" text-yellow-400→"#facc15" text-orange-500→"#f97316" | bg-[#HEX] or text-[#HEX] → use that exact hex
BORDER RADIUS: rounded→"4" rounded-md→"6" rounded-lg→"8" rounded-xl→"12" rounded-2xl→"16" rounded-3xl→"24" rounded-full→"9999"
OPACITY: opacity-50→"0.5" opacity-60→"0.6" opacity-70→"0.7" opacity-80→"0.8" opacity-90→"0.9"

STRUCTURE RULES:
1. Exactly ONE section element per output, always parent:0
2. SECTION > CONTAINER (max-width + centering) > layout CONTAINER or BLOCK > leaf elements
3. BLOCK = card/wrapper with padding/background; CONTAINER = layout (flex/grid) with no background
4. Leaf elements (heading, text-basic, button, image, icon, divider) never have children
5. Section _padding: ONLY top and bottom (never set left/right on section — set those on inner container)
6. All numeric values are STRINGS without "px": "40" not "40px" not 40
7. Every element must have a unique 6-char lowercase alphanumeric id
8. parent value must exactly match the id of the actual parent element (or 0 for section)
9. Max nesting depth: section > container > block > leaf (4 levels). Never deeper.
10. Never nest section inside section`;
            }

            // ================================================================
            // Helpers
            // ================================================================

            function extractHTMLFromResponse(resp) {
                const m = resp.match(/```html\n?([\s\S]*?)\n?```/);
                return m ? m[1].trim() : null;
            }

            function extractBricksJSONFromResponse(resp) {
                const cleaned = resp.trim();
                try { const p = JSON.parse(cleaned); if (p.content && Array.isArray(p.content)) return p; } catch(e) {}
                const m = cleaned.match(/\{[\s\S]*"content"\s*:\s*\[[\s\S]*\][\s\S]*\}/);
                if (m) { try { const p = JSON.parse(m[0]); if (p.content && Array.isArray(p.content)) return p; } catch(e) {} }
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
                li.setAttribute('data-balloon', 'SNN AI Agent');
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
                $('#snn-preview-approve-btn').on('click', function() { compileAndBuild($('#snn-preview-action-type').val()); });
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
                setInterval(autoSaveConversation, 30000);
            }

            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-bricks-chat-overlay').toggle();
                if (ChatState.isOpen) $('#snn-bricks-chat-input').focus();
            }

            async function sendMessage() {
                const input = $('#snn-bricks-chat-input');
                const msg   = input.val().trim();
                const imgs  = ChatState.attachedImages;
                if ((!msg && !imgs.length) || ChatState.isProcessing) return;
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
                const body = { model: cfg.model, messages, temperature: 0.7, max_tokens: opts.maxTokens || cfg.maxTokens || 4000 };
                debugLog('AI call:', body.model, messages.length, 'messages');
                const resp = await fetch(cfg.apiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${cfg.apiKey}` },
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
                scrollToBottom();
            }

            function formatMessage(c) {
                if (typeof markdown !== 'undefined' && markdown.toHTML) { try { return markdown.toHTML(c); } catch(e) {} }
                return c.replace(/\n/g, '<br>');
            }

            function scrollToBottom() { const $m = $('#snn-bricks-chat-messages'); $m.scrollTop($m[0].scrollHeight); }

            function clearChat() {
                ChatState.messages = []; ChatState.currentSessionId = null;
                ChatState.attachedImages = []; ChatState.currentHTMLPreview = null; ChatState.previewMode = null;
                ChatState.globalUsedIds.clear(); // Reset ID tracker
                removeApproveBar(); hideHTMLPreview(); renderImagePreviews();
                $('#snn-bricks-chat-messages').html('<div class="snn-bricks-chat-welcome"><h3>Conversation cleared</h3><p>Start a new conversation.</p></div>');
                $('.snn-bricks-chat-quick-actions').show();
            }

            function setAgentState(state, detail = '') {
                const $t = $('#snn-bricks-chat-state-text');
                const labels = { thinking: 'Thinking...', compiling: detail || 'Compiling to Bricks...', recovering: detail || 'Recovering...', error: 'Error', idle: '' };
                const lbl = labels[state] || '';
                lbl ? $t.text(lbl).show() : $t.hide();
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
.snn-bricks-chat-send { width: 42px; height: 70px; background: #161a1d; border: none; border-radius: 8px; color: #fff; cursor: pointer; display:flex; align-items: center; justify-content: center; flex-shrink: 0; }
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
/* Design Preview Pane — left of chat overlay */
.snn-bricks-preview-pane { position: fixed; top: 0; left: 0; right: 400px; bottom: 0; z-index: 999998; background: #fff; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.12); }
.snn-bricks-preview-header { background: #1e293b; color: #fff; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-shrink: 0; }
.snn-bricks-preview-title { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; white-space: nowrap; }
.snn-bricks-preview-badge { background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
.snn-bricks-preview-controls { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.snn-preview-action-select { padding: 5px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff; font-size: 12px; cursor: pointer; }
.snn-preview-action-select option { background: #1e293b; color: #fff; }
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
.snn-approve-select { padding: 4px 8px; border-radius: 6px; border: 1px solid #d1fae5; background: #fff; font-size: 12px; color: #374151; cursor: pointer; }
.snn-approve-build-btn { background: #16a34a; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.snn-approve-build-btn:hover { background: #15803d; }
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
add_action( 'wp_ajax_snn_pixabay_image',        'snn_pixabay_image_proxy_handler' );
add_action( 'wp_ajax_nopriv_snn_pixabay_image', 'snn_pixabay_image_proxy_handler' );

function snn_pixabay_image_proxy_handler() {
    $q       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : 'nature';
    $api_key = get_option( 'snn_pixabay_api_key', '992766-a3c727d4146f5ede8718f2d24' );

    // Build Unsplash fallback URL using the search keywords
    $unsplash_keywords = urlencode( str_replace( '+', ',', $q ) );
    $unsplash_fallback = 'https://source.unsplash.com/random/1280x720/?' . $unsplash_keywords;

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
            wp_redirect( $unsplash_fallback );
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

    // Fallback: Unsplash random image with keywords on any other failure
    wp_redirect( $unsplash_fallback );
    exit;
}
