<?php
/**
 * SNN AI Chat for Bricks Builder – Dual-Core Architecture
 *
 * File: ai-agent-and-chat-bricks.php
 *
 * Architecture:
 *   Phase 1  – Designer Agent  : Creates a creative layout brief (natural language).
 *   Phase 2  – Compiler Agent  : Translates the brief into strict Bricks JSON using
 *                                 _cssCustom for ALL visual styling.
 *   Phase 3  – Injector        : Flattens the AI tree into Bricks flat-array format
 *                                 and injects directly into the Vue reactive state.
 *
 * No external "ability" endpoints are used for design generation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Chat_Overlay {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_snn_bricks_get_schema', array( $this, 'ajax_get_bricks_schema' ) );

        if ( ! $this->is_bricks_builder_active() ) {
            return;
        }

        $main_chat = SNN_Chat_Overlay::get_instance();
        if ( ! $main_chat->is_enabled() ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
        add_action( 'wp_footer',          array( $this, 'render_overlay' ),  999 );
    }

    private function is_bricks_builder_active() {
        return ! is_admin() && isset( $_GET['bricks'] ) && $_GET['bricks'] === 'run';
    }

    /* ---------------------------------------------------------------
     * AJAX – Schema (global colors)
     * ------------------------------------------------------------- */

    public function ajax_get_bricks_schema() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $raw_palette   = get_option( 'bricks_color_palette', array() );
        $global_colors = array();

        foreach ( $raw_palette as $palette ) {
            $colors = isset( $palette['colors'] ) ? $palette['colors'] : $palette;
            if ( ! is_array( $colors ) ) {
                continue;
            }
            foreach ( $colors as $color ) {
                $hex = isset( $color['raw'] ) ? $color['raw'] : '';
                if ( ! $hex && isset( $color['light'] ) ) {
                    $hex = $color['light'];
                }
                if ( $hex && strpos( $hex, '#' ) === 0 ) {
                    $label           = isset( $color['name'] ) ? $color['name'] : $hex;
                    $global_colors[] = array( 'hex' => $hex, 'name' => $label );
                }
            }
        }

        $post_id  = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $elements = array();
        if ( $post_id ) {
            $raw = get_post_meta( $post_id, '_bricks_page_content_2', true );
            if ( is_array( $raw ) ) {
                $elements = array_map( function ( $el ) {
                    return array(
                        'id'     => $el['id']     ?? '',
                        'name'   => $el['name']   ?? '',
                        'parent' => $el['parent'] ?? 0,
                        'text'   => isset( $el['settings']['text'] )
                                      ? substr( wp_strip_all_tags( $el['settings']['text'] ), 0, 60 )
                                      : '',
                    );
                }, $raw );
            }
        }

        $element_types = array();
        if ( class_exists( '\Bricks\Elements' ) && is_array( \Bricks\Elements::$elements ) ) {
            $element_types = array_values( array_keys( \Bricks\Elements::$elements ) );
        }

        wp_send_json_success( array(
            'globalColors'       => $global_colors,
            'elements'           => $elements,
            'registeredElements' => $element_types,
        ) );
    }

    /* ---------------------------------------------------------------
     * Assets
     * ------------------------------------------------------------- */

    public function enqueue_assets() {
        wp_enqueue_script(
            'markdown-js',
            get_stylesheet_directory_uri() . '/assets/js/markdown.min.js',
            array(),
            '0.5.0',
            true
        );

        $main_chat = SNN_Chat_Overlay::get_instance();
        $ai_config = function_exists( 'snn_get_ai_api_config' ) ? snn_get_ai_api_config() : array();
        $ai_config['systemPrompt'] = $main_chat->get_system_prompt();
        $ai_config['maxTokens']    = $main_chat->get_token_count();

        global $post;

        wp_localize_script( 'jquery', 'snnBricksChatConfig', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'snn_ai_agent_nonce' ),
            'restNonce'     => wp_create_nonce( 'wp_rest' ),
            'currentUserId' => get_current_user_id(),
            'userName'      => wp_get_current_user()->display_name,
            'postId'        => $post ? $post->ID : 0,
            'postTitle'     => $post ? $post->post_title : '',
            'ai'            => $ai_config,
            'settings'      => array(
                'debugMode'  => $main_chat->is_debug_enabled(),
                'maxRetries' => $main_chat->get_max_retries(),
                'maxHistory' => $main_chat->get_max_history(),
            ),
        ) );

        wp_add_inline_style( 'bricks-builder', $this->get_inline_css() );
    }

    /* ---------------------------------------------------------------
     * Overlay HTML
     * ------------------------------------------------------------- */

    public function render_overlay() {
        $main_chat = SNN_Chat_Overlay::get_instance();
        ?>
        <div id="snn-bricks-chat-overlay" class="snn-bricks-chat-overlay" style="display:none;">
            <div class="snn-bricks-chat-container">

                <!-- Header -->
                <div class="snn-bricks-chat-header">
                    <div class="snn-bricks-chat-title">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>SNN AI Agent</span>
                        <span class="snn-bricks-state-badge" id="snn-bricks-state-badge"></span>
                    </div>
                    <div class="snn-bricks-chat-controls">
                        <button class="snn-bricks-chat-btn snn-bricks-chat-new" id="snn-bricks-chat-new-btn" title="New chat">
                            <span style="font-size:20px;line-height:1;">+</span>
                        </button>
                        <button class="snn-bricks-chat-btn" id="snn-bricks-chat-history-btn" title="History">
                            <span class="dashicons dashicons-backup"></span>
                        </button>
                        <button class="snn-bricks-chat-btn snn-bricks-chat-close" title="Close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- History Dropdown -->
                <div class="snn-bricks-chat-history-dropdown" id="snn-bricks-chat-history-dropdown" style="display:none;">
                    <div class="snn-bricks-history-header">
                        <strong><?php esc_html_e( 'Chat History', 'snn' ); ?></strong>
                        <button class="snn-bricks-history-close" id="snn-bricks-history-close">x</button>
                    </div>
                    <div id="snn-bricks-history-list"></div>
                </div>

                <?php if ( ! $main_chat->is_ai_globally_enabled() ) : ?>
                <div class="snn-bricks-chat-messages" id="snn-bricks-chat-messages">
                    <div class="snn-bricks-chat-welcome">
                        <div style="font-size:32px;">!</div>
                        <h3><?php esc_html_e( 'AI Features Disabled', 'snn' ); ?></h3>
                        <p><?php esc_html_e( 'Enable AI features in the settings to use this assistant.', 'snn' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-ai-settings' ) ); ?>" target="_blank">
                            <?php esc_html_e( 'Open AI Settings', 'snn' ); ?> ->
                        </a>
                    </div>
                </div>
                <div class="snn-bricks-chat-input-container">
                    <textarea id="snn-bricks-chat-input" class="snn-bricks-chat-input"
                        placeholder="<?php esc_attr_e( 'AI features are disabled...', 'snn' ); ?>"
                        rows="1" disabled></textarea>
                    <button id="snn-bricks-chat-send" class="snn-bricks-chat-send" disabled>
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <?php else : ?>

                <!-- Messages -->
                <div class="snn-bricks-chat-messages" id="snn-bricks-chat-messages">
                    <div class="snn-bricks-chat-welcome">
                        <h3><?php printf( esc_html__( 'Hello, %s!', 'snn' ), esc_html( wp_get_current_user()->display_name ) ); ?></h3>
                        <p><?php esc_html_e( 'Describe the page or section you want to create.', 'snn' ); ?></p>
                        <p><small><?php esc_html_e( 'Triple-Core: Designer → Art Director → Compiler → Inject', 'snn' ); ?></small></p>
                    </div>
                </div>

                <!-- Typing indicator -->
                <div class="snn-bricks-chat-typing" style="display:none;">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>

                <!-- State text -->
                <div class="snn-bricks-chat-state-text" id="snn-bricks-chat-state-text" style="display:none;"></div>

                <!-- Quick actions -->
                <div class="snn-bricks-chat-quick-actions" id="snn-bricks-quick-actions">
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Replace the entire page with a modern SaaS hero landing page. Dark background (#0d0d0d), bold white headline 'The Future of Work, Simplified', sub-text about productivity software, a primary CTA button 'Start Free Trial' in vibrant purple (#7c3aed), and a secondary 'See Demo' outlined button. Below the hero add a statistics bar showing 50k+ Users, 99.9% Uptime, 4.9 Star Rating. Then add a 3-column features section on white background with icon cards.">SaaS App</button>
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Replace the entire page with an artisan bakery homepage. Warm cream background (#fdf6ec), serif fonts, full-width hero with headline 'Baked Daily With Love' in dark brown (#3d2106), a paragraph about fresh sourdough, and an orange CTA 'Order Now'. Add a 2-column about section and a 3-column products section with product cards.">Bakery</button>
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Replace the entire page with a minimal creative agency portfolio. Off-white background (#f8f7f4), black text. Hero: 2-column 60/40 split, left has oversized 80px heading 'We Build Brands', description, black pill button 'View Work'. Right has image placeholder. Add a 4-column logo bar. Add a 2-column featured work grid with 4 project cards. End with a minimal black CTA section.">Agency</button>
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Replace the entire page with a luxury restaurant website. Black background (#0a0a0a), gold accent color (#c9a84c), elegant serif headings. Full-height hero: centered headline 'The Art of Fine Dining', gold divider, 'Reserve a Table' gold bordered button. A 3-column section: Ambience, Cuisine, Service with gold accents. An elegant menu section with dish names and gold prices. Reservation CTA.">Restaurant</button>
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Append a professional testimonials section. White background, centered heading 'What Our Clients Say'. A 3-column grid of testimonial cards: large quote mark in light purple, testimonial text, client name in bold, role in small gray. Cards with subtle shadow, white background, 12px border radius.">Testimonials</button>
                    <button class="snn-bricks-quick-action-btn"
                        data-message="Append a modern pricing section. Light gray background (#f3f4f6), centered heading 'Simple, Transparent Pricing'. 3-column pricing cards: Free ($0/mo), Pro ($29/mo highlighted in purple), Enterprise ($99/mo). Each card has plan name, price, 4-5 feature list items, and a CTA button. The middle Pro card has a border and slight elevation.">Pricing</button>
                </div>

                <!-- Image attachment -->
                <input type="file" id="snn-bricks-chat-file-input" accept="image/*" style="display:none;" />

                <!-- Input -->
                <div class="snn-bricks-chat-input-container">
                    <button id="snn-bricks-chat-attach-btn" class="snn-bricks-chat-attach-btn" title="Attach image">
                        <span class="dashicons dashicons-paperclip"></span>
                    </button>
                    <div class="snn-bricks-chat-input-wrapper">
                        <div id="snn-bricks-chat-image-preview" class="snn-bricks-chat-image-preview" style="display:none;"></div>
                        <textarea id="snn-bricks-chat-input" class="snn-bricks-chat-input"
                            placeholder="Describe what you want to create or paste a screenshot..."
                            rows="1"></textarea>
                    </div>
                    <button id="snn-bricks-chat-send" class="snn-bricks-chat-send" title="Send">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <script>
        (function($) {
            'use strict';

            /* =================================================================
             * CONSTANTS & STATE
             * =============================================================== */

            const AgentState = {
                IDLE:          'idle',
                DESIGNING:     'designing',
                ART_DIRECTING: 'art-directing',
                COMPILING:     'compiling',
                INJECTING:     'injecting',
                RETRYING:      'retrying',
                DONE:          'done',
                ERROR:         'error'
            };

            const MAX_RETRIES = snnBricksChatConfig.settings.maxRetries || 3;
            const MAX_HISTORY = snnBricksChatConfig.settings.maxHistory || 20;
            const DEBUG_MODE  = snnBricksChatConfig.settings.debugMode  || false;

            const debugLog = (...args) => { if (DEBUG_MODE) console.log('[BricksAI]', ...args); };

            const DEFAULT_ELEMENT_TYPES = [
                'section', 'container', 'block', 'div',
                'heading', 'text-basic', 'text', 'rich-text',
                'button', 'image', 'icon', 'icon-box',
                'divider', 'spacer', 'accordion', 'tabs',
                'slider', 'form', 'video', 'nav', 'code', 'svg'
            ];

            const ChatState = {
                messages:         [],
                isOpen:           false,
                isProcessing:     false,
                currentState:     AgentState.IDLE,
                currentSessionId: null,
                attachedImages:   [],
                bricksVueState:   null,
                bricksSchema:     null,
                bricksElements:   [],    // registered element type names
                bricksSchemaDef:  null,  // JSON Schema built from live registry
            };

            /* =================================================================
             * BRICKS VUE STATE HELPER
             * =============================================================== */

            const BricksHelper = {

                getVueState() {
                    if (ChatState.bricksVueState) return ChatState.bricksVueState;
                    try {
                        const app = document.querySelector('[data-v-app]');
                        if (!app || !app.__vue_app__) return null;
                        ChatState.bricksVueState = app.__vue_app__.config.globalProperties.$_state;
                        return ChatState.bricksVueState;
                    } catch (e) { return null; }
                },

                isAvailable() {
                    const s = this.getVueState();
                    return s && Array.isArray(s.content);
                },

                getCurrentContentSummary() {
                    const s = this.getVueState();
                    if (!s || !s.content || !s.content.length) return 'Empty page - no elements yet.';

                    const els   = s.content;
                    const roots = els.filter(e => !e.parent || e.parent === 0 || e.parent === '0');
                    let   out   = `Page has ${els.length} elements:\n`;

                    const describe = (el, depth) => {
                        const pad  = '  '.repeat(depth);
                        let   line = `${pad}[${el.name}]`;
                        const st   = el.settings || {};
                        if (st.text)    line += ' "' + String(st.text).replace(/<[^>]+>/g,'').substring(0,50) + '"';
                        else if (st.content) line += ' "' + String(st.content).replace(/<[^>]+>/g,'').substring(0,50) + '"';
                        if (st.tag)     line += ' <' + st.tag + '>';
                        out += line + '\n';
                        (el.children || []).forEach(cid => {
                            const child = els.find(e => e.id === cid);
                            if (child) describe(child, depth + 1);
                        });
                    };

                    roots.forEach(r => describe(r, 0));
                    return out;
                },

                generateId() {
                    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
                    let id = '';
                    for (let i = 0; i < 6; i++) id += chars[Math.floor(Math.random() * chars.length)];
                    return id;
                },

                flattenTree(nodes, parentId) {
                    parentId = parentId || 0;
                    const flat = [];

                    const self = this;
                    function processNode(node, pId) {
                        const id = self.generateId();
                        const el = {
                            id:       id,
                            name:     node.type || node.name || 'div',
                            parent:   pId,
                            children: [],
                            settings: node.settings || {}
                        };
                        flat.push(el);
                        if (Array.isArray(node.children) && node.children.length) {
                            node.children.forEach(function(child) {
                                const cid = processNode(child, id);
                                el.children.push(cid);
                            });
                        }
                        return id;
                    }

                    (nodes || []).forEach(function(node) { processNode(node, parentId); });
                    return flat;
                },

                applyToVueState(flatElements, action) {
                    action = action || 'append';
                    const s = this.getVueState();
                    if (!s || !Array.isArray(s.content)) {
                        return { success: false, error: 'Bricks Vue state unavailable.' };
                    }
                    try {
                        if (action === 'replace') {
                            s.content.splice(0, s.content.length);
                            flatElements.forEach(function(el) { s.content.push(el); });
                        } else if (action === 'prepend') {
                            for (let i = flatElements.length - 1; i >= 0; i--) {
                                s.content.unshift(flatElements[i]);
                            }
                        } else {
                            flatElements.forEach(function(el) { s.content.push(el); });
                        }
                        debugLog('Injected', flatElements.length, 'elements (' + action + ')');
                        return { success: true, count: flatElements.length };
                    } catch (e) {
                        return { success: false, error: e.message };
                    }
                }
            };

            /* =================================================================
             * INIT
             * =============================================================== */

            $(document).ready(function() {
                const waitInterval = setInterval(function() {
                    if (BricksHelper.isAvailable()) {
                        clearInterval(waitInterval);
                        debugLog('Bricks state ready');
                        initChat();
                        addToolbarButton();
                        loadSchema();
                    }
                }, 500);
                setTimeout(function() { clearInterval(waitInterval); }, 15000);
            });

            /* =================================================================
             * SCHEMA LOADER – reads directly from Bricks Vue reactive state
             * =============================================================== */

            function loadSchema() {
                const s = BricksHelper.getVueState();
                if (!s) { debugLog('loadSchema: Vue state not ready'); return; }

                /* --- Colors -------------------------------------------- */
                const rawPalette  = s.colorPalette || [];
                const globalColors = [];
                rawPalette.forEach(function(palette) {
                    const colors = palette.colors || palette;
                    if (!Array.isArray(colors)) return;
                    colors.forEach(function(c) {
                        // Only include top-level named colors that have a raw var()
                        if (c.raw && c.light) {
                            globalColors.push({ id: c.id, raw: c.raw, light: c.light, name: c.name || c.raw });
                        }
                    });
                });

                /* --- Size variables ------------------------------------ */
                const rawVars     = s.globalVariables || [];
                const sizeVars    = [];
                rawVars.forEach(function(v) {
                    if (v.name && v.value) {
                        sizeVars.push({ name: v.name, var: 'var(--' + v.name + ')', value: v.value });
                    }
                });

                ChatState.bricksSchema = { globalColors: globalColors, sizeVars: sizeVars };
                debugLog('Schema loaded from Vue state – colors:', globalColors.length, '| sizeVars:', sizeVars.length);

                loadElementTypes(s);
            }

            /* =================================================================
             * ELEMENT TYPE DISCOVERY
             * =============================================================== */

            function loadElementTypes(vueState) {
                var elementTypes = null;

                // Source 1: Bricks Vue reactive state (s.elements keyed by element name)
                if (vueState && vueState.elements && typeof vueState.elements === 'object') {
                    var keys = Object.keys(vueState.elements).filter(function(k) { return k.length > 0; });
                    if (keys.length > 0) {
                        elementTypes = keys;
                        debugLog('Element types from Vue state:', keys.length);
                    }
                }

                // Source 2: window.bricksData (Bricks global JS object)
                if (!elementTypes && window.bricksData && window.bricksData.elements) {
                    var bkeys = Object.keys(window.bricksData.elements).filter(function(k) { return k.length > 0; });
                    if (bkeys.length > 0) {
                        elementTypes = bkeys;
                        debugLog('Element types from bricksData:', bkeys.length);
                    }
                }

                if (elementTypes) {
                    ChatState.bricksElements  = elementTypes;
                    ChatState.bricksSchemaDef = buildBricksSchema(elementTypes);
                } else {
                    // Source 3: PHP AJAX (Bricks PHP registry via \Bricks\Elements::$elements)
                    loadElementTypesViaAjax();
                }
            }

            function loadElementTypesViaAjax() {
                $.ajax({
                    url:  snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action:  'snn_bricks_get_schema',
                        nonce:   snnBricksChatConfig.nonce,
                        post_id: snnBricksChatConfig.postId
                    },
                    success: function(r) {
                        var types = (r.success && Array.isArray(r.data.registeredElements) && r.data.registeredElements.length)
                            ? r.data.registeredElements
                            : DEFAULT_ELEMENT_TYPES;
                        ChatState.bricksElements  = types;
                        ChatState.bricksSchemaDef = buildBricksSchema(types);
                        debugLog('Element types via AJAX:', types.length);
                    },
                    error: function() {
                        ChatState.bricksElements  = DEFAULT_ELEMENT_TYPES;
                        ChatState.bricksSchemaDef = buildBricksSchema(DEFAULT_ELEMENT_TYPES);
                        debugLog('Element types: AJAX failed, using hardcoded fallback');
                    }
                });
            }

            function buildBricksSchema(elementTypes) {
                return {
                    type: 'object',
                    required: ['action_type', 'sections'],
                    properties: {
                        action_type: {
                            type: 'string',
                            enum: ['replace', 'append', 'prepend'],
                            description: 'replace clears the page, append adds after, prepend adds before'
                        },
                        sections: {
                            type: 'array',
                            description: 'Top-level Bricks element nodes. Each node can contain nested children.',
                            items: {
                                type: 'object',
                                required: ['type', 'settings', 'children'],
                                properties: {
                                    type: {
                                        type: 'string',
                                        enum: elementTypes,
                                        description: 'Registered Bricks element type name'
                                    },
                                    settings: {
                                        type: 'object',
                                        description: 'Native Bricks settings keys (_padding, _typography, _background, etc). Append :hover/:mobile_landscape for state variants.'
                                    },
                                    children: {
                                        type: 'array',
                                        description: 'Nested child element nodes (same structure, recursively)',
                                        items: { type: 'object' }
                                    }
                                }
                            }
                        }
                    }
                };
            }

            /* =================================================================
             * TOOLBAR BUTTON
             * =============================================================== */

            function addToolbarButton() {
                const selectors = [
                    '.bricks-toolbar ul.end',
                    'ul.group-wrapper.end',
                    '.group-wrapper.end'
                ];

                function tryInsert() {
                    for (let i = 0; i < selectors.length; i++) {
                        const tb = document.querySelector(selectors[i]);
                        if (tb) { createToolbarButton(tb); return true; }
                    }
                    return false;
                }

                if (tryInsert()) return;

                const obs = new MutationObserver(function() {
                    if (tryInsert()) obs.disconnect();
                });
                obs.observe(document.body, { childList: true, subtree: true });
                setTimeout(function() { obs.disconnect(); }, 15000);
            }

            function createToolbarButton(toolbar) {
                if (document.querySelector('.snn-bricks-ai-toggle')) return;

                const li = document.createElement('li');
                li.className = 'snn-bricks-ai-toggle';
                li.setAttribute('data-balloon', 'SNN AI Agent');
                li.setAttribute('data-balloon-pos', 'bottom');
                li.innerHTML = '<span class="snn-ai-icon" style="font-size:25px;background:linear-gradient(45deg,#7c3aed,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;cursor:pointer;line-height:1.2;display:inline-block;">&#10022;</span>';
                li.addEventListener('click', function(e) { e.preventDefault(); toggleChat(); });

                if (toolbar.lastElementChild) {
                    toolbar.insertBefore(li, toolbar.lastElementChild);
                } else {
                    toolbar.appendChild(li);
                }
            }

            /* =================================================================
             * CHAT INIT
             * =============================================================== */

            function initChat() {
                $('.snn-bricks-chat-close').on('click', function(e) { e.preventDefault(); toggleChat(); });
                $('#snn-bricks-chat-new-btn').on('click', clearChat);
                $('#snn-bricks-chat-history-btn').on('click', toggleHistoryDropdown);
                $('#snn-bricks-history-close').on('click', function() { $('#snn-bricks-chat-history-dropdown').hide(); });
                $('#snn-bricks-chat-send').on('click', sendMessage);

                $('#snn-bricks-chat-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
                }).on('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                }).on('paste', function(e) { handlePaste(e.originalEvent); });

                $('.snn-bricks-quick-action-btn').on('click', function() {
                    $('#snn-bricks-chat-input').val($(this).data('message'));
                    sendMessage();
                });

                $('#snn-bricks-chat-attach-btn').on('click', function() { $('#snn-bricks-chat-file-input').click(); });
                $('#snn-bricks-chat-file-input').on('change', function(e) { handleFileSelect(e.target.files); });

                setInterval(autoSaveConversation, 30000);
            }

            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-bricks-chat-overlay').toggle();
                if (ChatState.isOpen) $('#snn-bricks-chat-input').focus();
            }

            /* =================================================================
             * IMAGE ATTACHMENT
             * =============================================================== */

            async function handleFileSelect(files) {
                if (!files || !files.length) return;
                for (let i = 0; i < files.length; i++) {
                    const f = files[i];
                    if (!f.type.startsWith('image/')) continue;
                    try {
                        const b64 = await fileToBase64(f);
                        addImageAttachment(b64, f.name);
                    } catch(e) { console.error('Image error', e); }
                }
                $('#snn-bricks-chat-file-input').val('');
            }

            async function handlePaste(e) {
                const items = e.clipboardData ? e.clipboardData.items : null;
                if (!items) return;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.startsWith('image/')) {
                        e.preventDefault();
                        const file = items[i].getAsFile();
                        if (file) {
                            const b64 = await fileToBase64(file);
                            addImageAttachment(b64, 'pasted-image.png');
                        }
                    }
                }
            }

            function fileToBase64(file) {
                return new Promise(function(res, rej) {
                    const reader = new FileReader();
                    reader.onload  = function() { res(reader.result); };
                    reader.onerror = rej;
                    reader.readAsDataURL(file);
                });
            }

            function addImageAttachment(b64, name) {
                const id = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                ChatState.attachedImages.push({ id: id, data: b64, fileName: name });
                renderImagePreviews();
            }

            function removeImageAttachment(id) {
                ChatState.attachedImages = ChatState.attachedImages.filter(function(i) { return i.id !== id; });
                renderImagePreviews();
            }

            function renderImagePreviews() {
                const $p = $('#snn-bricks-chat-image-preview').empty();
                if (!ChatState.attachedImages.length) { $p.hide(); return; }
                $p.show();
                ChatState.attachedImages.forEach(function(img) {
                    const $wrap = $('<div>').addClass('snn-image-preview-item');
                    $wrap.append($('<img>').attr({ src: img.data, alt: img.fileName }));
                    const $rm = $('<button>').addClass('snn-image-preview-remove').html('x');
                    const imgId = img.id;
                    $rm.on('click', function() { removeImageAttachment(imgId); });
                    $p.append($wrap.append($rm));
                });
            }

            /* =================================================================
             * SEND MESSAGE
             * =============================================================== */

            async function sendMessage() {
                const $input    = $('#snn-bricks-chat-input');
                const message   = $input.val().trim();
                const hasImages = ChatState.attachedImages.length > 0;

                if ((!message && !hasImages) || ChatState.isProcessing) return;

                addMessage('user', message || '(image attached)', ChatState.attachedImages.slice());

                const images = ChatState.attachedImages.slice();
                $input.val('').css('height', 'auto');
                ChatState.attachedImages = [];
                renderImagePreviews();
                $('#snn-bricks-quick-actions').hide();

                await processWithDualCoreAI(message, images);
            }

            /* =================================================================
             * DUAL-CORE AI PIPELINE
             * =============================================================== */

            async function processWithDualCoreAI(userMessage, images) {
                images = images || [];
                ChatState.isProcessing = true;
                showTyping();

                try {

                    /* ---- PHASE 1: DESIGNER -------------------------------- */
                    setAgentState(AgentState.DESIGNING);
                    debugLog('Phase 1: Designer...');

                    const designerMsgs = buildDesignerMessages(userMessage, images);
                    const blueprint    = await callAI(designerMsgs, 'designer');

                    debugLog('Blueprint received:', blueprint.substring(0, 150));

                    /* ---- PHASE 1.5: ART DIRECTOR -------------------------- */
                    setAgentState(AgentState.ART_DIRECTING);
                    debugLog('Phase 1.5: Art Director...');

                    const artDirectorMsgs   = buildArtDirectorMessages(blueprint);
                    const enrichedBlueprint = await callAI(artDirectorMsgs, 'artdirector');

                    debugLog('Art-directed blueprint:', enrichedBlueprint.substring(0, 150));
                    addMessage('assistant', '**Design Blueprint (Art-Directed):**\n\n' + enrichedBlueprint);

                    /* ---- PHASE 2: COMPILER -------------------------------- */
                    setAgentState(AgentState.COMPILING);
                    debugLog('Phase 2: Compiler...');

                    const compilerMsgs  = buildCompilerMessages(enrichedBlueprint, userMessage);
                    let compilerResp    = await callAI(compilerMsgs, 'compiler');

                    /* ---- PHASE 3: INJECT (with retry) --------------------- */
                    setAgentState(AgentState.INJECTING);

                    let injected  = false;
                    let retries   = 0;
                    let lastError = '';

                    while (!injected && retries <= MAX_RETRIES) {
                        const parsed = parseCompilerOutput(compilerResp);

                        if (!parsed.error) {
                            const flat   = BricksHelper.flattenTree(parsed.sections, 0);
                            const result = BricksHelper.applyToVueState(flat, parsed.action_type || 'append');
                            if (result.success) {
                                injected = true;
                                hideTyping();
                                setAgentState(AgentState.DONE);
                                addMessage('assistant',
                                    '&#10003; Done! Injected **' + flat.length + ' elements** (' +
                                    (parsed.action_type || 'append') + ') into the canvas.');
                                autoSaveConversation();
                                break;
                            }
                            lastError = result.error || 'Injection failed.';
                        } else {
                            lastError = parsed.error;
                        }

                        if (retries < MAX_RETRIES) {
                            retries++;
                            setAgentState(AgentState.RETRYING);
                            debugLog('Retry ' + retries + '/' + MAX_RETRIES + ': ' + lastError);
                            showTyping();

                            const fixMsgs = [].concat(compilerMsgs, [
                                { role: 'assistant', content: compilerResp },
                                { role: 'user', content:
                                    'The previous JSON failed: "' + lastError + '"\n' +
                                    'Fix ONLY that issue. Re-output the complete corrected JSON in a ```json block.' }
                            ]);
                            compilerResp = await callAI(fixMsgs, 'compiler');
                        } else {
                            break;
                        }
                    }

                    if (!injected) {
                        hideTyping();
                        setAgentState(AgentState.ERROR);
                        addMessage('assistant',
                            '&#10007; Failed after ' + retries + ' retries. Last error: ' + lastError +
                            '\n\nTry rephrasing your request.');
                    }

                } catch (err) {
                    hideTyping();
                    setAgentState(AgentState.ERROR);
                    console.error('[BricksAI]', err);
                    addMessage('assistant', '&#10007; Error: ' + err.message);
                } finally {
                    ChatState.isProcessing = false;
                }
            }

            /* =================================================================
             * DESIGNER MESSAGES
             * =============================================================== */

            function buildDesignerMessages(userMessage, images) {
                const schema  = ChatState.bricksSchema || {};
                const colors  = schema.globalColors || [];
                const sizes   = schema.sizeVars    || [];

                /* Show each var name alongside its resolved value so the AI can judge visually */
                const colorBlock = colors.length
                    ? colors.map(function(c) {
                        return (c.raw || '') + '  =  ' + (c.light || '');
                      }).join('\n')
                    : null;

                const sizeBlock = sizes.length
                    ? sizes.map(function(v) { return v.var + '  =  ' + v.value; }).join('\n')
                    : null;

                const hasPalette = !!colorBlock;
                const hasSizes   = !!sizeBlock;

                const pageSummary = BricksHelper.getCurrentContentSummary();

                const systemPrompt =
                    'You are a senior UI/UX designer for WordPress / Bricks Builder pages.\n\n' +
                    'Your job: write a DESIGN BRIEF that the next AI (a JSON compiler) will use to build the page.\n\n' +

                    ( hasPalette
                        ? '╔══════════════════════════════════════════════╗\n' +
                          '  THIS SITE\'S COLOR PALETTE — YOU MUST USE THESE\n' +
                          '╚══════════════════════════════════════════════╝\n' +
                          colorBlock + '\n\n' +
                          '⚠️  MANDATORY COLOR RULE:\n' +
                          'Every color you mention in the brief MUST be written as the var() name above.\n' +
                          'NEVER write a hex code like #ffffff or #0d0d0d when the palette is available.\n' +
                          'Example: write "background: var(--c1)" NOT "background: #0a0a0a"\n' +
                          'Example: write "heading color: var(--c3)" NOT "heading color: white"\n' +
                          'Example: write "accent button: var(--c2)" NOT "button color: #7c3aed"\n\n'
                        : 'No color palette found – use descriptive color names (dark, white, accent-blue, etc.).\n\n'
                    ) +

                    ( hasSizes
                        ? '╔══════════════════════════════════════════════╗\n' +
                          '  THIS SITE\'S SPACING / SIZE VARIABLES — USE THESE\n' +
                          '╚══════════════════════════════════════════════╝\n' +
                          sizeBlock + '\n\n' +
                          '⚠️  MANDATORY SIZE RULE:\n' +
                          'Every padding, gap, font-size, and spacing value MUST be written as the var() name.\n' +
                          'NEVER write "80px" or "2rem" when a matching variable exists.\n' +
                          'Example: section padding → "var(--size-100)" NOT "80px"\n' +
                          'Example: h1 font-size → "var(--size-50)" NOT "48px"\n' +
                          'Example: card gap → "var(--size-24)" NOT "24px"\n\n'
                        : 'No size variables found – use px values.\n\n'
                    ) +

                    'CURRENT PAGE STATE:\n' + pageSummary + '\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'BRIEF STRUCTURE — cover all of these:\n' +
                    '══════════════════════════════════════════════\n' +
                    '1. Sections: how many, each section\'s purpose\n' +
                    '2. Each section\'s background (use var() name), layout (flex col / 2-col grid / etc.)\n' +
                    '3. Typography: heading tag (h1/h2), font-size (use var()), font-weight, color (use var())\n' +
                    '4. Full placeholder copy for every heading, paragraph, and button label\n' +
                    '5. Images: Unsplash Source API with mood-matching keywords — format: https://source.unsplash.com/WIDTHxHEIGHT/?keyword1,keyword2\n' +
                    '   Example dark/tech: https://source.unsplash.com/800x600/?abstract,dark,texture\n' +
                    '   Example minimal:   https://source.unsplash.com/1200x600/?modern,architecture,minimalist\n' +
                    '   Example luxury:    https://source.unsplash.com/800x600/?luxury,gold,interior\n' +
                    '   Always choose keywords that match the section\'s design mood and colour palette.\n' +
                    '6. Spacing: section _padding (var()), element _rowGap / _columnGap (var())\n' +
                    '7. Cards: border-radius, internal _padding, border color (use var() like var(--c1-l-9))\n\n' +

                    'OUTPUT RULES:\n' +
                    '- No JSON, no code blocks. Plain text / markdown paragraphs only.\n' +
                    '- Start writing the brief immediately – do not say "I will generate..." or any preamble.\n' +
                    '- Every color and size reference MUST use var() notation if palette/sizes are available above.';

                const userContent = [];
                if (userMessage) userContent.push({ type: 'text', text: userMessage });
                images.forEach(function(img) {
                    userContent.push({ type: 'image_url', image_url: { url: img.data } });
                });

                const historyMsgs = ChatState.messages.slice(-6).filter(function(m) {
                    return m.role !== 'error';
                }).map(function(m) {
                    return { role: m.role === 'user' ? 'user' : 'assistant', content: m.content };
                });

                const userMsg = (userContent.length === 1 && userContent[0].type === 'text')
                    ? userContent[0].text
                    : userContent;

                return [
                    { role: 'system', content: systemPrompt }
                ].concat(historyMsgs, [
                    { role: 'user', content: userMsg }
                ]);
            }

            /* =================================================================
             * COMPILER MESSAGES
             * =============================================================== */

            function buildCompilerMessages(blueprint, originalRequest) {
                const schema  = ChatState.bricksSchema || {};
                const colors  = schema.globalColors || [];
                const sizes   = schema.sizeVars    || [];

                /* Color reference */
                const colorRef = colors.length
                    ? colors.map(function(c) { return c.raw + ' -> light: ' + c.light + ' | id: "' + c.id + '"'; }).join('\n')
                    : '(no palette – use static hex values)';

                /* Size reference */
                const sizeRef = sizes.length
                    ? sizes.map(function(v) { return v.var + ' = ' + v.value; }).join('  |  ')
                    : '(no size variables – use px values)';

                /* Element types from live Bricks registry (or fallback) */
                const elementTypes = ChatState.bricksElements.length
                    ? ChatState.bricksElements.join(', ')
                    : DEFAULT_ELEMENT_TYPES.join(', ');

                const systemPrompt =
                    'You are a strict JSON compiler for Bricks Builder (WordPress page builder).\n' +
                    'Output ONLY a raw JSON object. No prose. No code blocks. No markdown.\n' +
                    'Your output is parsed directly with JSON.parse() — any non-JSON text will cause an error.\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'SITE COLOR PALETTE (use — do NOT invent colors)\n' +
                    '══════════════════════════════════════════════\n' +
                    colorRef + '\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'SITE SIZE VARIABLES (use for padding/gap/font-size)\n' +
                    '══════════════════════════════════════════════\n' +
                    sizeRef + '\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'OUTPUT SCHEMA\n' +
                    '══════════════════════════════════════════════\n' +
                    '{ "action_type": "replace|append|prepend", "sections": [ <nodes> ] }\n\n' +
                    'NODE: { "type": "<element>", "settings": { ... }, "children": [ <nodes> ] }\n\n' +
                    'VALID TYPES (live Bricks registry):\n' +
                    elementTypes + '\n\n' +
                    'HIERARCHY: section → container → block/div → content elements\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'SETTINGS — NATIVE BRICKS KEYS (preferred over _cssCustom)\n' +
                    '══════════════════════════════════════════════\n' +
                    '_padding/_margin : {"top":"var(--size-50)","right":"var(--size-50)","bottom":"var(--size-50)","left":"var(--size-50)"}  |  negative gap: {"bottom":"-30"}\n' +
                    '_typography      : {"font-size":"var(--size-50)","font-weight":"300","line-height":"1.4","color":{"id":"xxx","raw":"var(--c2)","light":"hsl(...)"},"text-align":"center","text-transform":"uppercase","letter-spacing":"1"}\n' +
                    '_background      : {"color":{"id":"xxx","raw":"var(--c1)","light":"hsl(...)"}}  OR  {"color":{"raw":"rgba(233,233,233,0.33)"}}\n' +
                    '_border          : {"radius":{"top":"16","right":"16","bottom":"16","left":"16"},"width":{"top":"1","right":"1","bottom":"1","left":"1"},"style":"solid","color":{...}}\n' +
                    '_display         : "grid"  |  _gridTemplateColumns: "1fr 1fr"  |  _gridGap: "var(--size-50)"\n' +
                    '_direction       : "row"  |  _columnGap: "var(--size-10)"  |  _rowGap: "var(--size-24)"\n' +
                    '_justifyContent  : "center"  |  _alignItems: "center"\n' +
                    '_widthMax        : "1200"  |  _width: "100%"  |  _height: "80vh"  |  _overflow: "hidden"  (always set on gradient cards)\n' +
                    '_order           : "_order:mobile_landscape":"-1"  (reorder block first on mobile)\n' +
                    '_gradient radial : {"applyTo":"overlay","gradientType":"radial","radialPosition":"top right","colors":[{"id":"a","color":{"raw":"var(--c2-d-8)"}},{"id":"b","color":{"raw":"var(--c2-d-10)"},"stop":"33"}]}\n' +
                    'button primary   : "text":"...", "style":"primary", "_padding":{"top":"20","bottom":"20","left":"var(--size-36)","right":"var(--size-36)"}, "_background:hover":{"color":{"raw":"color-mix(in srgb, var(--c2) 100%, #000 20%)"}}, "icon":{"library":"fontawesomeSolid","icon":"fas fa-arrow-right"},"iconPosition":"left","_cssCustom":"%root% i{transition:0.4s} %root%:hover i{transform:translateX(5px)}"\n' +
                    'button outline   : same as primary + "_background":{"color":{"raw":"var(--c3)"}}, "_typography":{"color":{"raw":"var(--c2)"},"font-weight":"500"}, "_border":{...1px solid var(--c1-l-9)...}, "_border:hover":{"color":{"raw":"var(--c1)"}}, "_typography:hover":{"color":{"raw":"var(--c1)"}}\n' +
                    'heading          : "text":"Build <em>Something</em> Great","tag":"h1","_cssCustom":"%root% em { color: var(--c2); }"\n' +
                    'section-label    : type "text-basic", _typography {uppercase, letter-spacing:"1", color:var(--c2), font-weight:"600", line-height:"1"}, "_margin":{"bottom":"-30"}  — place BEFORE every h2 heading\n' +
                    'image            : "image":{"external":"https://source.unsplash.com/800x600/?keyword1,keyword2","id":0}\n' +
                    'icon             : "icon":{"library":"fontawesomeSolid","icon":"fas fa-bolt"}, "iconColor":{...}, "iconSize":"24"\n' +
                    'icon-box feature : "icon":{...}, "content":"<p>...</p>", "direction":"row", "gap":"20", "iconSize":"24"\n' +
                    'icon-box check   : "icon":{"library":"fontawesomeSolid","icon":"fas fa-check"}, "content":"<p><strong>Point</strong>. Detail.</p>", "direction":"row","gap":"20","iconSize":"16","iconMargin":{"top":"5"},"iconColor":{"raw":"var(--c2)"},"iconBackgroundColor":{"raw":"var(--c2-l-10)"},"iconBorder":{"radius":{"top":"100","right":"100","bottom":"100","left":"100"}},"iconPadding":{"top":"8","right":"8","bottom":"8","left":"8"}\n' +
                    'dark-card block  : _background var(--c2-d-10) + _gradient radial overlay + _overflow:"hidden" + _typography color:var(--c3) + _border radius 16\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'STATE & BREAKPOINT SUFFIXES (append to any settings key)\n' +
                    '══════════════════════════════════════════════\n' +
                    'hover:            "_background:hover", "_typography:hover"\n' +
                    'active:           "_background:active"\n' +
                    'mobile landscape: "_padding:mobile_landscape", "_typography:mobile_landscape"\n' +
                    'mobile portrait:  "_padding:mobile_portrait"\n' +
                    'tablet portrait:  "_gridTemplateColumns:tablet_portrait"\n' +
                    'tablet landscape: "_gridTemplateColumns:tablet_landscape"\n\n' +

                    '_cssCustom — ONLY for: child selectors, hover-on-children, transitions, animations, backdrop-filter, pseudo-elements.\n' +
                    'Use %root% as selector. NEVER put padding/margin/color/display/gap/width/height/font-size in _cssCustom.\n\n' +

                    '══════════════════════════════════════════════\n' +
                    'EXAMPLE (hero + features with dark card + checklist)\n' +
                    '══════════════════════════════════════════════\n' +
                    '{"action_type":"replace","sections":[' +
                        '{"type":"section","settings":{"_padding":{"top":"var(--size-200)","bottom":"var(--size-200)"},"_padding:mobile_landscape":{"top":"var(--size-100)","bottom":"var(--size-100)"}},"children":[' +
                            '{"type":"container","settings":{"_display":"grid","_gridTemplateColumns":"1.3fr 0.7fr","_gridGap":"var(--size-100)","_alignItems":"center","_gridTemplateColumns:mobile_landscape":"1fr"},"children":[' +
                                '{"type":"block","settings":{"_rowGap":"var(--size-24)"},"children":[' +
                                    '{"type":"text-basic","settings":{"text":"PLATFORM","_typography":{"font-weight":"600","text-transform":"uppercase","letter-spacing":"1","color":{"id":"gnkmru","raw":"var(--c2)"},"line-height":"1"},"_margin":{"bottom":"-30"}},"children":[]},' +
                                    '{"type":"heading","settings":{"text":"Build the <em>Future</em> Today","tag":"h1","_typography":{"font-size":"var(--size-50)","font-weight":"300"},"_typography:mobile_landscape":{"font-size":"var(--size-36)"},"_cssCustom":"%root% em { color: var(--c2); }"},"children":[]},' +
                                    '{"type":"text-basic","settings":{"text":"A modern platform that accelerates your growth.","_typography":{"line-height":"2"}},"children":[]},' +
                                    '{"type":"block","settings":{"_direction":"row","_columnGap":"var(--size-10)","_rowGap":"var(--size-10)"},"children":[' +
                                        '{"type":"button","settings":{"text":"Get Started","style":"primary","link":{"type":"url","url":"#"},"_padding":{"top":"20","bottom":"20","left":"var(--size-36)","right":"var(--size-36)"},"icon":{"library":"fontawesomeSolid","icon":"fas fa-arrow-right"},"iconPosition":"left","_background:hover":{"color":{"raw":"color-mix(in srgb, var(--c2) 100%, #000 20%)"}},"_cssCustom":"%root% i{transition:0.4s} %root%:hover i{transform:translateX(5px)}"},"children":[]},' +
                                        '{"type":"button","settings":{"text":"Learn More","style":"primary","link":{"type":"url","url":"#"},"_padding":{"top":"20","bottom":"20","left":"var(--size-36)","right":"var(--size-36)"},"_background":{"color":{"id":"aoskmp","raw":"var(--c3)"}},"_typography":{"color":{"id":"gnkmru","raw":"var(--c2)"},"font-weight":"500"},"_border":{"width":{"top":"1","right":"1","bottom":"1","left":"1"},"style":"solid","color":{"id":"kysrnm","raw":"var(--c1-l-9)"}},"_border:hover":{"color":{"id":"dwvvob","raw":"var(--c1)"}},"_typography:hover":{"color":{"id":"dwvvob","raw":"var(--c1)"}}},"children":[]}' +
                                    ']}' +
                                ']},' +
                                '{"type":"image","settings":{"image":{"external":"https://source.unsplash.com/800x600/?modern,abstract,minimal","id":0},"_border":{"radius":{"top":"16","right":"16","bottom":"16","left":"16"}}},"children":[]}' +
                            ']}' +
                        ']},' +
                        '{"type":"section","settings":{"_background":{"color":{"raw":"rgba(233,233,233,0.33)"}},"_padding":{"top":"var(--size-200)","bottom":"var(--size-200)"}},"children":[' +
                            '{"type":"container","settings":{"_rowGap":"var(--size-100)"},"children":[' +
                                '{"type":"block","settings":{"_typography":{"text-align":"center"},"_rowGap":"var(--size-10)","_alignItems":"center"},"children":[' +
                                    '{"type":"text-basic","settings":{"text":"FEATURES","_typography":{"font-weight":"600","text-transform":"uppercase","letter-spacing":"1","color":{"id":"gnkmru","raw":"var(--c2)"},"line-height":"1"},"_margin":{"bottom":"-30"}},"children":[]},' +
                                    '{"type":"heading","settings":{"text":"Everything you <em>need</em>","tag":"h2","_typography":{"font-weight":"300","font-size":"var(--size-50)"},"_cssCustom":"%root% em { color: var(--c2); }"},"children":[]}' +
                                ']},' +
                                '{"type":"block","settings":{"_display":"grid","_gridTemplateColumns":"1fr 1fr","_gridGap":"var(--size-50)","_gridTemplateColumns:mobile_landscape":"1fr"},"children":[' +
                                    '{"type":"block","settings":{"_rowGap":"var(--size-36)"},"children":[' +
                                        '{"type":"text","settings":{"text":"<p>Feature description paragraph.</p>","_typography":{"line-height":"2"}},"children":[]},' +
                                        '{"type":"block","settings":{"_rowGap":"10"},"children":[' +
                                            '{"type":"icon-box","settings":{"icon":{"library":"fontawesomeSolid","icon":"fas fa-check"},"content":"<p><strong>Point one</strong>. Explanation here.</p>","direction":"row","gap":"20","iconSize":"16","iconMargin":{"top":"5"},"iconColor":{"id":"gnkmru","raw":"var(--c2)"},"iconBackgroundColor":{"id":"wtkcqc","raw":"var(--c2-l-10)"},"iconBorder":{"radius":{"top":"100","right":"100","bottom":"100","left":"100"}},"iconPadding":{"top":"8","right":"8","bottom":"8","left":"8"}},"children":[]},' +
                                            '{"type":"icon-box","settings":{"icon":{"library":"fontawesomeSolid","icon":"fas fa-check"},"content":"<p><strong>Point two</strong>. Explanation here.</p>","direction":"row","gap":"20","iconSize":"16","iconMargin":{"top":"5"},"iconColor":{"id":"gnkmru","raw":"var(--c2)"},"iconBackgroundColor":{"id":"wtkcqc","raw":"var(--c2-l-10)"},"iconBorder":{"radius":{"top":"100","right":"100","bottom":"100","left":"100"}},"iconPadding":{"top":"8","right":"8","bottom":"8","left":"8"}},"children":[]}' +
                                        ']}' +
                                    ']},' +
                                    '{"type":"block","settings":{"_padding":{"top":"var(--size-50)","right":"var(--size-50)","bottom":"var(--size-50)","left":"var(--size-50)"},"_background":{"color":{"id":"agpdxo","raw":"var(--c2-d-10)"}},"_typography":{"color":{"id":"aoskmp","raw":"var(--c3)"}},"_border":{"radius":{"top":"16","right":"16","bottom":"16","left":"16"}},"_gradient":{"applyTo":"overlay","gradientType":"radial","radialPosition":"top right","colors":[{"id":"a","color":{"id":"gkkewg","raw":"var(--c2-d-8)"}},{"id":"b","color":{"id":"agpdxo","raw":"var(--c2-d-10)"},"stop":"33"}]},"_overflow":"hidden","_justifyContent":"center","_alignItems":"center","_order:mobile_landscape":"-1"},"children":[' +
                                        '{"type":"heading","settings":{"text":"50k+","tag":"h3","_typography":{"font-size":"var(--size-100)","font-weight":"700"}},"children":[]},' +
                                        '{"type":"text-basic","settings":{"text":"Users worldwide","_typography":{"color":{"id":"ksgqoe","raw":"var(--c2-l-4)"}}},"children":[]}' +
                                    ']}' +
                                ']}' +
                            ']}' +
                        ']}' +
                    ']}\n\n' +
                    'Compile the design brief below. Use site palette and size variables wherever possible. ' +
                    'Use native Bricks settings as primary. _cssCustom only for child selectors, transitions, animations, backdrop-filter. ' +
                    'Output ONLY the raw JSON object — no code blocks, no explanation.';

                return [
                    { role: 'system', content: systemPrompt },
                    { role: 'user',   content:
                        'Original request: "' + originalRequest + '"\n\n--- DESIGN BRIEF ---\n' +
                        blueprint + '\n\nCompile this into the required JSON structure now.' }
                ];
            }

            /* =================================================================
             * ART DIRECTOR MESSAGES
             * =============================================================== */

            function buildArtDirectorMessages(blueprint) {
                const systemPrompt =
                    'You are a bold, opinionated Art Director reviewing a UI design brief for a Bricks Builder page.\n\n' +
                    'Your sole job is to REWRITE the brief in plain text — bolder, more dramatic, more visually rich.\n\n' +
                    '✦ ENHANCE — apply ALL of the following rules:\n' +
                    '  1. CONTRAST: Push heading/background contrast to its maximum. If mid-tones are used, go darker or brighter.\n' +
                    '  2. TYPOGRAPHY DRAMA: Increase the largest heading size by at least one step (e.g., var(--size-50) → var(--size-64)). Increase font-weight (400 → 700, 300 → 600). Consider adding an oversized decorative element or stat number.\n' +
                    '  3. LAYOUT DYNAMICS: Break boxy symmetry. Suggest an asymmetric split (60/40), an overlapping card that bleeds into the next section, or a full-bleed image hero. Avoid all-equal-columns wherever possible.\n' +
                    '  4. DEPTH & LAYERING: Add at least one overlapping element using negative margin, z-index, or a floating badge/ribbon. Suggest a gradient overlay or subtle noise texture via _cssCustom on a relevant section.\n' +
                    '  5. SPACING: Increase vertical whitespace between sections by roughly 30% — generous padding makes layouts feel premium.\n' +
                    '  6. IMAGES: Replace any generic image placeholders with specific Unsplash Source URLs using mood-matching keywords (e.g., https://source.unsplash.com/800x600/?luxury,hotel,interior). The URL format is https://source.unsplash.com/WIDTHxHEIGHT/?keyword1,keyword2.\n' +
                    '  7. MICRO-DETAILS: Add a 0.3s ease hover transform on the primary CTA button, one FontAwesome icon accent, and a subtle decorative divider or ruling line.\n\n' +
                    'STRICT RULES:\n' +
                    '- Preserve all var() color and size variable names from the original brief — do NOT invent new variable names.\n' +
                    '- Preserve the intended action_type (replace / append / prepend).\n' +
                    '- Output plain text / markdown ONLY. No JSON. No code blocks.\n' +
                    '- Begin immediately with "# Art-Directed Brief" — no preamble.\n' +
                    '- Be prescriptive and specific: the JSON compiler will follow your exact measurements, colours, and layout instructions.';

                return [
                    { role: 'system', content: systemPrompt },
                    { role: 'user',   content: '--- ORIGINAL DESIGN BRIEF ---\n\n' + blueprint + '\n\nEnhance this brief now. Be bold and specific.' }
                ];
            }

            /* =================================================================
             * CALL AI
             * =============================================================== */

            async function callAI(messages, agentType, retryCount) {
                agentType  = agentType  || 'designer';
                retryCount = retryCount || 0;

                const cfg = snnBricksChatConfig.ai;
                if (!cfg.apiKey || !cfg.apiEndpoint) throw new Error('AI API not configured.');

                const temperature = agentType === 'compiler' ? 0.15 : 0.75;
                const maxTokens   = cfg.maxTokens || (agentType === 'compiler' ? 6000 : 2000);

                const requestBody = {
                    model:       cfg.model,
                    messages:    messages,
                    temperature: temperature,
                    max_tokens:  maxTokens
                };

                // Compiler: force valid JSON output so we can JSON.parse() directly
                // json_object is broadly supported across OpenRouter models; ignored gracefully if not
                if (agentType === 'compiler') {
                    requestBody.response_format = { type: 'json_object' };
                }

                const resp = await fetch(cfg.apiEndpoint, {
                    method:  'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Authorization': 'Bearer ' + cfg.apiKey
                    },
                    body: JSON.stringify(requestBody)
                });

                if (resp.status === 429 && retryCount < 3) {
                    await sleep(3000 * (retryCount + 1));
                    return callAI(messages, agentType, retryCount + 1);
                }

                if (!resp.ok) {
                    const txt = await resp.text();
                    throw new Error('AI API ' + resp.status + ': ' + txt.substring(0, 200));
                }

                const data    = await resp.json();
                const content = data && data.choices && data.choices[0] && data.choices[0].message
                    ? data.choices[0].message.content
                    : null;

                if (!content || !content.trim()) throw new Error('AI returned empty response.');
                return content;
            }

            /* =================================================================
             * PARSE COMPILER OUTPUT
             * =============================================================== */

            function parseCompilerOutput(response) {
                let parsed;
                const raw = (response || '').trim();

                // Tier 1: direct JSON — works when response_format: json_object is honoured
                try {
                    parsed = JSON.parse(raw);
                } catch (e1) {
                    // Tier 2: extract from ```json ... ``` block (model ignored response_format)
                    const fenceMatch = raw.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
                    if (fenceMatch) {
                        try { parsed = JSON.parse(fenceMatch[1]); } catch (e2) { /* fall through */ }
                    }

                    // Tier 3: find first { ... } object in response (last resort)
                    if (!parsed) {
                        const objMatch = raw.match(/\{[\s\S]*\}/);
                        if (objMatch) {
                            try { parsed = JSON.parse(objMatch[0]); } catch (e3) { /* fall through */ }
                        }
                    }

                    if (!parsed) {
                        return { error: 'No valid JSON found in compiler response. Raw start: ' + raw.substring(0, 120) };
                    }
                }

                if (!parsed.sections || !Array.isArray(parsed.sections)) {
                    return { error: 'Missing or invalid "sections" array.' };
                }
                if (!parsed.sections.length) {
                    return { error: '"sections" array is empty.' };
                }

                return {
                    action_type: parsed.action_type || 'append',
                    sections:    parsed.sections
                };
            }

            /* =================================================================
             * UI HELPERS
             * =============================================================== */

            function sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }

            function showTyping() {
                $('.snn-bricks-chat-typing').show();
                scrollToBottom();
            }

            function hideTyping() { $('.snn-bricks-chat-typing').hide(); }

            function setAgentState(state) {
                ChatState.currentState = state;

                const badges = {
                    'designing':     { label: 'DESIGNING',    cls: 'badge-design'  },
                    'art-directing': { label: 'ART DIRECTOR', cls: 'badge-artdir'  },
                    'compiling':     { label: 'COMPILING',    cls: 'badge-compile' },
                    'injecting': { label: 'INJECTING', cls: 'badge-inject' },
                    'retrying':  { label: 'RETRYING',  cls: 'badge-retry'  },
                    'done':      { label: 'DONE',      cls: 'badge-done'   },
                    'error':     { label: 'ERROR',     cls: 'badge-error'  }
                };

                const $badge = $('#snn-bricks-state-badge');
                const $text  = $('#snn-bricks-chat-state-text');

                if (badges[state]) {
                    $badge.text(badges[state].label).attr('class', 'snn-bricks-state-badge ' + badges[state].cls).show();
                    $text.text(badges[state].label + '...').show();
                } else {
                    $badge.hide();
                    $text.hide();
                }

                if (state === 'done' || state === 'error') {
                    setTimeout(function() { $badge.hide(); $text.hide(); }, 4000);
                }
            }

            function addMessage(role, content, images) {
                images = images || [];
                const message = { role: role, content: content, timestamp: Date.now() };
                if (images.length && images[0] && images[0].data) message.images = images;
                ChatState.messages.push(message);

                const $msgs    = $('#snn-bricks-chat-messages');
                const $welcome = $msgs.find('.snn-bricks-chat-welcome');
                if ($welcome.length) $welcome.remove();

                const $msg = $('<div>')
                    .addClass('snn-bricks-chat-message')
                    .addClass('snn-bricks-chat-message-' + role);

                if (message.images && message.images.length) {
                    const $imgs = $('<div>').addClass('snn-message-images');
                    message.images.forEach(function(img) {
                        $imgs.append($('<img>').attr({ src: img.data, alt: img.fileName }));
                    });
                    $msg.append($imgs);
                }

                $msg.append($('<div>').html(formatMessage(content)));
                $msgs.append($msg);
                scrollToBottom();
            }

            function formatMessage(content) {
                if (typeof markdown !== 'undefined' && markdown.toHTML) {
                    try { return markdown.toHTML(content); } catch(e) { /* fall through */ }
                }
                return content.replace(/\n/g, '<br>');
            }

            function scrollToBottom() {
                const $m = $('#snn-bricks-chat-messages');
                $m.scrollTop($m[0].scrollHeight);
            }

            function clearChat() {
                ChatState.messages         = [];
                ChatState.currentSessionId = null;
                ChatState.attachedImages   = [];
                renderImagePreviews();
                $('#snn-bricks-chat-messages').html(
                    '<div class="snn-bricks-chat-welcome"><h3>Conversation cleared</h3><p>Start a new conversation.</p></div>'
                );
                $('#snn-bricks-quick-actions').show();
            }

            /* =================================================================
             * HISTORY & SAVE
             * =============================================================== */

            function autoSaveConversation() {
                if (!ChatState.messages.length) return;
                $.ajax({
                    url:  snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action:     'snn_save_chat_history',
                        nonce:      snnBricksChatConfig.nonce,
                        messages:   JSON.stringify(ChatState.messages),
                        session_id: ChatState.currentSessionId
                    },
                    success: function(r) {
                        if (r.success) ChatState.currentSessionId = r.data.session_id;
                    }
                });
            }

            function toggleHistoryDropdown() {
                const $d = $('#snn-bricks-chat-history-dropdown');
                if ($d.is(':visible')) { $d.hide(); return; }
                loadChatHistories();
                $d.show();
            }

            function loadChatHistories() {
                $('#snn-bricks-history-list').html('<div style="padding:12px;color:#666;">Loading...</div>');
                $.ajax({
                    url:  snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: { action: 'snn_get_chat_histories', nonce: snnBricksChatConfig.nonce },
                    success: function(r) {
                        if (r.success) renderHistoryList(r.data.histories || []);
                    }
                });
            }

            function renderHistoryList(histories) {
                const $list = $('#snn-bricks-history-list');
                if (!histories.length) { $list.html('<div style="padding:12px;color:#666;">No history.</div>'); return; }
                let html = '';
                histories.forEach(function(h) {
                    html += '<div class="snn-bricks-history-item" data-session-id="' + h.session_id + '">' +
                        '<div class="snn-bricks-history-title">' + (h.title || 'Conversation') + '</div>' +
                        '<div class="snn-bricks-history-meta">' + (h.message_count || 0) + ' messages</div>' +
                        '</div>';
                });
                $list.html(html);
                $list.find('.snn-bricks-history-item').on('click', function() {
                    const sid = $(this).data('session-id');
                    loadChatSession(sid);
                    $('#snn-bricks-chat-history-dropdown').hide();
                });
            }

            function loadChatSession(sessionId) {
                $.ajax({
                    url:  snnBricksChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action:     'snn_load_chat_history',
                        nonce:      snnBricksChatConfig.nonce,
                        session_id: sessionId
                    },
                    success: function(r) {
                        if (r.success && r.data.messages) {
                            ChatState.messages         = r.data.messages;
                            ChatState.currentSessionId = sessionId;
                            const $msgs = $('#snn-bricks-chat-messages').empty();
                            r.data.messages.forEach(function(m) {
                                $msgs.append(
                                    $('<div>')
                                        .addClass('snn-bricks-chat-message snn-bricks-chat-message-' + m.role)
                                        .html(formatMessage(m.content))
                                );
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

    /* ---------------------------------------------------------------
     * Inline CSS
     * ------------------------------------------------------------- */

    private function get_inline_css() {
        return '
.snn-bricks-ai-toggle { cursor: pointer; }
.snn-bricks-chat-overlay { position: fixed; top: 0; right: 0; bottom: 0; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.snn-bricks-chat-container { width: 420px; height: 100%; background: #fff; box-shadow: -2px 0 20px rgba(0,0,0,.18); display: flex; flex-direction: column; }
.snn-bricks-chat-header { background: #161a1d; color: #fff; padding: 8px 16px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.snn-bricks-chat-title  { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 600; }
.snn-bricks-chat-controls { display: flex; gap: 4px; }
.snn-bricks-chat-btn { background: rgba(255,255,255,.18); border: none; color: #fff; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; display: flex; justify-content: center; align-items: center; }
.snn-bricks-chat-btn:hover { background: rgba(255,255,255,.3); }
.snn-bricks-state-badge { display: none; font-size: 10px; font-weight: 700; letter-spacing: .06em; padding: 2px 7px; border-radius: 4px; text-transform: uppercase; margin-left: 4px; }
.badge-design  { background: #7c3aed; color: #fff; }
.badge-artdir  { background: #db2777; color: #fff; }
.badge-compile { background: #2563eb; color: #fff; }
.badge-inject  { background: #0891b2; color: #fff; }
.badge-retry   { background: #d97706; color: #fff; }
.badge-done    { background: #16a34a; color: #fff; }
.badge-error   { background: #dc2626; color: #fff; }
.snn-bricks-chat-history-dropdown { position: relative; background: #fff; border-bottom: 1px solid #e5e7eb; max-height: 280px; overflow-y: auto; flex-shrink: 0; }
.snn-bricks-history-header { padding: 10px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 600; }
.snn-bricks-history-close { background: none; border: none; font-size: 22px; cursor: pointer; line-height: 1; color: #6b7280; }
.snn-bricks-history-item { padding: 10px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
.snn-bricks-history-item:hover { background: #f9fafb; }
.snn-bricks-history-title { font-weight: 600; font-size: 13px; color: #111827; margin-bottom: 2px; }
.snn-bricks-history-meta  { font-size: 11px; color: #6b7280; }
.snn-bricks-chat-messages { flex: 1; overflow-y: auto; padding: 14px; background: #f9fafb; font-size: 13.5px; }
.snn-bricks-chat-welcome  { text-align: center; padding: 40px 16px; color: #374151; }
.snn-bricks-chat-welcome h3 { font-size: 16px; margin-bottom: 8px; }
.snn-bricks-chat-welcome p  { font-size: 13px; color: #6b7280; margin: 4px 0; }
.snn-bricks-chat-message { margin-bottom: 6px; padding: 8px 12px; border-radius: 10px; max-width: 96%; word-break: break-word; line-height: 1.55; }
.snn-bricks-chat-message-user      { background: #161a1d; color: #fff; margin-left: auto; }
.snn-bricks-chat-message-assistant { background: #fff; border: 1px solid #e5e7eb; margin-right: auto; color: #111827; }
.snn-bricks-chat-message-error     { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.snn-bricks-chat-typing { padding: 6px 14px; flex-shrink: 0; }
.typing-dots { display: flex; gap: 4px; }
.typing-dots span { width: 7px; height: 7px; border-radius: 50%; background: #9ca3af; animation: snn-typing 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes snn-typing { 0%,60%,100% { transform: translateY(0); opacity:.45; } 30% { transform: translateY(-7px); opacity:1; } }
.snn-bricks-chat-state-text { padding: 5px 14px; background: #f3f4f6; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; flex-shrink: 0; }
.snn-bricks-chat-quick-actions { padding: 6px; background: #fff; display: flex; gap: 5px; flex-wrap: wrap; border-top: 1px solid #f3f4f6; flex-shrink: 0; }
.snn-bricks-quick-action-btn { padding: 5px 11px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 11.5px; cursor: pointer; color: #374151; white-space: nowrap; }
.snn-bricks-quick-action-btn:hover { background: #161a1d; color: #fff; border-color: #161a1d; }
.snn-bricks-chat-input-container { padding: 10px; background: #fff; border-top: 1px solid #e5e7eb; display: flex; gap: 8px; align-items: flex-end; flex-shrink: 0; }
.snn-bricks-chat-input-wrapper { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.snn-bricks-chat-input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 11px; font-size: 13.5px; resize: none; min-height: 60px; max-height: 120px; font-family: inherit; }
.snn-bricks-chat-input:focus { outline: none; border-color: #7c3aed; }
.snn-bricks-chat-attach-btn { width: 38px; height: 38px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px; color: #6b7280; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-bricks-chat-attach-btn:hover { background: #e5e7eb; }
.snn-bricks-chat-send { width: 38px; height: 38px; background: #161a1d; border: none; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snn-bricks-chat-send:hover { background: #7c3aed; }
.snn-bricks-chat-image-preview { display: flex; flex-wrap: wrap; gap: 6px; }
.snn-image-preview-item { position: relative; width: 72px; height: 72px; border-radius: 6px; overflow: hidden; border: 1px solid #e5e7eb; }
.snn-image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
.snn-image-preview-remove { position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; background: rgba(0,0,0,.65); color: #fff; border: none; border-radius: 50%; cursor: pointer; font-size: 14px; line-height: 1; display: flex; align-items: center; justify-content: center; padding: 0; }
.snn-image-preview-remove:hover { background: #dc2626; }
.snn-message-images { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
.snn-message-images img { max-width: 180px; max-height: 180px; border-radius: 6px; object-fit: cover; }
        ';
    }
}

SNN_Bricks_Chat_Overlay::get_instance();
