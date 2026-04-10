<?php
/**
 * SNN Bricks Editor – Custom CSS Overlay
 *
 * Adds a floating, resizable CSS editor (CodeMirror) to the Bricks builder.
 * Syncs with the selected element's _cssCustom or the page's customCss in real-time.
 *
 * Activated by the "Enable Custom CSS Overlay" checkbox in
 * Bricks Builder Editor Settings.
 */

// ── Frontend script + style enqueue ──────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'snn_custom_css_overlay_enqueue' );
function snn_custom_css_overlay_enqueue() {
    if ( is_admin() ) {
        return;
    }

    $options = get_option( 'snn_editor_settings' );
    if (
        empty( $options['snn_custom_css_overlay_enabled'] ) ||
        ! isset( $_GET['bricks'] ) ||
        $_GET['bricks'] !== 'run' ||
        ! current_user_can( 'manage_options' )
    ) {
        return;
    }

    // Use WordPress's built-in code editor which properly loads CodeMirror with all addons
    $settings = wp_enqueue_code_editor( [ 'type' => 'text/css' ] );
    
    // Fallback: If code editor couldn't be enqueued, manually load CodeMirror
    if ( false === $settings ) {
        $v = get_bloginfo( 'version' );
        
        if ( ! wp_style_is( 'wp-codemirror', 'registered' ) ) {
            wp_register_style(
                'wp-codemirror',
                includes_url( 'js/codemirror/codemirror.min.css' ),
                [],
                $v
            );
        }
        wp_enqueue_style( 'wp-codemirror' );

        if ( ! wp_script_is( 'wp-codemirror', 'registered' ) ) {
            wp_register_script(
                'wp-codemirror',
                includes_url( 'js/codemirror/codemirror.min.js' ),
                [],
                $v,
                true
            );
        }
        wp_enqueue_script( 'wp-codemirror' );
    }
}

// ── HTML + CSS + JS injected into the Bricks builder page ────────────────────

