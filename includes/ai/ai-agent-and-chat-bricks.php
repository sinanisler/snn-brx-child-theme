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
                const idRemap = {};
                const leafTypes = new Set(['heading', 'text-basic', 'button', 'image', 'icon', 'divider', 'custom-html-css-script']);

                function genId() {
                    let id;
                    do { id = Math.random().toString(36).slice(2, 8); } while (localIds.has(id) || globalIdSet.has(id));
                    return id;
                }

                // Pass 1: ensure each element has required fields and unique id
                content.forEach(el => {
                    // Fix missing or duplicate IDs
                    if (!el.id) {
                        el.id = genId(); localIds.add(el.id); globalIdSet.add(el.id); fixed = true;
                        errors.push('Added missing ID: ' + el.id);
                    } else if (localIds.has(el.id) || globalIdSet.has(el.id)) {
                        const oldId = el.id;
                        const newId = genId();
                        idRemap[el.id] = newId;
                        errors.push('Dup ID ' + oldId + '→' + newId);
                        el.id = newId; localIds.add(newId); globalIdSet.add(newId); fixed = true;
                    } else {
                        localIds.add(el.id);
                        globalIdSet.add(el.id);
                    }
                    
                    // Fix missing name
                    if (!el.name) { el.name = 'block'; fixed = true; errors.push('Added missing name to ' + el.id); }
                    
                    // Fix missing parent
                    if (el.parent === undefined) { el.parent = 0; fixed = true; }
                    
                    // Ensure settings object exists
                    if (!el.settings) { el.settings = {}; fixed = true; }
                    
                    // Ensure children array exists for non-leaf elements
                    if (!leafTypes.has(el.name) && !el.children) { el.children = []; fixed = true; }
                    
                    // Remove children from leaf elements
                    if (leafTypes.has(el.name) && el.children) {
                        errors.push('Removed children from leaf element ' + el.id + ' (' + el.name + ')');
                        delete el.children;
                        fixed = true;
                    }
                });

                // Pass 2: remap stale parent/children refs and validate structure
                content.forEach(el => {
                    // Remap parent references
                    if (el.parent && idRemap[el.parent]) { el.parent = idRemap[el.parent]; fixed = true; }
                    
                    // Check for orphaned elements
                    if (el.parent !== 0 && !localIds.has(el.parent)) {
                        errors.push('Orphan ' + el.id + ' (invalid parent ' + el.parent + ')→root');
                        el.parent = 0; fixed = true;
                    }
                    
                    // Remap children references
                    if (el.children) {
                        el.children = el.children.map(c => idRemap[c] || c).filter(c => localIds.has(c));
                        if (el.children.length === 0 && !leafTypes.has(el.name)) {
                            // Container with no children — might be intentional, just note it
                            debugLog('Empty container:', el.id, el.name);
                        }
                    }
                });

                // Pass 3: Validate settings structure
                content.forEach(el => {
                    // Convert string number properties to strings if needed
                    if (el.settings._padding && typeof el.settings._padding === 'object') {
                        ['top', 'right', 'bottom', 'left'].forEach(side => {
                            if (el.settings._padding[side] !== undefined && typeof el.settings._padding[side] !== 'string') {
                                el.settings._padding[side] = String(el.settings._padding[side]);
                                fixed = true;
                            }
                        });
                    }
                    
                    // Same for margin
                    if (el.settings._margin && typeof el.settings._margin === 'object') {
                        ['top', 'right', 'bottom', 'left'].forEach(side => {
                            if (el.settings._margin[side] !== undefined && typeof el.settings._margin[side] !== 'string') {
                                el.settings._margin[side] = String(el.settings._margin[side]);
                                fixed = true;
                            }
                        });
                    }
                    
                    // Validate typography font-size is string
                    if (el.settings._typography?.['font-size'] && typeof el.settings._typography['font-size'] !== 'string') {
                        el.settings._typography['font-size'] = String(el.settings._typography['font-size']);
                        fixed = true;
                    }

                    // Clean up _cssGlobal: strip properties now handled natively
                    if (el.settings._cssGlobal && typeof el.settings._cssGlobal === 'string') {
                        el.settings._cssGlobal = el.settings._cssGlobal
                            .replace(/cursor:\s*pointer;?/g, '')        // Handled natively
                            .replace(/transition:[^;]+;?/g, '')         // Now using _cssTransition
                            .replace(/@media[^{]+\{[^}]+\}/g, '')       // Strip media queries (use native suffixes)
                            .trim();

                        // If the rule block is empty after cleanup, remove it
                        if (el.settings._cssGlobal.match(/^[^{]+\{\s*\}\s*$/) || el.settings._cssGlobal === '') {
                            delete el.settings._cssGlobal;
                            errors.push('Removed empty _cssGlobal from ' + el.id);
                            fixed = true;
                        }
                    }

                    // Remove old _css objects (AI hallucination from old training — use native suffixes instead)
                    if (el.settings._css) {
                        delete el.settings._css;
                        errors.push('Removed legacy _css from ' + el.id);
                        fixed = true;
                    }

                    // Fix incorrect gradient in _background.color.raw — convert to proper _gradient format
                    const bgRaw = el.settings._background && el.settings._background.color && el.settings._background.color.raw;
                    if (bgRaw && typeof bgRaw === 'string' && (bgRaw.includes('linear-gradient') || bgRaw.includes('radial-gradient'))) {
                        const isRadial = bgRaw.startsWith('radial');
                        const angleMatch = bgRaw.match(/(\d+)deg/);
                        const colorMatches = [...bgRaw.matchAll(/#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)/g)];
                        if (colorMatches.length >= 2) {
                            el.settings._gradient = {
                                applyTo: 'overlay',
                                gradientType: isRadial ? 'radial' : 'linear',
                                ...((!isRadial && angleMatch) ? { angle: angleMatch[1] } : {}),
                                colors: colorMatches.map((m, i) => ({ id: 'fx' + el.id + i, color: { raw: m[0] }, stop: String(Math.round(i / (colorMatches.length - 1) * 100)) }))
                            };
                            delete el.settings._background.color;
                            if (!Object.keys(el.settings._background).length) delete el.settings._background;
                            errors.push('Converted invalid gradient in _background.color.raw to _gradient for ' + el.id);
                            fixed = true;
                        }
                    }
                });

                if (fixed || errors.length) debugLog('JSON validation:', { fixed, errors: errors.length });
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
                ]);

                let bricksData = extractBricksJSONFromResponse(response);
                if (bricksData) return bricksData;

                // One retry with stricter prompt
                const retryResp = await callAI([
                    { role: 'system', content: buildPhase2SystemPrompt(sectionIndex, googleFonts) },
                    { role: 'user', content: 'Convert to Bricks JSON:\n\n' + sectionHtml },
                    { role: 'assistant', content: response },
                    { role: 'user', content: 'Invalid JSON. Return ONLY {"content":[...]}. No markdown, no code fences. Start with { end with }.' }
                ]);
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
- MANDATORY: Add data-bricks attributes to ALL structural elements to guide Phase 2 compilation:
  * <section data-bricks="section"> — top-level wrapper
  * <div data-bricks="container"> — layout/centering wrapper (use for flex/grid containers)
  * <div data-bricks="block"> — styled wrapper/card (use for boxes with padding, background, borders)
  * <h1 data-bricks="heading">, <h2 data-bricks="heading">, etc. — headings
  * <p data-bricks="text-basic"> — body text/paragraphs
  * <button data-bricks="button"> — buttons/CTAs
  * <img data-bricks="image"> — images
  * <div data-bricks="custom-html-css-script"> — raw HTML/CSS/JS component (use ONLY when standard elements cannot achieve the result, e.g. animated SVGs, canvas, complex interactive widgets, or embed code)
- Use clean semantic structure: <section data-bricks="section"> → <div data-bricks="container"> → <div data-bricks="block"> → <h2 data-bricks="heading">, <p data-bricks="text-basic">, <button data-bricks="button">
- Simple, semantic class names OK for structure reference: \"container\", \"card\", \"grid\", \"wrapper\" — but ALL visual styling MUST be inline
- This flat structure with data-bricks tagging allows each section to be compiled accurately into Bricks Builder elements
- NOTE: custom CSS (via _cssCustom) can be added to ANY element — prefer this over custom-html-css-script when possible
- WHEN TO USE custom-html-css-script: only for components truly impossible with standard elements (SVG animations, canvas, iframes, complex JS widgets). For everything else, use block + _cssCustom.

LAYOUT PATTERNS (all via inline styles + data-bricks attributes):

Centered container:
  <div data-bricks="container" style="max-width: 1200px; margin: 0 auto; padding: 0 24px;">

Flex column layout:
  <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">

Flex row layout (NO FLEX-WRAP — use Grid instead for multi-column):
  <div data-bricks="container" style="display: flex; flex-direction: row; gap: 40px; align-items: center; justify-content: space-between;">

Grid layout (2 columns — PREFERRED for side-by-side content):
  <div data-bricks="container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px;">

Grid layout (3 columns — PREFERRED for feature grids):
  <div data-bricks="container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">

Card with padding and shadow:
  <div data-bricks="block" style="background: #ffffff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">

STRICT LAYOUT RULES:
✓ USE CSS GRID for all side-by-side layouts (2-column heroes, 3-column features, 4-column grids)
✓ NEVER use flex-wrap for macro layouts — it causes desktop wrapping issues
✓ Use Flexbox only for single-direction layouts (vertical stacks, simple horizontal bars)
✓ Grid syntax: display: grid; grid-template-columns: repeat(N, 1fr); gap: 32px;
✓ For asymmetric layouts: grid-template-columns: 2fr 1fr; (60/40 split) or 1fr 2fr; (40/60 split)

EXAMPLE COMPLETE STRUCTURE (with data-bricks attributes):
<style>@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');</style>

<section data-bricks="section" style="background: #0f172a; padding: 80px 0;">
  <div data-bricks="container" class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 24px; display: flex; flex-direction: column; gap: 32px; align-items: center;">
    <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -1px; margin: 0;">Premium Heading</h1>
    <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; font-weight: 400; color: rgba(203, 213, 225, 1); line-height: 1.7; text-align: center; max-width: 700px; margin: 0;">Supporting description with readable line height and proper spacing.</p>
    <button data-bricks="button" style="background: #2563eb; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 14px 32px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.2s;">Call to Action</button>
  </div>
</section>

EXAMPLE 2-COLUMN GRID HERO (NO FLEX-WRAP):
<section data-bricks="section" style="background: #f5f0eb; padding: 100px 0;">
  <div data-bricks="container" style="max-width: 1400px; margin: 0 auto; padding: 0 24px; display: grid; grid-template-columns: 2fr 1fr; gap: 60px; align-items: center;">
    <div data-bricks="block" style="display: flex; flex-direction: column; gap: 24px;">
      <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 72px; font-weight: 900; color: #111827; line-height: 1.1; margin: 0;">We Make Brands People Love</h1>
      <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; color: #4b5563; line-height: 1.7; margin: 0;">Creative studio specializing in bold brand identities and digital experiences.</p>
      <button data-bricks="button" style="background: #ff6b35; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 16px 32px; border: none; border-radius: 8px; cursor: pointer;">View Our Work</button>
    </div>
    <img data-bricks="image" src="..." style="width: 100%; height: 600px; object-fit: cover; border-radius: 12px;" />
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

            function buildPhase2SystemPrompt(sectionIndex, googleFonts) {
                const fontContext = googleFonts ? `\nGOOGLE FONTS DETECTED:\n${googleFonts}\nUSE these font families in _typography settings (without quotes, just the font name).\n` : '';
                return `You are an expert Bricks Builder JSON compiler. You receive ONE HTML section with INLINE CSS styles and convert it to valid Bricks Builder JSON.

TASK: Convert the provided HTML section to Bricks Builder JSON.
- You are compiling ONE section at a time — not the whole page
- The output must contain exactly one top-level section element with parent:0
- Parse ALL inline style attributes and convert them accurately to Bricks settings
- CRITICAL: Use RAW CSS values via {"raw": "..."} format for all CSS properties — this is your PRIMARY method
- Parse data-bricks attributes to determine exact element types (section/container/block)
- Never invent content or add sections from memory
${fontContext}
OUTPUT: Return ONLY valid JSON. No markdown, no backticks, no explanation. Start with { and end with }

SCHEMA: {"content":[/* array of element objects */]}

ELEMENT STRUCTURE:
{
  "id": "s1abc123",
  "name": "section"|"container"|"block"|"heading"|"text-basic"|"button"|"image"|"icon"|"divider"|"custom-html-css-script",
  "parent": "parent_id" or 0,
  "children": ["child_id1", "child_id2"],
  "settings": {
    /* element-specific settings */
    "_cssGlobal": "custom CSS rules applied globally to element across all breakpoints",
    "_css": {
      "desktop": ".brxe-abc123 { custom: rules; }",
      "tablet-portrait": ".brxe-abc123 { mobile-specific: rules; }",
      "mobile-landscape": ".brxe-abc123 { phone: rules; }"
    }
  },
  "label": "optional descriptive label"
}

ID FORMAT: Every element MUST have a unique 6-character lowercase alphanumeric id, prefixed with "s${sectionIndex}_" (e.g., "s${sectionIndex}_abc123", "s${sectionIndex}_def456").

ELEMENT TYPES & CORE SETTINGS:

=== RAW CSS VALUE FORMAT (PRIMARY METHOD) ===
For ALL CSS properties, you can use the {"raw": "css-value"} format to pass native CSS directly:

Colors:
  OLD: {"color": {"hex": "#ff0000", "alpha": "0.5"}}
  NEW: {"color": {"raw": "rgba(255, 0, 0, 0.5)"}} ✓ PREFERRED
  Also: {"color": {"raw": "#ff0000"}} or {"color": {"raw": "rgb(255, 0, 0)"}}

Backgrounds:
  Solid: {"_background": {"color": {"raw": "#0f172a"}}}
  RGBA: {"_background": {"color": {"raw": "rgba(15, 23, 42, 0.95)"}}}
  GRADIENT — use _gradient (NEVER put a CSS gradient string in _background.color.raw):
    Linear: {"_gradient": {"applyTo": "overlay", "gradientType": "linear", "angle": "135", "colors": [{"id": "ga1", "color": {"raw": "#2563eb"}, "stop": "0"}, {"id": "ga2", "color": {"raw": "#9333ea"}, "stop": "100"}]}}
    Radial:  {"_gradient": {"applyTo": "overlay", "gradientType": "radial", "colors": [{"id": "gb1", "color": {"raw": "#1e293b"}, "stop": "0"}, {"id": "gb2", "color": {"raw": "#050810"}, "stop": "100"}]}}

Typography Colors:
  {"_typography": {"color": {"raw": "#ffffff"}, "font-size": "60"}}
  {"_typography": {"color": {"raw": "rgba(255, 255, 255, 0.8)"}}}

Border Colors:
  {"_border": {"color": {"raw": "#e5e7eb"}, "style": "solid"}}

Box Shadow Colors:
  {"_boxShadow": {"values": [{"color": {"raw": "rgba(0, 0, 0, 0.1)"}, "offsetX": "0", "offsetY": "4"}]}}

WHEN TO USE RAW:
- Any color value (hex, rgb, rgba, hsl)
- Any gradient (linear-gradient, radial-gradient)
- Complex CSS values that don't fit standard schema
- Whenever you see inline CSS — copy it directly as {"raw": "value"}

WHEN TO USE STANDARD FORMAT:
- Simple numeric values: "_padding": {"top": "40"}
- Layout properties: "_direction": "column", "_display": "grid"
- Font families: "font-family": "Inter" (no quotes in value)

section (always parent:0, ONE per output):
  {"id":"s${sectionIndex}_sec001","name":"section","parent":0,"children":["s${sectionIndex}_con001"],"settings":{"_padding":{"top":"80","bottom":"80"},"_background":{"color":{"raw":"#0f172a"}}},"label":"Hero Section"}
  - Section padding: ONLY top/bottom (never left/right)
  - Background: use raw format for colors, gradients, rgba

container (layout wrapper — flex or grid):
  Flex column: {"name":"container","settings":{"_direction":"column","_rowGap":"24","_widthMax":"1200px","_margin":{"left":"auto","right":"auto"},"_padding":{"left":"24","right":"24"}}}
  Flex row: {"name":"container","settings":{"_direction":"row","_columnGap":"32","_alignItems":"center","_justifyContent":"space-between"}}
  CSS Grid: {"name":"container","settings":{"_display":"grid","_gridTemplateColumns":"1fr 1fr 1fr","_gridGap":"32","_widthMax":"1200px","_margin":{"left":"auto","right":"auto"}}}

STRICT NESTING RULES — NEVER VIOLATE:
  - NEVER place a container inside another container. container cannot be a child of container.
  - NEVER mix container and block as siblings inside the same parent container.
  - The ONLY valid hierarchy is: section > container > [block | heading | text-basic | button | image | icon | divider]
  - container children must be: block, heading, text-basic, button, image, icon, divider — NEVER another container.
  - block children must be: heading, text-basic, button, image, icon, divider — NEVER container or block.
  - If you need a two-column layout: section > container (grid/flex-row) > block > [elements]
  - If you need centered content: section > container (max-width centered) > [elements directly]
  - WRONG: section > container > block + container  ← FORBIDDEN
  - WRONG: section > container > container          ← FORBIDDEN
  - RIGHT: section > container > block              ← CORRECT
  - RIGHT: section > container > heading            ← CORRECT

block (wrapper element — card, box, div with styling):
  {"name":"block","settings":{"_direction":"column","_rowGap":"16","_padding":{"top":"32","right":"32","bottom":"32","left":"32"},"_background":{"color":{"raw":"#ffffff"}},"_border":{"radius":{"top":"12","right":"12","bottom":"12","left":"12"},"width":{"top":"1","right":"1","bottom":"1","left":"1"},"style":"solid","color":{"raw":"#e5e7eb"}}}}

heading:
  {"name":"heading","settings":{"text":"Your Heading Text","tag":"h1","_typography":{"font-size":"60","font-weight":"900","color":{"raw":"#ffffff"},"line-height":"1.1","text-align":"center","font-family":"Playfair Display","letter-spacing":"-1px"},"_margin":{"bottom":"20"}}}

text-basic:
  {"name":"text-basic","settings":{"text":"Paragraph or body text content goes here.","_typography":{"font-size":"18","line-height":"1.7","color":{"raw":"#4b5563"},"font-family":"Inter"}}}

button:
  {"name":"button","settings":{"text":"Button Label","link":{"type":"external","url":"#"},"_background":{"color":{"raw":"#2563eb"}},"_typography":{"color":{"raw":"#ffffff"},"font-weight":"600","font-size":"16","font-family":"Inter"},"_padding":{"top":"14","right":"28","bottom":"14","left":"28"},"_border":{"radius":{"top":"8","right":"8","bottom":"8","left":"8"}}}}

image:
  {"name":"image","settings":{"image":{"url":"https://example.com/image.jpg","size":"full"},"_width":"100%","_height":"400px","_objectFit":"cover","_border":{"radius":{"top":"12","right":"12","bottom":"12","left":"12"}}}}

icon:
  {"name":"icon","settings":{"icon":{"library":"themify","icon":"ti-star"},"_typography":{"font-size":"32","color":{"raw":"#f59e0b"}}}}

divider:
  {"name":"divider","settings":{"_height":"1px","_background":{"color":{"raw":"#e5e7eb"}},"_margin":{"top":"24","bottom":"24"}}}

custom-html-css-script (USE ONLY when standard elements cannot achieve the result — SVG animations, canvas, iframes, complex JS widgets):
  {"name":"custom-html-css-script","parent":"parent_id","children":[],"settings":{"content":"<div>Your HTML here</div>\n<style>\n/* scoped CSS */\n</style>\n<script>\n// JS if needed\n<\/script>"}}
  - "content" holds the full raw HTML/CSS/JS string
  - Treat it as a leaf element — no children array items, children must be []
  - Can receive _cssCustom like any element for additional scoped CSS

CUSTOM CSS (_cssCustom — available on ALL elements):
  Use _cssCustom to add arbitrary CSS scoped to the element's generated class (.brxe-ID):
  "_cssCustom": "#brxe-abc123 { mix-blend-mode: multiply; filter: blur(2px); }"
  - Preferred over custom-html-css-script for pure CSS effects
  - Use when the needed style has no native Bricks setting (mix-blend-mode, filter, clip-path, etc.)

KEY SETTINGS REFERENCE (values are STRINGS without "px" unless specified):

LAYOUT & FLEXBOX:
  _direction: "row"|"column"
  _display: "flex"|"grid"|"block"|"inline-block"
  _columnGap: "32"  (string, no units)
  _rowGap: "24"
  _justifyContent: "center"|"flex-start"|"flex-end"|"space-between"|"space-around"
  _alignItems: "center"|"flex-start"|"flex-end"|"stretch"
  _flexWrap: "wrap"|"nowrap"
  
CSS GRID:
  _gridTemplateColumns: "1fr 1fr 1fr"|"repeat(3, 1fr)"|"2fr 1fr"
  _gridTemplateRows: "auto"|"200px 200px"
  _gridGap: "32"
  _gridColumn: "span 2"|"1 / 3"
  _gridRow: "span 2"|"1 / 2"

SIZING:
  _width: "100%"|"50%"|"400px"
  _widthMax: "1200px"|"900px"
  _widthMin: "300px"
  _height: "400px"|"100vh"|"auto"
  _minHeight: "500px"|"100vh"
  
SPACING:
  _padding: {"top":"40","right":"40","bottom":"40","left":"40"}  (individual sides as strings)
  _margin: {"top":"0","right":"auto","bottom":"0","left":"auto"}  (use "auto" for centering)

BACKGROUND:
  Solid color: _background: {"color": {"raw": "#000000"}}
  RGBA: _background: {"color": {"raw": "rgba(15, 23, 42, 0.9)"}}
  Image (still uses standard format): _background: {"image": {"url": "https://...", "size": "cover", "position": "center center"}}
  GRADIENT — use _gradient property (NEVER use linear-gradient/radial-gradient inside _background.color.raw):
    Linear:   _gradient: {"applyTo": "overlay", "gradientType": "linear", "angle": "135", "colors": [{"id": "ga1", "color": {"raw": "#2563eb"}, "stop": "0"}, {"id": "ga2", "color": {"raw": "#9333ea"}, "stop": "100"}]}
    Radial:   _gradient: {"applyTo": "overlay", "gradientType": "radial", "colors": [{"id": "gb1", "color": {"raw": "#1e293b"}, "stop": "0"}, {"id": "gb2", "color": {"raw": "#050810"}, "stop": "100"}]}
    applyTo: "overlay" | "background" — angle (string degrees, linear only) — stop (string %, optional)

TYPOGRAPHY:
  _typography: {
    "font-family": "Playfair Display"|"Inter"|"Lora" (NO quotes in value),
    "font-size": "20",
    "font-weight": "400"|"500"|"600"|"700"|"800"|"900",
    "line-height": "1.5"|"1.7",
    "letter-spacing": "0"|"-1px"|"0.05em",
    "text-align": "left"|"center"|"right",
    "text-transform": "none"|"uppercase"|"lowercase"|"capitalize",
    "font-style": "normal"|"italic",
    "color": {"raw": "#000000"|"rgba(0, 0, 0, 0.8)"|"rgb(255, 255, 255)"}
  }

BORDER:
  _border: {
    "radius": {"top": "12", "right": "12", "bottom": "12", "left": "12"},
    "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
    "style": "solid"|"dashed"|"dotted"|"none",
    "color": {"raw": "#e5e7eb"|"rgba(229, 231, 235, 0.5)"}
  }

BOX SHADOW:
  Simple: _boxShadow: {"values": [{"offsetX": "0", "offsetY": "4", "blur": "6", "spread": "0", "color": {"raw": "rgba(0, 0, 0, 0.1)"}}]}
  Multiple: _boxShadow: {"values": [{"offsetX": "0", "offsetY": "4", "blur": "6", "color": {"raw": "rgba(0, 0, 0, 0.1)"}}, {"offsetX": "0", "offsetY": "10", "blur": "20", "color": {"raw": "rgba(0, 0, 0, 0.05)"}}]}

POSITION:
  _position: "relative"|"absolute"|"fixed"|"sticky"
  _top: "0"|"20px"
  _left: "0"|"50%"
  _right: "0"
  _bottom: "0"
  _zIndex: "10"|"100"

MISC:
  _opacity: "0.8"  (string, 0–1)
  _overflow: "hidden"|"visible"|"auto"|"scroll"
  _aspectRatio: "16/9"|"4/3"|"1/1"
  _objectFit: "cover"|"contain"|"fill"|"none"

CUSTOM CSS (_cssGlobal only — for animations and complex selectors):
  Use _cssGlobal ONLY for @keyframes animations and advanced pseudo-element selectors.
  Do NOT use _cssGlobal for transitions (use _cssTransition), hover (use native hover suffixes), or media queries (use native breakpoint suffixes).
    "_cssGlobal": "@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .brxe-card001 { animation: fadeIn 0.5s ease-out; }"

  Complex selectors (pseudo-elements only):
    "_cssGlobal": ".brxe-nav001 > * + * { margin-left: 24px; } .brxe-card001::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); opacity: 0.1; }"

TRANSITIONS & HOVER STATES (NATIVE SUFFIXES — NO _cssGlobal):
  Transitions: "_cssTransition": "all 0.3s ease"
  Hover Background: data-hover-background="darkred" in HTML → "_background:hover": {"color": {"raw": "darkred"}}
  Hover Transform: data-hover-transform="translateY(-2px)" in HTML → "_transform:hover": "translateY(-2px)"
  Hover Box Shadow: → "_boxShadow:hover": {"values": [{"offsetX": "0", "offsetY": "8", "blur": "16", "spread": "0", "color": {"raw": "rgba(0,0,0,0.2)"}}]}

  Example — Button with hover:
  {
    "name": "button",
    "settings": {
      "_background": {"color": {"raw": "#2563eb"}},
      "_background:hover": {"color": {"raw": "#1d4ed8"}},
      "_transform:hover": "translateY(-2px)",
      "_cssTransition": "all 0.3s ease"
    }
  }

PARSING INLINE CSS → BRICKS (USE RAW FORMAT):
  Extract from style="..." attributes and use raw format for CSS values:
  - background: #0f172a → _background: {"color": {"raw": "#0f172a"}}
  - background: rgba(15,23,42,0.9) → _background: {"color": {"raw": "rgba(15, 23, 42, 0.9)"}}
  - background: linear-gradient(90deg, #06b6d4, #3b82f6) → _gradient: {"applyTo": "overlay", "gradientType": "linear", "angle": "90", "colors": [{"id": "ga1", "color": {"raw": "#06b6d4"}, "stop": "0"}, {"id": "ga2", "color": {"raw": "#3b82f6"}, "stop": "100"}]}
  - background: radial-gradient(circle at top, #1e293b, #050810) → _gradient: {"applyTo": "overlay", "gradientType": "radial", "colors": [{"id": "gb1", "color": {"raw": "#1e293b"}, "stop": "0"}, {"id": "gb2", "color": {"raw": "#050810"}, "stop": "100"}]}
  - padding: 40px 20px → _padding: {"top": "40", "right": "20", "bottom": "40", "left": "20"}
  - padding: 60px 0 → _padding: {"top": "60", "right": "0", "bottom": "60", "left": "0"}
  - font-size: 48px → _typography: {"font-size": "48"}
  - font-family: 'Inter', sans-serif → _typography: {"font-family": "Inter"}
  - color: #ffffff → _typography: {"color": {"raw": "#ffffff"}}
  - color: rgba(255,255,255,0.8) → _typography: {"color": {"raw": "rgba(255, 255, 255, 0.8)"}}
  - display: flex → _display: "flex"
  - flex-direction: column → _direction: "column"
  - gap: 32px → _columnGap: "32" or _rowGap: "32" depending on flex-direction
  - border-radius: 12px → _border: {"radius": {"top": "12", "right": "12", "bottom": "12", "left": "12"}}
  - border-color: #e5e7eb → _border: {"color": {"raw": "#e5e7eb"}}
  - box-shadow: 0 4px 6px rgba(0,0,0,0.1) → _boxShadow: {"values": [{"offsetX": "0", "offsetY": "4", "blur": "6", "spread": "0", "color": {"raw": "rgba(0, 0, 0, 0.1)"}}]}
  - transition: all 0.3s ease → "_cssTransition": "all 0.3s ease"
  - transform: scale(1.05) → "_cssGlobal": ".brxe-abc123 { transform: scale(1.05); }"
  - :hover states → use "_background:hover", "_transform:hover", "_boxShadow:hover" native suffixes (see TRANSITIONS & HOVER STATES section)

DATA-BRICKS ATTRIBUTE PARSING:
  The HTML will contain data-bricks attributes that tell you the EXACT element type:
  - <section data-bricks="section"> → {"name": "section"}
  - <div data-bricks="container"> → {"name": "container"}
  - <div data-bricks="block"> → {"name": "block"}
  - <h1 data-bricks="heading"> → {"name": "heading"}
  - <p data-bricks="text-basic"> → {"name": "text-basic"}
  - <button data-bricks="button"> → {"name": "button"}
  - <img data-bricks="image"> → {"name": "image"}
  - <div data-bricks="custom-html-css-script"> → {"name": "custom-html-css-script"} — place all inner HTML/CSS/JS in settings.content
  CRITICAL: Respect the data-bricks attribute — it ensures correct Bricks hierarchy (Section > Container > Block/Elements)

For transforms and complex animations only, use _cssGlobal:
  Example: style="transform: scale(1.05);"
  → "_cssGlobal": ".brxe-abc123 { transform: scale(1.05); }"
  Transitions belong in "_cssTransition". Hover states belong in native hover suffix keys.

STRUCTURE RULES:
1. ONE section element per output (parent:0)
2. Section → Container (centering/max-width) → Container (layout) or Block (card) → Leaf elements
3. Block = styled wrapper (padding, background, border). Container = layout (flex/grid, no visual styling)
4. Leaf elements (heading, text-basic, button, image, icon, divider) NEVER have children
5. Every element needs unique id, correct parent reference
6. All numeric property values are STRINGS without units: "40" not "40px" not 40
7. Use {"raw": "css-value"} format for ALL color values and complex CSS
8. Parse data-bricks attributes to determine element types — respect the structural hints
9. Max nesting: 4–5 levels deep maximum
10. parent value must exactly match an element's id (or 0 for section)

VALIDATION CHECKLIST:
✓ Unique IDs with s${sectionIndex}_ prefix
✓ Valid parent-child relationships  
✓ No orphaned elements
✓ All properties as strings
✓ Proper hex colors
✓ Font families without quotes in value
✓ ONE section with parent:0
✓ Leaf elements have no children

RESPONSIVE DESIGN (NATIVE BREAKPOINT SUFFIXES — NO _css OBJECT, NO MEDIA QUERIES):
Do NOT write media queries. Do NOT use _css object. To change a style on a smaller screen, duplicate the property key and append the breakpoint suffix: :tablet_portrait, :mobile_landscape, or :mobile_portrait.

Available suffixes (append to any settings key):
  :tablet_portrait  — screens ≤ 1024px
  :mobile_landscape — screens ≤ 767px
  :mobile_portrait  — screens ≤ 479px

Example — Responsive typography (font-size 60+):
{
  "name": "heading",
  "settings": {
    "text": "Responsive Heading",
    "_typography": {"font-size": "60", "font-weight": "900"},
    "_typography:tablet_portrait": {"font-size": "48"},
    "_typography:mobile_landscape": {"font-size": "36"}
  }
}

Example — Responsive padding/layout:
{
  "name": "container",
  "settings": {
    "_padding": {"top": "80", "bottom": "80"},
    "_padding:tablet_portrait": {"top": "60", "bottom": "60"},
    "_padding:mobile_landscape": {"top": "40", "bottom": "40"}
  }
}

Example — Responsive Grid (3+ columns MUST include tablet/mobile variants):
{
  "name": "container",
  "settings": {
    "_display": "grid",
    "_gridTemplateColumns": "repeat(3, 1fr)",
    "_gridTemplateColumns:tablet_portrait": "repeat(2, 1fr)",
    "_gridTemplateColumns:mobile_landscape": "1fr",
    "_gridGap": "32",
    "_gridGap:mobile_landscape": "16"
  }
}

Example — Responsive flex direction:
{
  "name": "container",
  "settings": {
    "_display": "flex",
    "_direction": "row",
    "_direction:mobile_landscape": "column",
    "_columnGap": "32",
    "_columnGap:mobile_landscape": "16"
  }
}

AUTOMATIC RESPONSIVE RULES (apply these always):
- If _typography font-size is 60+: MUST add :tablet_portrait (48) and :mobile_landscape (36)
- If grid has 3+ columns: MUST add :tablet_portrait (2 cols) and :mobile_landscape (1 col)
- If grid has 2 columns: MUST add :mobile_landscape (1 col)
- If flex row has gap 32+: MUST add :mobile_landscape with reduced gap (16)
- If flex-direction is row with large items: MUST add :mobile_landscape column

STRICT 1:1 DOM MAPPING & POSITIONING:
NEVER merge or delete HTML nodes into CSS pseudo-elements (::before/::after). Every HTML tag provided must become its own Bricks JSON object with its own entry in the content array.
For absolutely positioned elements (position: absolute in inline style), map natively:
  "_position": "absolute", "_top": "20", "_right": "auto", "_bottom": "auto", "_left": "0", "_zIndex": "10"

ADVANCED CSS EXAMPLES:

Animations (use _cssGlobal for @keyframes only):
"_cssGlobal": "@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .brxe-card001 { animation: fadeIn 0.5s ease-out; }"

Complex pseudo-element selectors:
"_cssGlobal": ".brxe-nav001 > * + * { margin-left: 24px; } .brxe-card001::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); opacity: 0.1; }"`;

            }

            // ================================================================
            // Helpers
            // ================================================================

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
