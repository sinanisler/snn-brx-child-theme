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
        'snn-settings', // Parent slug
        __( 'Code Snippets', 'snn-custom-codes' ), // Page title
        __( 'Code Snippets', 'snn-custom-codes' ), // Menu title
        'manage_options', // Capability
        'snn-custom-codes-snippets', // Menu slug
        'snn_custom_codes_snippets_page' // Function to display the page
    );
}
add_action( 'admin_menu', 'snn_custom_codes_snippets_add_submenu', 10 );

/**
 * Enqueue CodeMirror assets and add inline JavaScript.
 */
function snn_custom_codes_snippets_enqueue_assets( $hook ) {
    // Determine the correct hook for the snippets page.
    $current_screen = get_current_screen();
    $is_correct_page = false;
    if ($current_screen) {
        $valid_ids = [
            'snn-settings_page_snn-custom-codes-snippets', // Submenu of 'snn-settings'
            'toplevel_page_snn-settings_page_snn-custom-codes-snippets', // If 'snn-settings' is top-level
            'admin_page_snn-custom-codes-snippets' // If added under a generic admin page (less common for add_submenu_page)
        ];
         // Check current screen ID against known valid IDs or the base hook
         if (in_array($current_screen->id, $valid_ids) || $current_screen->base === 'snn-settings_page_snn-custom-codes-snippets') {
            $is_correct_page = true;
        }
    }
    
    // Fallback check using $hook if $current_screen is not definitive or available early enough
    // This checks if the hook suffix contains our page slug.
    // Also, a more direct check for the page query arg.
    if (!$is_correct_page && 
        (strpos($hook, 'snn-custom-codes-snippets') === false && (!isset($_GET['page']) || $_GET['page'] !== 'snn-custom-codes-snippets'))) {
        return;
    }


    // Enqueue CodeMirror
    $cm_settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) ); // For PHP
    if ( false === $cm_settings ) {
        // Fallback if CodeMirror can't be initialized (e.g., user preference disabled it)
        wp_enqueue_script('jquery'); // Ensure jQuery is loaded for basic fallback
        return;
    }

    // Enqueue WordPress scripts and styles for the editor
    wp_enqueue_script( 'wp-theme-plugin-editor' );
    wp_enqueue_style( 'wp-codemirror' );
    wp_enqueue_style( 'dashicons' ); // For icons like compare revisions

    // Inline script to initialize CodeMirror on textareas
    wp_add_inline_script(
        'wp-theme-plugin-editor',
        sprintf(
            'jQuery( function( $ ) {
                var editorSettings = %s;
                $( "#snn_frontend_code, #snn_footer_code, #snn_admin_code, #snn_functions_code" ).each( function() {
                    if (wp && wp.codeEditor) { // Check if CodeMirror API is available
                        wp.codeEditor.initialize( this, editorSettings );
                    } else {
                        // Basic styling if CodeMirror fails (e.g. user disabled it in profile)
                        $(this).css({"font-family": "monospace", "font-size": "13px", "border": "1px solid #ddd", "width": "100%%", "padding": "10px"});
                    }
                });
            } );',
            wp_json_encode( $cm_settings )
        )
    );

    // JavaScript for AJAX handling of revisions and notices
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

    // Handle 'Preview in Editor' button click for revisions
    $('body').on('click', '.snn-preview-revision', function(e) {
        e.preventDefault();
        var revisionId = $(this).data('revision-id');
        var button = $(this);
        var originalButtonText = button.text();
        // Get the ID of the currently active editor's textarea from the panel's data attribute
        var activeEditorTextareaId = $('.snn-revisions-panel').data('active-editor-id');

        if (!activeEditorTextareaId) {
            alert('Could not determine active editor. Ensure data-active-editor-id is set on .snn-revisions-panel.');
            return;
        }
        
        var editorTextarea = $('#' + activeEditorTextareaId);
        var cmInstance = null;

        // Try to get the CodeMirror instance associated with the textarea
        if (editorTextarea.length) {
            if (editorTextarea.get(0).CodeMirror) { // Instance directly on textarea
                cmInstance = editorTextarea.get(0).CodeMirror;
            } else if (editorTextarea.next('.CodeMirror').get(0) && editorTextarea.next('.CodeMirror').get(0).CodeMirror) {
                // Instance on the .CodeMirror wrapper div next to the textarea
                cmInstance = editorTextarea.next('.CodeMirror').get(0).CodeMirror;
            }
        }

        if (!cmInstance) {
            // Fallback if CodeMirror instance isn't found (e.g., editor disabled by user)
            // Update textarea value directly
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

        // If CodeMirror instance is found, use its API
        button.prop('disabled', true).text(snn_revisions_vars.loading_text);

        $.ajax({
            url: snn_revisions_vars.ajax_url, type: 'POST',
            data: { action: 'snn_get_revision_content', revision_id: revisionId, nonce: snn_revisions_vars.nonce },
            success: function(response) {
                if (response.success) {
                    cmInstance.setValue(response.data.content);
                    cmInstance.refresh(); // Refresh CM to show new content
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

    // Confirmation for 'Restore & Save' button
    $('body').on('click', '.snn-restore-revision-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_restore_text)) {
            e.preventDefault(); // Prevent form submission if user cancels
        }
    });

    // Show 'Restore & Save' button when 'Preview in Editor' is clicked
    $('body').on('click', '.snn-preview-revision', function() {
        // Hide all other restore buttons first to prevent multiple showing
        $('.snn-restore-revision-button').hide();
        // Show the restore button specific to this revision item
        $(this).closest('li').find('.snn-restore-revision-button').show();
    });
    
    // Confirmation for 'Clear All Revisions' button
    $('body').on('click', '.snn-clear-revisions-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_revisions_text)) {
            e.preventDefault();
        }
    });

    // Confirmation for 'Clear All Error Logs' button
    $('body').on('click', '.snn-clear-error-logs-button', function(e) {
        if (!confirm(snn_revisions_vars.confirm_clear_logs_text)) {
            e.preventDefault();
        }
    });

    // AJAX for dismissing the fatal error admin notice
    $('body').on('click', '.snn-dismiss-fatal-notice', function(e) {
        e.preventDefault();
        var \$button = \$(this);
        $.ajax({
            url: snn_revisions_vars.ajax_url, // Use the global ajax_url
            type: 'POST',
            data: {
                action: 'snn_dismiss_fatal_error_notice',
                nonce: '" . esc_js(wp_create_nonce('snn_dismiss_fatal_notice_nonce')) . "' // Specific nonce for this action
            },
            success: function(response) {
                if (response.success) {
                    \$button.closest('.notice-error.snn-fatal-error-notice').fadeOut(); // Fade out the specific notice
                } else {
                    alert('Could not dismiss notice: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
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
 * Add custom CSS to admin head for the snippets page.
 */
function snn_custom_codes_snippets_admin_styles() {
    // Simplified check: If the 'page' GET parameter is 'snn-custom-codes-snippets'
    // and the current user can manage options (basic security check for admin pages).
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'snn-custom-codes-snippets' || ! current_user_can('manage_options') ) {
        return; // Exit if not the correct page or insufficient permissions
    }

    // Output the styles
    echo '<style>
        /* General styling for the settings page */
        h3{margin-top:10px} /* Reset margin for h3 if needed */
        th,td{padding:0 !important} /* Reset padding for th,td if needed by theme */
        .CodeMirror { min-height: 620px !important; border: 1px solid #ddd; }
        .snn-snippet-nav-tab-wrapper { margin-bottom: 15px; }
        .snn-snippet-description { margin-bottom: 10px; font-style: italic; color: #555; }
        .form-table th { width: 200px; } /* Consistent width for settings labels */

        /* Flex layout for editor and revisions panel */
        .snn-editor-revision-wrapper { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 5px; }
        .snn-editor-area { flex: 3; min-width: 380px; position: relative; } /* Editor takes more space */
        .snn-revisions-panel {
            flex: 1; /* Revisions panel takes less space */
            min-width: 300px; /* Minimum width before wrapping */
            max-width: 360px; /* Maximum width */
            border-left: 1px solid #ccd0d4; /* Separator line */
            padding-left: 20px;
        }
        .snn-revisions-panel-inner {
            max-height: 680px; /* Max height for scrollbar */
            overflow-y: auto;  /* Enable vertical scrollbar if content exceeds max-height */
            padding-right: 10px; /* Space for scrollbar */
        }
        .snn-revisions-list { list-style: none; margin: 0; padding: 0; }
        .snn-revisions-list li {
            margin-bottom: 0px; /* Reduced from 10px */
            padding-bottom: 5px; /* Reduced from 10px */
            border-bottom: 1px solid #eee;
        }
        .snn-revisions-list li:last-child { border-bottom: none; }
        .snn-revisions-list .revision-info { display: block; font-size: 0.9em; color: #555; margin-bottom: 8px; }
        .snn-revisions-list .revision-actions button,
        .snn-revisions-list .revision-actions .snn-view-comparison-link {
            margin-right: 5px;
            margin-top: 5px; /* Added for spacing */
            vertical-align: middle;
        }
        .snn-revisions-list .revision-actions .snn-view-comparison-link .dashicons {
            font-size: 14px; /* Dashicon size */
            text-decoration: none;
            vertical-align: text-bottom; /* Align with text */
            position: relative; /* For fine-tuning alignment */
            top: 5px; /* Adjusted for better alignment with buttons */
        }
        .snn-revisions-panel h4 { margin-top: 0; font-size: 1.1em; }
        .snn-php-execution-warning { border-left-width: 4px; margin-top: 15px; margin-bottom: 15px; }
        .snn-clear-revisions-button { margin-top: 10px; }
        .snn-manage-revisions-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        
        /* Error Logs Table Styling */
        .snn-error-logs-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .snn-error-logs-table th, .snn-error-logs-table td { border: 1px solid #ddd; padding: 8px !important; text-align: left; vertical-align: top; }
        .snn-error-logs-table th { background-color: #f9f9f9; }
        .snn-error-logs-table td pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; font-size: 12px; }
        .snn-error-logs-table .snn-log-message { max-width: 400px; overflow-wrap: break-word; }
        .snn-error-logs-table .snn-log-actions { width: 100px; }

        /* Fatal Error Notice Styling (admin notice) */
        .snn-fatal-error-notice strong { color: #dc3232; }
        .snn-fatal-error-notice code { background: #f9f9f9; border: 1px solid #ddd; padding: 2px 4px; font-size: 0.9em; display: block; white-space: pre-wrap; word-break: break-all;}

        /* Styles for fatal error indication on the settings row itself */
        .snn-setting-row-error {
            background-color: #fbeaea !important; /* Light red background */
            border-left: 4px solid #dc3232 !important; /* Red left border */
        }
        .snn-setting-row-error th,
        .snn-setting-row-error td {
            padding-top: 12px !important; 
            padding-bottom: 12px !important;
        }
        .snn-setting-row-error td .description { /* Style for the error message text below checkbox */
            color: #c00 !important; 
            font-weight: bold !important;
            margin-top: 5px !important;
        }
        .snn-setting-row-error label { /* Ensure label text is clearly visible */
             color: #333; 
        }
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
    if ( ! is_array( $logs ) ) { // Ensure logs is an array
        $logs = array();
    }

    $log_entry = array(
        'timestamp'    => current_time( 'mysql' ), // WordPress current time in MySQL format
        'type'         => sanitize_text_field( $type ),
        'message'      => wp_strip_all_tags( $message ), // Basic sanitization for display
        'snippet_slug' => sanitize_text_field( $snippet_slug ),
        'file'         => sanitize_text_field( $file ),
        'line'         => absint( $line ),
    );

    // Add new log entry to the beginning of the array
    array_unshift( $logs, $log_entry );

    // Keep only the most recent N entries (defined by SNN_CUSTOM_CODES_MAX_LOG_ENTRIES)
    if ( count( $logs ) > SNN_CUSTOM_CODES_MAX_LOG_ENTRIES ) {
        $logs = array_slice( $logs, 0, SNN_CUSTOM_CODES_MAX_LOG_ENTRIES );
    }

    update_option( SNN_CUSTOM_CODES_LOG_OPTION, $logs );
}


/**
 * Helper function to get a specific code snippet's content from its CPT.
 */
function snn_get_code_snippet_content( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug, // Post slug
        'posts_per_page'   => 1,
        'post_status'      => 'private', // Snippets are stored as private posts
        'suppress_filters' => true, // For consistency, bypass filters
    );
    $snippet_posts = get_posts( $args );
    if ( ! empty( $snippet_posts ) && isset( $snippet_posts[0]->post_content ) ) {
        return $snippet_posts[0]->post_content;
    }
    return ''; // Return empty string if not found
}

/**
 * Helper function to get a specific code snippet's CPT ID by its slug.
 */
function snn_get_code_snippet_id( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug,
        'posts_per_page'   => 1,
        'post_status'      => 'private',
        'fields'           => 'ids', // Only retrieve post IDs
        'suppress_filters' => true,
    );
    $snippet_ids = get_posts( $args );
    return ! empty( $snippet_ids ) ? $snippet_ids[0] : 0; // Return ID or 0 if not found
}

/**
 * Executes a PHP code snippet with output buffering and error handling.
 * Catches ParseError and Throwable for broader error capturing from eval().
 */
function snn_execute_php_snippet( $code_to_execute, $snippet_location_slug ) {
    if ( empty( trim( $code_to_execute ) ) ) {
        return ''; // Do nothing if code is empty
    }

    $error_occurred = false;
    // $error_details_for_log structure is prepared but filled by handlers/catch blocks.
    // $error_message_for_admin_comment is no longer used for direct output to avoid breaking pages.

    // Custom error handler for non-fatal errors (Warnings, Notices, etc.)
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_occurred, $snippet_location_slug) {
        // Ignore E_DEPRECATED and E_STRICT if WP_DEBUG is not enabled
        if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
            if ( $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED || $errno === E_STRICT ) {
                return true; // Don't log or treat as an error unless WP_DEBUG is on
            }
        }

        $error_occurred = true; // Mark that an error happened
        $error_type_str = 'PHP Error'; // Default type
        switch ($errno) { // Determine error type string
            case E_WARNING: case E_USER_WARNING: $error_type_str = 'PHP Warning'; break;
            case E_NOTICE: case E_USER_NOTICE: $error_type_str = 'PHP Notice'; break;
            case E_DEPRECATED: case E_USER_DEPRECATED: $error_type_str = 'PHP Deprecated'; break;
            case E_STRICT: $error_type_str = 'PHP Strict'; break;
        }
        
        // Log this non-fatal error. File/line from eval'd code context can be tricky.
        // $errfile and $errline reported by set_error_handler for eval'd code might point to the eval() call itself.
        // It's often more useful to log 'eval()\'d code' as the file and rely on the message.
        snn_log_error_event($error_type_str, $errstr, $snippet_location_slug, 'eval()\'d code (runtime)', $errline);
        return true; // Prevent default PHP error handler from running
    });

    ob_start(); // Start output buffering

    try {
        // The "? >" before $code_to_execute is crucial. It ensures that if the code
        // doesn't start with <?php, it's treated as HTML. If it does, PHP takes over.
        eval( "?>" . $code_to_execute ); 
    } catch (ParseError $e) { // Specifically catch ParseError (syntax errors)
        $error_occurred = true;
        snn_log_error_event('PHP Parse Error', $e->getMessage(), $snippet_location_slug, 'eval()\'d code (parse)', $e->getLine());
    } catch (Throwable $e) { // Catch other Throwables (like Error, Exception)
        $error_occurred = true;
        snn_log_error_event('PHP Exception/Error (' . get_class($e) . ')', $e->getMessage(), $snippet_location_slug, 'eval()\'d code (throwable)', $e->getLine());
    }

    $output_from_snippet = ob_get_clean(); // Get buffered output
    restore_error_handler(); // Restore previous error handler

    if ( $error_occurred ) {
        // For non-fatal errors caught here, we log them.
        // We return an empty string or a comment to avoid breaking the page layout.
        // Admins should check the error logs tab for details.
        // Fatal errors are handled by the shutdown handler.
        return "\n\n"; // More descriptive comment
    }

    return $output_from_snippet; // Return the output from the snippet
}

/**
 * Display the admin page for managing custom code snippets.
 */
function snn_custom_codes_snippets_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'snn-custom-codes' ) );
    }

    // Definitions for each snippet location
    $snippet_defs = array(
        'frontend' => array(
            'title'       => __( 'Frontend Head PHP/HTML', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-frontend-head', // Used as post_name and for retrieval
            'field_id'    => 'snn_frontend_code', // HTML ID for textarea
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
            'title'       => __( 'Direct PHP (functions.php style)', 'snn-custom-codes' ),
            'slug'        => 'snn-snippet-functions-php',
            'field_id'    => 'snn_functions_code',
            'description' => __( 'PHP executed immediately when this code feature loads (no hook) – similar to putting code in <code>functions.php</code>. Use for hooks, filters, and functions. Avoid direct output here unless intended. Errors can break your site.', 'snn-custom-codes' ),
        ),
    );

    $settings_saved_message_type = 'updated'; // Default message type for settings errors

    // Handle form submissions (Save All, Clear Logs, Clear Revisions, Restore Revision)
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['snn_codes_snippets_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snn_codes_snippets_nonce'] ) ), 'snn_save_codes_snippets' ) ) {
        
        // Handle Clear Error Logs Action
        if ( isset( $_POST['snn_clear_error_logs_button'] ) ) {
            check_admin_referer( 'snn_clear_error_logs_action', 'snn_clear_error_logs_nonce' );
            update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() ); // Clear logs
            add_settings_error('snn-custom-codes', 'logs_cleared', __('All error logs have been cleared.', 'snn-custom-codes'), 'updated');
            $_GET['tab'] = 'error_logs'; // Stay on the logs tab
        }
        // Handle Clear Revisions Action for a specific snippet
        elseif ( isset( $_POST['snn_clear_revisions_button'] ) && ! empty( $_POST['snn_clear_revisions_button'] ) ) {
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
                $_GET['tab'] = $snippet_key_to_clear; // Stay on the current snippet tab
            }
        } 
        // Handle Restore Revision Action for a specific snippet
        elseif ( isset( $_POST['snn_restore_submit_button'] ) && ! empty( $_POST['snn_restore_submit_button'] ) ) {
            // Value of button is "restore_REVISIONID_SNIPPETKEY"
            $restore_action = sanitize_text_field( wp_unslash( $_POST['snn_restore_submit_button'] ) );
            $parts = explode( '_', $restore_action ); 
            if ( count($parts) === 3 && 'restore' === $parts[0] ) {
                $revision_id = absint( $parts[1] );
                $snippet_key_for_restore = sanitize_key( $parts[2] );

                if ( $revision_id && isset( $snippet_defs[ $snippet_key_for_restore ] ) ) {
                    $target_snippet_def = $snippet_defs[ $snippet_key_for_restore ];
                    $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );
                    $revision = wp_get_post_revision( $revision_id );

                    // Check permissions and validity
                    if ( $target_post_id && $revision && $revision->post_parent == $target_post_id && current_user_can( 'edit_post', $target_post_id ) ) {
                        wp_restore_post_revision( $revision_id ); // Restore the revision to be the main post content
                        // The actual saving of this restored content happens if 'Save All' is clicked.
                        // For immediate effect, we update $_POST so the textarea shows the restored content.
                        $_POST[ $target_snippet_def['field_id'] ] = $revision->post_content; 
                        $_GET['tab'] = $snippet_key_for_restore; // Stay on the current tab
                        add_settings_error('snn-custom-codes', 'revision_restored', sprintf(__('Revision for "%s" has been loaded into the editor. Click "Save All Snippets & Settings" to make it live.', 'snn-custom-codes'), esc_html($target_snippet_def['title'])), 'updated');
                    } else {
                        add_settings_error('snn-custom-codes', 'restore_failed', __('Failed to restore revision. Invalid ID or permissions.', 'snn-custom-codes'), 'error');
                        $settings_saved_message_type = 'error';
                         $_GET['tab'] = $snippet_key_for_restore; 
                    }
                }
            }
        }

        // Save global enable/disable setting and all snippet contents
        // This runs if "Save All" is clicked, or after a successful restore (to save the restored content if user then clicks save)
        if ( isset($_POST['snn_save_all_settings_button']) ) {
            $is_enabled = isset( $_POST['snn_codes_snippets_enabled'] ) ? 1 : 0;
            update_option( 'snn_codes_snippets_enabled', $is_enabled );

            // If snippets are being re-enabled by the user, clear any existing fatal error notice transient
            if ($is_enabled) {
                delete_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT);
            }

            $all_snippets_processed_successfully = true;
            foreach ( $snippet_defs as $key => $def ) {
                if ( isset( $_POST[ $def['field_id'] ] ) ) { 
                    $new_code_content = wp_unslash( $_POST[ $def['field_id'] ] );
                    $snippet_post_id = snn_get_code_snippet_id( $def['slug'] );
                    $post_data = array(
                        'post_title'   => $def['title'],
                        'post_content' => $new_code_content,
                        'post_status'  => 'private', // Snippets are always private
                        'post_type'    => 'snn_code_snippet',
                        'post_name'    => $def['slug'], 
                    );
                    if ( $snippet_post_id ) { // Existing snippet, update it
                        $post_data['ID'] = $snippet_post_id;
                        $updated_id = wp_update_post( $post_data, true ); // true to return WP_Error on failure
                        if ( is_wp_error( $updated_id ) ) {
                            add_settings_error('snn-custom-codes', 'update_failed_' . $key, sprintf(__('Failed to update snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($updated_id->get_error_message())), 'error');
                            $all_snippets_processed_successfully = false;
                        }
                    } else { // New snippet, insert it
                        $inserted_id = wp_insert_post( $post_data, true ); // true to return WP_Error on failure
                        if ( is_wp_error( $inserted_id ) ) {
                            add_settings_error('snn-custom-codes', 'insert_failed_' . $key, sprintf(__('Failed to create snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($inserted_id->get_error_message())), 'error');
                            $all_snippets_processed_successfully = false;
                        }
                    }
                }
            }

            // Determine overall success message
            $notices = get_settings_errors('snn-custom-codes');
            $has_specific_action_message = false; // Check if a more specific message (like restore/clear) was already added
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

    // Get current state for display
    $enabled_globally = get_option( 'snn_codes_snippets_enabled', 0 );
    $current_tab_key = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'frontend'; // Default to 'frontend' tab
    if ( ! array_key_exists( $current_tab_key, $snippet_defs ) && $current_tab_key !== 'error_logs' ) {
        $current_tab_key = 'frontend'; // Fallback to 'frontend' if tab is invalid
    }

    // Fetch current code for each snippet for display
    // If a restore just happened, the $_POST data for the current tab might be fresher than the saved post content
    $codes_for_display = array();
    foreach ( $snippet_defs as $key => $def ) {
        if ($key === $current_tab_key && isset($_POST[$def['field_id']]) && isset($_POST['snn_restore_submit_button'])) {
            // If restoring the current tab's snippet, use the content from $_POST (which holds the revision's content)
            $codes_for_display[ $key ] = wp_unslash($_POST[$def['field_id']]);
        } else {
            $codes_for_display[ $key ] = snn_get_code_snippet_content( $def['slug'] );
        }
    }

    settings_errors('snn-custom-codes'); // Display any admin notices queued for 'snn-custom-codes'
    ?>
    <div class="wrap">
        <h1> <?php esc_html_e( 'Manage Code Snippets', 'snn-custom-codes' ); ?> </h1>
        
        <form method="post" action="admin.php?page=snn-custom-codes-snippets&tab=<?php echo esc_attr($current_tab_key); ?>">
            <?php wp_nonce_field( 'snn_save_codes_snippets', 'snn_codes_snippets_nonce' ); // Nonce for the whole form ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <?php
                    // Determine if a fatal error has occurred and disabled snippets
                    $fatal_error_occurred = (bool) get_transient(SNN_FATAL_ERROR_NOTICE_TRANSIENT);
                    // Apply error class to the row if snippets are disabled AND it was due to a fatal error
                    $row_class = ( ! $enabled_globally && $fatal_error_occurred ) ? 'snn-setting-row-error' : '';
                    ?>
                    <tr class="<?php echo esc_attr( $row_class ); ?>">
                        <th scope="row"><?php esc_html_e( 'Global Snippet Execution', 'snn-custom-codes' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( 'Global Snippet Execution', 'snn-custom-codes' ); ?></span></legend>
                                <label for="snn_codes_snippets_enabled">
                                    <input type="checkbox" id="snn_codes_snippets_enabled" name="snn_codes_snippets_enabled" value="1" 
                                        <?php checked( 1, $enabled_globally ); ?>
                                        <?php /* REMOVED: disabled( ! $enabled_globally && $fatal_error_occurred ); */ ?>
                                    >
                                    <?php esc_html_e( 'Enable execution of all custom PHP snippets', 'snn-custom-codes' ); ?>
                                </label>
                                <?php if ( ! $enabled_globally && $fatal_error_occurred ) : // Show message if disabled due to fatal error ?>
                                    <p class="description">
                                        <?php esc_html_e( 'Execution was automatically disabled due to a fatal error. Please check the Error Logs tab, resolve the issue, then re-check this box and save settings to re-enable.', 'snn-custom-codes' ); ?>
                                    </p>
                                <?php elseif ( ! $enabled_globally ) : // Show general message if disabled for other reasons ?>
                                     <p class="description">
                                        <?php esc_html_e( 'Snippet execution is currently disabled. Check this box and save settings to enable.', 'snn-custom-codes' ); ?>
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
                // Add Error Logs tab link
                $logs_tab_active_class = ( $current_tab_key === 'error_logs' ) ? 'nav-tab-active' : '';
                $logs_tab_url = admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' );
                echo '<a href="' . esc_url( $logs_tab_url ) . '" class="nav-tab ' . esc_attr( $logs_tab_active_class ) . '">' . esc_html__( 'Error Logs', 'snn-custom-codes' ) . '</a>';
                ?>
            </h2>

            <?php if ( $current_tab_key === 'error_logs' ) : // Display Error Logs Tab Content ?>
            <div id="snn-tab-content-error-logs" class="snn-tab-content">
                <h3><?php esc_html_e( 'Snippet Execution Error Logs', 'snn-custom-codes' ); ?></h3>
                <p><?php printf(esc_html__( 'This log shows the last %d errors recorded from snippet executions. If a fatal error occurs, snippet execution will be globally disabled.', 'snn-custom-codes' ), SNN_CUSTOM_CODES_MAX_LOG_ENTRIES); ?></p>
                <?php
                $error_logs = get_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
                if ( ! is_array( $error_logs ) ) $error_logs = array(); 

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
                                <td><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $log_entry['timestamp'] ) ) ); ?></td>
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
            <?php elseif ( isset( $snippet_defs[ $current_tab_key ] ) ) : // Display Snippet Editor Tab Content
                $active_snippet_def = $snippet_defs[ $current_tab_key ];
                $current_code_value = isset($codes_for_display[ $current_tab_key ]) ? $codes_for_display[ $current_tab_key ] : ''; 
                
                // If form was submitted (e.g. after restore), and it's the current tab, use the POSTed value to show unsaved changes
                if( $current_tab_key === sanitize_key( (isset($_POST['snn_snippet_key_to_clear']) ? $_POST['snn_snippet_key_to_clear'] : '') ) || // after clear revisions
                    ( isset($_POST['snn_restore_submit_button']) && explode('_', sanitize_text_field(wp_unslash($_POST['snn_restore_submit_button'])))[2] === $current_tab_key ) // after restore
                ){
                    if(isset($_POST[$active_snippet_def['field_id']])){
                         $current_code_value = wp_unslash($_POST[$active_snippet_def['field_id']]);
                    }
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
                                    // Nonce for revision comparison link
                                    $comparison_link_nonce = wp_create_nonce( 'view-revision_' . $revision->ID );
                                    $comparison_link       = admin_url( 'revision.php?revision=' . $revision->ID . '&nonce=' . $comparison_link_nonce );
                                    $time_diff             = human_time_diff( strtotime( $revision->post_date_gmt ), current_time( 'timestamp', true ) );
                                    $revision_date_title   = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $revision->post_date ) );
                                    $revision_info         = sprintf( '%s by %s (%s %s)', $revision_date_title, $revision_author_name, $time_diff, __('ago', 'snn-custom-codes') );
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
                                                style="display:none;"> <?php esc_html_e( 'Load Revision & Save', 'snn-custom-codes' ); ?>
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
 * Initialize snippet execution hooks based on saved content and global setting.
 */
function snn_custom_codes_snippets_init_execution() {
    // Only proceed if snippets are globally enabled
    if ( ! get_option( 'snn_codes_snippets_enabled', 0 ) ) {
        return;
    }

    // Execute "Direct PHP (functions.php style)" snippet
    $direct_code = snn_get_code_snippet_content( 'snn-snippet-functions-php' );
    if ( ! empty( trim( $direct_code ) ) ) {
        // Output from direct code is echoed. Errors are handled by snn_execute_php_snippet.
        echo snn_execute_php_snippet( $direct_code, 'snn-snippet-functions-php' );
    }

    // Add hooks for other snippets only if they have content
    if ( ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-frontend-head' ) ) ) ) {
        add_action( 'wp_head', 'snn_custom_codes_snippets_frontend_output', 1 ); // High priority for head
    }
    if ( ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-footer' ) ) ) ) {
        add_action( 'wp_footer', 'snn_custom_codes_snippets_footer_output', 9999 ); // Low priority for footer
    }
    if ( is_admin() && ! empty( trim( snn_get_code_snippet_content( 'snn-snippet-admin-head' ) ) ) ) {
        add_action( 'admin_head', 'snn_custom_codes_snippets_admin_output', 1 ); // High priority for admin head
    }
}
add_action( 'init', 'snn_custom_codes_snippets_init_execution', 99 ); // Run late in init

/** Output callback for frontend head snippet */
function snn_custom_codes_snippets_frontend_output() {
    $code = snn_get_code_snippet_content( 'snn-snippet-frontend-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-frontend-head' );
}
/** Output callback for frontend footer snippet */
function snn_custom_codes_snippets_footer_output()    {
    $code = snn_get_code_snippet_content( 'snn-snippet-footer' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-footer' );
}
/** Output callback for admin head snippet */
function snn_custom_codes_snippets_admin_output()     {
    $code = snn_get_code_snippet_content( 'snn-snippet-admin-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-admin-head' );
}

/**
 * AJAX handler for fetching revision content to preview in editor.
 */
add_action( 'wp_ajax_snn_get_revision_content', 'snn_ajax_get_revision_content_callback' );
function snn_ajax_get_revision_content_callback() {
    // Verify nonce and user capabilities
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_preview_revision_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) { 
        wp_send_json_error( array( 'message' => __( 'Permission denied to manage options.', 'snn-custom-codes' ) ), 403 );
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
    // Also check if user can edit the parent post of the revision
    if ( ! current_user_can( 'edit_post', $revision->post_parent ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied for accessing this revision content.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    wp_send_json_success( array( 'content' => $revision->post_content, 'title'    => wp_post_revision_title_expanded( $revision ) ) );
}

/**
 * AJAX handler for dismissing the fatal error admin notice.
 */
add_action( 'wp_ajax_snn_dismiss_fatal_error_notice', 'snn_ajax_dismiss_fatal_error_notice_callback' );
function snn_ajax_dismiss_fatal_error_notice_callback() {
    // Verify nonce and user capabilities
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snn_dismiss_fatal_notice_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'snn-custom-codes' ) ), 403 );
        return;
    }
    // Delete the transient that triggers the notice
    delete_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );
    wp_send_json_success();
}


/**
 * Register fatal error shutdown handler.
 * This needs to be registered early to catch fatal errors from snippets.
 */
function snn_register_fatal_error_handler() {
    register_shutdown_function( 'snn_fatal_error_shutdown_handler' );
}
add_action( 'init', 'snn_register_fatal_error_handler', 1 ); // Register very early

/**
 * Fatal error shutdown handler.
 * Checks for fatal errors, logs them, and disables snippets if the error seems to originate from them.
 */
function snn_fatal_error_shutdown_handler() {
    $error = error_get_last(); // Get the last error

    // Check if it's a fatal error type we should handle
    if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ] ) ) {
        
        $error_source_is_snippet = false;
        // Normalize paths for reliable comparison
        // __FILE__ will point to the file this code is in (e.g., functions.php of child theme)
        $current_file_path_normalized = wp_normalize_path(__FILE__); 
        $error_file_normalized = isset($error['file']) ? wp_normalize_path($error['file']) : '';

        // Heuristic 1: Error message contains "eval()'d code" - this is a strong indicator
        if (isset($error['message']) && strpos( $error['message'], "eval()'d code" ) !== false) {
             $error_source_is_snippet = true;
        }
        // Heuristic 2: Error file is this file (where eval is called)
        // AND the message contains 'eval()' or the file path reported by PHP contains "eval()'d code"
        // This helps catch errors where PHP reports the error in the file calling eval().
        elseif ( !empty($error_file_normalized) && $error_file_normalized === $current_file_path_normalized &&
                 ( (isset($error['message']) && strpos( $error['message'], 'eval()' ) !== false) || strpos( $error_file_normalized, "eval()'d code" ) !== false )
        ) {
            $error_source_is_snippet = true;
        }
        // Heuristic 3: Check if the error message involves one of our snippet execution wrapper functions
        // This can help catch errors where the file/line is less clear but the context points to our execution.
        if (!$error_source_is_snippet && isset($error['message'])) {
            if (preg_match('/call_user_func_array\(\s*["\'](snn_custom_codes_snippets_(frontend_output|footer_output|admin_output))["\']/', $error['message'])) {
                $error_source_is_snippet = true;
            }
        }

        // Only act if snippets are currently enabled AND we think the error is from a snippet
        if ( $error_source_is_snippet && get_option( 'snn_codes_snippets_enabled', 0 ) ) {
            
            $snippet_slug_guess = 'unknown_or_direct_fatal'; // Default guess for slug

            // Try to guess the snippet slug based on context from the error message or call stack
            $message_lower = strtolower($error['message']);
            if (strpos($message_lower, 'snn_custom_codes_snippets_frontend_output') !== false) $snippet_slug_guess = 'snn-snippet-frontend-head';
            elseif (strpos($message_lower, 'snn_custom_codes_snippets_footer_output') !== false) $snippet_slug_guess = 'snn-snippet-footer';
            elseif (strpos($message_lower, 'snn_custom_codes_snippets_admin_output') !== false) $snippet_slug_guess = 'snn-snippet-admin-head';
            // If error is in this file, involves eval, and isn't one of the hooked outputs, it's likely the direct PHP snippet.
            elseif (strpos($error_file_normalized, $current_file_path_normalized) !== false && 
                    strpos($message_lower, 'eval') !== false && 
                    !preg_match('/snn_custom_codes_snippets_(frontend|footer|admin)_output/', $message_lower)) {
                $snippet_slug_guess = 'snn-snippet-functions-php';
            }

            // Log the fatal error
            snn_log_error_event(
                'PHP Fatal Error',
                $error['message'],
                $snippet_slug_guess, 
                $error['file'], 
                $error['line']  
            );

            // CRITICAL: Disable global snippet execution as a safety measure
            update_option( 'snn_codes_snippets_enabled', 0 );

            // Set a transient to display an admin notice about the fatal error
            $notice_data = [
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
                'type'    => snn_get_php_error_type_string($error['type']) // Get user-friendly error type
            ];
            set_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT, $notice_data, DAY_IN_SECONDS ); // Notice persists for 1 day or until dismissed
        }
    }
}

