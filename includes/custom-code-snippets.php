<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('SNN_CUSTOM_CODES_LOG_OPTION', 'snn_custom_codes_error_log');
define('SNN_CUSTOM_CODES_MAX_LOG_ENTRIES', 150);
define('SNN_FATAL_ERROR_NOTICE_TRANSIENT', 'snn_fatal_error_admin_notice');

/**
 * Register the Custom Post Type for Code Snippets.
 */
function snn_custom_codes_snippets_register_cpt() {
    $labels = array(
        'name'               => _x( 'Code Snippets', 'post type general name', 'snn-custom-codes' ),
        'singular_name'      => _x( 'Code Snippet', 'post type singular name', 'snn-custom-codes' ),
        'all_items'          => __( 'All Code Snippets', 'snn-custom-codes' ),
        'edit_item'          => __( 'Edit Code Snippet', 'snn-custom-codes' ),
        'new_item'           => __( 'New Code Snippet', 'snn-custom-codes' ),
        'view_item'          => __( 'View Code Snippet', 'snn-custom-codes' ),
        'search_items'       => __( 'Search Code Snippets', 'snn-custom-codes' ),
        'not_found'          => __( 'No code snippets found', 'snn-custom-codes' ),
        'not_found_in_trash' => __( 'No code snippets found in Trash', 'snn-custom-codes' ),
        'revisions'          => __( 'Revisions', 'snn-custom-codes' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => false,
        'show_in_menu'       => false,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'revisions' ),
        'has_archive'        => false,
        'show_in_rest'       => false,
    );
    register_post_type( 'snn_code_snippet', $args );
}
add_action( 'init', 'snn_custom_codes_snippets_register_cpt' );

/**
 * Add the submenu page for managing snippets.
 */
function snn_custom_codes_snippets_add_submenu() {
    add_submenu_page(
        'snn-settings',
        __( 'Code Snippets', 'snn-custom-codes' ),
        __( 'Code Snippets', 'snn-custom-codes' ),
        'manage_options',
        'snn-custom-codes-snippets',
        'snn_custom_codes_snippets_page'
    );
}
add_action( 'admin_menu', 'snn_custom_codes_snippets_add_submenu', 10 );

/**
 * Enqueue CodeMirror assets and add inline JavaScript.
 */
function snn_custom_codes_snippets_enqueue_assets( $hook ) {
    // Determine the correct hook for the snippets page.
    // This can vary if 'snn-settings' is a top-level menu or a submenu.
    $current_screen = get_current_screen();
    $is_correct_page = false;
    if ($current_screen) {
        $valid_ids = [
            'snn-settings_page_snn-custom-codes-snippets', // Submenu of 'snn-settings'
            'toplevel_page_snn-settings_page_snn-custom-codes-snippets', // If 'snn-settings' is top-level
            'admin_page_snn-custom-codes-snippets' // If added under a generic admin page
        ];
         if (in_array($current_screen->id, $valid_ids) || $current_screen->base === 'snn-settings_page_snn-custom-codes-snippets') {
            $is_correct_page = true;
        }
    }
    // Fallback check using $hook if $current_screen is not definitive
    if (!$is_correct_page && strpos($hook, 'snn-custom-codes-snippets') === false) {
        return;
    }


    $cm_settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
    if ( false === $cm_settings ) {
        wp_enqueue_script('jquery');
        return;
    }

    wp_enqueue_script( 'wp-theme-plugin-editor' );
    wp_enqueue_style( 'wp-codemirror' );
    wp_enqueue_style( 'dashicons' );

    wp_add_inline_script(
        'wp-theme-plugin-editor',
        sprintf(
            'jQuery( function( $ ) {
                var editorSettings = %s;
                $( "#snn_frontend_code, #snn_footer_code, #snn_admin_code, #snn_functions_code" ).each( function() {
                    if (wp && wp.codeEditor) {
                        wp.codeEditor.initialize( this, editorSettings );
                    } else {
                        $(this).css({"font-family": "monospace", "font-size": "13px", "border": "1px solid #ddd", "width": "100%%", "padding": "10px"});
                    }
                });
            } );',
            wp_json_encode( $cm_settings )
        )
    );

    $ajax_nonce = wp_create_nonce( 'snn_preview_revision_nonce' );
    $js_for_revisions = "
