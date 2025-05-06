<?php


// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Custom Post Type for Code Snippets.
 * This CPT will store the individual code snippets.
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
        'show_ui'            => false, // Not managed via standard CPT UI
        'show_in_menu'       => false, // Added via custom submenu page
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'revisions' ), // IMPORTANT: 'revisions' must be supported
        'has_archive'        => false,
        'show_in_rest'       => false, // Not using REST API for this
    );
    register_post_type( 'snn_code_snippet', $args );
}
add_action( 'init', 'snn_custom_codes_snippets_register_cpt' );

/**
 * Add the submenu page for managing snippets under "snn-settings".
 * IMPORTANT: The parent menu 'snn-settings' must already exist.
 */
function snn_custom_codes_snippets_add_submenu() {
    add_submenu_page(
        'snn-settings', // Parent slug (e.g., your main plugin settings page slug)
        __( 'PHP Code Snippets', 'snn-custom-codes' ), // Page title
        __( 'PHP Code Snippets', 'snn-custom-codes' ), // Menu title
        'manage_options', // Capability required
        'snn-custom-codes-snippets', // Menu slug (unique)
        'snn_custom_codes_snippets_page' // Function to display the page
    );
}
add_action( 'admin_menu', 'snn_custom_codes_snippets_add_submenu', 10 );

/**
 * Enqueue CodeMirror assets and add inline JavaScript for revisions.
 *
 * @param string $hook The current admin page hook.
 */
function snn_custom_codes_snippets_enqueue_assets( $hook ) {
    $expected_hook = 'snn-settings_page_snn-custom-codes-snippets';

    // Let's try to get the screen object and check its base and ID.
    $current_screen = get_current_screen();
    if ( ! $current_screen || $current_screen->id !== $expected_hook && $current_screen->id !== 'toplevel_page_snn-settings_page_snn-custom-codes-snippets' && $current_screen->base !== 'snn-settings_page_snn-custom-codes-snippets') {
         // Fallback for common pattern if $current_screen->id is not an exact match (e.g. if parent is a top-level menu)
        if (strpos($hook, 'snn-custom-codes-snippets') === false) {
            return;
        }
    }


    // Enqueue CodeMirror for PHP syntax highlighting
    $cm_settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
    if ( false === $cm_settings ) {
        // Fallback if CodeMirror can't be enqueued (e.g., if user disabled it)
        wp_enqueue_script('jquery'); // Ensure jQuery is available
        // Optionally, add a notice or log an error here
        return;
    }

    wp_enqueue_script( 'wp-theme-plugin-editor' ); // Depends on CodeMirror
    wp_enqueue_style( 'wp-codemirror' );
    wp_enqueue_style( 'dashicons' ); // For icons in the revisions list

    // Inline script for initializing CodeMirror on main textareas
    wp_add_inline_script(
        'wp-theme-plugin-editor', // Hook into this script as it's already enqueued and depends on codemirror
        sprintf(
            'jQuery( function( $ ) {
                var editorSettings = %s;
                $( "#snn_frontend_code, #snn_footer_code, #snn_admin_code" ).each( function() {
                    if (wp && wp.codeEditor) {
                        wp.codeEditor.initialize( this, editorSettings );
                    } else {
                        // Fallback styling if wp.codeEditor is not available for some reason
                        $(this).css({"font-family": "monospace", "font-size": "13px", "border": "1px solid #ddd", "width": "100%%", "padding": "10px"});
                    }
                });
            } );',
            wp_json_encode( $cm_settings )
        )
    );

    // Inline JavaScript for AJAX revision preview, restore confirmation, and clear revisions confirmation
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
        confirm_clear_revisions_text: '" . esc_js(__('Are you absolutely sure you want to delete all revisions for this snippet? This action cannot be undone.', 'snn-custom-codes')) . "'
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
            // Fallback for preview if CodeMirror instance not found
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
                    cmInstance.refresh(); // Good practice after setting value
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
});
";
    wp_add_inline_script( 'wp-theme-plugin-editor', $js_for_revisions );
}
add_action( 'admin_enqueue_scripts', 'snn_custom_codes_snippets_enqueue_assets' );

/**
 * Add custom CSS to admin head for styling the plugin page.
 */
function snn_custom_codes_snippets_admin_styles() {
    $screen = get_current_screen();
    // Adjust this check based on the final hook of your settings page
    $expected_hook_id_1 = 'snn-settings_page_snn-custom-codes-snippets';
    $expected_hook_id_2 = 'toplevel_page_snn-settings_page_snn-custom-codes-snippets'; // If snn-settings is a top-level menu

    if ( $screen && ($screen->id === $expected_hook_id_1 || $screen->id === $expected_hook_id_2) ) {
        echo '<style>
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
        </style>';
    }
}
add_action( 'admin_head', 'snn_custom_codes_snippets_admin_styles' );