/**
 * Helper function to convert PHP error constant to a user-friendly string.
 */
function snn_get_php_error_type_string($type) {
    switch($type) {
        case E_ERROR: return 'E_ERROR (Fatal run-time error)';
        case E_WARNING: return 'E_WARNING (Run-time warning)';
        case E_PARSE: return 'E_PARSE (Compile-time parse error)';
        case E_NOTICE: return 'E_NOTICE (Run-time notice)';
        case E_CORE_ERROR: return 'E_CORE_ERROR (Fatal error during PHP startup)';
        case E_CORE_WARNING: return 'E_CORE_WARNING (Warning during PHP startup)';
        case E_COMPILE_ERROR: return 'E_COMPILE_ERROR (Fatal compile-time error)';
        case E_COMPILE_WARNING: return 'E_COMPILE_WARNING (Compile-time warning)';
        case E_USER_ERROR: return 'E_USER_ERROR (User-generated error message)';
        case E_USER_WARNING: return 'E_USER_WARNING (User-generated warning message)';
        case E_USER_NOTICE: return 'E_USER_NOTICE (User-generated notice message)';
        case E_STRICT: return 'E_STRICT (Run-time notice for deprecated code or bad practices)';
        case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR (Catchable fatal error)';
        case E_DEPRECATED: return 'E_DEPRECATED (Run-time notice for code that will not work in future PHP versions)';
        case E_USER_DEPRECATED: return 'E_USER_DEPRECATED (User-generated warning for deprecated code)';
        default: return "Unknown error type ($type)";
    }
}