jQuery(document).ready(function($) {
    var snn_revisions_vars = {
        ajax_url: '" . esc_url( admin_url( 'admin-ajax.php' ) ) . "',
        nonce: '" . esc_js( $ajax_nonce ) . "',
        loading_text: '" . esc_js(__( 'Loading...', 'snn-custom-codes' )) . "',
        preview_text: '" . esc_js(__( 'Preview in Editor', 'snn-custom-codes' )) . "',
        error_text: '" . esc_js(__( 'Error', 'snn-custom-codes' )) . "',
        ajax_error_text: '" . esc_js(__( 'AJAX error fetching revision.', 'snn-custom-codes' )) . "',
        confirm_restore_text: '" . esc_js(__('Are you sure you want to restore this revision and save? The current content in the editor will be overwritten, saved, and then executed. This could break your site if the revision contains errors.', 'snn-custom-codes')) . "',
        confirm_clear_revisions_text: '" . esc_js(__('Are you absolutely sure you want to delete all revisions for this snippet? This action cannot be undone.', 'snn-custom-codes')) . "',
        confirm_clear_logs_text: '" . esc_js(__('Are you absolutely sure you want to delete all error logs? This action cannot be undone.', 'snn-custom-codes')) . "'
    };

    $('body').on('click', '.snn-preview-revision', function(e) {
        e.preventDefault();
        var revisionId = $(this).data('revision-id');
        var button = $(this);
        var originalButtonText = button.text();
        var activeEditorTextareaId = $('.snn-revisions-panel').data('active-editor-id');

        if (!activeEditorTextareaId) {
            alert('Could not determine active editor. Ensure data-active-editor-id is set on .snn-revisions-panel.');
            return;
        }
        
        var editorTextarea = $('#' + activeEditorTextareaId);
        var cmInstance = null;

        if (editorTextarea.length) {
            if (editorTextarea.get(0).CodeMirror) {
                cmInstance = editorTextarea.get(0).CodeMirror;
            } else if (editorTextarea.next('.CodeMirror').get(0) && editorTextarea.next('.CodeMirror').get(0).CodeMirror) {
                cmInstance = editorTextarea.next('.CodeMirror').get(0).CodeMirror;
            }
        }

        if (!cmInstance) {
            button.prop('disabled', true).text(snn_revisions_vars.loading_text);
            $.ajax({
                url: snn_revisions_vars.ajax_url, type: 'POST',
                data: { action: 'snn_get_revision_content', revision_id: revisionId, nonce: snn_revisions_vars.nonce },
                success: function(response) {
                    if (response.success) { editorTextarea.val(response.data.content); }
                    else { alert(snn_revisions_vars.error_text + ': ' + (response.data.message || snn_revisions_vars.ajax_error_text)); }
                },
                error: function() { alert(snn_revisions_vars.ajax_error_text); },
                complete: function() { button.prop('disabled', false).text(originalButtonText); }
            });
            return;
        }

        button.prop('disabled', true).text(snn_revisions_vars.loading_text);

        $.ajax({
            url: snn_revisions_vars.ajax_url, type: 'POST',
            data: { action: 'snn_get_revision_content', revision_id: revisionId, nonce: snn_revisions_vars.nonce },
            success: function(response) {
                if (response.success) {
                    cmInstance.setValue(response.data.content);
                    cmInstance.refresh();
                } else {
                    alert(snn_revisions_vars.error_text + ': ' + (response.data.message || snn_revisions_vars.ajax_error_text));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(snn_revisions_vars.ajax_error_text + '\\n' + textStatus + ': ' + errorThrown);
            },
            complete: function() { button.prop('disabled', false).text(originalButtonText); }
        });
    });

    $('body').on('click', '.snn-restore-revision-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_restore_text)) {
            e.preventDefault();
        }
    });

    $('body').on('click', '.snn-clear-revisions-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_revisions_text)) {
            e.preventDefault();
        }
    });

    $('body').on('click', '.snn-clear-error-logs-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_logs_text)) {
            e.preventDefault();
        }
    });

    // Dismiss fatal error notice
    $('body').on('click', '.snn-dismiss-fatal-notice', function(e) {
        e.preventDefault();
        var \$button = \$(this);
        $.ajax({
            url: snn_revisions_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'snn_dismiss_fatal_error_notice',
                nonce: '" . esc_js(wp_create_nonce('snn_dismiss_fatal_notice_nonce')) . "'
            },
            success: function(response) {
                if (response.success) {
                    \$button.closest('.notice-error').fadeOut();
                } else {
                    alert('Could not dismiss notice: ' + response.data.message);
                }
            },
            error: function() {
                alert('AJAX error dismissing notice.');
            }
        });
    });
});
";
    wp_add_inline_script( 'wp-theme-plugin-editor', $js_for_revisions );
}
add_action( 'admin_enqueue_scripts', 'snn_custom_codes_snippets_enqueue_assets' );

/**
 * Add custom CSS to admin head.
 */