/**
 * Helper function to get a specific code snippet's content.
 *
 * @param string $slug The slug of the code snippet post.
 * @return string The content of the snippet, or empty string if not found.
 */
function snn_get_code_snippet_content( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug, // 'name' is for post_name (slug)
        'posts_per_page'   => 1,
        'post_status'      => 'private', // Snippets are stored as private posts
        'suppress_filters' => true, // For consistency
    );
    $snippet_posts = get_posts( $args );
    return ( ! empty( $snippet_posts ) && isset( $snippet_posts[0]->post_content ) ) ? $snippet_posts[0]->post_content : '';
}

/**
 * Helper function to get a specific code snippet's CPT ID.
 *
 * @param string $slug The slug of the code snippet post.
 * @return int The ID of the snippet post, or 0 if not found.
 */
function snn_get_code_snippet_id( $slug ) {
    $args = array(
        'post_type'        => 'snn_code_snippet',
        'name'             => $slug,
        'posts_per_page'   => 1,
        'post_status'      => 'private',
        'fields'           => 'ids', // Only fetch the ID
        'suppress_filters' => true,
    );
    $snippet_ids = get_posts( $args );
    return ! empty( $snippet_ids ) ? $snippet_ids[0] : 0;
}

/**
 * Executes a PHP code snippet with output buffering and error handling.
 *
 * @param string $code_to_execute The PHP code.
 * @param string $snippet_location_slug A slug identifying the snippet's location (for error messages).
 * @return string The output from the snippet, or an HTML comment with error details if execution fails.
 */
