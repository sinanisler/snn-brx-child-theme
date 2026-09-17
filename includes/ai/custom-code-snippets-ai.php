<?php
/**
 * AI assistant for Code Snippets.
 *
 * A ✦ button on the snippet editors (modern and legacy) opens a small chat
 * sidebar. The prompt goes through the AI proxy (the API key stays on the
 * server) together with the snippet's code type, location and, optionally,
 * its current code or selection. Replies can be copied, or inserted at the
 * editor's cursor (replacing the selection). Nothing is saved: inserted code
 * still goes through the snippets draft -> test -> publish flow.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_snippets_ai_enqueue() {
    if ( ! isset( $_GET['page'] ) || 'snn-custom-codes-snippets' !== $_GET['page'] || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( 'yes' !== get_option( 'snn_ai_enabled', 'no' ) || ! function_exists( 'snn_get_ai_api_config' ) || ! function_exists( 'snn_snippets_current_view' ) ) {
        return;
    }
    if ( ! in_array( snn_snippets_current_view(), array( 'legacy', 'edit', 'new' ), true ) || ! wp_script_is( 'snn-code-snippets', 'enqueued' ) ) {
        return;
    }

    $config = snn_get_ai_api_config();
    $data   = array(
        'maxTokens'    => (int) $config['maxTokens'], // AI Settings owns the limit.
        'isOpenRouter' => 'custom' !== get_option( 'snn_ai_provider', 'openrouter' ),
        'i18n'      => array(
            'title'        => __( 'AI Code Assistant', 'snn' ),
            'open'         => __( 'Generate code with AI', 'snn' ),
            'close'        => __( 'Close', 'snn' ),
            'placeholder'  => __( 'Describe the code you want, or what to change… (Ctrl+Enter to send)', 'snn' ),
            'send'         => __( 'Generate', 'snn' ),
            'includeCode'  => __( 'Include current code', 'snn' ),
            'selectionOnly'=> __( '(selection only)', 'snn' ),
            'copy'         => __( 'Copy', 'snn' ),
            'copied'       => __( 'Copied', 'snn' ),
            'insert'       => __( 'Insert at cursor', 'snn' ),
            'inserted'     => __( 'Inserted', 'snn' ),
            'clear'        => __( 'New chat', 'snn' ),
            'thinking'     => __( 'Generating…', 'snn' ),
            'empty'        => __( 'Ask for a new snippet, or tick "Include current code" to edit the existing one. Inserted code is not saved until you click Save Snippet, and PHP is still tested before it goes live.', 'snn' ),
            'error'        => __( 'Error', 'snn' ),
            'noRoom'       => __( 'The model used its whole token budget before writing any code (reasoning models think first). Try a shorter request, a non-reasoning model, or raise Max Tokens in AI Settings.', 'snn' ),
            'noContent'    => __( 'The model returned an empty reply. Please try again.', 'snn' ),
            'truncated'    => __( 'The reply hit the token limit and is cut off, so check the end of the code before using it. Ask to "continue", or raise Max Tokens in AI Settings.', 'snn' ),
        ),
    );

    wp_add_inline_style( 'dashicons', snn_snippets_ai_css() );
    wp_add_inline_script( 'snn-code-snippets', 'window.snnSnippetsAi = ' . wp_json_encode( $data ) . ";\n" . snn_snippets_ai_js() );
}
add_action( 'admin_enqueue_scripts', 'snn_snippets_ai_enqueue', 20 );

function snn_snippets_ai_css() {
    return <<<'CSS'
.snn-ai-open { display: inline-flex; align-items: center; gap: 4px; background: none; border: 1px solid #c3c4c7; border-radius: 4px; padding: 0 8px; height: 30px; cursor: pointer; }
.snn-ai-open:hover { border-color: #2271b1; }
.snn-ai-star { font-size: 22px; line-height: 1.2; background: linear-gradient(45deg, #2271b1, #ffffff); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.snn-ai-legacy-bar { display: flex; justify-content: flex-end; margin: 0 0 6px; }
.snn-ai-split { display: flex; align-items: stretch; }
.snn-ai-code { flex: 1; min-width: 0; }
.snn-ai-panel { display: none; position: relative; flex: 0 0 360px; border: 1px solid #dcdcde; border-left: 0; background: #fff; }
.snn-ai-panel.is-open { display: block; }
.snn-ai-panel-inner { position: absolute; inset: 0; display: flex; flex-direction: column; }
.snn-ai-open.is-active { border-color: #2271b1; background: #f0f6fc; }
@media (max-width: 1100px) {
    .snn-ai-split { flex-direction: column; }
    .snn-ai-panel { flex-basis: auto; height: 520px; border-left: 1px solid #dcdcde; border-top: 0; }
}
.snn-ai-head { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-bottom: 1px solid #dcdcde; }
.snn-ai-head h2 { margin: 0; font-size: 14px; flex: 1; }
.snn-ai-close { background: none; border: 0; font-size: 22px; line-height: 1; cursor: pointer; color: #50575e; }
.snn-ai-messages { flex: 1; min-height: 0; overflow-y: auto; padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
.snn-ai-hint { color: #646970; font-size: 12px; margin: 0; }
.snn-ai-msg-user { align-self: flex-end; max-width: 85%; background: #2271b1; color: #fff; border-radius: 8px 8px 0 8px; padding: 8px 10px; white-space: pre-wrap; word-break: break-word; }
.snn-ai-msg-ai { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; }
.snn-ai-msg-ai pre { margin: 0; padding: 10px; max-height: 360px; overflow: auto; font-size: 12px; white-space: pre; }
.snn-ai-msg-ai.is-error pre { color: #b32d2e; white-space: pre-wrap; }
.snn-ai-actions { display: flex; gap: 6px; padding: 6px 8px; border-top: 1px solid #dcdcde; }
.snn-ai-form { border-top: 1px solid #dcdcde; padding: 10px 14px 14px; }
.snn-ai-form textarea { width: 100%; min-height: 80px; resize: vertical; }
.snn-ai-form-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.snn-ai-form-row .snn-spacer { flex: 1; }
.snn-ai-form .spinner { float: none; margin: 0; }
CSS;
}

function snn_snippets_ai_js() {
    return <<<'JS'
jQuery( function ( $ ) {
    var cfg  = window.snnSnippetsAi || {};
    var t    = cfg.i18n || {};
    var area = document.querySelector( '#snn_modern_code, #snn_frontend_code, #snn_footer_code, #snn_admin_code, #snn_functions_code' );
    if ( ! area || ! window.SNN_AI_Helpers ) { return; }

    var LEGACY = {
        snn_frontend_code:  'Legacy "Frontend Head": HTML with optional <?php ?> blocks, printed inside <head> on the front end (wp_head, priority 1).',
        snn_footer_code:    'Legacy "Frontend Footer": HTML with optional <?php ?> blocks, printed before </body> on the front end (wp_footer, priority 9999).',
        snn_admin_code:     'Legacy "Admin Head": HTML with optional <?php ?> blocks, printed inside <head> of wp-admin pages (admin_head, priority 1).',
        snn_functions_code: 'Legacy "functions.php": runs inside the init hook at priority 10, starting in HTML mode, so PHP must be wrapped in <?php ... ?>. add_action( \'init\', ... ) needs priority 11 or higher here.'
    };
    var TYPE_RULES = {
        php:      'Pure PHP. Do NOT start with <?php and do not close with ?>.',
        html_php: 'HTML with embedded <?php ?> blocks. The code starts in HTML mode, so PHP must be inside <?php ... ?>.',
        html:     'Plain HTML only. No PHP.',
        css:      'Plain CSS only. Do NOT wrap it in <style> tags.',
        js:       'Plain JavaScript only. Do NOT wrap it in <script> tags.'
    };

    var history = [];
    var busy    = false;
    var lastPos = { start: 0, end: 0 };

    function cm() { return window.snnSnippetEditors ? window.snnSnippetEditors[ area.id ] : null; }
    function el( tag, cls, text ) {
        var node = document.createElement( tag );
        if ( cls ) { node.className = cls; }
        if ( text !== undefined ) { node.textContent = text; }
        return node;
    }

    // ----- Button ------------------------------------------------------------
    var openBtn = el( 'button', 'snn-ai-open' );
    openBtn.type = 'button';
    openBtn.title = t.open;
    openBtn.setAttribute( 'aria-label', t.open );
    openBtn.appendChild( el( 'span', 'snn-ai-star', '✦' ) );
    openBtn.appendChild( el( 'span', '', 'AI' ) );
    var head = document.querySelector( '.snn-code-head' );
    if ( head && area.id === 'snn_modern_code' ) {
        head.insertBefore( openBtn, head.querySelector( 'label[for="snn_code_type"]' ) );
    } else {
        var bar = el( 'div', 'snn-ai-legacy-bar' );
        bar.appendChild( openBtn );
        area.parentNode.insertBefore( bar, area );
    }

    // ----- Panel -------------------------------------------------------------
    // Docked to the right of the code editor: [ code | AI ], same height.
    var split    = el( 'div', 'snn-ai-split' );
    var codeWrap = el( 'div', 'snn-ai-code' );
    var cmWrap   = cm() ? cm().getWrapperElement() : null;
    area.parentNode.insertBefore( split, area );
    split.appendChild( codeWrap );
    codeWrap.appendChild( area );
    if ( cmWrap ) { codeWrap.appendChild( cmWrap ); }

    var panel = el( 'aside', 'snn-ai-panel' );
    panel.setAttribute( 'aria-label', t.title );
    panel.innerHTML =
        '<div class="snn-ai-panel-inner"><div class="snn-ai-head"><span class="snn-ai-star">✦</span><h2></h2>' +
        '<button type="button" class="button button-small snn-ai-clear"></button>' +
        '<button type="button" class="snn-ai-close">×</button></div>' +
        '<div class="snn-ai-messages"></div>' +
        '<div class="snn-ai-form"><textarea></textarea>' +
        '<div class="snn-ai-form-row"><label><input type="checkbox" class="snn-ai-include"> <span class="snn-ai-include-label"></span></label>' +
        '<span class="snn-spacer"></span><span class="spinner"></span>' +
        '<button type="button" class="button button-primary snn-ai-send"></button></div></div></div>';
    split.appendChild( panel );

    var q        = function ( s ) { return panel.querySelector( s ); };
    var list     = q( '.snn-ai-messages' );
    var prompt   = q( 'textarea' );
    var include  = q( '.snn-ai-include' );
    var incLabel = q( '.snn-ai-include-label' );
    var sendBtn  = q( '.snn-ai-send' );
    var spinner  = q( '.spinner' );
    q( 'h2' ).textContent = t.title;
    q( '.snn-ai-clear' ).textContent = t.clear;
    q( '.snn-ai-close' ).setAttribute( 'aria-label', t.close );
    prompt.placeholder = t.placeholder;
    sendBtn.textContent = t.send;

    function showHint() { list.innerHTML = ''; list.appendChild( el( 'p', 'snn-ai-hint', t.empty ) ); }
    showHint();

    function currentCode() { var c = cm(); return c ? c.getValue() : area.value; }
    function currentSelection() {
        var c = cm();
        if ( c ) { return c.getSelection(); }
        return area.value.slice( lastPos.start, lastPos.end );
    }
    function refreshInclude() {
        var has = currentCode().trim() !== '';
        include.disabled = ! has;
        if ( ! has ) { include.checked = false; }
        incLabel.textContent = t.includeCode + ( currentSelection() ? ' ' + t.selectionOnly : '' );
    }

    function open() {
        include.checked = currentCode().trim() !== '';
        refreshInclude();
        panel.classList.add( 'is-open' );
        openBtn.classList.add( 'is-active' );
        if ( cm() ) { cm().refresh(); }
        prompt.focus();
    }
    function close() {
        panel.classList.remove( 'is-open' );
        openBtn.classList.remove( 'is-active' );
        if ( cm() ) { cm().refresh(); }
        openBtn.focus();
    }

    openBtn.addEventListener( 'click', function () { panel.classList.contains( 'is-open' ) ? close() : open(); } );
    q( '.snn-ai-close' ).addEventListener( 'click', close );
    q( '.snn-ai-clear' ).addEventListener( 'click', function () { history = []; showHint(); prompt.focus(); } );
    panel.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { close(); } } );
    prompt.addEventListener( 'focus', refreshInclude );
    prompt.addEventListener( 'keydown', function ( e ) {
        if ( ( e.ctrlKey || e.metaKey ) && e.key === 'Enter' ) { e.preventDefault(); send(); }
    } );
    // Plain textarea (code editor off): remember the caret, focus moves to the panel.
    area.addEventListener( 'blur', function () { lastPos = { start: area.selectionStart, end: area.selectionEnd }; } );

    // ----- Context -----------------------------------------------------------
    function systemPrompt() {
        var type = $( '#snn_code_type' ).val() || 'html_php';
        var lines = [
            'You are an expert WordPress developer writing code for a code snippets manager inside WordPress admin.',
            'Reply with ONLY the code. No explanations, no markdown, no code fences.',
            'Code type: ' + type + '. ' + TYPE_RULES[ type ],
            'Comments: explain the code inside comments, since the reply is code only. ' +
                'Start new code with a short header comment: what the snippet does and how to use it (for example the shortcode with every attribute, its default and what it changes). ' +
                'Above each function, class, hook and non-obvious block, add a short comment (1-3 lines) saying what it does and how it works. ' +
                'Keep comments short and plain; do not comment trivial lines. ' +
                'Use the comment syntax of the code type: PHP /** */ and //, CSS /* */, JavaScript //, HTML <!-- -->. ' +
                'When editing existing code, keep its comments and update them if the code changes.'
        ];
        if ( LEGACY[ area.id ] ) {
            lines.push( 'Location: ' + LEGACY[ area.id ] );
        } else {
            var place = $( '#snn_location option:selected' );
            lines.push( 'Location: ' + place.text() + ' (hook stage "' + place.data( 'stage' ) + '"). ' + ( place.data( 'help' ) || '' ) );
            var prio = $( '#snn_priority' ).val();
            if ( prio ) { lines.push( 'Hook priority: ' + prio + '.' ); }
            try {
                var cond = JSON.parse( $( '#snn_conditions_input' ).val() || '{}' );
                if ( cond.enabled ) { lines.push( 'The snippet already has conditional logic (' + cond.action + ' when): ' + JSON.stringify( cond.groups ) + '. Do not re-implement these checks in code.' ); }
            } catch ( e ) {}
        }
        if ( type === 'php' || type === 'html_php' ) {
            lines.push(
                'PHP rules: prefix function and class names uniquely (e.g. snn_) and wrap declarations in function_exists()/class_exists() checks; ' +
                'redeclaring an existing name is rejected. Use WordPress APIs, escape output (esc_html, esc_attr, esc_url), sanitize input, check nonces and capabilities. ' +
                'Must be compatible with PHP 7.4+. Never call exit or die on normal requests.'
            );
        }
        lines.push( 'When given existing code and asked to change it, return the COMPLETE updated code for the part you were given, so it can replace it.' );
        return lines.join( '\n' );
    }

    function stripFences( text ) {
        var m = /```[a-zA-Z0-9_-]*\s*\n([\s\S]*?)```/.exec( text );
        return ( m ? m[1] : text ).replace( /^\s+|\s+$/g, '' );
    }

    // ----- Messages ----------------------------------------------------------
    function addUser( text ) {
        if ( list.querySelector( '.snn-ai-hint' ) ) { list.innerHTML = ''; }
        list.appendChild( el( 'div', 'snn-ai-msg-user', text ) );
        list.scrollTop = list.scrollHeight;
    }
    function flash( button, label ) {
        var old = button.textContent;
        button.textContent = label;
        setTimeout( function () { button.textContent = old; }, 1500 );
    }
    function addReply( code, isError ) {
        var box = el( 'div', 'snn-ai-msg-ai' + ( isError ? ' is-error' : '' ) );
        box.appendChild( el( 'pre', '', code ) );
        if ( ! isError ) {
            var actions = el( 'div', 'snn-ai-actions' );
            var copy    = el( 'button', 'button button-small', t.copy );
            var insert  = el( 'button', 'button button-primary button-small', t.insert );
            copy.type = insert.type = 'button';
            copy.addEventListener( 'click', function () {
                var done = function () { flash( copy, t.copied ); };
                if ( navigator.clipboard && window.isSecureContext ) {
                    navigator.clipboard.writeText( code ).then( done );
                } else {
                    var tmp = el( 'textarea' );
                    tmp.value = code;
                    document.body.appendChild( tmp );
                    tmp.select();
                    document.execCommand( 'copy' );
                    tmp.remove();
                    done();
                }
            } );
            insert.addEventListener( 'click', function () {
                var c = cm();
                if ( c ) {
                    // CodeMirror keeps its cursor and selection while unfocused.
                    c.replaceSelection( code, 'around' );
                    c.focus();
                } else {
                    area.value = area.value.slice( 0, lastPos.start ) + code + area.value.slice( lastPos.end );
                    lastPos = { start: lastPos.start, end: lastPos.start + code.length };
                    area.focus();
                    area.setSelectionRange( lastPos.start, lastPos.end );
                }
                flash( insert, t.inserted );
            } );
            actions.appendChild( copy );
            actions.appendChild( insert );
            box.appendChild( actions );
        }
        list.appendChild( box );
        list.scrollTop = list.scrollHeight;
    }

    function send() {
        var text = prompt.value.trim();
        if ( ! text || busy ) { return; }

        var content = text;
        if ( include.checked ) {
            var sel  = currentSelection();
            var code = sel || currentCode();
            content = ( sel ? 'Selected part of the snippet:\n' : 'Current snippet code:\n' ) + code + '\n\nTask: ' + text;
        }

        addUser( text );
        prompt.value = '';
        history.push( { role: 'user', content: content } );
        history = history.slice( -12 );

        busy = true;
        sendBtn.disabled = true;
        spinner.classList.add( 'is-active' );

        window.SNN_AI_Helpers.makeTextCompletion( {
            messages:    [ { role: 'system', content: systemPrompt() } ].concat( history ),
            temperature: 0.3,
            maxTokens:   cfg.maxTokens,
            // Reasoning models can spend the whole budget thinking and return no
            // content. OpenRouter understands this; other endpoints may reject it.
            additionalParams: cfg.isOpenRouter ? { reasoning: { effort: 'low', exclude: true } } : {}
        } ).then( function ( data ) {
            var choice = data && data.choices && data.choices[0] ? data.choices[0] : {};
            var raw    = choice.message && typeof choice.message.content === 'string' ? choice.message.content : '';
            var cut    = choice.finish_reason === 'length';
            if ( ! raw.trim() ) {
                throw new Error( cut ? t.noRoom : t.noContent );
            }
            var reply = stripFences( raw );
            history.push( { role: 'assistant', content: reply } );
            addReply( reply, false );
            if ( cut ) { addReply( t.truncated, true ); }
        } ).catch( function ( err ) {
            history.pop();
            addReply( t.error + ': ' + ( err && err.message ? err.message : String( err ) ), true );
        } ).then( function () {
            busy = false;
            sendBtn.disabled = false;
            spinner.classList.remove( 'is-active' );
            refreshInclude();
        } );
    }
    sendBtn.addEventListener( 'click', send );
} );
JS;
}
