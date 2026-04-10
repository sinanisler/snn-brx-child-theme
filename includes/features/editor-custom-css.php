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
        #snn-css-editor-wrap {
            flex: 1;
            overflow: hidden;
            background: var(--builder-bg-2, #2a2a3d);
            min-height: 0;
        }
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
    </style>

    <!-- SNN Custom CSS Overlay -->
    <div id="snn-css-overlay" class="snn-hidden">
        <!-- Resize handle dragged upward -->
        <div id="snn-css-resize-handle"></div>

        <!-- Top bar -->
        <div id="snn-css-topbar">
            <span id="snn-css-title">CSS – Page</span>
            <div id="snn-css-topbar-actions">
                <!-- Collapse / expand -->
                <button class="snn-css-topbar-btn" id="snn-css-collapse-btn" title="Collapse">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
                        <path d="M6 15l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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

        <!-- CodeMirror editor (initialized directly on this div) -->
        <div id="snn-css-editor-wrap"></div>
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
        var cmInstance     = null;
        var isCollapsed    = false;
        var lastFullHeight = 260;
        var currentMode    = 'page';
        var currentElemId  = null;
        var isSyncing      = false;

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
                    colBtn.querySelector('svg path').setAttribute('d', 'M6 9l6-6 6 6');
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
        overlay    = document.getElementById('snn-css-overlay');
        editorWrap = document.getElementById('snn-css-editor-wrap');
        titleEl    = document.getElementById('snn-css-title');

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

        function getPageSettings() {
            var state = getBricksState();
            if (!state) return null;
            try { return state.pageSettings || null; } catch(e) { return null; }
        }

        /* ── Read / write CSS ── */
        function readCurrentCss() {
            var activeEl = getActiveElement();
            if (activeEl && activeEl.id) {
                return (activeEl.settings && activeEl.settings._cssCustom) || '';
            }
            var page = getPageSettings();
            return (page && page.customCss) || '';
        }

        function writeCurrentCss(value) {
            var activeEl = getActiveElement();
            if (activeEl && activeEl.id) {
                if (!activeEl.settings) activeEl.settings = {};
                activeEl.settings._cssCustom = value;
                return;
            }
            var page = getPageSettings();
            if (page) page.customCss = value;
        }

        /* ── Update title ── */
        function updateTitle() {
            if (!titleEl) return;
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
                btn.querySelector('svg path').setAttribute('d', 'M6 9l6-6 6 6');
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
                btn.querySelector('svg path').setAttribute('d', 'M6 15l6 6 6-6');
            }
            if (cmInstance) cmInstance.refresh();
            saveState();
        }

        /* ── r+Tab shortcut ── */
        function handleRootShortcut(cm) {
            var activeEl = getActiveElement();
            var snippet = (activeEl && activeEl.id)
                ? '#brxe-' + activeEl.id + ' {\n  \n}'
                : '%root% {\n  \n}';
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
            
            // Enable CSS autocomplete
            if (typeof CM.showHint === 'function' && CM.hint && CM.hint.css) {
                cmInstance.on('inputRead', function(cm) {
                    if (!cm.state.completionActive) {
                        CM.showHint(cm, CM.hint.css, { completeSingle: false });
                    }
                });
            }
            
            console.log('CodeMirror editor initialized successfully');
            cmInstance.on('change', function(cm) {
                if (isSyncing) return;
                isSyncing = true;
                writeCurrentCss(cm.getValue());
                isSyncing = false;
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
            setInterval(tryInsertAndWatch, 100);
        }

        // --- Additional watchers for robust overlay operation ---
        function watchActiveElement() {
            var lastId = null;
            setInterval(function() {
                var activeEl = getActiveElement();
                var id = activeEl && activeEl.id ? activeEl.id : null;
                if (id !== lastId) {
                    lastId = id;
                    updateTitle();
                    syncFromBricks();
                }
            }, 100);
        }

        function watchPanelSizes() {
            var lastLeft = null, lastRight = null;
            setInterval(function() {
                var leftPanel  = document.getElementById('bricks-panel-inner');
                var rightPanel = document.getElementById('bricks-structure');
                var leftWidth  = leftPanel && window.getComputedStyle(leftPanel).display !== 'none' ? leftPanel.offsetWidth : 0;
                var rightWidth = rightPanel && window.getComputedStyle(rightPanel).display !== 'none' ? rightPanel.offsetWidth : 0;
                if (leftWidth !== lastLeft || rightWidth !== lastRight) {
                    lastLeft = leftWidth;
                    lastRight = rightWidth;
                    adjustLayout();
                }
            }, 100);
        }

        // ── Initialize everything ──
        initResize();
        initOverlayButtons();
        watchForPanelHeader();

        waitForVue(function() {
            restoreState();
            watchActiveElement();
            watchPanelSizes();
        });

    }());
    </script>
    <?php
}