function snn_execute_php_snippet( $code_to_execute, $snippet_location_slug ) {
    if ( empty( trim( $code_to_execute ) ) ) {
        return ''; // No code to execute
    }

    $error_occurred = false;
    $error_message_for_log = ''; 
    $error_message_for_admin_comment = ''; 

    // Custom error handler for non-fatal errors
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_occurred, &$error_message_for_log, &$error_message_for_admin_comment, $snippet_location_slug) {
        $error_occurred = true;
        $error_message_for_log = sprintf(
            "SNN Custom Code Snippet Error in '%s': Type %d - %s in file %s on line %d.",
            $snippet_location_slug, $errno, $errstr, $errfile, $errline
        );
        $error_message_for_admin_comment = sprintf(
            "Error: [%d] %s on line %d.", $errno, esc_html($errstr), $errline
        );
        error_log($error_message_for_log);
        return true; 
    });

    ob_start(); 

    try {
        // eval() executes the code. User can include <?php tags or not.
        eval( "?>" . $code_to_execute ); // Prepending "? >" ensures that if the code doesn't start with <?php, it's treated as HTML outside PHP blocks.
                                       // If it does start with <?php, the "? >" before it is ignored.
    } catch (ParseError $e) { 
        $error_occurred = true;
        $error_message_for_log = sprintf(
            "SNN Custom Code Snippet Parse Error in '%s': %s in %s on line %d. Code (first 200 chars): %s",
            $snippet_location_slug, $e->getMessage(), $e->getFile(), $e->getLine(), substr($code_to_execute, 0, 200)
        );
        $error_message_for_admin_comment = sprintf(
            "Parse Error: %s on line %d.", esc_html($e->getMessage()), $e->getLine()
        );
        error_log($error_message_for_log);
    } catch (Throwable $e) { 
        $error_occurred = true;
        $error_message_for_log = sprintf(
            "SNN Custom Code Snippet Exception/Error in '%s': %s in %s on line %d. Code (first 200 chars): %s",
            $snippet_location_slug, get_class($e) . ": " . $e->getMessage(), $e->getFile(), $e->getLine(), substr($code_to_execute, 0, 200)
        );
        $error_message_for_admin_comment = sprintf(
            "Exception/Error (%s): %s on line %d.", esc_html(get_class($e)), esc_html($e->getMessage()), $e->getLine()
        );
        error_log($error_message_for_log);
    }

    $output_from_snippet = ob_get_clean(); 
    restore_error_handler(); 

    if ( $error_occurred ) {
        if ( current_user_can( 'manage_options' ) ) {
            return "\n\n";
        } else {
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

    // Define snippet types and their properties
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
    );

    $settings_saved_message_type = 'updated'; // Default message type

    // Handle form submissions
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['snn_codes_snippets_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snn_codes_snippets_nonce'] ) ), 'snn_save_codes_snippets' ) ) {
        
        // Handle Clear Revisions Action
        if ( isset( $_POST['snn_clear_revisions_button'] ) && ! empty( $_POST['snn_clear_revisions_button'] ) ) {
            $snippet_key_to_clear = isset( $_POST['snn_snippet_key_to_clear'] ) ? sanitize_key( $_POST['snn_snippet_key_to_clear'] ) : '';

            if ( $snippet_key_to_clear && isset( $snippet_defs[ $snippet_key_to_clear ] ) ) {
                // Verify nonce for clearing revisions for this specific tab
                check_admin_referer( 'snn_clear_revisions_' . $snippet_key_to_clear, 'snn_clear_revisions_nonce_' . $snippet_key_to_clear );

                $target_snippet_def = $snippet_defs[ $snippet_key_to_clear ];
                $target_post_id = snn_get_code_snippet_id( $target_snippet_def['slug'] );

                if ( $target_post_id && current_user_can( 'delete_post', $target_post_id ) ) {
                    $revisions_to_delete = wp_get_post_revisions( $target_post_id, array( 'fields' => 'ids', 'posts_per_page' => -1 ) );
                    if ( !empty($revisions_to_delete) ) {
                        $deleted_count = 0;
                        foreach ( $revisions_to_delete as $revision_id_to_delete ) {
                            if ( wp_delete_post_revision( $revision_id_to_delete ) ) {
                                $deleted_count++;
                            }
                        }
                        if ($deleted_count > 0) {
                             add_settings_error('snn-custom-codes', 'revisions_cleared', sprintf(__( '%d revision(s) for "%s" cleared successfully.', 'snn-custom-codes' ), $deleted_count, esc_html($target_snippet_def['title'])), 'updated');
                        } else {
                             add_settings_error('snn-custom-codes', 'revisions_clear_failed_none_deleted', sprintf(__( 'No revisions were deleted for "%s". They might have been cleared already or an error occurred.', 'snn-custom-codes' ), esc_html($target_snippet_def['title'])), 'warning');
                        }
                    } else {
                        add_settings_error('snn-custom-codes', 'no_revisions_to_clear', sprintf(__( 'No revisions found to clear for "%s".', 'snn-custom-codes' ), esc_html($target_snippet_def['title'])), 'info');
                    }
                } else {
                    add_settings_error('snn-custom-codes', 'clear_revisions_failed_permissions', __('Failed to clear revisions. Invalid snippet or insufficient permissions.', 'snn-custom-codes'), 'error');
                    $settings_saved_message_type = 'error';
                }
                $_GET['tab'] = $snippet_key_to_clear; // Ensure the correct tab is active after action
            }
        } 
        // Handle Restore Revision Action (check this *after* clear, as restore also saves)
        elseif ( isset( $_POST['snn_restore_submit_button'] ) && ! empty( $_POST['snn_restore_submit_button'] ) ) {
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
                        $_POST[ $target_snippet_def['field_id'] ] = $revision->post_content; // Pre-fill textarea with restored content for saving
                        $_GET['tab'] = $snippet_key_for_restore;
                        add_settings_error('snn-custom-codes', 'revision_restored', sprintf(__('Revision for "%s" restored and will be saved.', 'snn-custom-codes'), esc_html($target_snippet_def['title'])), 'updated');
                    } else {
                        add_settings_error('snn-custom-codes', 'restore_failed', __('Failed to restore revision. Invalid ID or permissions.', 'snn-custom-codes'), 'error');
                        $settings_saved_message_type = 'error';
                    }
                }
            }
        }

        // Save global enable/disable setting (always do this if form submitted, unless clear was the only action and we want to stop)
        $is_enabled = isset( $_POST['snn_codes_snippets_enabled'] ) ? 1 : 0;
        update_option( 'snn_codes_snippets_enabled', $is_enabled );

        // Save content for all defined snippets from their respective textareas
        $all_snippets_processed_successfully = true;
        foreach ( $snippet_defs as $key => $def ) {
            if ( isset( $_POST[ $def['field_id'] ] ) ) {
                $new_code_content = wp_unslash( $_POST[ $def['field_id'] ] ); // Code comes directly from textarea
                $snippet_post_id = snn_get_code_snippet_id( $def['slug'] );

                $post_data = array(
                    'post_title'   => $def['title'],
                    'post_content' => $new_code_content,
                    'post_status'  => 'private',
                    'post_type'    => 'snn_code_snippet',
                    'post_name'    => $def['slug'],
                );

                if ( $snippet_post_id ) { // Existing snippet
                    $post_data['ID'] = $snippet_post_id;
                    $updated_id = wp_update_post( $post_data, true );
                    if ( is_wp_error( $updated_id ) ) {
                        add_settings_error('snn-custom-codes', 'update_failed_' . $key, sprintf(__('Failed to update snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($updated_id->get_error_message())), 'error');
                        $all_snippets_processed_successfully = false;
                    }
                } else { // New snippet
                    $inserted_id = wp_insert_post( $post_data, true );
                    if ( is_wp_error( $inserted_id ) ) {
                        add_settings_error('snn-custom-codes', 'insert_failed_' . $key, sprintf(__('Failed to create snippet: %s - %s', 'snn-custom-codes'), esc_html($def['title']), esc_html($inserted_id->get_error_message())), 'error');
                        $all_snippets_processed_successfully = false;
                    }
                }
            }
        }

        // General save message, if no specific restore/clear message was more prominent or if there were no errors.
        $notices = get_settings_errors('snn-custom-codes');
        $has_specific_action_message = false;
        foreach ($notices as $notice) {
            if (in_array($notice['code'], ['revision_restored', 'revisions_cleared', 'no_revisions_to_clear'])) {
                $has_specific_action_message = true;
                break;
            }
        }

        if ( $all_snippets_processed_successfully && !$has_specific_action_message && $settings_saved_message_type === 'updated' ) {
            add_settings_error('snn-custom-codes', 'settings_saved', __('All snippets and settings saved.', 'snn-custom-codes'), 'updated');
        } elseif (!$all_snippets_processed_successfully && $settings_saved_message_type !== 'error') { // Avoid double error if already set
            add_settings_error('snn-custom-codes', 'save_errors', __('Some snippets could not be saved. Please check messages above.', 'snn-custom-codes'), 'error');
        }

    } // End of POST handling

    // Fetch current settings and codes for display
    $enabled_globally = get_option( 'snn_codes_snippets_enabled', 0 );
    $current_tab_key = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'frontend'; 
    if ( ! array_key_exists( $current_tab_key, $snippet_defs ) ) {
        $current_tab_key = 'frontend'; 
    }

    $codes_for_display = array();
    foreach ( $snippet_defs as $key => $def ) {
        $codes_for_display[ $key ] = snn_get_code_snippet_content( $def['slug'] );
    }

    settings_errors('snn-custom-codes'); // Display admin notices
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Manage PHP Code Snippets', 'snn-custom-codes' ); ?></h1>

        
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
                ?>
            </h2>

            <?php if ( isset( $snippet_defs[ $current_tab_key ] ) ) :
                $active_snippet_def = $snippet_defs[ $current_tab_key ];
                $current_code_value = isset($codes_for_display[ $current_tab_key ]) ? $codes_for_display[ $current_tab_key ] : ''; 
                // If form was submitted (e.g. restore, or failed save), show the submitted value to avoid losing edits.
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
                                                class="button button-primary button-small snn-restore-revision-button">
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

            <?php submit_button( __( 'Save All Snippets & Settings', 'snn-custom-codes' ), 'primary large', 'snn_save_all_settings_button' ); ?>
        </form>
    </div>
    <?php
} // End of snn_custom_codes_snippets_page()

/**
 * Initialize snippet execution hooks based on global setting and snippet content.
 */
function snn_custom_codes_snippets_init_execution() {
    if ( ! get_option( 'snn_codes_snippets_enabled', 0 ) ) {
        return; // Global switch is off
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

/** Output callback for frontend head */
function snn_custom_codes_snippets_frontend_output() {
    $code = snn_get_code_snippet_content( 'snn-snippet-frontend-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-frontend-head' );
}
/** Output callback for frontend footer */
function snn_custom_codes_snippets_footer_output()   {
    $code = snn_get_code_snippet_content( 'snn-snippet-footer' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-footer' );
}
/** Output callback for admin head */
function snn_custom_codes_snippets_admin_output()    {
    $code = snn_get_code_snippet_content( 'snn-snippet-admin-head' );
    echo snn_execute_php_snippet( $code, 'snn-snippet-admin-head' );
}

/**
 * AJAX handler for fetching revision content to preview in CodeMirror.
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

    wp_send_json_success( array(
        'content' => $revision->post_content, 
        'title'   => wp_post_revision_title_expanded( $revision ), 
    ) );
}

/** Activation hook: Register CPT and flush rewrite rules. */
function snn_custom_codes_plugin_activate() {
    snn_custom_codes_snippets_register_cpt(); 
    flush_rewrite_rules(); 
    if ( false === get_option( 'snn_codes_snippets_enabled', false ) ) {
        update_option( 'snn_codes_snippets_enabled', 0 ); // Default to disabled
    }
}
register_activation_hook( __FILE__, 'snn_custom_codes_plugin_activate' );

/** Deactivation hook: Flush rewrite rules. */
function snn_custom_codes_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'snn_custom_codes_plugin_deactivate' );

/**
 * Load plugin textdomain for internationalization.
 */
function snn_custom_codes_load_textdomain() {
    load_plugin_textdomain( 'snn-custom-codes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'snn_custom_codes_load_textdomain' );

?>