function snn_custom_codes_snippets_admin_styles() {
    $screen = get_current_screen();
    $is_correct_page = false;
    if ($screen) {
        $valid_ids = [
            'snn-settings_page_snn-custom-codes-snippets',
            'toplevel_page_snn-settings_page_snn-custom-codes-snippets',
            'admin_page_snn-custom-codes-snippets'
        ];
         if (in_array($screen->id, $valid_ids) || $screen->base === 'snn-settings_page_snn-custom-codes-snippets') {
            $is_correct_page = true;
        }
    }
    if (!$is_correct_page) return;

    echo '<style>
        h3{margin:0}
        th,td{padding:0 !important}
        .CodeMirror { min-height: 620px !important; border: 1px solid #ddd; }
        .snn-snippet-nav-tab-wrapper { margin-bottom: 15px; }
        .snn-snippet-description { margin-bottom: 10px; font-style: italic; color: #555; }
        .form-table th { width: 200px; }

        .snn-editor-revision-wrapper { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .snn-editor-area { flex: 3; min-width: 380px; position: relative; }
        .snn-revisions-panel {
            flex: 1;
            min-width: 300px;
            max-width: 360px;
            border-left: 1px solid #ccd0d4;
            padding-left: 20px;
        }
        .snn-revisions-panel-inner {
            max-height: 680px; 
            overflow-y: auto;
            padding-right: 10px; /* For scrollbar */
        }
        .snn-revisions-list { list-style: none; margin: 0; padding: 0; }
        .snn-revisions-list li {
            margin-bottom: 0px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .snn-revisions-list li:last-child { border-bottom: none; }
        .snn-revisions-list .revision-info { display: block; font-size: 0.9em; color: #555; margin-bottom: 8px; }
        .snn-revisions-list .revision-actions button,
        .snn-revisions-list .revision-actions .snn-view-comparison-link {
            margin-right: 5px;
            margin-top: 5px;
            vertical-align: middle;
        }
        .snn-revisions-list .revision-actions .snn-view-comparison-link .dashicons {
            font-size: 14px;
            text-decoration: none;
            vertical-align: text-bottom;
            position: relative;
            top: 5px;
        }
        .snn-revisions-panel h4 { margin-top: 0; font-size: 1.1em; }
        .snn-php-execution-warning { border-left-width: 4px; margin-top: 15px; margin-bottom: 15px; }
        .snn-clear-revisions-button { margin-top: 10px; }
        .snn-manage-revisions-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .snn-error-logs-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .snn-error-logs-table th, .snn-error-logs-table td { border: 1px solid #ddd; padding: 8px !important; text-align: left; vertical-align: top; }
        .snn-error-logs-table th { background-color: #f9f9f9; }
        .snn-error-logs-table td pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; font-size: 12px; }
        .snn-error-logs-table .snn-log-message { max-width: 400px; }
        .snn-error-logs-table .snn-log-actions { width: 100px; }
        .snn-fatal-error-notice strong { color: #dc3232; }
        .snn-fatal-error-notice code { background: #f9f9f9; border: 1px solid #ddd; padding: 2px 4px; font-size: 0.9em; }
    </style>';
}
add_action( 'admin_head', 'snn_custom_codes_snippets_admin_styles' );

/**
 * Helper function to log an error event.
 *
 * @param string $type Type of error (e.g., 'PHP Warning', 'Fatal Error', 'Parse Error').
 * @param string $message The error message.
 * @param string $snippet_slug Slug of the snippet causing the error.
 * @param string $file File where the error occurred.
 * @param int    $line Line number of the error.
 */
function snn_log_error_event( $type, $message, $snippet_slug, $file = '', $line = 0 ) {
    $logs = get_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
    if ( ! is_array( $logs ) ) {
        $logs = array();
    }

    $log_entry = array(
        'timestamp'    => current_time( 'mysql' ),
        'type'         => sanitize_text_field( $type ),
        'message'      => wp_strip_all_tags( $message ), // Basic sanitization for display
        'snippet_slug' => sanitize_text_field( $snippet_slug ),
        'file'         => sanitize_text_field( $file ),
        'line'         => absint( $line ),
    );

    // Add to the beginning of the array
    array_unshift( $logs, $log_entry );

    // Keep only the most recent N entries
    if ( count( $logs ) > SNN_CUSTOM_CODES_MAX_LOG_ENTRIES ) {
        $logs = array_slice( $logs, 0, SNN_CUSTOM_CODES_MAX_LOG_ENTRIES );
    }

    update_option( SNN_CUSTOM_CODES_LOG_OPTION, $logs );
}


/**
 * Helper function to get a specific code snippet's content.
 */
function snn_get_code_snippet_content( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug,
        'posts_per_page'   => 1,
        'post_status'      => 'private',
        'suppress_filters' => true,
    );
    $snippet_posts = get_posts( $args );
    return ( ! empty( $snippet_posts ) && isset( $snippet_posts[0]->post_content ) ) ? $snippet_posts[0]->post_content : '';
}

/**
 * Helper function to get a specific code snippet's CPT ID.
 */
function snn_get_code_snippet_id( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug,
        'posts_per_page'   => 1,
        'post_status'      => 'private',
        'fields'           => 'ids',
        'suppress_filters' => true,
    );
    $snippet_ids = get_posts( $args );
    return ! empty( $snippet_ids ) ? $snippet_ids[0] : 0;
}

/**
 * Executes a PHP code snippet with output buffering and error handling.
 */
function snn_execute_php_snippet( $code_to_execute, $snippet_location_slug ) {
    if ( empty( trim( $code_to_execute ) ) ) {
        return '';
    }

    $error_occurred = false;
    $error_details_for_log = [
        'type' => '', 'message' => '', 'file' => '', 'line' => 0
    ];
    $error_message_for_admin_comment = '';

    // Custom error handler for non-fatal errors
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_occurred, &$error_details_for_log, &$error_message_for_admin_comment, $snippet_location_slug) {
        // Ignore E_DEPRECATED and E_STRICT if not debugging
        if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
            if ( $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED || $errno === E_STRICT ) {
                return true; // Don't treat as an error to log or display unless WP_DEBUG is on
            }
        }

        $error_occurred = true;
        $error_type_str = 'PHP Error';
        switch ($errno) {
            case E_WARNING: case E_USER_WARNING: $error_type_str = 'PHP Warning'; break;
            case E_NOTICE: case E_USER_NOTICE: $error_type_str = 'PHP Notice'; break;
            case E_DEPRECATED: case E_USER_DEPRECATED: $error_type_str = 'PHP Deprecated'; break;
            case E_STRICT: $error_type_str = 'PHP Strict'; break;
        }
        
        $error_details_for_log = [
            'type' => $error_type_str,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        $error_message_for_admin_comment = sprintf(
            "Error in snippet '%s': [%s] %s on line %d.",
            esc_html($snippet_location_slug), $error_type_str, esc_html($errstr), $errline
        );
        // Log this non-fatal error
        snn_log_error_event($error_details_for_log['type'], $error_details_for_log['message'], $snippet_location_slug, $error_details_for_log['file'], $error_details_for_log['line']);
        return true; // Prevent default PHP error handler
    });

    ob_start();

    try {
        eval( "?>" . $code_to_execute );
    } catch (ParseError $e) {
        $error_occurred = true;
        $error_details_for_log = [
            'type' => 'PHP Parse Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(), // This will likely be the current file, with line in eval()
            'line' => $e->getLine()
        ];
        $error_message_for_admin_comment = sprintf(
            "Parse Error in snippet '%s': %s on line %d.",
            esc_html($snippet_location_slug), esc_html($e->getMessage()), $e->getLine()
        );
        snn_log_error_event($error_details_for_log['type'], $error_details_for_log['message'], $snippet_location_slug, $error_details_for_log['file'], $error_details_for_log['line']);
    } catch (Throwable $e) {
        $error_occurred = true;
        $error_details_for_log = [
            'type' => 'PHP Exception/Error (' . get_class($e) . ')',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $error_message_for_admin_comment = sprintf(
            "Exception/Error (%s) in snippet '%s': %s on line %d.",
            esc_html(get_class($e)), esc_html($snippet_location_slug), esc_html($e->getMessage()), $e->getLine()
        );
        snn_log_error_event($error_details_for_log['type'], $error_details_for_log['message'], $snippet_location_slug, $error_details_for_log['file'], $error_details_for_log['line']);
    }

    $output_from_snippet = ob_get_clean();
    restore_error_handler();

    if ( $error_occurred ) {
        // For admins, show a comment in the HTML output.
        if ( current_user_can( 'manage_options' ) ) {
             // The detailed error is already logged.
            return "\n\n";
        } else {
            // For non-admins, return an empty comment or nothing to avoid exposing error details.
            return "\n\n";
        }
    }

    return $output_from_snippet;
}

/**
 * Display the admin page for managing custom code snippets.
 */
function snn_custom_codes_snippets_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'snn-custom-codes' ) );
    }

    $snippet_defs = array(
        'frontend' => array( /* ... as before ... */ ),
        'footer'   => array( /* ... as before ... */ ),
        'admin'    => array( /* ... as before ... */ ),
        'functions' => array( /* ... as before ... */ ),
    );
    // Re-populate $snippet_defs as they were in the original code
     $snippet_defs = array(
        'frontend' => array(
            'title'       => __( 'Frontend Head PHP/HTML', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-frontend-head',
            'field_id'    => 'snn_frontend_code',
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> tags on the frontend. Use for dynamic meta tags, conditional CSS/JS links, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn-custom-codes' ),
        ),
        'footer'   => array(
            'title'       => __( 'Frontend Footer PHP/HTML', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-footer',
            'field_id'    => 'snn_footer_code',
            'description' => __( 'PHP code or HTML executed before the <code>&lt;/body&gt;</code> tag on the frontend. Use for late-loading dynamic content, analytics, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn-custom-codes' ),
        ),
        'admin'    => array(
            'title'       => __( 'Admin Head PHP/HTML', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-admin-head',
            'field_id'    => 'snn_admin_code',
            'description' => __( 'PHP code or HTML executed within the <code>&lt;head&gt;</code> of WordPress admin pages. Use for conditional admin CSS/JS, admin modifications, etc. You can use <code>&lt;?php ?&gt;</code> tags for PHP code.', 'snn-custom-codes' ),
        ),
        'functions' => array(
            'title'       => __( 'Direct PHP (functions.php)', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-functions-php',
            'field_id'    => 'snn_functions_code',
            'description' => __( 'PHP executed immediately when the plugin loads (no hook) – just like putting code in <code>functions.php</code>. ', 'snn-custom-codes' ),
        ),
    );


    $settings_saved_message_type = 'updated';

    // Handle form submissions
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['snn_codes_snippets_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snn_codes_snippets_nonce'] ) ), 'snn_save_codes_snippets' ) ) {
        
        // Handle Clear Error Logs Action
        if ( isset( $_POST['snn_clear_error_logs_button'] ) ) {
            check_admin_referer( 'snn_clear_error_logs_action', 'snn_clear_error_logs_nonce' );
            update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
            add_settings_error('snn-custom-codes', 'logs_cleared', __('All error logs have been cleared.', 'snn-custom-codes'), 'updated');
            $_GET['tab'] = 'error_logs'; // Keep user on the logs tab
        }
        // Handle Clear Revisions Action
        elseif ( isset( $_POST['snn_clear_revisions_button'] ) && ! empty( $_POST['snn_clear_revisions_button'] ) ) {
            // ... (existing clear revisions logic, ensure $_GET['tab'] is set)
            $snippet_key_to_clear = isset( $_POST['snn_snippet_key_to_clear'] ) ? sanitize_key( $_POST['snn_snippet_key_to_clear'] ) : '';
            if ( $snippet_key_to_clear && isset( $snippet_defs[ $snippet_key_to_clear ] ) ) {
                check_admin_referer( 'snn_clear_revisions_' . $snippet_key_to_clear, 'snn_clear_revisions_nonce_' . $snippet_key_to_clear );
                $target_snippet_def = $snippet_defs[ $snippet_key_to_clear ];
                $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );
                if ( $target_post_id && current_user_can( 'delete_post', $target_post_id ) ) {
                    $revisions_to_delete = wp_get_post_revisions( $target_post_id, array( 'fields' => 'ids', 'posts_per_page' => -1 ) );
                    if ( !empty($revisions_to_delete) ) {
                        $deleted_count = 0;
                        foreach ( $revisions_to_delete as $revision_id_to_delete ) {
                            if ( wp_delete_post_revision( $revision_id_to_delete ) ) $deleted_count++;
                        }
                        if ($deleted_count > 0) add_settings_error('snn-custom-codes', 'revisions_cleared', sprintf(__( '%d revision(s) for "%s" cleared successfully.', 'snn-custom-codes' ), $deleted_count, esc_html($target_snippet_def['title'])), 'updated');
                        else add_settings_error('snn-custom-codes', 'revisions_clear_failed_none_deleted', sprintf(__( 'No revisions were deleted for "%s".', 'snn-custom-codes' ), esc_html($target_snippet_def['title'])), 'warning');
                    } else add_settings_error('snn-custom-codes', 'no_revisions_to_clear', sprintf(__( 'No revisions found to clear for "%s".', 'snn-custom-codes' ), esc_html($target_snippet_def['title'])), 'info');
                } else {
                    add_settings_error('snn-custom-codes', 'clear_revisions_failed_permissions', __('Failed to clear revisions. Invalid snippet or insufficient permissions.', 'snn-custom-codes'), 'error');
                    $settings_saved_message_type = 'error';
                }
                $_GET['tab'] = $snippet_key_to_clear;
            }
        } 
        // Handle Restore Revision Action
        elseif ( isset( $_POST['snn_restore_submit_button'] ) && ! empty( $_POST['snn_restore_submit_button'] ) ) {
            // ... (existing restore revision logic, ensure $_GET['tab'] is set)
            $restore_action = sanitize_text_field( wp_unslash( $_POST['snn_restore_submit_button'] ) );
            $parts = explode( '_', $restore_action );
            if ( count($parts) === 3 && 'restore' === $parts[0] ) {
                $revision_id = absint( $parts[1] );
                $snippet_key_for_restore = sanitize_key( $parts[2] );
                if ( $revision_id && isset( $snippet_defs[ $snippet_key_for_restore ] ) ) {
                    $target_snippet_def = $snippet_defs[ $snippet_key_for_restore ];
                    $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );
                    $revision = wp_get_post_revision( $revision_id );
                    if ( $target_post_id && $revision && $revision->post_parent == $target_post_id && current_user_can( 'edit_post', $target_post_id ) ) {
                        wp_restore_post_revision( $revision_id );
                        $_POST[ $target_snippet_def['field_id'] ] = $revision->post_content;
                        $_GET['tab'] = $snippet_key_for_restore;
                        add_settings_error('snn-custom-codes', 'revision_restored', sprintf(__('Revision for "%s" restored and will be saved.', 'snn-custom-codes'), esc_html($target_snippet_def['title'])), 'updated');
                    } else {
                        add_settings_error('snn-custom-codes', 'restore_failed', __('Failed to restore revision. Invalid ID or permissions.', 'snn-custom-codes'), 'error');
                        $settings_saved_message_type = 'error';
                    }
                }
            }
        }

        // Save global enable/disable setting (unless only clearing logs)
        if ( ! isset( $_POST['snn_clear_error_logs_button'] ) ) {
            $is_enabled = isset( $_POST['snn_codes_snippets_enabled'] ) ? 1 : 0;
            update_option( 'snn_codes_snippets_enabled', $is_enabled );

            // If snippets are being re-enabled, clear the fatal error notice transient
            if ($is_enabled) {
                delete_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT);
            }

            // Save content for all defined snippets
            $all_snippets_processed_successfully = true;
            foreach ( $snippet_defs as $key => $def ) {
                if ( isset( $_POST[ $def['field_id'] ] ) ) {
                    $new_code_content = wp_unslash( $_POST[ $def['field_id'] ] );
                    $snippet_post_id = snn_get_code_snippet_id( $def['slug'] );
                    $post_data = array(
                        'post_title'   => $def['title'], 'post_content' => $new_code_content,
                        'post_status'  => 'private', 'post_type'    => 'snn_code_snippet',
                        'post_name'    => $def['slug'],
                    );
                    if ( $snippet_post_id ) {
                        $post_data['ID'] = $snippet_post_id;
                        $updated_id = wp_update_post( $post_data, true );
                        if ( is_wp_error( $updated_id ) ) {
                            add_settings_error('snn-custom-codes', 'update_failed_' . $key, sprintf(__('Failed to update snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($updated_id->get_error_message())), 'error');
                            $all_snippets_processed_successfully = false;
                        }
                    } else {
                        $inserted_id = wp_insert_post( $post_data, true );
                        if ( is_wp_error( $inserted_id ) ) {
                            add_settings_error('snn-custom-codes', 'insert_failed_' . $key, sprintf(__('Failed to create snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($inserted_id->get_error_message())), 'error');
                            $all_snippets_processed_successfully = false;
                        }
                    }
                }
            }

            $notices = get_settings_errors('snn-custom-codes');
            $has_specific_action_message = false;
            foreach ($notices as $notice) {
                if (in_array($notice['code'], ['revision_restored', 'revisions_cleared', 'no_revisions_to_clear', 'logs_cleared'])) {
                    $has_specific_action_message = true;
                    break;
                }
            }

            if ( $all_snippets_processed_successfully && !$has_specific_action_message && $settings_saved_message_type === 'updated' ) {
                add_settings_error('snn-custom-codes', 'settings_saved', __('All snippets and settings saved.', 'snn-custom-codes'), 'updated');
            } elseif (!$all_snippets_processed_successfully && $settings_saved_message_type !== 'error') {
                add_settings_error('snn-custom-codes', 'save_errors', __('Some snippets could not be saved. Please check messages above.', 'snn-custom-codes'), 'error');
            }
        }

    } // End of POST handling

    $enabled_globally = get_option( 'snn_codes_snippets_enabled', 0 );
    $current_tab_key = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'frontend'; 
    if ( ! array_key_exists( $current_tab_key, $snippet_defs ) && $current_tab_key !== 'error_logs' ) {
        $current_tab_key = 'frontend'; 
    }

    $codes_for_display = array();
    foreach ( $snippet_defs as $key => $def ) {
        $codes_for_display[ $key ] = snn_get_code_snippet_content( $def['slug'] );
    }

    settings_errors('snn-custom-codes');
    ?>
    <div class="wrap">
        <h1> <?php esc_html_e( 'Manage Code Snippets', 'snn-custom-codes' ); ?> </h1>
        
        <form method="post" action="admin.php?page=snn-custom-codes-snippets&tab=<?php echo esc_attr($current_tab_key); ?>">
            <?php wp_nonce_field( 'snn_save_codes_snippets', 'snn_codes_snippets_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Global Snippet Execution', 'snn-custom-codes' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( 'Global Snippet Execution', 'snn-custom-codes' ); ?></span></legend>
                                <label for="snn_codes_snippets_enabled">
                                    <input type="checkbox" id="snn_codes_snippets_enabled" name="snn_codes_snippets_enabled" value="1" <?php checked( 1, $enabled_globally ); ?>>
                                    <?php esc_html_e( 'Enable execution of all custom PHP snippets', 'snn-custom-codes' ); ?>
                                </label>
                                <?php if ( ! $enabled_globally && get_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT) ) : ?>
                                    <p class="description" style="color: #dc3232;">
                                        <?php esc_html_e( 'Execution was automatically disabled due to a fatal error. Please check the Error Logs tab, resolve the issue, and then re-enable.', 'snn-custom-codes' ); ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="nav-tab-wrapper snn-snippet-nav-tab-wrapper">
                <?php
                foreach ( $snippet_defs as $key => $def ) {
                    $active_class = ( $current_tab_key === $key ) ? 'nav-tab-active' : '';
                    $tab_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=' . $key );
                    echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . esc_attr( $active_class ) . '">' . esc_html( $def['title'] ) . '</a>';
                }
                // Add Error Logs tab
                $logs_tab_active_class = ( $current_tab_key === 'error_logs' ) ? 'nav-tab-active' : '';
                $logs_tab_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' );
                echo '<a href="' . esc_url( $logs_tab_url ) . '" class="nav-tab ' . esc_attr( $logs_tab_active_class ) . '">' . esc_html__( 'Error Logs', 'snn-custom-codes' ) . '</a>';
                ?>
            </h2>

            <?php if ( $current_tab_key === 'error_logs' ) : ?>
                <div id="snn-tab-content-error-logs" class="snn-tab-content">
                    <h3><?php esc_html_e( 'Snippet Execution Error Logs', 'snn-custom-codes' ); ?></h3>
                    <p><?php esc_html_e( 'This log shows the last 50 errors recorded from snippet executions. If a fatal error occurs, snippet execution will be globally disabled.', 'snn-custom-codes' ); ?></p>
                    <?php
                    $error_logs = get_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
                    if ( ! is_array( $error_logs ) ) $error_logs = array(); // Ensure it's an array

                    if ( ! empty( $error_logs ) ) : ?>
                        <table class="snn-error-logs-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Timestamp', 'snn-custom-codes' ); ?></th>
                                    <th><?php esc_html_e( 'Type', 'snn-custom-codes' ); ?></th>
                                    <th><?php esc_html_e( 'Snippet Location', 'snn-custom-codes' ); ?></th>
                                    <th class="snn-log-message"><?php esc_html_e( 'Message', 'snn-custom-codes' ); ?></th>
                                    <th><?php esc_html_e( 'File', 'snn-custom-codes' ); ?></th>
                                    <th><?php esc_html_e( 'Line', 'snn-custom-codes' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $error_logs as $log_entry ) : ?>
                                <tr>
                                    <td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $log_entry['timestamp'] ) ) ); ?></td>
                                    <td><?php echo esc_html( $log_entry['type'] ); ?></td>
                                    <td><?php echo esc_html( $log_entry['snippet_slug'] ); ?></td>
                                    <td class="snn-log-message"><pre><?php echo esc_html( $log_entry['message'] ); ?></pre></td>
                                    <td><?php echo esc_html( $log_entry['file'] ); ?></td>
                                    <td><?php echo esc_html( $log_entry['line'] ); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <?php wp_nonce_field( 'snn_clear_error_logs_action', 'snn_clear_error_logs_nonce' ); ?>
                            <button type="submit" name="snn_clear_error_logs_button" class="button button-danger snn-clear-error-logs-button">
                                <?php esc_html_e( 'Clear All Error Logs', 'snn-custom-codes' ); ?>
                            </button>
                        </p>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No errors logged yet.', 'snn-custom-codes' ); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ( isset( $snippet_defs[ $current_tab_key ] ) ) :
                $active_snippet_def = $snippet_defs[ $current_tab_key ];
                $current_code_value = isset($codes_for_display[ $current_tab_key ]) ? $codes_for_display[ $current_tab_key ] : ''; 
                if(isset($_POST[$active_snippet_def['field_id']])) { 
                    $current_code_value = wp_unslash($_POST[$active_snippet_def['field_id']]);
                }

                $active_snippet_post_id = snn_get_code_snippet_id( $active_snippet_def['slug'] );
                $revisions = array();
                if ( $active_snippet_post_id && wp_revisions_enabled( get_post( $active_snippet_post_id ) ) ) {
                    $revisions = wp_get_post_revisions( $active_snippet_post_id, array( 'posts_per_page' => 20, 'orderby' => 'post_date', 'order' => 'DESC' ) );
                }
            ?>
            <div class="snn-editor-revision-wrapper">
                <div class="snn-editor-area">
                    <div id="snn-tab-content-<?php echo esc_attr( $current_tab_key ); ?>" class="snn-tab-content">
                        <h3><?php echo esc_html( $active_snippet_def['title'] ); ?></h3>
                        <p class="snn-snippet-description"><?php echo wp_kses_post( $active_snippet_def['description'] ); ?></p>
                         <?php if ( $active_snippet_def['slug'] === 'snn-snippet-functions-php' ): ?>
                            <div class="notice notice-warning inline snn-php-execution-warning">
                                <p><strong><?php esc_html_e('Warning:', 'snn-custom-codes'); ?></strong> <?php esc_html_e('Code in this section runs like functions.php. Errors here can easily break your site. Test thoroughly!', 'snn-custom-codes'); ?></p>
                            </div>
                        <?php endif; ?>
                        <textarea id="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>"
                                  name="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>"
                                  class="large-text code"
                                  rows="25"
                                  placeholder="<?php esc_attr_e( 'Enter your PHP code or HTML here...', 'snn-custom-codes' ); ?>"
                        ><?php echo esc_textarea( $current_code_value ); ?></textarea>
                    </div>
                </div>

                <div class="snn-revisions-panel" data-active-editor-id="<?php echo esc_attr( $active_snippet_def['field_id'] ); ?>">
                    <h4><?php printf( esc_html__( 'Revisions for %s', 'snn-custom-codes' ), esc_html( $active_snippet_def['title'] ) ); ?></h4>
                    <div class="snn-revisions-panel-inner">
                        <?php if ( ! empty( $revisions ) ) : ?>
                            <ul class="snn-revisions-list">
                                <?php foreach ( $revisions as $revision ) :
                                    $revision_author_id   = $revision->post_author;
                                    $revision_author_info = get_userdata( $revision_author_id );
                                    $revision_author_name = $revision_author_info ? esc_html($revision_author_info->display_name) : __( 'Unknown Author', 'snn-custom-codes' );
                                    $comparison_link      = admin_url( 'revision.php?revision=' . $revision->ID . '&nonce=' . wp_create_nonce('view-revision_' . $revision->ID) );
                                    $time_diff            = human_time_diff( strtotime( $revision->post_date_gmt ), current_time( 'timestamp', true ) );
                                    $revision_date_title  = date_i18n( __( 'M j, Y @ H:i T' ), strtotime( $revision->post_date ) );
                                    $revision_info        = sprintf( '%s by %s (%s %s)', $revision_date_title, $revision_author_name, $time_diff, __('ago', 'snn-custom-codes') );
                                ?>
                                <li>
                                    <span class="revision-info"><?php echo esc_html( $revision_info ); ?></span>
                                    <div class="revision-actions">
                                        <button type="button" class="button button-secondary button-small snn-preview-revision"
                                                data-revision-id="<?php echo esc_attr( $revision->ID ); ?>">
                                            <?php esc_html_e( 'Preview in Editor', 'snn-custom-codes' ); ?>
                                        </button>
                                        <a href="<?php echo esc_url( $comparison_link ); ?>" target="_blank"
                                           class="button button-outlined button-small snn-view-comparison-link"
                                           title="<?php esc_attr_e( 'View full comparison in new tab', 'snn-custom-codes' ); ?>">
                                            <span class="dashicons dashicons-search"></span> <?php esc_html_e('Compare', 'snn-custom-codes'); ?>
                                        </a>
                                        <button type="submit"
                                                name="snn_restore_submit_button"
                                                value="restore_<?php echo esc_attr( $revision->ID ) . '_' . esc_attr( $current_tab_key ); ?>"
                                                class="button button-primary button-small snn-restore-revision-button"
                                                style="display:none">
                                            <?php esc_html_e( 'Restore & Save', 'snn-custom-codes' ); ?>
                                        </button>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="snn-manage-revisions-section">
                                <?php wp_nonce_field( 'snn_clear_revisions_' . $current_tab_key, 'snn_clear_revisions_nonce_' . $current_tab_key ); ?>
                                <input type="hidden" name="snn_snippet_key_to_clear" value="<?php echo esc_attr($current_tab_key); ?>">
                                <button type="submit" name="snn_clear_revisions_button" value="clear_<?php echo esc_attr($current_tab_key); ?>" class="button button-danger snn-clear-revisions-button">
                                    <?php esc_html_e( 'Clear All Revisions for this Snippet', 'snn-custom-codes' ); ?>
                                </button>
                                <p class="description"><?php esc_html_e( 'This will permanently delete all revisions for this snippet. Cannot be undone.', 'snn-custom-codes' ); ?></p>
                            </div>
                        <?php elseif ( $active_snippet_post_id ) : ?>
                            <p><?php esc_html_e( 'No past revisions found. Save changes to create revisions.', 'snn-custom-codes' ); ?></p>
                        <?php else : ?>
                            <p><?php esc_html_e( 'Save this snippet to start tracking revisions.', 'snn-custom-codes' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($current_tab_key !== 'error_logs'): // Don't show global save button on logs tab ?>
                <?php submit_button( __( 'Save All Snippets & Settings', 'snn-custom-codes' ), 'primary large', 'snn_save_all_settings_button' ); ?>
            <?php endif; ?>
        </form>
    </div>
    <?php
}

/**
 * Initialize snippet execution hooks.
 */
function snn_custom_codes_snippets_init_execution() {
    if ( ! get_option( 'snn_codes_snippets_enabled', 0 ) ) {
        return;
    }

    $direct_code = snn_get_code_snippet_content( 'snn-snippet-functions-php' );
    if ( ! empty( trim( $direct_code ) ) ) {
        // Output from direct code is echoed directly. Errors are handled by snn_execute_php_snippet.
        echo snn_execute_php_snippet( $direct_code, 'snn-snippet-functions-php' );
    }

    if ( ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-frontend-head' ) ) ) ) {
        add_action( 'wp_head', 'snn_custom_codes_snippets_frontend_output', 1 );
    }
    if ( ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-footer' ) ) ) ) {
        add_action( 'wp_footer', 'snn_custom_codes_snippets_footer_output', 9999 );
    }
    if ( is_admin() && ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-admin-head' ) ) ) ) {
        add_action( 'admin_head', 'snn_custom_codes_snippets_admin_output', 1 );
    }
}
add_action( 'init', 'snn_custom_codes_snippets_init_execution', 99 ); 

/** Output callbacks */
function snn_custom_codes_snippets_frontend_output() {
    $code = snn_get_code_snippet_content( 'snn-snippet-frontend-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-frontend-head' );
}
function snn_custom_codes_snippets_footer_output()   {
    $code = snn_get_code_snippet_content( 'snn-snippet-footer' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-footer' );
}
function snn_custom_codes_snippets_admin_output()    {
    $code = snn_get_code_snippet_content( 'snn-snippet-admin-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-admin-head' );
}

/**
 * AJAX handler for fetching revision content.
 */
add_action( 'wp_ajax_snn_get_revision_content', 'snn_ajax_get_revision_content_callback' );
function snn_ajax_get_revision_content_callback() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_preview_revision_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) { 
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    $revision_id = isset( $_POST['revision_id'] ) ? absint( $_POST['revision_id'] ) : 0;
    if ( ! $revision_id ) {
        wp_send_json_error( array( 'message' => __( 'Missing revision ID.', 'snn-custom-codes' ) ) );
        return;
    }
    $revision = wp_get_post_revision( $revision_id );
    if ( ! $revision ) {
        wp_send_json_error( array( 'message' => __( 'Revision not found.', 'snn-custom-codes' ) ) );
        return;
    }
    if ( ! current_user_can( 'edit_post', $revision->post_parent ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied for accessing this revision.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    wp_send_json_success( array( 'content' => $revision->post_content, 'title'   => wp_post_revision_title_expanded( $revision ) ) );
}

/**
 * AJAX handler for dismissing the fatal error notice.
 */
add_action( 'wp_ajax_snn_dismiss_fatal_error_notice', 'snn_ajax_dismiss_fatal_error_notice_callback' );
function snn_ajax_dismiss_fatal_error_notice_callback() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_dismiss_fatal_notice_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    wp_send_json_success();
}


/**
 * Register fatal error shutdown handler.
 * This needs to be registered early.
 */
function snn_register_fatal_error_handler() {
    register_shutdown_function( 'snn_fatal_error_shutdown_handler' );
}
add_action( 'init', 'snn_register_fatal_error_handler', 1 ); // Register early on init

/**
 * Fatal error shutdown handler.
 * Checks for fatal errors, logs them, and disables snippets if the error is from this plugin.
 */
function snn_fatal_error_shutdown_handler() {
    $error = error_get_last();

    // Check if it's a fatal error type and if snippets are currently enabled
    if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ] ) ) {
        // Heuristic: Check if the error message or file indicates it's from our eval'd code or execution context.
        // The file for eval'd code is often reported as the file containing eval() with "(...) : eval()'d code"
        // or the error message itself might contain "eval()'d code".
        $error_source_is_snippet = false;
        $current_file_path = __FILE__; // Path to this plugin file

        if ( isset($error['file']) && (strpos( $error['file'], "eval()'d code" ) !== false || strpos( $error['file'], $current_file_path ) !== false )) {
            $error_source_is_snippet = true;
        } elseif (isset($error['message']) && strpos( $error['message'], "eval()'d code" ) !== false) {
             $error_source_is_snippet = true;
        }
        // A more specific check could involve inspecting the call stack if possible, but that's complex in a shutdown handler.

        if ( $error_source_is_snippet && get_option( 'snn_codes_snippets_enabled', 0 ) ) {
            // Determine snippet slug if possible (difficult in shutdown handler for fatal errors)
            // For now, log as 'unknown' or 'general fatal error'
            $snippet_slug_guess = 'unknown_fatal_error_source';
            // Try to get a hint from the error message if it mentions one of our known function names
            if (preg_match('/snn_custom_codes_snippets_(frontend_output|footer_output|admin_output|direct_execution)/', $error['message'], $matches)) {
                if ($matches[1] === 'frontend_output') $snippet_slug_guess = 'snn-snippet-frontend-head';
                elseif ($matches[1] === 'footer_output') $snippet_slug_guess = 'snn-snippet-footer';
                elseif ($matches[1] === 'admin_output') $snippet_slug_guess = 'snn-snippet-admin-head';
                elseif ($matches[1] === 'direct_execution') $snippet_slug_guess = 'snn-snippet-functions-php'; // Assuming direct execution implies functions.php style
            }


            snn_log_error_event(
                'PHP Fatal Error',
                $error['message'],
                $snippet_slug_guess, // This is a best guess
                $error['file'],
                $error['line']
            );

            // Disable global snippet execution
            update_option( 'snn_codes_snippets_enabled', 0 );

            // Set a transient to display an admin notice. Store the error message for display.
            $notice_data = [
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
                'type'    => $error['type']
            ];
            set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, $notice_data, DAY_IN_SECONDS );

            // Optionally, you could try to redirect to the admin page if it's safe,
            // but for fatal errors, it's often better to let WordPress handle its recovery if possible.
            // If WordPress's own recovery mode kicks in, this notice will still be there when admin logs back in.
        }
    }
}