add_action( 'wp_footer', 'snn_custom_css_overlay_output', 99 );
function snn_custom_css_overlay_output() {
    if ( is_admin() ) {
        return;
    }

    $options = get_option( 'snn_editor_settings' );
    if (
        empty( $options['snn_custom_css_overlay_enabled'] ) ||
        ! isset( $_GET['bricks'] ) ||
        $_GET['bricks'] !== 'run' ||
        ! current_user_can( 'manage_options' )
    ) {
        return;
    }

    // Check if AI features are enabled
    $snn_css_ai_enabled = false;
    $snn_css_ai_config  = [];
    if ( get_option( 'snn_ai_enabled', 'no' ) === 'yes' && function_exists( 'snn_get_ai_api_config' ) ) {
        $ai_cfg = snn_get_ai_api_config();
        if ( ! empty( $ai_cfg['apiKey'] ) && ! empty( $ai_cfg['apiEndpoint'] ) ) {
            $snn_css_ai_enabled = true;
            $snn_css_ai_config  = $ai_cfg;
        }
    }
    ?>
    <style id="snn-custom-css-overlay-styles">
        /* ── Overlay container ── */
        #snn-css-overlay {
            position: fixed;
            bottom: 0;
            left: 0;       /* adjusted dynamically by JS */
            right: 0;      /* adjusted dynamically by JS */
            z-index: 999;
            background: var(--builder-bg, #1e1e2e);
            border-top: 2px solid var(--builder-bg-2, #2a2a3d);
            display: flex;
            flex-direction: column;
            transition: height 0.2s ease;
            /* Initial height */
            height: 260px;
            min-height: 30px;
        }
        #snn-css-overlay.snn-collapsed {
            height: 30px !important;
        }
        #snn-css-overlay.snn-hidden {
            display: none !important;
        }

        /* ── Resize handle ── */
        #snn-css-resize-handle {
            position: relative;
            height: 5px;
            cursor: ns-resize;
            background: var(--builder-bg-2, #2a2a3d);
            flex-shrink: 0;
            transition: transform 0.15s ease, background 0.15s ease;
            transform-origin: bottom;  /* Grow upward from the bottom edge */
        }
        /* Invisible larger hit zone — extends upward only, not into the topbar */
        #snn-css-resize-handle::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 0;
            right: 0;
            height: 13px;
            cursor: ns-resize;
        }
        /* Visual indicator line */
        #snn-css-resize-handle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 2px;
            border-radius: 2px;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        #snn-css-resize-handle:hover {
            transform: scaleY(3);  /* 5px * 3 = 15px visually, but no layout shift */
            background: var(--builder-color-accent, #7b68ee);
        }
        #snn-css-resize-handle:hover::after {
            opacity: 1;
        }
        #snn-css-resize-handle.dragging {
            transform: scaleY(4);  /* 5px * 4 = 20px visually */
            background: var(--builder-color-accent, #7b68ee);
        }
        #snn-css-resize-handle.dragging::after {
            opacity: 1;
            background: rgba(255,255,255,0.4);
        }

        /* ── Top bar ── */
        #snn-css-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--builder-bg, #1e1e2e);
            padding: 0 8px;
            height: 30px;
            flex-shrink: 0;
            gap: 6px;
            overflow: hidden;
        }
        #snn-css-title {
            font-size: 11px;
            color: #aaa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            font-family: monospace;
        }
        #snn-css-bp-indicator {
            font-size: 10px;
            font-family: monospace;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 3px;
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.55);
            background: rgba(255,255,255,.05);
            flex-shrink: 0;
            white-space: nowrap;
            transition: background .15s, color .15s, border-color .15s;
        }
        #snn-css-bp-indicator[data-bp="desktop"]          { color: rgba(255,255,255,.55); }
        #snn-css-bp-indicator[data-bp="tablet_portrait"]  { color: #89dceb; border-color: rgba(137,220,235,.3); background: rgba(137,220,235,.08); }
        #snn-css-bp-indicator[data-bp="mobile_landscape"] { color: #a6e3a1; border-color: rgba(166,227,161,.3); background: rgba(166,227,161,.08); }
        #snn-css-bp-indicator[data-bp="mobile_portrait"]  { color: #fab387; border-color: rgba(250,179,135,.3); background: rgba(250,179,135,.08); }
        #snn-css-topbar-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }
        .snn-css-topbar-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 3px;
            padding: 0;
            transition: background 0.15s, color 0.15s;
        }
        .snn-css-topbar-btn:hover {
            background: var(--builder-bg-2, #2a2a3d);
            color: #fff;
        }
        .snn-css-topbar-btn svg {
            width: 14px;
            height: 14px;
        }

        /* ── Editor area ── */
        #snn-css-editor-wrap .CodeMirror {
            height: 100%;
            background: var(--builder-bg-2, #2a2a3d);
            color: #cdd6f4;
            font-size: 13px;
            font-family: 'Fira Mono', 'Cascadia Code', 'Consolas', monospace;
        }
        #snn-css-editor-wrap .CodeMirror-scroll {
            height: 100%;
        }
        #snn-css-editor-wrap .CodeMirror-gutters {
            background: var(--builder-bg, #1e1e2e);
            border-right: 1px solid var(--builder-bg-2, #2a2a3d);
        }
        #snn-css-editor-wrap .CodeMirror-linenumber {
            color: rgba(255,255,255,.22);
        }
        #snn-css-editor-wrap .CodeMirror-cursor {
            border-left-color: rgba(255,255,255,.75) !important;
        }
        #snn-css-editor-wrap .CodeMirror-selected,
        #snn-css-editor-wrap .CodeMirror-line::selection,
        #snn-css-editor-wrap .CodeMirror-line > span::selection {
            background: rgba(120,100,230,.28) !important;
        }
        #snn-css-editor-wrap .CodeMirror-activeline-background {
            background: rgba(255,255,255,.035) !important;
        }
        #snn-css-editor-wrap .CodeMirror-matchingbracket {
            color: #fff !important;
            background: rgba(120,100,230,.4);
            outline: none;
        }
        /* ── CSS syntax token colours (Catppuccin Mocha) ── */
        #snn-css-editor-wrap .cm-keyword    { color: #cba6f7; }  /* at-rules, !important */
        #snn-css-editor-wrap .cm-atom       { color: #fab387; }  /* named colours, values */
        #snn-css-editor-wrap .cm-number     { color: #fab387; }  /* numbers + units */
        #snn-css-editor-wrap .cm-def        { color: #89b4fa; }  /* custom-property defs */
        #snn-css-editor-wrap .cm-variable   { color: #cdd6f4; }
        #snn-css-editor-wrap .cm-variable-2 { color: #89dceb; }
        #snn-css-editor-wrap .cm-variable-3,
        #snn-css-editor-wrap .cm-type       { color: #89b4fa; }
        #snn-css-editor-wrap .cm-property   { color: #89dceb; }  /* property names */
        #snn-css-editor-wrap .cm-operator   { color: rgba(255,255,255,.55); }
        #snn-css-editor-wrap .cm-comment    { color: rgba(255,255,255,.3); font-style: italic; }
        #snn-css-editor-wrap .cm-string     { color: #a6e3a1; }
        #snn-css-editor-wrap .cm-string-2   { color: #a6e3a1; }
        #snn-css-editor-wrap .cm-meta       { color: #cba6f7; }  /* @media etc */
        #snn-css-editor-wrap .cm-qualifier  { color: #fab387; }  /* .class / #id selectors */
        #snn-css-editor-wrap .cm-builtin    { color: #cba6f7; }
        #snn-css-editor-wrap .cm-bracket    { color: rgba(255,255,255,.55); }
        #snn-css-editor-wrap .cm-tag        { color: #f38ba8; }  /* element selectors */
        #snn-css-editor-wrap .cm-attribute  { color: #fab387; }
        #snn-css-editor-wrap .cm-hr         { color: rgba(255,255,255,.28); }
        #snn-css-editor-wrap .cm-link       { color: #89b4fa; text-decoration: underline; }
        #snn-css-editor-wrap .cm-error      { color: #f38ba8; }
        .CodeMirror-hints {
            z-index: 100000 !important;
            background: var(--builder-bg, #1e1e2e) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            border-radius: 4px !important;
            box-shadow: 0 4px 16px rgba(0,0,0,.5) !important;
            font-family: 'Cascadia Code','Fira Mono','Consolas',monospace !important;
            font-size: 12px !important;
        }
        .CodeMirror-hint {
            color: #cdd6f4 !important;
            padding: 2px 8px !important;
        }
        .CodeMirror-hint-active {
            background: black !important;
            color: #fff !important;
        }

        /* ── AI Sidebar ── */
        #snn-css-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }
        #snn-css-editor-wrap {
            flex: 1;
            overflow: hidden;
            background: var(--builder-bg-2, #2a2a3d);
            min-height: 0;
            min-width: 0;
        }
        #snn-css-ai-sidebar {
            width: 300px;
            flex-shrink: 0;
            background: var(--builder-bg, #1e1e2e);
            border-left: 1px solid rgba(255,255,255,.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #snn-css-ai-sidebar.snn-hidden {
            display: none !important;
        }
        #snn-css-ai-sidebar-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 8px;
            gap: 6px;
            box-sizing: border-box;
        }
        #snn-css-ai-sidebar-title {
            font-size: 10px;
            color: rgba(255,255,255,.4);
            text-transform: uppercase;
            letter-spacing: .06em;
            font-family: monospace;
            flex-shrink: 0;
        }
        #snn-css-ai-prompt {
            flex: 1;
            resize: none;
            background: var(--builder-bg-2, #2a2a3d);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 4px;
            color: #cdd6f4;
            font-size: 12px;
            font-family: 'Cascadia Code','Fira Mono','Consolas',monospace;
            padding: 6px 8px;
            outline: none;
            min-height: 60px;
            line-height: 1.5;
        }
        #snn-css-ai-prompt:focus {
            border-color: rgba(123,104,238,.5);
        }
        #snn-css-ai-prompt::placeholder {
            color: rgba(255,255,255,.25);
        }
        #snn-css-ai-response {
            flex: 1;
            overflow-y: auto;
            background: var(--builder-bg-2, #2a2a3d);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 4px;
            color: #a6e3a1;
            font-size: 11px;
            font-family: 'Cascadia Code','Fira Mono','Consolas',monospace;
            padding: 6px 8px;
            white-space: pre-wrap;
            word-break: break-word;
            display: none;
            min-height: 0;
        }
        #snn-css-ai-spinner {
            text-align: center;
            color: rgba(255,255,255,.4);
            font-size: 11px;
            padding: 6px 0;
            display: none;
        }
        #snn-css-ai-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }
        .snn-css-ai-btn {
            flex: 1;
            background: var(--builder-bg-2, #2a2a3d);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 3px;
            color: #cdd6f4;
            font-size: 11px;
            cursor: pointer;
            padding: 4px 6px;
            transition: background .15s, color .15s;
            text-align: center;
        }
        .snn-css-ai-btn:hover {
            background: var(--builder-color-accent, #000000);
            color: #000000;
            border-color: transparent;
        }
        .snn-css-ai-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }
        #snn-css-ai-send-btn:hover:not(:disabled) {
            background: #000000;
            color:white;
        }
        #snn-css-ai-apply-btns {
            display: none;
            gap: 4px;
            flex-shrink: 0;
        }
        .snn-css-topbar-btn.snn-css-ai-toggle-btn.active {
            color: var(--builder-color-accent, #7b68ee);
            background: rgba(123,104,238,.15);
        }
    </style>

    <!-- SNN Custom CSS Overlay -->
    <div id="snn-css-overlay" class="snn-hidden">
        <!-- Resize handle dragged upward -->
        <div id="snn-css-resize-handle"></div>

        <!-- Top bar -->
        <div id="snn-css-topbar">
            <span id="snn-css-title">CSS – Page</span>
            <span id="snn-css-bp-indicator" data-bp="desktop" title="Active breakpoint">Desktop</span>
            <div id="snn-css-topbar-actions">
                <?php if ( $snn_css_ai_enabled ) : ?>
                <!-- AI assistant toggle -->
                <button class="snn-css-topbar-btn snn-css-ai-toggle-btn" id="snn-css-ai-btn" title="AI CSS Assistant">
                    <span style="font-size:22px;background:linear-gradient(45deg,#2271b1,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:inline-block;cursor:pointer;line-height:1.2">✦</span>
                </button>
                <?php endif; ?>
                <!-- Collapse / expand -->
                <button class="snn-css-topbar-btn" id="snn-css-collapse-btn" title="Collapse">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <!-- Close (hide entirely) -->
                <button class="snn-css-topbar-btn" id="snn-css-close-btn" title="Close">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Editor + AI sidebar -->
        <div id="snn-css-body">
            <!-- CodeMirror editor (initialized directly on this div) -->
            <div id="snn-css-editor-wrap"></div>

            <?php if ( $snn_css_ai_enabled ) : ?>
            <!-- AI Sidebar -->
            <div id="snn-css-ai-sidebar" class="snn-hidden">
                <div id="snn-css-ai-sidebar-inner">
                    <div id="snn-css-ai-sidebar-title">AI CSS Assistant</div>
                    <textarea id="snn-css-ai-prompt" placeholder="Describe the CSS you need&#10;e.g. make it a flex container centered&#10;or add a hover scale effect…" rows="4"></textarea>
                    <div id="snn-css-ai-spinner">Generating…</div>
                    <div id="snn-css-ai-response"></div>
                    <div id="snn-css-ai-actions">
                        <button class="snn-css-ai-btn" id="snn-css-ai-send-btn">Generate</button>
                    </div>
                    <div id="snn-css-ai-apply-btns">
                        <button class="snn-css-ai-btn" id="snn-css-ai-inject-btn">Inject</button>
                        <button class="snn-css-ai-btn" id="snn-css-ai-replace-btn">Replace</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        // Don't run in the preview iframe - only in the main editor frame
        if (window.location.href.indexOf('brickspreview=true') > -1) {
            return;
        }
        
        // Also check if we're in an iframe without the editor UI
        if (window.self !== window.top) {
            setTimeout(function() {
                if (!document.querySelector('#bricks-panel-header')) {
                    return;
                }
            }, 500);
        }

        /* ── State ── */
        var overlay        = null;
        var editorWrap     = null;
        var titleEl        = null;
        var bpIndicatorEl  = null;
        var cmInstance     = null;
        var isCollapsed    = false;
        var lastFullHeight = 260;
        var currentMode    = 'page';
        var currentElemId  = null;
        var isSyncing      = false;
        var writeCssTimer  = null;

        /* ── Breakpoint helpers ── */
        var BP_LABELS = {
            'desktop'          : 'D',
            'tablet_portrait'  : 'T',
            'mobile_landscape' : 'ML',
            'mobile_portrait'  : 'M'
        };
        var BP_FULL_LABELS = {
            'desktop'          : 'Desktop',
            'tablet_portrait'  : 'Tablet',
            'mobile_landscape' : 'Mobile Landscape',
            'mobile_portrait'  : 'Mobile'
        };

        function getActiveBreakpoint() {
            var state = getBricksState();
            if (!state) return 'desktop';
            try { return state.breakpointActive || 'desktop'; } catch(e) { return 'desktop'; }
        }

        function getCssKeyForBreakpoint(bp) {
            if (!bp || bp === 'desktop') return '_cssCustom';
            return '_cssCustom:' + bp;
        }

        /* ── localStorage persistence ── */
        var STORAGE_KEY = 'snn_css_overlay_state';

        function saveState() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    open      : overlay && !overlay.classList.contains('snn-hidden'),
                    collapsed : isCollapsed,
                    height    : lastFullHeight
                }));
            } catch(e) {}
        }

        function loadState() {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                return raw ? JSON.parse(raw) : null;
            } catch(e) { return null; }
        }

        function restoreState() {
            var state = loadState();
            if (!state) return;

            // Restore saved height
            if (state.height && state.height > 60) {
                lastFullHeight = state.height;
            }

            if (!state.open) return;

            // Show the overlay
            overlay.classList.remove('snn-hidden');

            if (state.collapsed) {
                // Restore collapsed state visually
                isCollapsed = true;
                overlay.classList.add('snn-collapsed');
                var colBtn = document.getElementById('snn-css-collapse-btn');
                if (colBtn) {
                    colBtn.title = 'Expand';
                    colBtn.querySelector('svg path').setAttribute('d', 'M6 15l6-6 6 6');
                }
                adjustLayout();
            } else {
                // Restore open + expanded state – init editor
                overlay.style.height = lastFullHeight + 'px';
                if (!cmInstance) {
                    initCodeMirror();
                    setTimeout(function() {
                        updateTitle(); syncFromBricks();
                        adjustLayout();
                        if (cmInstance) { cmInstance.refresh(); }
                    }, 50);
                } else {
                    updateTitle(); syncFromBricks();
                    adjustLayout();
                    cmInstance.refresh();
                }
            }
        }

        /* ── Initialize DOM references ── */
        overlay       = document.getElementById('snn-css-overlay');
        editorWrap    = document.getElementById('snn-css-editor-wrap');
        titleEl       = document.getElementById('snn-css-title');
        bpIndicatorEl = document.getElementById('snn-css-bp-indicator');

        /* ── Wait for Bricks Vue app (no CodeMirror dependency) ── */
        function waitForVue(cb) {
            if (document.querySelector('[data-v-app]')) {
                cb();
            } else {
                setTimeout(function() { waitForVue(cb); }, 300);
            }
        }

        /* ── Bricks state helpers ── */
        function getBricksState() {
            try {
                var app = document.querySelector('[data-v-app]');
                if (!app) return null;
                return app.__vue_app__.config.globalProperties.$_state;
            } catch(e) { return null; }
        }

        function getActiveElement() {
            var state = getBricksState();
            if (!state) return null;
            try { return state.activeElement || null; } catch(e) { return null; }
        }

        function getActiveClass() {
            var state = getBricksState();
            if (!state) return null;
            try { return state.activeClass || null; } catch(e) { return null; }
        }

        function getPageSettings() {
            var state = getBricksState();
            if (!state) return null;
            try { return state.pageSettings || null; } catch(e) { return null; }
        }

        /* ── Read / write CSS (breakpoint-aware) ── */
        // Priority: activeClass > activeElement > page
        // For element/class CSS the key is '_cssCustom' on desktop,
        // '_cssCustom:tablet_portrait' / ':mobile_landscape' / ':mobile_portrait' otherwise.
        // Page CSS uses 'customCss' on desktop only (Bricks doesn't store per-bp page CSS).
        function readCurrentCss() {
            var bp  = getActiveBreakpoint();
            var key = getCssKeyForBreakpoint(bp);
            var activeClass = getActiveClass();
            if (activeClass && activeClass.id) {
                return (activeClass.settings && activeClass.settings[key]) || '';
            }
            var activeEl = getActiveElement();
            if (activeEl && activeEl.id) {
                return (activeEl.settings && activeEl.settings[key]) || '';
            }
            // Page CSS: only desktop has customCss; other BPs unsupported by Bricks
            var page = getPageSettings();
            return (page && page.customCss) || '';
        }

        function writeCurrentCss(value) {
            var bp  = getActiveBreakpoint();
            var key = getCssKeyForBreakpoint(bp);
            var activeClass = getActiveClass();
            if (activeClass && activeClass.id) {
                if (!activeClass.settings) activeClass.settings = {};
                activeClass.settings[key] = value;
                return;
            }
            var activeEl = getActiveElement();
            if (activeEl && activeEl.id) {
                if (!activeEl.settings) activeEl.settings = {};
                activeEl.settings[key] = value;
                return;
            }
            // Page CSS: only desktop
            var page = getPageSettings();
            if (page) page.customCss = value;
        }

        /* ── Update title + breakpoint indicator ── */
        function updateTitle() {
            if (!titleEl) return;
            var bp       = getActiveBreakpoint();
            var bpLabel  = BP_FULL_LABELS[bp] || bp;
            var activeClass = getActiveClass();
            if (activeClass && activeClass.id) {
                currentMode   = 'class';
                currentElemId = activeClass.id;
                titleEl.textContent = 'CSS \u2013 .' + activeClass.name + ' (class)';
            } else {
                var activeEl = getActiveElement();
                if (activeEl && activeEl.id) {
                    currentMode   = 'element';
                    currentElemId = activeEl.id;
                    titleEl.textContent = 'CSS \u2013 #brxe-' + activeEl.id + ' (' + (activeEl.name || '') + ')';
                } else {
                    currentMode   = 'page';
                    currentElemId = null;
                    titleEl.textContent = 'CSS \u2013 Page';
                }
            }
            // Update breakpoint indicator pill
            if (bpIndicatorEl) {
                bpIndicatorEl.textContent = bpLabel;
                bpIndicatorEl.setAttribute('data-bp', bp);
            }
        }

        /* ── Sync from Bricks ── */
        function syncFromBricks() {
            if (isSyncing || !cmInstance) return;
            var val = readCurrentCss();
            if (cmInstance.getValue() !== val) {
                isSyncing = true;
                var cursor = cmInstance.getCursor();
                cmInstance.setValue(val);
                cmInstance.setCursor(cursor);
                isSyncing = false;
            }
        }

        /* ── Layout adjust ── */
        function adjustLayout() {
            if (!overlay) return;
            var leftPanel  = document.getElementById('bricks-panel-inner');
            var rightPanel = document.getElementById('bricks-structure');
            var leftWidth  = 0;
            var rightWidth = 0;
            if (leftPanel && window.getComputedStyle(leftPanel).display !== 'none') {
                leftWidth = leftPanel.offsetWidth || 0;
            }
            if (rightPanel && window.getComputedStyle(rightPanel).display !== 'none') {
                rightWidth = rightPanel.offsetWidth || 0;
            }
            overlay.style.left  = leftWidth  + 'px';
            overlay.style.right = rightWidth + 'px';
        }

        /* ── Collapse / expand ── */
        function collapse() {
            if (isCollapsed || !overlay) return;
            isCollapsed    = true;
            lastFullHeight = overlay.offsetHeight;
            overlay.classList.add('snn-collapsed');
            var btn = document.getElementById('snn-css-collapse-btn');
            if (btn) {
                btn.title = 'Expand';
                btn.querySelector('svg path').setAttribute('d', 'M6 15l6-6 6 6');
            }
            saveState();
        }

        function expand() {
            if (!isCollapsed || !overlay) return;
            isCollapsed = false;
            overlay.classList.remove('snn-collapsed');
            overlay.style.height = lastFullHeight + 'px';
            var btn = document.getElementById('snn-css-collapse-btn');
            if (btn) {
                btn.title = 'Collapse';
                btn.querySelector('svg path').setAttribute('d', 'M6 9l6 6 6-6');
            }
            if (cmInstance) cmInstance.refresh();
            saveState();
        }

        /* ── r+Tab shortcut ── */
        function handleRootShortcut(cm) {
            var activeClass = getActiveClass();
            var activeEl    = getActiveElement();
            var snippet;
            if (activeClass && activeClass.name) {
                snippet = '.' + activeClass.name + ' {\n  \n}';
            } else if (activeEl && activeEl.id) {
                snippet = '#brxe-' + activeEl.id + ' {\n  \n}';
            } else {
                snippet = '%root% {\n  \n}';
            }
            var cur = cm.getCursor();
            cm.replaceRange(snippet, cur);
            cm.setCursor({ line: cur.line + 1, ch: 2 });
            cm.focus();
        }

        /* ── CodeMirror init (lazy – called on first overlay open) ── */
        function initCodeMirror() {
            if (cmInstance) return;
            if (!editorWrap) return;
            editorWrap.innerHTML = '';
            
            // WordPress can load CodeMirror as wp.CodeMirror or window.CodeMirror
            var CM = window.wp && window.wp.CodeMirror ? window.wp.CodeMirror : window.CodeMirror;
            
            if (!CM || typeof CM !== 'function') {
                console.error('CodeMirror not found. wp.CodeMirror:', window.wp?.CodeMirror, 'window.CodeMirror:', window.CodeMirror);
                return;
            }
            
            console.log('Initializing CodeMirror editor...');
            cmInstance = CM(editorWrap, {
                mode            : 'css',
                lineNumbers     : true,
                lineWrapping    : false,
                theme           : 'default',
                value           : '',
                indentUnit      : 2,
                tabSize         : 2,
                indentWithTabs  : false,
                styleActiveLine : true,
                matchBrackets   : true,
                extraKeys       : {
                    'Ctrl-Space' : function (editor) {
                        // safe: only call autocomplete if the addon is loaded
                        if (typeof CM.showHint === 'function') {
                            CM.showHint(editor, CM.hint && CM.hint.css ? CM.hint.css : undefined, { completeSingle: false });
                        }
                    },
                    'Tab' : function(cm) {
                        var cur        = cm.getCursor();
                        var line       = cm.getLine(cur.line);
                        var charBefore = cur.ch > 0 ? line.charAt(cur.ch - 1) : '';
                        if (charBefore === 'r') {
                            cm.replaceRange('', { line: cur.line, ch: cur.ch - 1 }, cur);
                            handleRootShortcut(cm);
                        } else {
                            cm.replaceSelection('  ');
                        }
                    }
                },
                hintOptions : { completeSingle: false }
            });
            
            // Enable CSS autocomplete — only trigger on meaningful CSS characters,
            // not on Enter, Space, backspace, etc.
            if (typeof CM.showHint === 'function' && CM.hint && CM.hint.css) {
                cmInstance.on('inputRead', function(cm, change) {
                    var ch = change.text && change.text[0];
                    if (ch && /^[a-zA-Z:\-\(]$/.test(ch) && !cm.state.completionActive) {
                        CM.showHint(cm, CM.hint.css, { completeSingle: false });
                    }
                });
            }
            
            console.log('CodeMirror editor initialized successfully');
            cmInstance.on('change', function(cm) {
                if (isSyncing) return;
                clearTimeout(writeCssTimer);
                writeCssTimer = setTimeout(function() {
                    isSyncing = true;
                    writeCurrentCss(cm.getValue());
                    isSyncing = false;
                }, 300);
            });
        }

        /* ── Resize handle drag ── */
        function initResize() {
            var handle = document.getElementById('snn-css-resize-handle');
            if (!handle) return;
            
            var dragging = false;
            
            function onMouseDown(e) {
                if (isCollapsed) return;
                dragging = true;
                handle.classList.add('dragging');
                document.body.style.cursor = 'ns-resize';
                document.body.style.userSelect = 'none';
                overlay.style.transition = 'none';
                e.preventDefault();
            }
            
            function onMouseMove(e) {
                if (!dragging) return;
                
                // Direct calculation: height = distance from bottom of viewport
                var newHeight = Math.max(60, Math.min(window.innerHeight - 100, window.innerHeight - e.clientY));
                overlay.style.height = newHeight + 'px';
                lastFullHeight = newHeight;
            }
            
            function stopDragging() {
                if (!dragging) return;
                dragging = false;
                handle.classList.remove('dragging');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                overlay.style.transition = '';
                saveState();
                
                // Refresh CodeMirror only after dragging ends
                if (cmInstance) {
                    setTimeout(function() {
                        cmInstance.refresh();
                    }, 0);
                }
            }
            
            handle.addEventListener('mousedown', onMouseDown);
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', stopDragging);
            
            // Stop dragging when mouse leaves the window
            document.addEventListener('mouseleave', stopDragging);
            
            // Stop dragging on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && dragging) {
                    stopDragging();
                }
            });
        }

        /* ── Initialize overlay buttons (collapse/close) ── */
        function initOverlayButtons() {
            var collapseBtn = document.getElementById('snn-css-collapse-btn');
            var closeBtn = document.getElementById('snn-css-close-btn');
            
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function() {
                    if (isCollapsed) {
                        expand();
                    } else {
                        collapse();
                    }
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    if (overlay) {
                        overlay.classList.add('snn-hidden');
                        saveState();
                    }
                });
            }
        }

        /* ── Toggle button injected into #bricks-panel-inner #bricks-panel-header .actions ── */
        function insertToggleButton() {
            var actionsEl = document.querySelector('#bricks-panel-inner #bricks-panel-header .actions');
            if (!actionsEl) return false;

            // Check if button already exists
            var li = actionsEl.querySelector('.snn-css-toggle-li');
            if (!li) {
                li = document.createElement('li');
                li.className = 'snn-css-toggle-li';
                li.setAttribute('data-balloon', 'Custom CSS');
                li.setAttribute('data-balloon-pos', 'bottom-right');
                li.style.cursor = 'pointer';
                li.innerHTML = '<span class="bricks-svg-wrapper" data-name="snn-css">'
                    + '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="bricks-svg" style="width:18px;height:18px;">'
                    + '<path d="M7 8L3 12L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    + '<path d="M17 8L21 12L17 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    + '<path d="M14 4L10 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                    + '</svg></span>';

                li.addEventListener('click', function() {
                    if (!overlay) return;
                    if (overlay.classList.contains('snn-hidden')) {
                        overlay.classList.remove('snn-hidden');
                        saveState();
                        if (isCollapsed) expand();
                        if (!cmInstance) {
                            initCodeMirror();
                            setTimeout(function() {
                                updateTitle(); syncFromBricks();
                                adjustLayout();
                                if (cmInstance) { cmInstance.refresh(); cmInstance.focus(); }
                            }, 50);
                        } else {
                            updateTitle(); syncFromBricks();
                            adjustLayout();
                            cmInstance.refresh(); cmInstance.focus();
                        }
                    } else {
                        overlay.classList.add('snn-hidden');
                        saveState();
                    }
                });
            }

            // Always move as FIRST item
            if (actionsEl.firstElementChild !== li) {
                actionsEl.insertBefore(li, actionsEl.firstElementChild);
            }

            return true;
        }

        /* ── Watch for the panel header – aggressive multi-level observation ── */
        function watchForPanelHeader() {
            var watchedActionsEl = null;
            var elObs = null;
            var headerObs = null;

            function tryInsertAndWatch() {
                var leftPanel = document.getElementById('bricks-panel-inner');
                if (!leftPanel) return;
                
                var actionsEl = leftPanel.querySelector('#bricks-panel-header .actions');
                if (!actionsEl) return;

                if (actionsEl !== watchedActionsEl) {
                    if (elObs) elObs.disconnect();
                    watchedActionsEl = actionsEl;
                    elObs = new MutationObserver(function() {
                        requestAnimationFrame(insertToggleButton);
                    });
                    elObs.observe(actionsEl, { childList: true, subtree: true });
                }

                insertToggleButton();
            }

            function watchHeader() {
                var leftPanel = document.getElementById('bricks-panel-inner');
                if (!leftPanel) return;
                
                var header = leftPanel.querySelector('#bricks-panel-header');
                if (header) {
                    if (headerObs) headerObs.disconnect();
                    headerObs = new MutationObserver(function() {
                        requestAnimationFrame(tryInsertAndWatch);
                    });
                    headerObs.observe(header, { childList: true, subtree: true });
                }
            }

            var bodyObs = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var mutation = mutations[i];
                    for (var j = 0; j < mutation.addedNodes.length; j++) {
                        var node = mutation.addedNodes[j];
                        if (node.nodeType === 1) {
                            if (node.id === 'bricks-panel-inner' || node.querySelector('#bricks-panel-inner')) {
                                requestAnimationFrame(function() {
                                    tryInsertAndWatch();
                                    watchHeader();
                                });
                                break;
                            }
                        }
                    }
                }
            });
            bodyObs.observe(document.body, { childList: true, subtree: true });

            tryInsertAndWatch();
            watchHeader();
        }

        // --- Additional watchers for robust overlay operation ---
        function watchActiveElement() {
            // Prefer Vue's own reactivity over polling.
            // $_state is a Vue 3 reactive proxy — we install a getter trap on
            // activeElement so we're notified exactly when Bricks changes it,
            // with zero polling overhead.
            try {
                var app = document.querySelector('[data-v-app]');
                var state = app && app.__vue_app__ && app.__vue_app__.config.globalProperties.$_state;
                if (state && typeof state === 'object') {
                    var lastId = state.activeElement && state.activeElement.id ? state.activeElement.id : null;
                    // Vue 3 reactive proxies fire Proxy set traps — wrap with defineProperty
                    // on the raw target if accessible, otherwise fall back to a lightweight
                    // 250ms poll (much cheaper than 100ms).
                    var raw = state.__v_raw || state;

                    var _activeEl = raw.activeElement;
                    Object.defineProperty(raw, 'activeElement', {
                        configurable: true,
                        enumerable  : true,
                        get: function() { return _activeEl; },
                        set: function(v) {
                            _activeEl = v;
                            updateTitle();
                            syncFromBricks();
                        }
                    });

                    var _activeClass = raw.activeClass;
                    Object.defineProperty(raw, 'activeClass', {
                        configurable: true,
                        enumerable  : true,
                        get: function() { return _activeClass; },
                        set: function(v) {
                            _activeClass = v;
                            updateTitle();
                            syncFromBricks();
                        }
                    });

                    var _breakpointActive = raw.breakpointActive;
                    Object.defineProperty(raw, 'breakpointActive', {
                        configurable: true,
                        enumerable  : true,
                        get: function() { return _breakpointActive; },
                        set: function(v) {
                            _breakpointActive = v;
                            updateTitle();
                            syncFromBricks();
                        }
                    });

                    return; // success — no interval needed
                }
            } catch(e) {}

            // Fallback: coarse poll at 250ms (was 100ms)
            var lastKey = null;
            setInterval(function() {
                var activeClass = getActiveClass();
                var activeEl    = getActiveElement();
                var bp  = getActiveBreakpoint();
                var key = (activeClass && activeClass.id
                    ? 'class:' + activeClass.id
                    : (activeEl && activeEl.id ? 'el:' + activeEl.id : 'page'))
                    + '@' + bp;
                if (key !== lastKey) {
                    lastKey = key;
                    updateTitle();
                    syncFromBricks();
                }
            }, 250);
        }

        function watchPanelSizes() {
            // ResizeObserver fires only on actual size changes — zero polling cost.
            var ro = new ResizeObserver(function() {
                adjustLayout();
            });
            var leftPanel  = document.getElementById('bricks-panel-inner');
            var rightPanel = document.getElementById('bricks-structure');
            if (leftPanel)  ro.observe(leftPanel);
            if (rightPanel) ro.observe(rightPanel);

            // If panels aren't in the DOM yet, watch for them via MutationObserver
            if (!leftPanel || !rightPanel) {
                var mo = new MutationObserver(function() {
                    if (!leftPanel) {
                        leftPanel = document.getElementById('bricks-panel-inner');
                        if (leftPanel) ro.observe(leftPanel);
                    }
                    if (!rightPanel) {
                        rightPanel = document.getElementById('bricks-structure');
                        if (rightPanel) ro.observe(rightPanel);
                    }
                    if (leftPanel && rightPanel) mo.disconnect();
                });
                mo.observe(document.body, { childList: true, subtree: true });
            }
        }

        /* ── AI Sidebar ── */
        <?php if ( $snn_css_ai_enabled ) : ?>
        var snnCssAiConfig = <?php echo wp_json_encode( [
            'apiKey'      => $snn_css_ai_config['apiKey'],
            'apiEndpoint' => $snn_css_ai_config['apiEndpoint'],
            'model'       => $snn_css_ai_config['model'],
            'provider'    => $snn_css_ai_config['modelProvider'] ?? '',
        ] ); ?>;

        /* ── Read Bricks global color palette + size variables from Vue reactive state ── */
        function getBricksDesignTokens() {
            var tokens = { colors: [], sizes: [] };
            try {
                var app   = document.querySelector('[data-v-app]');
                var state = app && app.__vue_app__ && app.__vue_app__.config.globalProperties.$_state;
                if (!state) return tokens;

                if (state.colorPalette) {
                    var palette = Array.from(state.colorPalette);
                    if (palette.length && palette[0] && palette[0].colors) {
                        tokens.colors = Array.from(palette[0].colors).map(function(c) {
                            return { raw: c.raw, hex: c.light || '' };
                        });
                    }
                }

                if (state.globalVariables) {
                    tokens.sizes = Array.from(state.globalVariables).map(function(v) {
                        return { name: v.name, value: v.value, cssVar: '--' + v.name };
                    });
                }
            } catch(e) {}
            return tokens;
        }

        function buildTokenContext() {
            var tokens = getBricksDesignTokens();
            var ctx = '';
            if (tokens.colors.length) {
                ctx += '\n\nBRICKS GLOBAL COLOR PALETTE (use these var() names when referencing site colors):\n';
                ctx += tokens.colors.map(function(c) {
                    return '  ' + c.raw + (c.hex ? ' → ' + c.hex : '');
                }).join('\n');
            }
            if (tokens.sizes.length) {
                ctx += '\n\nBRICKS GLOBAL SIZE VARIABLES (use these var() names for spacing/sizing):\n';
                ctx += tokens.sizes.map(function(v) {
                    return '  var(' + v.cssVar + ') = ' + v.value;
                }).join('\n');
            }
            return ctx;
        }

        function initAiSidebar() {
            var aiBtn      = document.getElementById('snn-css-ai-btn');
            var aiSidebar  = document.getElementById('snn-css-ai-sidebar');
            var aiPrompt   = document.getElementById('snn-css-ai-prompt');
            var aiSpinner  = document.getElementById('snn-css-ai-spinner');
            var aiResponse = document.getElementById('snn-css-ai-response');
            var aiSendBtn  = document.getElementById('snn-css-ai-send-btn');
            var aiApplyBtns   = document.getElementById('snn-css-ai-apply-btns');
            var aiInjectBtn   = document.getElementById('snn-css-ai-inject-btn');
            var aiReplaceBtn  = document.getElementById('snn-css-ai-replace-btn');

            if (!aiBtn || !aiSidebar) return;

            var lastAiCode = '';

            aiBtn.addEventListener('click', function() {
                var hidden = aiSidebar.classList.toggle('snn-hidden');
                aiBtn.classList.toggle('active', !hidden);
                if (!hidden && cmInstance) {
                    setTimeout(function() { cmInstance.refresh(); }, 50);
                }
            });

            aiSendBtn.addEventListener('click', function() {
                var prompt = aiPrompt.value.trim();
                if (!prompt) return;

                var existingCss = cmInstance ? cmInstance.getValue() : '';
                var contextLabel = titleEl ? titleEl.textContent : 'Page';
                var tokenCtx = buildTokenContext();

                var systemContent = 'You are a CSS coding expert. Respond ONLY with valid raw CSS code — no explanations, no markdown, no code fences. Just the CSS.';
                if (tokenCtx) {
                    systemContent += '\n\nYou have access to the site\'s Bricks Builder design tokens below. Use the var() names when referencing colors or sizes — only if they are appropriate for the task. Do NOT force tokens into the CSS if the user asked for something unrelated.' + tokenCtx;
                }

                var messages = [
                    {
                        role: 'system',
                        content: systemContent
                    },
                    {
                        role: 'user',
                        content: 'Context: ' + contextLabel + (existingCss ? '\n\nExisting CSS:\n' + existingCss : '') + '\n\nTask: ' + prompt
                    }
                ];

                aiSendBtn.disabled = true;
                aiSpinner.style.display = 'block';
                aiResponse.style.display = 'none';
                aiApplyBtns.style.display = 'none';
                lastAiCode = '';

                var helpers = window.SNN_AI_Helpers;
                if (!helpers || typeof helpers.makeTextCompletion !== 'function') {
                    aiSpinner.style.display = 'none';
                    aiResponse.textContent = 'Error: SNN_AI_Helpers not loaded.';
                    aiResponse.style.display = 'block';
                    aiSendBtn.disabled = false;
                    return;
                }

                helpers.makeTextCompletion({
                    apiEndpoint : snnCssAiConfig.apiEndpoint,
                    apiKey      : snnCssAiConfig.apiKey,
                    model       : snnCssAiConfig.model,
                    messages    : messages,
                    provider    : snnCssAiConfig.provider || null,
                    temperature : 0.4,
                    maxTokens   : 2000
                }).then(function(data) {
                    var text = helpers.extractContent ? helpers.extractContent(data) : (data && data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content) || '';
                    // Strip markdown code fences if AI included them anyway
                    text = text.replace(/^```(?:css)?\s*/i, '').replace(/```\s*$/i, '').trim();
                    lastAiCode = text;
                    aiResponse.textContent = text;
                    aiResponse.style.display = 'block';
                    aiApplyBtns.style.display = 'flex';
                }).catch(function(err) {
                    aiResponse.textContent = 'Error: ' + (err && err.message ? err.message : String(err));
                    aiResponse.style.display = 'block';
                }).finally(function() {
                    aiSpinner.style.display = 'none';
                    aiSendBtn.disabled = false;
                });
            });

            aiInjectBtn.addEventListener('click', function() {
                if (!lastAiCode || !cmInstance) return;
                var existing = cmInstance.getValue();
                var newVal = existing ? existing + '\n\n' + lastAiCode : lastAiCode;
                cmInstance.setValue(newVal);
                cmInstance.focus();
            });

            aiReplaceBtn.addEventListener('click', function() {
                if (!lastAiCode || !cmInstance) return;
                cmInstance.setValue(lastAiCode);
                cmInstance.focus();
            });

            // Send on Ctrl+Enter in the prompt textarea
            aiPrompt.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    aiSendBtn.click();
                }
            });
        }
        <?php endif; ?>

        // ── Initialize everything ──
        initResize();
        initOverlayButtons();
        watchForPanelHeader();
        <?php if ( $snn_css_ai_enabled ) : ?>
        initAiSidebar();
        <?php endif; ?>

        waitForVue(function() {
            restoreState();
            watchActiveElement();
            watchPanelSizes();
        });

    }());
    </script>
    <?php
}
