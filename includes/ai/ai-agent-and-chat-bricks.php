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



<button class="snn-bricks-quick-action-btn" data-message="Design a luxury real estate homepage for 'Noir Properties'. Use palette: near-black #0D0D0D, warm white #F5F2EE, gold #C9A44A. SECTION 1 — Full-viewport hero: near-black background (#0D0D0D), 2-column grid (60/40), left column has a thin gold overline text 'Exclusive Properties', large serif heading 'Live Above the Ordinary' in white (font-size 68px, Playfair Display), short subheading in warm white opacity 0.7, two side-by-side buttons (filled gold + ghost white outline); right column has a tall property interior photo (height 100vh, object-fit cover, border-radius 0). SECTION 2 — Stats band: dark charcoal background (#1A1A1A), single-row 4-column grid, each column is a centered block with a large gold number (font-size 52px, Playfair Display) and a small white label below (font-size 13px, letter-spacing 0.1em, uppercase) — '850+' Properties Sold, '$2.4B' Total Sales, '14' Global Cities, '98%' Client Satisfaction. SECTION 3 — Featured listings: warm white background (#F5F2EE), centered heading 'Featured Properties', 3-column card grid, each card is a block with a property photo (height 260px, object-fit cover, border-radius 0 on top), padding block below with gold address text (small caps), large price in near-black, small bedroom/sqft details in gray, and a minimal 'View Property' text link in gold. SECTION 4 — Full-width 2-column split: left is a dramatic living room interior photo (min-height 560px, object-fit cover), right has near-black background, centered content with Playfair Display heading 'Curated for the Discerning Buyer' in white (font-size 42px), paragraph in warm white opacity 0.7, gold divider line (width 60px, height 2px), and ghost button 'Meet Our Advisors'. SECTION 5 — Testimonials: off-white background (#F5F2EE), centered section heading, 3-column grid of quote cards each with a top gold quotation mark (font-size 48px), italic quote text in dark gray, thin divider, client name in bold near-black, client title in small gold uppercase text. SECTION 6 — CTA footer: near-black background (#0D0D0D), centered layout, small gold overline, massive heading 'Schedule a Private Consultation' in white (Playfair Display, 52px), short subtext, gold filled button + white ghost button side by side, bottom thin gold divider, and tiny copyright text.">
Real Estate</button>

<button class="snn-bricks-quick-action-btn" data-message="Design a premium skincare brand homepage for 'Luminos'. Use palette: blush #F7E8E0, charcoal #1C1C1C, dusty rose #D4A574, white #FEFEFE. SECTION 1 — Hero: blush background (#F7E8E0), 2-column grid (55/45), left column has a small rose-colored pill label 'Science-Backed Skincare', serif heading 'Skin That Speaks' (Playfair Display, 72px, charcoal), italic subheading 'Nature-inspired. Clinically proven.' in dusty rose, two buttons (filled charcoal + ghost outline); right column has a large product bottle hero shot centered with a soft drop shadow block behind it (white background, border-radius 50% to create a soft circle backdrop). SECTION 2 — Ingredient highlights: white background, centered heading 'What Goes Inside', 5-column grid of ingredient blocks each with a small icon (leaf/drop/sparkle from themify icons), ingredient name in bold charcoal (font-size 15px), and a 1-line benefit in muted gray — Vitamin C, Retinol, Hyaluronic Acid, Niacinamide, Peptides. SECTION 3 — Product grid: blush background (#F7E8E0), centered heading 'The Collection', 3-column grid of product cards — each card is a white block (border-radius 16px, box-shadow subtle), product photo on top (height 240px, object-fit contain, padding), product name in charcoal bold, skin concern tag in small rose uppercase text, price in bold, 'Add to Bag' button in charcoal full-width. SECTION 4 — The Ritual: white background, 2-column grid (50/50), left has a tall model close-up photo (border-radius 12px, height 560px, object-fit cover), right has centered flex-column layout with serif heading 'The 3-Step Morning Ritual' (Playfair Display, 42px), then 3 numbered steps each as a block with large number in dusty rose (font-size 36px, bold), step title in charcoal bold, short description in gray. SECTION 5 — Results section: charcoal background (#1C1C1C), centered heading 'Real Results. Real People.' in white (Playfair Display), subheading in blush-tinted white, 2-column grid with two before/after image pairs side by side (each a block with border-radius 12px and a small white label overlay at bottom with client name and result text like 'Reduced dark spots by 70% in 8 weeks'). SECTION 6 — Press band: white background, small centered gray text 'As Seen In', 5-column grid of text-based press names styled in bold charcoal (Vogue, Elle, Harper's Bazaar, Allure, Byrdie) with a subtle gray bottom border under each — rendered as heading elements in large elegant font, muted tone. SECTION 7 — Email CTA footer: blush background (#F7E8E0), centered heading 'Start Your Skin Journey' in charcoal (Playfair Display, 48px), subtext about free samples with first order, large charcoal filled button 'Shop Now', and small italic disclaimer text below.">
Skincare</button>

<button class="snn-bricks-quick-action-btn" data-message="Design a bold SaaS homepage for 'Flowmatic' — an AI workflow automation tool. Use palette: #050B18 deep navy background, #0F1E3D card color, #3B82F6 electric blue, #10B981 green, white text. SECTION 1 — Hero: deep navy background (#050B18), flex-column centered layout, small pill-style block with blue border and 'Now in Beta' text in blue (border-radius 20px, inline padding), massive heading 'Automate Everything. Ship Faster.' in white (Inter, 72px, font-weight 900, line-height 1.1), subheading about connecting your tools without code in blue-gray, two side-by-side buttons (blue filled 'Start Free' + ghost 'Watch Demo'), below buttons a product UI screenshot image with a blue box-shadow glow (box-shadow: 0 0 60px rgba(59,130,246,0.3)). SECTION 2 — Social proof band: slightly lighter navy (#0A1628), centered gray text 'Trusted by teams at', then a 6-column grid of company name blocks styled as bold white text at reduced opacity (0.5) — Stripe, Linear, Vercel, Notion, Figma, Shopify. SECTION 3 — Features grid: deep navy background, centered white heading 'Everything you need to move faster', 3-column grid of feature cards — each card is a block (#0F1E3D background, border 1px solid rgba(59,130,246,0.2), border-radius 12px, padding 32px) with a blue icon at top (themify icon, font-size 28px, color #3B82F6), bold white feature title (font-size 18px), gray description text. SECTION 4 — Pricing: deep navy background, centered heading 'Simple Pricing' in white, 3-column grid of plan cards — Starter (#0F1E3D, normal border), Pro (blue border glow: border 2px solid #3B82F6, box-shadow 0 0 30px rgba(59,130,246,0.2), slightly lighter background), Enterprise (#0F1E3D); each card has plan name in blue uppercase small, price in white (font-size 48px bold), '/month' in gray, 5 feature bullet items as text-basic blocks, CTA button (filled blue for Pro, ghost for others). SECTION 5 — Testimonials: deep navy background, centered white heading, 3-column grid of testimonial cards (#0F1E3D background, border-radius 12px, blue left border 3px) each with italic quote in light gray, author avatar image (60x60, border-radius 50%), author name in white bold, company in blue small text. SECTION 6 — Stats band: blue gradient background (linear-gradient 135deg #2563EB to #1D4ED8), 4-column grid, each column centered with large white bold number (font-size 56px) and white label below — '10M+' Tasks Automated, '50K' Teams, '99.9%' Uptime, '4.9★' Rating. SECTION 7 — Bottom CTA: deep navy, centered heading 'Your workflows are waiting.' in white (Inter 56px bold), subtext in blue-gray, large blue filled button 'Get Started Free', small text 'No credit card required' in muted gray below.">
SaaS</button>