/**
 * Display an admin notice if a fatal error occurred and snippets were disabled.
 */
function snn_display_fatal_error_admin_notice() {
    // Only show to users who can manage options
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $fatal_error_details = get_transient( SNN_FATAL_ERROR_NOTICE_TRANSIENT );

    // If the transient exists and contains our expected array structure
    if ( $fatal_error_details && is_array($fatal_error_details) ) {
        ?>
        <div class="notice notice-error is-dismissible snn-fatal-error-notice">
            <p><strong><?php esc_html_e( 'CRITICAL: Custom Code Snippets Disabled!', 'snn-custom-codes' ); ?></strong></p>
            <p>
                <?php esc_html_e( 'The "SNN Custom Codes" feature automatically disabled all snippet executions due to a fatal PHP error. This is a safety measure to prevent your site from breaking further.', 'snn-custom-codes' ); ?>
            </p>
            <p><strong><?php esc_html_e( 'Error Details:', 'snn-custom-codes' ); ?></strong></p>
            <p>
                <code>
                    <?php 
                    // Ensure all parts of fatal_error_details are set before using them to prevent notices
                    $type = isset($fatal_error_details['type']) ? $fatal_error_details['type'] : 'Unknown Type';
                    $message = isset($fatal_error_details['message']) ? $fatal_error_details['message'] : 'No message provided.';
                    $file = isset($fatal_error_details['file']) ? $fatal_error_details['file'] : 'Unknown file.';
                    $line = isset($fatal_error_details['line']) ? $fatal_error_details['line'] : 'Unknown line.';
                    echo esc_html( sprintf( "Type: %s\nMessage: %s\nFile: %s\nLine: %d", $type, $message, $file, $line ) ); 
                    ?>
                </code>
            </p>
            <p>
                <?php
                printf(
                    wp_kses_post( __( 'Please review the <a href="%s">Error Logs tab</a> for more details, identify and fix the problematic snippet. Once fixed, you can re-enable "Global Snippet Execution" on the custom code settings page and save.', 'snn-custom-codes' ) ),
                    esc_url( admin_url( 'admin.php?page=snn-custom-codes-snippets&tab=error_logs' ) )
                );
                ?>
            </p>
             <p><button type="button" class="button snn-dismiss-fatal-notice"><?php esc_html_e('Dismiss This Notice', 'snn-custom-codes'); ?></button></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'snn_display_fatal_error_admin_notice' );


/** * Activation hook: Register CPT, flush rewrite rules, set default options.
 * This function would typically run when a theme (containing this code) is activated.
 */
function snn_custom_codes_feature_activate() {
    snn_custom_codes_snippets_register_cpt(); // Ensure CPT is registered
    flush_rewrite_rules(); // Important after CPT registration

    // Initialize the global enabled option if it doesn't exist, default to 0 (disabled) for safety
    if ( false === get_option( 'snn_codes_snippets_enabled', false ) ) {
        update_option( 'snn_codes_snippets_enabled', 0 ); 
    }
    // Initialize log option as an empty array if it doesn't exist
    if ( false === get_option( SNN_CUSTOM_CODES_LOG_OPTION, false ) ) {
        update_option( SNN_CUSTOM_CODES_LOG_OPTION, array() );
    }
}

function snn_custom_codes_feature_deactivate() {
    flush_rewrite_rules();
}

?>