/**
 * Display an admin notice if a fatal error occurred and snippets were disabled.
 */
function snn_display_fatal_error_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $fatal_error_details = get_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );

    if ( $fatal_error_details && is_array($fatal_error_details) ) {
        ?>
        <div class="notice notice-error is-dismissible snn-fatal-error-notice">
            <p><strong><?php esc_html_e( 'CRITICAL: Custom Code Snippets Disabled!', 'snn-custom-codes' ); ?></strong></p>
            <p>
                <?php esc_html_e( 'The "SNN Custom Codes" plugin automatically disabled all snippet executions due to a fatal PHP error. This is a safety measure to prevent your site from breaking further.', 'snn-custom-codes' ); ?>
            </p>
            <p><strong><?php esc_html_e( 'Error Details:', 'snn-custom-codes' ); ?></strong></p>
            <p>
                <code>
                    <?php echo esc_html( sprintf( "Type: %s, Message: %s, File: %s, Line: %d", $fatal_error_details['type'], $fatal_error_details['message'], $fatal_error_details['file'], $fatal_error_details['line'] ) ); ?>
                </code>
            </p>
            <p>
                <?php
                printf(
                    wp_kses_post( __( 'Please review the <a href="%s">Error Logs tab</a> for more details, identify and fix the problematic snippet. Once fixed, you can re-enable "Global Snippet Execution" on the plugin settings page.', 'snn-custom-codes' ) ),
                    esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' ) )
                );
                ?>
            </p>
             <p><button type="button" class="button snn-dismiss-fatal-notice"><?php esc_html_e('Dismiss This Notice', 'snn-custom-codes'); ?></button></p>
        </div>
        <?php
        // Note: The 'is-dismissible' class from WordPress handles its own AJAX.
        // Our custom button is for clearing our transient if user wants to acknowledge before fixing.
        // WordPress's dismiss will hide it for the session, our button clears the underlying trigger.
    }
}
add_action( 'admin_notices', 'snn_display_fatal_error_admin_notice' );


/** Activation hook */
function snn_custom_codes_plugin_activate() {
    snn_custom_codes_snippets_register_cpt(); 
    flush_rewrite_rules(); 
    if ( false === get_option( 'snn_codes_snippets_enabled', false ) ) {
        update_option( 'snn_codes_snippets_enabled', 0 );
    }
    // Initialize log option if it doesn't exist
    if ( false === get_option( SNN_CUSTOM_CODES_LOG_OPTION, false ) ) {
        update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
    }
}
register_activation_hook( __FILE__, 'snn_custom_codes_plugin_activate' );

/** Deactivation hook */
function snn_custom_codes_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'snn_custom_codes_plugin_deactivate' );

/** Load plugin textdomain */
function snn_custom_codes_load_textdomain() {
    load_plugin_textdomain( 'snn-custom-codes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'snn_custom_codes_load_textdomain' );

?>