<button class="snn-bricks-quick-action-btn" data-message="Design a high-end creative director portfolio for 'Elena Vasquez'. Use palette: pure black #000000, pure white #FFFFFF, warm off-white #F5F3EF, accent red-orange #FF3D00. SECTION 1 — Hero: pure black background, 2-column grid (55/45), left column flex-column layout with top small text 'Creative Director — Available for Projects' in red-orange (font-size 12px, letter-spacing 0.15em, uppercase), then stacked headings: 'ELENA' (Inter, 96px, font-weight 900, color white, line-height 1, margin 0), 'VASQUEZ' (Inter, 96px, font-weight 200, color white, line-height 1, margin 0, letter-spacing -2px), thin horizontal divider (white, 60px width, 1px height, margin 24px 0), subheading 'Brand Identity · Art Direction · Campaign' in white opacity 0.5 (font-size 14px), then two small buttons (filled red-orange 'View Work' + ghost white outline 'Contact'); right column has a high-contrast black and white portrait photo (height 100vh, object-fit cover, object-position top). SECTION 2 — Ticker-style band: off-white background (#F5F3EF), single row of service name blocks in a flex-row container — BRANDING · EDITORIAL · ART DIRECTION · CAMPAIGN · IDENTITY · STRATEGY — each as a heading element (Inter, 13px, font-weight 700, letter-spacing 0.2em, uppercase, charcoal color) separated by a red-orange dot icon or divider element. SECTION 3 — Work grid: black background, left-aligned heading 'Selected Work' in white (Inter, 48px, bold) + small red-orange overline 'Projects'; 2-column grid of project cards, top 2 cards are tall (height 420px) full-bleed images with a block overlay (background: linear-gradient to top, rgba(0,0,0,0.7) to transparent) at the bottom containing project name in white bold and category in red-orange small uppercase; below those, 3 smaller square cards (height 260px) same overlay style. SECTION 4 — About: off-white background (#F5F3EF), 2-column grid (45/55), left has a square photo with red-orange thick border-left (border-left: 4px solid #FF3D00), right has red-orange small overline 'About', heading 'I make brands feel inevitable.' (Playfair Display, 42px, near-black, italic), paragraph bio in dark gray, then a block with 5 client names listed as heading elements in black bold (Nike, Spotify, Bottega Veneta, Apple, Dior) with a thin gray divider between each. SECTION 5 — Services: off-white background, 4 full-width horizontal service rows each as a block with top border (1px solid #D0CEC9), 2-column flex layout inside — left has large number '01'–'04' (Inter, 72px, font-weight 900, color: black opacity 0.08) next to service name (Inter, 32px, bold, black), right has short description in gray; alternating off-white and white row backgrounds. SECTION 6 — Awards band: black background, centered small white text 'Recognition' (uppercase, letter-spacing), then 4-column grid of award name blocks in white bold (D&AD, Cannes Lions, Webby Award, The One Show) with a small red-orange icon above each. SECTION 7 — Contact footer: black background, centered massive heading 'Let's Work.' (Inter, 80px, font-weight 900, white), subtext email address in red-orange (font-size 24px), row of 3 social link ghost buttons (Instagram, LinkedIn, Behance), and tiny copyright text in white opacity 0.3.">
Portfolio</button>

<button class="snn-bricks-quick-action-btn" data-message="Design a premium fitness coaching homepage for 'FORM' by coach Marcus Reid. Use palette: near-black #0A0A0A, crimson red #E11D48, off-white #F5F5F5, white #FFFFFF. SECTION 1 — Hero: near-black background with a dramatic gym interior photo as background-image (background-size cover, background-position center), dark overlay block (position absolute, background rgba(10,10,10,0.75)), content block centered with red left accent border (border-left: 4px solid #E11D48, padding-left 24px) containing: small red uppercase text 'Elite Performance Coaching' (letter-spacing 0.15em, font-size 12px), massive heading 'Built Different.' (Inter, 84px, font-weight 900, white, line-height 1), subheading about 1-on-1 coaching for serious athletes in off-white opacity 0.8, two buttons side by side (filled crimson red 'Apply Now' + ghost white outline 'See Results', both large padding). SECTION 2 — Stats bar: crimson red background (#E11D48), 4-column grid, each column centered block with large bold white number (Inter, 56px, font-weight 900) and white label below (font-size 13px, opacity 0.85) — '1,200+' Athletes Trained, '8 Years' Experience, '94%' Hit Their Goal, '#1' Rated Coach. SECTION 3 — Programs: off-white background (#F5F5F5), centered heading 'Choose Your Program' in near-black (Inter, 48px, bold), 3-column grid of program cards — each a block (white background, border-radius 12px, box-shadow subtle, padding 40px); middle card has red top border (border-top: 3px solid #E11D48) and a 'Most Popular' small red badge block at top; each card has program name in bold near-black (font-size 22px), short description in gray, thin divider, 5 feature items as text-basic with a red checkmark icon prefix, duration + price in near-black bold, full-width red CTA button for middle card and ghost button for others. SECTION 4 — Results: near-black background (#0A0A0A), 2-column grid (50/50), left column has centered white heading 'Real People. Real Results.' (Inter, 42px, bold) + 3 result blocks each with client photo (60x60, border-radius 50%), client name in white bold, result achieved in red bold (e.g., '-22kg in 16 weeks'), short quote in off-white opacity 0.7; right column has a dramatic transformation image (height 520px, object-fit cover, border-radius 12px). SECTION 5 — How It Works: off-white background, centered heading 'The FORM Method', 4-column grid of step blocks each with a large red step number (Inter, 64px, font-weight 900, #E11D48, opacity 0.15 — rendered via typography color), red icon above (themify icons, font-size 28px, color #E11D48), step title in near-black bold (font-size 18px), short description in gray. SECTION 6 — About coach: white background, 2-column grid (45/55), left has a serious high-contrast coach portrait (height 560px, object-fit cover, border-radius 12px), right has red small overline 'Your Coach', heading 'Marcus Reid' (Inter, 52px, bold, near-black), credentials listed as text-basic blocks (Certified Strength Coach, NSCA-CPT, Ex-NFL Training Staff, 8+ Years), a red horizontal divider (60px wide, 3px tall), and large italic quote in gray 'I don't train people who want to look fit. I train people who want to be elite.' SECTION 7 — Bottom CTA: near-black background, centered red small overline text, heading 'Ready to Build Your Best Body?' (Inter, 52px, bold, white), subtext about limited spots in off-white, large red filled button 'Apply for Coaching', small italic text '— Only 12 spots available this month' in off-white opacity 0.6.">
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
                    <a href="https://sinanisler.com/github-support" target="_blank" data-balloon="If SNN-BRX saving you time and money consider supporting the project monthly." data-balloon-length="medium">Consider Supporting SNN-BRX ❤</a>
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
                        // Reassign the array reference so Vue's reactivity system picks up the
                        // change correctly and Bricks re-initialises all canvas event listeners.
                        s.content = [...els];
                        debugLog('Replaced with', els.length, 'elements');
                        // Force Bricks to re-render the canvas and re-attach drag/edit listeners
                        setTimeout(() => {
                            if (window.bricksCore?.builder?.canvas?.render) {
                                window.bricksCore.builder.canvas.render();
                            }
                        }, 150);
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
                            }
                        }, 150);
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
            // PHASE 1 — HTML + Native CSS Design Generation
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

                    // Clean up _cssGlobal: strip properties now handled natively
                    if (el.settings._cssGlobal && typeof el.settings._cssGlobal === 'string') {
                        el.settings._cssGlobal = el.settings._cssGlobal
                            .replace(/cursor:\s*pointer;?/g, '')
                            .replace(/transition:[^;]+;?/g, '')
                            .replace(/@media[^{]+\{[^}]+\}/g, '')
                            .trim();
                        if (el.settings._cssGlobal.match(/^[^{]+\{\s*\}\s*$/) || el.settings._cssGlobal === '') {
                            delete el.settings._cssGlobal;
                            fixed = true;
                        }
                    }

                    // Remove legacy _css object (use native breakpoint suffixes instead)
                    if (el.settings._css) {
                        delete el.settings._css;
                        errors.push('Removed legacy _css from ' + el.id);
                        fixed = true;
                    }

                    // Fix gradient in _background.color.raw — convert to proper _gradient format
                    const bgRaw = el.settings._background?.color?.raw;
                    if (bgRaw && typeof bgRaw === 'string' && (bgRaw.includes('linear-gradient') || bgRaw.includes('radial-gradient'))) {
                        const isRadial     = bgRaw.startsWith('radial');
                        const angleMatch   = bgRaw.match(/(\d+)deg/);
                        const colorMatches = [...bgRaw.matchAll(/#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)/g)];
                        if (colorMatches.length >= 2) {
                            el.settings._gradient = {
                                applyTo: 'overlay',
                                gradientType: isRadial ? 'radial' : 'linear',
                                ...((!isRadial && angleMatch) ? { angle: angleMatch[1] } : {}),
                                colors: colorMatches.map((m, i) => ({ id: genId(), color: { raw: m[0] }, stop: String(Math.round(i / (colorMatches.length - 1) * 100)) }))
                            };
                            delete el.settings._background.color;
                            if (!Object.keys(el.settings._background).length) delete el.settings._background;
                            errors.push('Converted gradient in _background.color.raw → _gradient for ' + el.id);
                            fixed = true;
                        }
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
                setAgentState('compiling');

                // Reset global ID tracker for this compilation session
                ChatState.globalUsedIds.clear();

                const sections = parseHTMLIntoSections(ChatState.currentHTMLPreview);
                const total    = sections.length;
                addMessage('assistant', '⚡ Compiling ' + total + ' section' + (total > 1 ? 's' : '') + ' with JavaScript compiler...');

                let builtCount = 0;
                for (let i = 0; i < sections.length; i++) {
                    const { label, html } = sections[i];
                    setAgentState('compiling', 'Building "' + label + '" (' + (i + 1) + '/' + total + ')...');
                    // No typing indicator needed - compilation is instant!
                    try {
                        const bricksData = await compileSingleSection(html, label, i + 1);
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
                        addMessage('error', '✗ "' + label + '" error: ' + e.message);
                    }
                }

                ChatState.isProcessing = false;
                setAgentState('idle');

                if (builtCount > 0) {
                    addMessage('assistant', '🎉 Done! ' + builtCount + '/' + total + ' sections compiled instantly and built in Bricks.');
                    // Keep preview visible so user can still compare the HTML
                    // hideHTMLPreview();
                    removeApproveBar();
                    ChatState.previewMode        = null;
                    // Keep currentHTMLPreview so toggle button remains functional
                    // ChatState.currentHTMLPreview = null;
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

⚡ NEW: Your designs are now compiled to Bricks using a LIGHTNING-FAST JavaScript compiler — instant conversion, zero API costs!

YOUR JOB:
When the user requests a design, layout, page or section — generate a complete, beautiful HTML mockup using:
- ONLY INLINE CSS STYLES (style="...") — ABSOLUTELY NO Tailwind, NO class-based utility frameworks, NO external CSS classes except simple semantic names like "container", "card", "grid"
- Google Fonts (@import in <style> tag at top of body)
- Real, production-quality content — actual headings, descriptions, CTAs (no Lorem Ipsum for main content)
- Real images via Pixabay proxy: ${ajaxUrl}?action=snn_pixabay_image&q=KEYWORDS (use different, specific keywords for each image)

OUTPUT FORMAT:
1. Write 1–2 sentences describing the design approach and color palette
2. Output the complete HTML in a \`\`\`html code block

STYLING RULES (CRITICAL — NO SHORTCUTS):
- Use INLINE style="..." attributes on EVERY visual element
- Example: <h1 style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -0.5px; margin: 0 0 20px 0;">
- Include Google Fonts: <style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');</style>
- Specify ALL visual properties: font-family, font-size, font-weight, color, line-height, letter-spacing, text-align, padding, margin, background, background-color, border, border-radius, box-shadow, opacity, display, flex properties, grid properties, width, height, max-width, object-fit, position, top, left, right, bottom, z-index, transform, transition
- Use standard CSS property names only: padding: 40px 20px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px
- Colors MUST be hex codes: #111827, #ffffff, #2563eb, rgba(0,0,0,0.1) for transparency
- All sizes MUST include units: font-size: 48px; padding: 60px 0; gap: 32px; width: 100%; max-width: 1200px
- Font stacks with fallbacks: 'Playfair Display', serif OR 'Inter', sans-serif OR 'Lato', sans-serif
- NO UTILITY CLASSES: Never use Tailwind, Bootstrap, or any utility class framework syntax
- ALL LAYOUT via inline styles: display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 24px;
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
- Every distinct visual section MUST be a DIRECT child of <body> using semantic HTML5 tags: <section>, <header>, <footer>, <nav>
- NEVER wrap sections inside <main>, <div>, or any container — each section must be a direct body child
- Content inside <main> is treated as ONE single section (avoid unless intended)
- MANDATORY: Add data-bricks attributes to ALL structural elements to guide compilation:
  * <section data-bricks="section"> — top-level section wrapper
  * <div data-bricks="container"> — centering wrapper. Use ONLY ONCE per section as the DIRECT child of <section>. NEVER use for inner layouts.
  * <div data-bricks="block"> — ALL inner layouts, grids, flex columns/rows, cards, boxes. This is the universal layout element.
  * <h1 data-bricks="heading"> through <h6 data-bricks="heading"> — headings (tag attr sets h1/h2/etc.)
  * <p data-bricks="text-basic"> — body text. Can contain inline HTML: <strong>, <em>, <a>, <br>
  * <a data-bricks="text-link"> — text link with optional icon. Set href for the link URL.
  * <button data-bricks="button"> — buttons/CTAs. Set href for link URL.
  * <img data-bricks="image"> — images (src, alt, object-fit, aspect-ratio all supported)
  * <ul data-bricks="text-basic"> or <ol data-bricks="text-basic"> — lists (rendered as native HTML inside text-basic)
  * <div data-bricks="custom-html-css-script"> — raw HTML component (ONLY for SVG animations, canvas, iframes, complex widgets)

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

- Use clean semantic structure: <section data-bricks="section"> → <div data-bricks="container"> → <div data-bricks="block"> → content elements
- ALL visual styling MUST be inline style="..." — no class-based frameworks
- CONTAINER RULE: one container per section (centering only). ALL inner layouts use block.

LAYOUT PATTERNS (all via inline styles + data-bricks attributes):

Centered section wrapper (ONLY ONE PER SECTION — direct child of section):
  <div data-bricks="container" style="max-width: 1200px; margin: 0 auto; padding: 0 24px;">

Flex column layout (use block):
  <div data-bricks="block" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">

Flex row layout (use block):
  <div data-bricks="block" style="display: flex; flex-direction: row; gap: 40px; align-items: center; justify-content: space-between;">

Flex item with align-self (any element can have align-self):
  <div data-bricks="block" style="align-self: flex-start; flex-grow: 1;">

Grid 2 columns (use block):
  <div data-bricks="block" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px;">

Grid 3 columns (use block):
  <div data-bricks="block" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">

Grid 4 columns (use block):
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

STRICT LAYOUT RULES:
✓ USE CSS GRID for all side-by-side layouts (heroes, feature grids, card grids)
✓ NEVER use flex-wrap for macro layouts — causes desktop wrapping issues
✓ Use Flexbox for single-direction layouts (vertical stacks, horizontal bars, icon rows)
✓ Grid syntax: display: grid; grid-template-columns: repeat(N, 1fr); gap: 32px;
✓ For asymmetric layouts: grid-template-columns: 2fr 1fr; OR 3fr 2fr; OR 1fr 2fr;
✓ align-self works on ANY element inside a flex or grid container

EXAMPLE COMPLETE STRUCTURE (with data-bricks attributes):
<style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');</style>

<section data-bricks="section" style="background: #0f172a; padding: 80px 0;">
  <div data-bricks="container" style="max-width: 1200px; margin: 0 auto; padding: 0 24px;">
    <div data-bricks="block" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">
      <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -1px; margin: 0;">Premium Heading</h1>
      <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; font-weight: 400; color: rgba(203, 213, 225, 1); line-height: 1.7; text-align: center; max-width: 700px; margin: 0;">Supporting description with readable line height and proper spacing.</p>
      <button data-bricks="button" style="background: #2563eb; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 14px 32px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.2s;">Call to Action</button>
    </div>
  </div>
</section>

EXAMPLE 2-COLUMN GRID HERO (section > container > block[grid] > block[column]):
<section data-bricks="section" style="background: #f5f0eb; padding: 100px 0;">
  <div data-bricks="container" style="max-width: 1400px; margin: 0 auto; padding: 0 24px;">
    <div data-bricks="block" style="display: grid; grid-template-columns: 2fr 1fr; gap: 60px; align-items: center;">
      <div data-bricks="block" style="display: flex; flex-direction: column; gap: 24px;">
        <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 72px; font-weight: 900; color: #111827; line-height: 1.1; margin: 0;">We Make Brands People Love</h1>
        <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; color: #4b5563; line-height: 1.7; margin: 0;">Creative studio specializing in bold brand identities and digital experiences.</p>
        <button data-bricks="button" style="background: #ff6b35; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 16px 32px; border: none; border-radius: 8px; cursor: pointer;">View Our Work</button>
      </div>
      <img data-bricks="image" src="..." style="width: 100%; height: 600px; object-fit: cover; border-radius: 12px;" />
    </div>
  </div>
</section>

WHEN NOT TO GENERATE HTML:
- User asks a question → respond in plain text only
- User says \"change X to Y\" (editing existing element) → explain that direct Bricks edit is better
- User refines the preview (\"make it darker\" / \"add a testimonials section\") → generate complete NEW replacement HTML incorporating their changes
- Unsure about intent → ask ONE clarifying question, then proceed with best interpretation

CRITICAL REMINDERS:
✓ ONLY inline styles — NO class-based styling frameworks
✓ Every visual property explicitly defined in style=\"...\"
✓ Sections as direct <body> children for independent compilation
✓ Real content, real images, production-ready design quality
✓ Semantic HTML structure with descriptive class names for structure only`;
            }

            /*
             * ================================================================
             * LEGACY CODE — Phase 2 AI Prompt (NO LONGER USED)
             * ================================================================
             * 
             * This massive prompt system has been REPLACED by the JavaScript
             * compiler (compileHtmlToBricksJson) which provides:
             * - INSTANT compilation (milliseconds vs 10+ seconds)
             * - ZERO API costs (no tokens consumed)
             * - 100% RELIABLE output (no AI hallucinations)
             * - PERFECT layout preservation (flex-direction, container boundaries)
             * 
             * Kept here for reference only. To re-enable AI compilation:
             * 1. Uncomment this function
             * 2. Restore the old compileSingleSection implementation
             * 3. Replace callAI() calls in the compilation flow
             * 
             * DATE DEPRECATED: 2026-03-07
             * ================================================================
             */
            
            /*
            function buildPhase2SystemPrompt(sectionIndex, googleFonts) {
                const fontContext = googleFonts ? `\nGOOGLE FONTS DETECTED:\n${googleFonts}\nUSE these font families in _typography settings (without quotes, just the font name).\n` : '';
                return `You are an expert Bricks Builder JSON compiler...`;
                // [Full prompt content omitted for brevity - see git history to restore]
            }
            */

            // ================================================================
            // Helpers
            // ================================================================

            // ================================================================
            // STEP 1: CSS-to-Bricks Mapping Dictionary
            // ================================================================
            
            /**
             * The "Rosetta Stone" — Maps CSS properties to Bricks settings paths
             * Type: 'direct' (simple 1:1), 'boxModel' (padding/margin), 'typography', 'raw' (wrap in {raw:...})
             */
            const CSS_TO_BRICKS_MAP = {
                // Box Model
                'padding':          { type: 'boxModel', target: '_padding' },
                'padding-top':      { type: 'directBox', target: '_padding', side: 'top' },
                'padding-right':    { type: 'directBox', target: '_padding', side: 'right' },
                'padding-bottom':   { type: 'directBox', target: '_padding', side: 'bottom' },
                'padding-left':     { type: 'directBox', target: '_padding', side: 'left' },
                'margin':           { type: 'boxModel', target: '_margin' },
                'margin-top':       { type: 'directBox', target: '_margin', side: 'top' },
                'margin-right':     { type: 'directBox', target: '_margin', side: 'right' },
                'margin-bottom':    { type: 'directBox', target: '_margin', side: 'bottom' },
                'margin-left':      { type: 'directBox', target: '_margin', side: 'left' },
                
                // Layout & Flexbox
                'display':          { type: 'direct', target: '_display' },
                'flex-direction':   { type: 'direct', target: '_direction', map: {'row': 'row', 'column': 'column', 'row-reverse': 'row-reverse', 'column-reverse': 'column-reverse'} },
                'justify-content':  { type: 'direct', target: '_justifyContent' },
                'align-items':      { type: 'direct', target: '_alignItems' },
                'align-content':    { type: 'direct', target: '_alignContent' },
                'align-self':       { type: 'direct', target: '_alignSelf' },
                'justify-self':     { type: 'direct', target: '_justifySelf' },
                'flex-wrap':        { type: 'direct', target: '_flexWrap' },
                'flex-grow':        { type: 'direct', target: '_flexGrow' },
                'flex-shrink':      { type: 'direct', target: '_flexShrink' },
                'flex-basis':       { type: 'direct', target: '_flexBasis' },
                'order':            { type: 'direct', target: '_order' },
                'gap':              { type: 'gapHandler' }, // Special: distributes to _columnGap/_rowGap
                'column-gap':       { type: 'numeric', target: '_columnGap' },
                'row-gap':          { type: 'numeric', target: '_rowGap' },

                // Grid
                'grid-template-columns': { type: 'direct', target: '_gridTemplateColumns' },
                'grid-template-rows':    { type: 'direct', target: '_gridTemplateRows' },
                'grid-template-areas':   { type: 'direct', target: '_gridTemplateAreas' },
                'grid-gap':              { type: 'numeric', target: '_gridGap' },
                'grid-column':           { type: 'direct', target: '_gridColumn' },
                'grid-row':              { type: 'direct', target: '_gridRow' },
                'grid-area':             { type: 'direct', target: '_gridArea' },
                'grid-auto-flow':        { type: 'direct', target: '_direction' }, // maps to same _direction as flex-direction
                'grid-auto-columns':     { type: 'direct', target: '_gridAutoColumns' },
                'grid-auto-rows':        { type: 'direct', target: '_gridAutoRows' },
                'grid-column-start':     { type: 'direct', target: '_gridColumnStart' },
                'grid-column-end':       { type: 'direct', target: '_gridColumnEnd' },
                'grid-row-start':        { type: 'direct', target: '_gridRowStart' },
                'grid-row-end':          { type: 'direct', target: '_gridRowEnd' },
                
                // Sizing
                'width':            { type: 'direct', target: '_width' },
                'max-width':        { type: 'direct', target: '_widthMax' },
                'min-width':        { type: 'direct', target: '_widthMin' },
                'height':           { type: 'direct', target: '_height' },
                'min-height':       { type: 'direct', target: '_minHeight' },
                'max-height':       { type: 'direct', target: '_maxHeight' },
                
                // Background (will use raw format)
                'background':            { type: 'backgroundHandler' },
                'background-color':      { type: 'backgroundColor' },
                'background-image':      { type: 'backgroundImage' },
                'background-size':       { type: 'backgroundSize' },
                'background-position':   { type: 'backgroundPosition' },
                'background-repeat':     { type: 'backgroundRepeat' },
                'background-attachment': { type: 'backgroundAttachment' },
                'background-blend-mode': { type: 'backgroundBlendMode' },
                
                // Typography (goes into _typography object)
                'font-family':      { type: 'typography', target: 'font-family', transform: 'cleanFontFamily' },
                'font-size':        { type: 'typography', target: 'font-size', transform: 'numeric' },
                'font-weight':      { type: 'typography', target: 'font-weight' },
                'font-style':       { type: 'typography', target: 'font-style' },
                'line-height':      { type: 'typography', target: 'line-height' },
                'letter-spacing':   { type: 'typography', target: 'letter-spacing' },
                'text-align':       { type: 'typography', target: 'text-align' },
                'text-transform':   { type: 'typography', target: 'text-transform' },
                'color':            { type: 'typography', target: 'color', transform: 'raw' },
                
                // Border
                'border-radius':             { type: 'borderRadius' },
                'border-top-left-radius':    { type: 'borderRadiusCorner', corner: 'top' },
                'border-top-right-radius':   { type: 'borderRadiusCorner', corner: 'right' },
                'border-bottom-right-radius':{ type: 'borderRadiusCorner', corner: 'bottom' },
                'border-bottom-left-radius': { type: 'borderRadiusCorner', corner: 'left' },
                'border':           { type: 'borderHandler' },
                'border-top':       { type: 'borderSide', side: 'top' },
                'border-right':     { type: 'borderSide', side: 'right' },
                'border-bottom':    { type: 'borderSide', side: 'bottom' },
                'border-left':      { type: 'borderSide', side: 'left' },
                'border-width':     { type: 'borderWidth' },
                'border-style':     { type: 'borderStyle' },
                'border-color':     { type: 'borderColor' },
                
                // Box Shadow
                'box-shadow':       { type: 'boxShadow' },
                
                // Position
                'position':         { type: 'direct', target: '_position' },
                'top':              { type: 'direct', target: '_top' },
                'right':            { type: 'direct', target: '_right' },
                'bottom':           { type: 'direct', target: '_bottom' },
                'left':             { type: 'direct', target: '_left' },
                'z-index':          { type: 'direct', target: '_zIndex' },
                
                // Misc
                'opacity':          { type: 'direct', target: '_opacity' },
                'overflow':         { type: 'direct', target: '_overflow' },
                'overflow-x':       { type: 'direct', target: '_overflowX' },
                'overflow-y':       { type: 'direct', target: '_overflowY' },
                'object-fit':       { type: 'direct', target: '_objectFit' },
                'object-position':  { type: 'direct', target: '_objectPosition' },
                'aspect-ratio':     { type: 'direct', target: '_aspectRatio' },
                'cursor':           { type: 'ignore' }, // Not needed in Bricks
                'transition':       { type: 'direct', target: '_cssTransition' },
                'transform':        { type: 'cssGlobal' }, // Use _cssGlobal for transforms
                'visibility':       { type: 'direct', target: '_visibility' },
                'pointer-events':   { type: 'ignore' },

                // Text extras — goes into _cssGlobal (Bricks has no native mapping for these)
                'text-decoration':  { type: 'cssGlobal' },
                'white-space':      { type: 'cssGlobal' },
                'word-break':       { type: 'cssGlobal' },
                'text-overflow':    { type: 'cssGlobal' },
                'line-clamp':       { type: 'cssGlobal' },
                '-webkit-line-clamp': { type: 'cssGlobal' },
                'text-shadow':      { type: 'cssGlobal' },

                // Ignored
                'outline':          { type: 'ignore' },
                'list-style':       { type: 'ignore' },
                'box-sizing':       { type: 'ignore' },
                'vertical-align':   { type: 'ignore' },
            };

            /**
             * Parse inline CSS from style attribute into structured object
             * Example: "padding: 20px 10px; color: #fff" → {padding: "20px 10px", color: "#fff"}
             */
            function parseInlineCSS(styleString) {
                if (!styleString || typeof styleString !== 'string') return {};
                const styles = {};
                styleString.split(';').forEach(rule => {
                    const colonIndex = rule.indexOf(':');
                    if (colonIndex === -1) return;
                    const prop = rule.substring(0, colonIndex).trim();
                    const value = rule.substring(colonIndex + 1).trim();
                    if (prop && value) styles[prop] = value;
                });
                return styles;
            }

            /**
             * Extract CSS value and convert to Bricks format
             * Example: "48px" → "48", "1.5em" → "1.5", "#ffffff" → "#ffffff"
             */
            function extractNumericValue(cssValue) {
                if (!cssValue) return '';
                const match = cssValue.match(/^([\d.]+)(?:px|em|rem|%)?$/);
                return match ? match[1] : cssValue;
            }

            /**
             * Parse padding/margin shorthand into object
             * Example: "20px 10px" → {top:"20",right:"10",bottom:"20",left:"10"}
             */
            function parseBoxModel(value) {
                if (!value) return {};
                const parts = value.trim().split(/\s+/).map(extractNumericValue);
                if (parts.length === 1) return {top:parts[0],right:parts[0],bottom:parts[0],left:parts[0]};
                if (parts.length === 2) return {top:parts[0],right:parts[1],bottom:parts[0],left:parts[1]};
                if (parts.length === 3) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[1]};
                if (parts.length === 4) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[3]};
                return {};
            }

            function extractBricksJSONFromResponse(resp) {
                const cleaned = resp.trim();
                try { const p = JSON.parse(cleaned); if (p.content && Array.isArray(p.content)) return p; } catch(e) {}
                const m = cleaned.match(/\{[\s\S]*"content"\s*:\s*\[[\s\S]*\][\s\S]*\}/);
                if (m) { try { const p = JSON.parse(m[0]); if (p.content && Array.isArray(p.content)) return p; } catch(e) {} }
                return null;
            }

            // ================================================================
            // STEP 2: Core JavaScript Compiler — HTML to Bricks JSON
            // ================================================================

            /**
             * Main compiler function: converts an HTML string to Bricks Builder JSON
             * This replaces the AI-based Phase 2 compilation with 100% JavaScript
             * 
             * @param {string} html - The HTML string to compile (one section)
             * @param {string} googleFonts - Optional Google Fonts URL from @import
             * @return {object} - Bricks JSON structure {content: [...]}
             */
            function compileHtmlToBricksJson(html, googleFonts = '') {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = [];
                const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
                const usedIds = new Set();
                
                // Generate 6-letter Bricks ID
                function genId() {
                    let id;
                    do {
                        id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
                    } while (usedIds.has(id) || ChatState.globalUsedIds.has(id));
                    usedIds.add(id);
                    ChatState.globalUsedIds.add(id);
                    return id;
                }
                
                // Extract numeric value from CSS (e.g., "48px" → "48")
                function extractNumeric(cssValue) {
                    if (!cssValue) return '';
                    const match = String(cssValue).match(/^([\d.]+)(?:px|em|rem|%)?$/);
                    return match ? match[1] : cssValue;
                }
                
                // Clean font family (remove quotes)
                function cleanFontFamily(fontFamily) {
                    if (!fontFamily) return '';
                    return fontFamily.replace(/['"]/g, '').split(',')[0].trim();
                }
                
                // Parse box model (padding/margin) — handles shorthand
                function parseBoxModelValue(value) {
                    if (!value) return {};
                    const parts = value.trim().split(/\s+/).map(extractNumeric);
                    if (parts.length === 1) return {top:parts[0],right:parts[0],bottom:parts[0],left:parts[0]};
                    if (parts.length === 2) return {top:parts[0],right:parts[1],bottom:parts[0],left:parts[1]};
                    if (parts.length === 3) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[1]};
                    if (parts.length === 4) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[3]};
                    return {};
                }
                
                // Parse box shadow
                function parseBoxShadow(value) {
                    if (!value || value === 'none') return null;
                    // Simple parser for: offsetX offsetY blur spread color
                    const parts = value.split(/\s+/);
                    const values = [];
                    let offsetX = '0', offsetY = '0', blur = '0', spread = '0', color = 'rgba(0,0,0,0.1)';
                    
                    if (parts.length >= 2) {
                        offsetX = extractNumeric(parts[0]);
                        offsetY = extractNumeric(parts[1]);
                    }
                    if (parts.length >= 3) blur = extractNumeric(parts[2]);
                    if (parts.length >= 4) {
                        // Check if parts[3] is a color or spread
                        if (parts[3].startsWith('#') || parts[3].startsWith('rgba') || parts[3].startsWith('rgb')) {
                            color = parts[3];
                        } else {
                            spread = extractNumeric(parts[3]);
                            if (parts.length >= 5) color = parts.slice(4).join(' ');
                        }
                    }
                    
                    values.push({
                        offsetX: offsetX,
                        offsetY: offsetY,
                        blur: blur,
                        spread: spread,
                        color: { raw: color }
                    });
                    
                    return { values };
                }
                
                // Parse border
                function parseBorder(value) {
                    if (!value || value === 'none') return null;
                    // Simple parser for: width style color
                    const parts = value.split(/\s+/);
                    let width = '1', style = 'solid', color = '#000000';
                    
                    parts.forEach(part => {
                        if (part.match(/^\d/)) width = extractNumeric(part);
                        else if (['solid', 'dashed', 'dotted', 'double'].includes(part)) style = part;
                        else if (part.startsWith('#') || part.startsWith('rgba') || part.startsWith('rgb')) color = part;
                    });
                    
                    return {
                        width: { top: width, right: width, bottom: width, left: width },
                        style: style,
                        color: { raw: color }
                    };
                }
                
                // Parse border-radius — handles 1/2/3/4 value shorthand
                // Bricks uses radius corners as: top=top-left, right=top-right, bottom=bottom-right, left=bottom-left
                function parseBorderRadius(value) {
                    if (!value) return null;
                    // Handle "50%" or "100px" (single value) or shorthand
                    const parts = value.trim().split(/\s+/).map(v => extractNumeric(v));
                    let tl, tr, br, bl;
                    if (parts.length === 1)      { tl = tr = br = bl = parts[0]; }
                    else if (parts.length === 2)  { tl = br = parts[0]; tr = bl = parts[1]; }
                    else if (parts.length === 3)  { tl = parts[0]; tr = bl = parts[1]; br = parts[2]; }
                    else                          { tl = parts[0]; tr = parts[1]; br = parts[2]; bl = parts[3]; }
                    return { radius: { top: tl, right: tr, bottom: br, left: bl } };
                }
                
                // Parse gradient from CSS
                function parseGradient(value) {
                    if (!value || (!value.includes('linear-gradient') && !value.includes('radial-gradient'))) {
                        return null;
                    }
                    
                    const isRadial = value.startsWith('radial');
                    const angleMatch = value.match(/(\d+)deg/);
                    const colorMatches = [...value.matchAll(/#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)/g)];
                    
                    if (colorMatches.length < 2) return null;
                    
                    return {
                        applyTo: 'overlay',
                        gradientType: isRadial ? 'radial' : 'linear',
                        ...((!isRadial && angleMatch) ? { angle: angleMatch[1] } : {}),
                        colors: colorMatches.map((m, i) => ({
                            id: genId(),
                            color: { raw: m[0] },
                            stop: String(Math.round(i / (colorMatches.length - 1) * 100))
                        }))
                    };
                }
                
                /**
                 * Convert CSS styles object to Bricks settings object
                 */
                function stylesToBricksSettings(cssStyles) {
                    const settings = {};
                    
                    // Process each CSS property using the mapping dictionary
                    Object.keys(cssStyles).forEach(prop => {
                        const value = cssStyles[prop];
                        const mapping = CSS_TO_BRICKS_MAP[prop];
                        
                        if (!mapping || mapping.type === 'ignore') return;
                        
                        switch (mapping.type) {
                            case 'direct':
                                settings[mapping.target] = mapping.map ? mapping.map[value] || value : value;
                                break;
                                
                            case 'numeric':
                                settings[mapping.target] = extractNumeric(value);
                                break;
                                
                            case 'boxModel':
                                settings[mapping.target] = parseBoxModelValue(value);
                                break;
                                
                            case 'directBox':
                                if (!settings[mapping.target]) settings[mapping.target] = {};
                                settings[mapping.target][mapping.side] = extractNumeric(value);
                                break;
                                
                            case 'gapHandler':
                                // Distribute gap to both columnGap and rowGap
                                const gapVal = extractNumeric(value);
                                settings._columnGap = gapVal;
                                settings._rowGap = gapVal;
                                break;
                                
                            case 'typography':
                                if (!settings._typography) settings._typography = {};
                                let typoValue = value;
                                if (mapping.transform === 'numeric') typoValue = extractNumeric(value);
                                else if (mapping.transform === 'cleanFontFamily') typoValue = cleanFontFamily(value);
                                else if (mapping.transform === 'raw') typoValue = { raw: value };
                                settings._typography[mapping.target] = typoValue;
                                break;
                                
                            case 'backgroundColor':
                                if (value.includes('gradient')) {
                                    const grad = parseGradient(value);
                                    if (grad) settings._gradient = grad;
                                } else {
                                    if (!settings._background) settings._background = {};
                                    settings._background.color = { raw: value };
                                }
                                break;
                                
                            case 'backgroundHandler':
                                // Parse complex background property
                                if (value.includes('linear-gradient') || value.includes('radial-gradient')) {
                                    const grad = parseGradient(value);
                                    if (grad) settings._gradient = grad;
                                } else if (value.match(/^(#|rgba?|hsl)/)) {
                                    if (!settings._background) settings._background = {};
                                    settings._background.color = { raw: value };
                                } else if (value.startsWith('url(')) {
                                    // Background image
                                    const urlMatch = value.match(/url\(['"]?([^'"]+)['"]?\)/);
                                    if (urlMatch) {
                                        if (!settings._background) settings._background = {};
                                        settings._background.image = { url: urlMatch[1] };
                                    }
                                }
                                break;
                                
                            case 'backgroundImage':
                                const urlMatch = value.match(/url\(['"]?([^'"]+)['"]?\)/);
                                if (urlMatch) {
                                    if (!settings._background) settings._background = {};
                                    settings._background.image = { url: urlMatch[1] };
                                    // Check for size and position in cssStyles
                                    if (cssStyles['background-size']) {
                                        settings._background.image.size = cssStyles['background-size'];
                                    }
                                    if (cssStyles['background-position']) {
                                        settings._background.image.position = cssStyles['background-position'];
                                    }
                                }
                                break;
                                
                            case 'boxShadow':
                                const shadow = parseBoxShadow(value);
                                if (shadow) settings._boxShadow = shadow;
                                break;
                                
                            case 'borderRadius':
                                const radius = parseBorderRadius(value);
                                if (radius) {
                                    if (!settings._border) settings._border = {};
                                    Object.assign(settings._border, radius);
                                }
                                break;

                            case 'borderRadiusCorner':
                                if (!settings._border) settings._border = {};
                                if (!settings._border.radius) settings._border.radius = {};
                                settings._border.radius[mapping.corner] = extractNumeric(value);
                                break;

                            case 'borderStyle':
                                if (!settings._border) settings._border = {};
                                settings._border.style = value;
                                break;

                            case 'borderWidth':
                                const borderWidthVal = extractNumeric(value);
                                if (!settings._border) settings._border = {};
                                settings._border.width = {
                                    top: borderWidthVal, right: borderWidthVal,
                                    bottom: borderWidthVal, left: borderWidthVal
                                };
                                break;

                            case 'borderColor':
                                if (!settings._border) settings._border = {};
                                settings._border.color = { raw: value };
                                break;

                            case 'borderHandler':
                                const border = parseBorder(value);
                                if (border) settings._border = border;
                                break;

                            case 'borderSide': {
                                // e.g. border-top: 2px solid #000
                                const sideResult = parseBorder(value);
                                if (sideResult) {
                                    if (!settings._border) settings._border = {};
                                    if (!settings._border.width) settings._border.width = { top:'0', right:'0', bottom:'0', left:'0' };
                                    settings._border.width[mapping.side] = sideResult.width.top;
                                    if (!settings._border.style) settings._border.style = sideResult.style;
                                    if (!settings._border.color) settings._border.color = sideResult.color;
                                }
                                break;
                            }

                            case 'backgroundSize': {
                                if (!settings._background) settings._background = {};
                                if (value === 'cover' || value === 'contain') {
                                    settings._background.size = value;
                                } else {
                                    settings._background.size = 'custom';
                                    settings._background.custom = value;
                                }
                                break;
                            }

                            case 'backgroundPosition':
                                if (!settings._background) settings._background = {};
                                settings._background.position = value;
                                break;

                            case 'backgroundRepeat':
                                if (!settings._background) settings._background = {};
                                settings._background.repeat = value;
                                break;

                            case 'backgroundAttachment':
                                if (!settings._background) settings._background = {};
                                settings._background.attachment = value;
                                break;

                            case 'backgroundBlendMode':
                                if (!settings._background) settings._background = {};
                                settings._background.blendMode = value;
                                break;

                            case 'cssGlobal':
                                // For transforms, text-decoration, and complex CSS without native Bricks mapping
                                if (!settings._cssGlobal) settings._cssGlobal = '';
                                settings._cssGlobal += ` ${prop}: ${value};`;
                                break;
                        }
                    });
                    
                    return settings;
                }
                
                /**
                 * Recursively walk DOM element and convert to Bricks JSON
                 */
                function elementToBricks(element, parentId = 0) {
                    // Skip text nodes, comments, scripts, styles
                    if (element.nodeType !== 1) return null;
                    const tagName = element.tagName.toLowerCase();
                    if (['script', 'style', 'meta', 'link', 'title'].includes(tagName)) return null;
                    
                    // Determine Bricks element type from data-bricks attribute or tag name
                    let bricksName = element.getAttribute('data-bricks');
                    if (!bricksName) {
                        // Fallback tag → Bricks element mapping
                        // Note: all basic Bricks elements share the same style settings
                        // (padding, margin, typography, background, border, shadow, position, sizing, flex/grid)
                        // so the CSS → settings conversion applies uniformly to all element types.
                        const tagMap = {
                            'section': 'section',
                            'header': 'section',
                            'footer': 'section',
                            'nav': 'block',       // nav as block (section has strict Bricks constraints)
                            'article': 'block',
                            'aside': 'block',
                            'main': 'block',
                            'div': 'block',
                            'figure': 'block',
                            'figcaption': 'text-basic',
                            'h1': 'heading', 'h2': 'heading', 'h3': 'heading',
                            'h4': 'heading', 'h5': 'heading', 'h6': 'heading',
                            'p': 'text-basic',
                            'span': 'text-basic',
                            'strong': 'text-basic',
                            'em': 'text-basic',
                            'small': 'text-basic',
                            'blockquote': 'text-basic',
                            'button': 'button',
                            'a': 'text-link',     // anchors → text-link (has link + icon support)
                            'img': 'image',
                            'ul': 'text-basic',   // lists rendered as HTML in text-basic
                            'ol': 'text-basic',
                            'table': 'text-basic',
                            'hr': 'block',
                            'svg': 'custom-html-css-script',
                            'canvas': 'custom-html-css-script',
                            'iframe': 'custom-html-css-script',
                        };
                        bricksName = tagMap[tagName] || 'block';
                    }
                    
                    // Generate element object
                    const id = genId();
                    const bricksElement = {
                        id: id,
                        name: bricksName,
                        parent: parentId,
                        children: [],
                        settings: {},
                        themeStyles: []
                    };
                    
                    // Parse inline styles
                    const styleAttr = element.getAttribute('style');
                    if (styleAttr) {
                        const cssStyles = parseInlineCSS(styleAttr);
                        const bricksSettings = stylesToBricksSettings(cssStyles);
                        Object.assign(bricksElement.settings, bricksSettings);
                    }

                    // CSS default for flex-direction is 'row', but Bricks Builder defaults to 'column'.
                    // Explicitly set 'row' when flex is used without a direction, to match CSS behavior.
                    if (bricksElement.settings._display === 'flex' && !bricksElement.settings._direction) {
                        bricksElement.settings._direction = 'row';
                    }
                    
                    // Helper: parse href to Bricks link object
                    function parseLink(el) {
                        const href = el.getAttribute('href') || el.getAttribute('data-href') || '#';
                        return {
                            type: (href.startsWith('#') || href.startsWith('/')) ? 'internal' : 'external',
                            url: href
                        };
                    }

                    // Handle specific element types
                    // All elements also share common style settings (_padding, _margin, _typography,
                    // _background, _border, _boxShadow, _display, flex/grid props, _position, sizing)
                    // handled above via stylesToBricksSettings. Here we set element-specific content fields.
                    let isLeaf = false;
                    switch (bricksName) {
                        case 'heading':
                            bricksElement.settings.text = element.innerHTML.trim(); // allow inner <span> bold/italic
                            bricksElement.settings.tag  = ['h1','h2','h3','h4','h5','h6'].includes(tagName) ? tagName : 'h2';
                            break;

                        case 'text-basic':
                            // Preserve inner HTML (bold, italic, links, lists, etc.)
                            bricksElement.settings.text = element.outerHTML.trim();
                            // For ul/ol/table — treat as leaf (no children walked)
                            if (['ul','ol','table','blockquote'].includes(tagName)) return bricksElement;
                            break;

                        case 'text':
                            // Bricks "text" (rich text) element — wraps full innerHTML
                            bricksElement.settings.text = '<p>' + element.innerHTML.trim() + '</p>';
                            break;

                        case 'text-link': {
                            bricksElement.settings.text = element.textContent.trim();
                            bricksElement.settings.link = parseLink(element);
                            // If it has an <img> child, let it be a button style
                            break;
                        }

                        case 'button': {
                            bricksElement.settings.text = element.textContent.trim();
                            // data-href on <button>, href on <a>
                            const btnHref = element.getAttribute('href') || element.getAttribute('data-href');
                            if (btnHref) bricksElement.settings.link = parseLink(element);
                            break;
                        }

                        case 'image': {
                            const src = element.getAttribute('src') || element.getAttribute('data-src');
                            if (src) bricksElement.settings.image = { url: src, size: 'full' };
                            const alt = element.getAttribute('alt');
                            if (alt) bricksElement.settings.alt = alt;
                            isLeaf = true; // img is a void element — no children
                            break;
                        }

                        case 'custom-html-css-script':
                            bricksElement.settings.content = element.outerHTML;
                            isLeaf = true; // Leaf element — no children walked
                            break;

                        case 'section':
                            // section/container/block — no content fields, children handled below
                            break;

                        default:
                            // block, div, etc. — no content fields, children handled below
                            break;
                    }
                    
                    // Handle data-hover attributes
                    const hoverBg = element.getAttribute('data-hover-background');
                    if (hoverBg) {
                        if (!bricksElement.settings._background) bricksElement.settings._background = {};
                        bricksElement.settings['_background:hover'] = { color: { raw: hoverBg } };
                        // Add transition if not present
                        if (!bricksElement.settings._cssTransition) {
                            bricksElement.settings._cssTransition = 'all 0.3s ease';
                        }
                    }
                    
                    const hoverTransform = element.getAttribute('data-hover-transform');
                    if (hoverTransform) {
                        bricksElement.settings['_transform:hover'] = hoverTransform;
                        if (!bricksElement.settings._cssTransition) {
                            bricksElement.settings._cssTransition = 'all 0.3s ease';
                        }
                    }
                    
                    // Add to content array
                    content.push(bricksElement);

                    // Process children recursively (skip for leaf elements like img, svg)
                    if (!isLeaf) {
                        Array.from(element.children).forEach(child => {
                            const childElement = elementToBricks(child, id);
                            if (childElement) {
                                bricksElement.children.push(childElement.id);
                            }
                        });
                    }
                    
                    return bricksElement;
                }
                
                // Start compilation from body
                const bodyElements = Array.from(doc.body.children);
                bodyElements.forEach(element => {
                    elementToBricks(element, 0);
                });
                
                // Apply responsive rules automatically
                applyResponsiveRules(content);
                
                return { content };
            }
            
            /**
             * STEP 3: Apply automatic responsive adjustments
             * - Large typography (60+) gets tablet and mobile variants
             * - Multi-column grids get responsive breakpoints
             * - Flex rows get mobile column stacking (ALL flex rows, not just large-gap ones)
             * - Padding/margin reduced on mobile
             * Note: breakpoint suffix pattern is :tablet_portrait and :mobile_landscape
             * This same pattern works for ALL Bricks element style settings since all
             * basic elements share the same style tab (padding, margin, typography, background, border, etc.)
             */
            function applyResponsiveRules(contentArray) {
                contentArray.forEach(element => {
                    const settings = element.settings;

                    // ── Typography: scale down large font sizes on smaller screens ──
                    if (settings._typography && settings._typography['font-size']) {
                        const fontSize = parseInt(settings._typography['font-size']);
                        if (fontSize >= 72) {
                            if (!settings['_typography:tablet_portrait'])
                                settings['_typography:tablet_portrait'] = { 'font-size': String(Math.round(fontSize * 0.7)) };
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.5)) };
                        } else if (fontSize >= 48) {
                            if (!settings['_typography:tablet_portrait'])
                                settings['_typography:tablet_portrait'] = { 'font-size': String(Math.round(fontSize * 0.75)) };
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.6)) };
                        } else if (fontSize >= 32) {
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.75)) };
                        }
                    }

                    // ── Grid: responsive column layouts ──
                    if (settings._gridTemplateColumns) {
                        const colMatch = settings._gridTemplateColumns.match(/repeat\((\d+),/);
                        // Count fractions in value (e.g. "1fr 2fr 1fr" = 3 columns)
                        const frCount = (settings._gridTemplateColumns.match(/\d*fr/g) || []).length;
                        const colCount = colMatch ? parseInt(colMatch[1]) : frCount;

                        if (colCount >= 4) {
                            if (!settings['_gridTemplateColumns:tablet_portrait'])
                                settings['_gridTemplateColumns:tablet_portrait'] = 'repeat(2, 1fr)';
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        } else if (colCount === 3) {
                            if (!settings['_gridTemplateColumns:tablet_portrait'])
                                settings['_gridTemplateColumns:tablet_portrait'] = 'repeat(2, 1fr)';
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        } else if (colCount === 2) {
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        }

                        // Reduce grid gap on mobile
                        const gridGap = parseInt(settings._columnGap || settings._gridGap || 0);
                        if (gridGap > 16) {
                            if (!settings['_columnGap:mobile_landscape'])
                                settings['_columnGap:mobile_landscape'] = String(Math.round(gridGap * 0.5));
                            if (!settings['_rowGap:mobile_landscape'])
                                settings['_rowGap:mobile_landscape'] = String(Math.round(gridGap * 0.5));
                        }
                    }

                    // ── Flex rows: stack to column on mobile ──
                    // ALL flex rows get stacked — not just large-gap ones.
                    // This matches real-world mobile design expectations.
                    if (settings._display === 'flex' && settings._direction === 'row') {
                        if (!settings['_direction:mobile_landscape'])
                            settings['_direction:mobile_landscape'] = 'column';

                        // Reduce column gap (becomes vertical gap after stacking)
                        const gap = parseInt(settings._columnGap || 0);
                        if (gap > 16 && !settings['_columnGap:mobile_landscape'])
                            settings['_columnGap:mobile_landscape'] = String(Math.round(gap * 0.5));

                        // Reset justify-content so stacked items fill width
                        if (settings._justifyContent && settings._justifyContent !== 'flex-start' && !settings['_justifyContent:mobile_landscape'])
                            settings['_justifyContent:mobile_landscape'] = 'flex-start';
                    }

                    // ── Section padding: reduce on tablet and mobile ──
                    if (settings._padding) {
                        const topPad    = parseInt(settings._padding.top    || 0);
                        const botPad    = parseInt(settings._padding.bottom || topPad);
                        const leftPad   = parseInt(settings._padding.left   || 0);
                        const rightPad  = parseInt(settings._padding.right  || leftPad);

                        if (topPad >= 80) {
                            if (!settings['_padding:tablet_portrait'])
                                settings['_padding:tablet_portrait'] = {
                                    top: String(Math.round(topPad * 0.7)),
                                    bottom: String(Math.round(botPad * 0.7)),
                                    left: settings._padding.left, right: settings._padding.right
                                };
                            if (!settings['_padding:mobile_landscape'])
                                settings['_padding:mobile_landscape'] = {
                                    top: String(Math.round(topPad * 0.5)),
                                    bottom: String(Math.round(botPad * 0.5)),
                                    left: leftPad > 20 ? String(Math.round(leftPad * 0.6)) : settings._padding.left,
                                    right: rightPad > 20 ? String(Math.round(rightPad * 0.6)) : settings._padding.right
                                };
                        } else if (topPad >= 40) {
                            if (!settings['_padding:mobile_landscape'])
                                settings['_padding:mobile_landscape'] = {
                                    top: String(Math.round(topPad * 0.6)),
                                    bottom: String(Math.round(botPad * 0.6)),
                                    left: settings._padding.left, right: settings._padding.right
                                };
                        }
                    }

                    // ── Width/max-width: full width on mobile ──
                    if (settings._widthMax && settings._widthMax !== '100%') {
                        if (!settings['_widthMax:mobile_landscape'])
                            settings['_widthMax:mobile_landscape'] = '100%';
                    }
                });
            }

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
.snn-bricks-chat-support a { font-size: 14px; color: #666; text-decoration: none; transition: color 0.2s; }
.snn-bricks-chat-support a:hover { color: #820808; }
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
