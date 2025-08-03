<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers a custom post type for storing activity log entries.
 * This post type is set to be private and not queryable by the public.
 */
function snn_register_activity_log_post_type() {
    $args = array(
        'public'                => false,
        'publicly_queryable'    => false,
        'show_ui'               => false,
        'show_in_menu'          => false,
        'query_var'             => false,
        'rewrite'               => false,
        'capability_type'       => 'post',
        'has_archive'           => false,
        'hierarchical'          => false,
        'supports'              => array( 'title', 'editor' ),
    );
    register_post_type( 'snn_activity_log', $args );
}
add_action( 'init', 'snn_register_activity_log_post_type' );

/**
 * Adds the "Activity Logs" submenu page under the 'snn-settings' parent menu.
 */
function snn_activity_log_page() {
    add_submenu_page(
        'snn-settings',
        __( 'Activity Logs', 'snn-activity-log' ),
        __( 'Activity Logs', 'snn-activity-log' ),
        'manage_options',
        'snn-activity-log',
        'snn_activity_log_page_html'
    );
}
add_action( 'admin_menu', 'snn_activity_log_page' );

/**
 * Registers the settings for the activity log page.
 * This includes a global enable/disable toggle, a log limit, and individual toggles for each log type.
 */
function snn_activity_log_register_settings() {
    register_setting( 'snn_activity_log_options', 'snn_activity_log_enable' );
    register_setting( 'snn_activity_log_options', 'snn_activity_log_limit', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1000,
    ) );

    // Register all logging option settings dynamically
    $logging_options = snn_get_logging_options();
    foreach ( $logging_options as $category => $options ) {
        foreach ( $options as $key => $label ) {
            register_setting( 'snn_activity_log_options', 'snn_log_' . $key, array(
                'type'    => 'boolean',
                'default' => true,
            ) );
        }
    }
}
add_action( 'admin_init', 'snn_activity_log_register_settings' );

/**
 * Defines and returns an array of all available logging options, categorized for better organization.
 * Each option has a unique key and a human-readable label.
 *
 * @return array Associative array of logging options.
 */
function snn_get_logging_options() {
    return array(
        'user_activities' => array(
            'user_login'            => __( 'User Login', 'snn-activity-log' ),
            'user_logout'           => __( 'User Logout', 'snn-activity-log' ),
            'user_register'         => __( 'User Registration', 'snn-activity-log' ),
            'user_profile_update'   => __( 'User Profile Updates', 'snn-activity-log' ),
            'user_deleted'          => __( 'User Deletion', 'snn-activity-log' ),
            'user_role_change'      => __( 'User Role Changes', 'snn-activity-log' ),
            'password_reset'        => __( 'Password Reset Requests', 'snn-activity-log' ),
            'failed_login'          => __( 'Failed Login Attempts', 'snn-activity-log' ),
        ),
        'content_activities' => array(
            'post_created'          => __( 'Post/Page Creation', 'snn-activity-log' ),
            'post_updated'          => __( 'Post/Page Updates', 'snn-activity-log' ),
            'post_deleted'          => __( 'Post/Page Deletion', 'snn-activity-log' ),
            'post_trashed'          => __( 'Post/Page Trashed', 'snn-activity-log' ),
            'post_status_change'    => __( 'Post Status Changes', 'snn-activity-log' ),
            'attachment_uploaded'   => __( 'Media Uploads', 'snn-activity-log' ),
            'attachment_deleted'    => __( 'Media Deletion', 'snn-activity-log' ),
            'attachment_updated'    => __( 'Media Updates', 'snn-activity-log' ),
        ),
        'comment_activities' => array(
            'comment_posted'        => __( 'Comment Posted', 'snn-activity-log' ),
            'comment_approved'      => __( 'Comment Approved', 'snn-activity-log' ),
            'comment_unapproved'    => __( 'Comment Unapproved', 'snn-activity-log' ),
            'comment_trashed'       => __( 'Comment Trashed', 'snn-activity-log' ),
            'comment_spammed'       => __( 'Comment Marked as Spam', 'snn-activity-log' ),
            'comment_deleted'       => __( 'Comment Deleted', 'snn-activity-log' ),
        ),
        'system_activities' => array(
            'plugin_activated'      => __( 'Plugin Activation', 'snn-activity-log' ),
            'plugin_deactivated'    => __( 'Plugin Deactivation', 'snn-activity-log' ),
            'plugin_deleted'        => __( 'Plugin Deletion', 'snn-activity-log' ),
            'theme_switched'        => __( 'Theme Switch', 'snn-activity-log' ),
            'theme_updated'         => __( 'Theme Updates', 'snn-activity-log' ),
            'theme_deleted'         => __( 'Theme Deletion', 'snn-activity-log' ),
            'core_updated'          => __( 'WordPress Core Updates', 'snn-activity-log' ),
            'widget_updated'        => __( 'Widget Changes', 'snn-activity-log' ),
            'menu_updated'          => __( 'Menu Updates', 'snn-activity-log' ),
            'option_updated'        => __( 'Settings Changes', 'snn-activity-log' ),
            'export_performed'      => __( 'Data Exports', 'snn-activity-log' ),
            'cron_executed'         => __( 'Scheduled Tasks (Cron)', 'snn-activity-log' ),
        ),
        'security_activities' => array(
            'file_edited'           => __( 'Theme/Plugin File Edits', 'snn-activity-log' ),
            'user_capability_change' => __( 'User Capability Changes', 'snn-activity-log' ),
            'privacy_request'       => __( 'Privacy Data Requests', 'snn-activity-log' ),
            'privacy_erase'         => __( 'Privacy Data Erasure', 'snn-activity-log' ),
            'application_password'  => __( 'Application Password Events', 'snn-activity-log' ),
        ),
        'taxonomy_activities' => array(
            'term_created' => __( 'Category/Tag Creation', 'snn-activity-log' ),
            'term_edited'  => __( 'Category/Tag Updates', 'snn-activity-log' ),
            'term_deleted' => __( 'Category/Tag Deletion', 'snn-activity-log' ),
        ),
        'database_activities' => array(
            'db_query_error'  => __( 'Database Query Errors', 'snn-activity-log' ),
            'db_optimization' => __( 'Database Optimization', 'snn-activity-log' ),
        ),
    );
}

/**
 * Checks if a specific logging option is enabled based on its key.
 *
 * @param string $log_type The key of the log type to check (e.g., 'user_login').
 * @return bool True if the log type is enabled, false otherwise.
 */
function snn_is_log_type_enabled( $log_type ) {
    return get_option( 'snn_log_' . $log_type, true );
}

/**
 * The main function to log user activity.
 * It creates a new custom post type entry for each activity.
 *
 * @param string $action    The action performed by the user (e.g., 'User Logged In').
 * @param string $object    The object the action was performed on (e.g., username, post title).
 * @param int    $object_id The ID of the object (e.g., user ID, post ID).
 * @param string $log_type  The type of log for filtering and enabling/disabling (e.g., 'user_login').
 */
function snn_log_user_activity( $action, $object = '', $object_id = 0, $log_type = '' ) {
    // Stop if the feature is disabled globally or if the action is empty.
    if ( ! get_option( 'snn_activity_log_enable' ) || empty( trim( $action ) ) ) {
        return;
    }

    // Check if this specific log type is enabled
    if ( ! empty( $log_type ) && ! snn_is_log_type_enabled( $log_type ) ) {
        return;
    }

    $user = wp_get_current_user();

    if ( $user->ID ) {
        $user_info = "{$user->user_login} (ID: {$user->ID})";
    } else {
        $user_info = 'system'; // For actions not tied to a logged-in user (e.g., cron, failed login)
    }

    // Using a robust separator to avoid parsing issues in the title.
    $log_title = "{$user_info} || {$action}";
    $log_content = "Object: {$object}\nObject ID: {$object_id}\nIP Address: " . ( $_SERVER['REMOTE_ADDR'] ?? 'N/A' );

    // Add user agent for specific security-related logs
    if ( in_array( $log_type, array( 'failed_login', 'user_login', 'file_edited' ) ) ) {
        $log_content .= "\nUser Agent: " . ( $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' );
    }

    $post_id = wp_insert_post( array(
        'post_type'    => 'snn_activity_log',
        'post_title'   => wp_strip_all_tags( $log_title ),
        'post_content' => $log_content,
        'post_status'  => 'publish',
        'meta_input'   => array(
            'log_type' => $log_type,
        ),
    ) );

    // If the post was successfully inserted, trim the log to maintain the limit.
    if ( $post_id ) {
        snn_trim_activity_log();
    }
}

/**
 * Trims the activity log to the specified limit.
 * Oldest entries are deleted automatically when the limit is exceeded.
 */
function snn_trim_activity_log() {
    $limit = get_option( 'snn_activity_log_limit', 1000 );

    $args = array(
        'post_type'      => 'snn_activity_log',
        'posts_per_page' => -1, // Get all posts to determine how many to delete
        'orderby'        => 'date',
        'order'          => 'ASC', // Order by oldest first
        'fields'         => 'ids', // Only retrieve post IDs for efficiency
    );
    $log_posts = new WP_Query( $args );

    if ( $log_posts->post_count > $limit ) {
        // Calculate how many posts need to be deleted (the oldest ones)
        $posts_to_delete = array_slice( $log_posts->posts, 0, $log_posts->post_count - $limit );
        foreach ( $posts_to_delete as $post_id ) {
            wp_delete_post( $post_id, true ); // Delete permanently
        }
    }
}

/**
 * Hooks for various user activities.
 */

// User login
add_action( 'wp_login', function( $user_login, $user ) {
    snn_log_user_activity( 'User Logged In', $user_login, $user->ID, 'user_login' );
}, 10, 2 );

// User logout
add_action( 'wp_logout', function( $user_id ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        snn_log_user_activity( 'User Logged Out', $user->user_login, $user_id, 'user_logout' );
    }
});

// User registration
add_action( 'user_register', function( $user_id ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        snn_log_user_activity( 'User Registered', $user->user_login, $user_id, 'user_register' );
    }
});

// User profile update
add_action( 'profile_update', function( $user_id, $old_user_data ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        snn_log_user_activity( 'User Profile Updated', $user->user_login, $user_id, 'user_profile_update' );
    }
}, 10, 2 );

// User deleted
add_action( 'deleted_user', function( $user_id, $reassign ) {
    snn_log_user_activity( 'User Deleted', 'User ID: ' . $user_id, $user_id, 'user_deleted' );
}, 10, 2 );

// User role change
add_action( 'set_user_role', function( $user_id, $role, $old_roles ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        $old_roles_str = is_array( $old_roles ) ? implode( ', ', $old_roles ) : 'none';
        snn_log_user_activity( "User Role Changed from {$old_roles_str} to {$role}", $user->user_login, $user_id, 'user_role_change' );
    }
}, 10, 3 );

// Password reset
add_action( 'password_reset', function( $user, $new_pass ) {
    snn_log_user_activity( 'Password Reset', $user->user_login, $user->ID, 'password_reset' );
}, 10, 2 );

// Failed login attempts
add_action( 'wp_login_failed', function( $username ) {
    snn_log_user_activity( 'Failed Login Attempt', $username, 0, 'failed_login' );
});

// Post/Page updated (including creation and status changes)
add_action( 'post_updated', function( $post_id, $post_after, $post_before ) {
    // Ignore revisions, our own log entries, and items being trashed (handled by wp_trash_post)
    if ( wp_is_post_revision( $post_id ) || $post_after->post_type === 'snn_activity_log' || $post_after->post_status === 'trash' ) {
        return;
    }

    $post_type = get_post_type_object( $post_after->post_type );
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';

    // Check if it's a new post (from auto-draft or new status)
    if ( $post_before->post_status === 'auto-draft' || $post_before->post_status === 'new' ) {
        snn_log_user_activity( "{$action_label} Created", $post_after->post_title, $post_id, 'post_created' );
    } else {
        snn_log_user_activity( "{$action_label} Updated", $post_after->post_title, $post_id, 'post_updated' );

        // Log status changes specifically
        if ( $post_before->post_status !== $post_after->post_status ) {
            snn_log_user_activity(
                "{$action_label} Status Changed from {$post_before->post_status} to {$post_after->post_status}",
                $post_after->post_title,
                $post_id,
                'post_status_change'
            );
        }
    }
}, 10, 3 );

// Post/Page trashed
add_action( 'wp_trash_post', function( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type === 'snn_activity_log' ) {
        return;
    }
    $post_type = get_post_type_object( $post->post_type );
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';
    snn_log_user_activity( "{$action_label} Trashed", $post->post_title, $post_id, 'post_trashed' );
});

// Post/Page deleted permanently
add_action( 'delete_post', function( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type === 'snn_activity_log' ) {
        return;
    }
    $post_type = get_post_type_object( $post->post_type );
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';
    snn_log_user_activity( "{$action_label} Deleted Permanently", $post->post_title, $post_id, 'post_deleted' );
});

// Media uploads
add_action( 'add_attachment', function( $attachment_id ) {
    $attachment = get_post( $attachment_id );
    if ( $attachment ) {
        snn_log_user_activity( 'Media Uploaded', $attachment->post_title, $attachment_id, 'attachment_uploaded' );
    }
});

// Media deleted
add_action( 'delete_attachment', function( $attachment_id ) {
    $attachment = get_post( $attachment_id );
    if ( $attachment ) {
        snn_log_user_activity( 'Media Deleted', $attachment->post_title, $attachment_id, 'attachment_deleted' );
    }
});

// Media updated
add_action( 'edit_attachment', function( $attachment_id ) {
    $attachment = get_post( $attachment_id );
    if ( $attachment ) {
        snn_log_user_activity( 'Media Updated', $attachment->post_title, $attachment_id, 'attachment_updated' );
    }
});

// Comment posted
add_action( 'comment_post', function( $comment_id, $comment_approved, $commentdata ) {
    $post = get_post( $commentdata['comment_post_ID'] );
    $post_title = $post ? $post->post_title : 'Unknown Post';
    snn_log_user_activity( 'Comment Posted', $post_title, $comment_id, 'comment_posted' );
}, 10, 3 );

// Comment status transitions
add_action( 'transition_comment_status', function( $new_status, $old_status, $comment ) {
    if ( $new_status === $old_status ) {
        return;
    }

    $post = get_post( $comment->comment_post_ID );
    $post_title = $post ? $post->post_title : 'Unknown Post';

    switch ( $new_status ) {
        case 'approved':
            snn_log_user_activity( 'Comment Approved', $post_title, $comment->comment_ID, 'comment_approved' );
            break;
        case 'unapproved':
            snn_log_user_activity( 'Comment Unapproved', $post_title, $comment->comment_ID, 'comment_unapproved' );
            break;
        case 'trash':
            snn_log_user_activity( 'Comment Trashed', $post_title, $comment->comment_ID, 'comment_trashed' );
            break;
        case 'spam':
            snn_log_user_activity( 'Comment Marked as Spam', $post_title, $comment->comment_ID, 'comment_spammed' );
            break;
    }
}, 10, 3 );

// Comment deleted
add_action( 'deleted_comment', function( $comment_id, $comment ) {
    $post = get_post( $comment->comment_post_ID );
    $post_title = $post ? $post->post_title : 'Unknown Post';
    snn_log_user_activity( 'Comment Deleted', $post_title, $comment_id, 'comment_deleted' );
}, 10, 2 );

// Plugin activated
add_action( 'activated_plugin', function( $plugin ) {
    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
    snn_log_user_activity( 'Plugin Activated', $plugin_data['Name'] ?? 'Unknown Plugin', 0, 'plugin_activated' );
});

// Plugin deactivated
add_action( 'deactivated_plugin', function( $plugin ) {
    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
    snn_log_user_activity( 'Plugin Deactivated', $plugin_data['Name'] ?? 'Unknown Plugin', 0, 'plugin_deactivated' );
});

// Plugin deleted
add_action( 'deleted_plugin', function( $plugin_file, $deleted ) {
    if ( $deleted ) {
        snn_log_user_activity( 'Plugin Deleted', $plugin_file, 0, 'plugin_deleted' );
    }
}, 10, 2 );

// Theme switched
add_action( 'switch_theme', function( $new_name, $new_theme, $old_theme ) {
    $old_name = $old_theme->get( 'Name' );
    snn_log_user_activity( "Theme Switched from {$old_name} to {$new_name}", $new_name, 0, 'theme_switched' );
}, 10, 3 );

// Theme updated or Core updated (using upgrader_process_complete)
add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
    // Theme updates
    if ( $options['action'] == 'update' && $options['type'] == 'theme' ) {
        foreach ( $options['themes'] as $theme ) {
            $theme_data = wp_get_theme( $theme );
            snn_log_user_activity( 'Theme Updated', $theme_data->get( 'Name' ), 0, 'theme_updated' );
        }
    }
    // Core updates
    if ( $options['action'] == 'update' && $options['type'] == 'core' ) {
        snn_log_user_activity( 'WordPress Core Updated', 'Version ' . get_bloginfo( 'version' ), 0, 'core_updated' );
    }
}, 10, 2 );

// Widget updated (detects save/remove actions in sidebar admin)
add_action( 'sidebar_admin_setup', function() {
    if ( isset( $_POST['savewidget'] ) || isset( $_POST['removewidget'] ) ) {
        $action = isset( $_POST['savewidget'] ) ? 'Widget Updated' : 'Widget Removed';
        $widget_id = $_POST['widget-id'] ?? 'Unknown Widget';
        snn_log_user_activity( $action, $widget_id, 0, 'widget_updated' );
    }
});

// Menu updated
add_action( 'wp_update_nav_menu', function( $menu_id ) {
    $menu = wp_get_nav_menu_object( $menu_id );
    if ( $menu ) {
        snn_log_user_activity( 'Menu Updated', $menu->name, $menu_id, 'menu_updated' );
    }
});

// Settings/Options updated
add_action( 'updated_option', function( $option_name, $old_value, $value ) {
    // Define prefixes for internal WordPress options that update very frequently and should be skipped.
    $skip_prefixes = array( '_transient_', '_site_transient_', 'cron', '_session_' );
    // Define exact matches for our own plugin's settings to prevent recursive logging.
    $skip_exact_matches = array( 'snn_activity_log_enable', 'snn_activity_log_limit' );

    // Skip logging for our own individual logging toggles (snn_log_*)
    if ( strpos( $option_name, 'snn_log_' ) === 0 ) {
        return;
    }

    // Skip if the option name is an exact match in our skip list
    if ( in_array( $option_name, $skip_exact_matches ) ) {
        return;
    }

    // Skip if the option name starts with any of the defined internal prefixes
    foreach ( $skip_prefixes as $prefix ) {
        if ( strpos( $option_name, $prefix ) === 0 ) {
            return;
        }
    }

    // If not skipped, log the setting update.
    snn_log_user_activity( 'Setting Updated', $option_name, 0, 'option_updated' );
}, 10, 3 );

// Export performed
add_action( 'export_wp', function( $args ) {
    $content = $args['content'] ?? 'all';
    snn_log_user_activity( 'WordPress Export Performed', "Content: {$content}", 0, 'export_performed' );
});

// File edited (e.g., theme/plugin editor)
add_action( 'wp_redirect', function( $location ) {
    // This hook is triggered before redirect, allowing us to check POST data.
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'update' && isset( $_POST['file'] ) ) {
        $file = sanitize_text_field( $_POST['file'] );
        snn_log_user_activity( 'File Edited', $file, 0, 'file_edited' );
    }
    return $location; // Always return the location to ensure redirect continues.
});

// Term created (categories, tags, custom taxonomies)
add_action( 'created_term', function( $term_id, $tt_id, $taxonomy ) {
    $term = get_term( $term_id, $taxonomy );
    if ( $term && ! is_wp_error( $term ) ) {
        snn_log_user_activity( "Term Created in {$taxonomy}", $term->name, $term_id, 'term_created' );
    }
}, 10, 3 );

// Term edited
add_action( 'edited_term', function( $term_id, $tt_id, $taxonomy ) {
    $term = get_term( $term_id, $taxonomy );
    if ( $term && ! is_wp_error( $term ) ) {
        snn_log_user_activity( "Term Updated in {$taxonomy}", $term->name, $term_id, 'term_edited' );
    }
}, 10, 3 );

// Term deleted
add_action( 'delete_term', function( $term_id, $tt_id, $taxonomy, $deleted_term ) {
    if ( $deleted_term ) {
        snn_log_user_activity( "Term Deleted from {$taxonomy}", $deleted_term->name, $term_id, 'term_deleted' );
    }
}, 10, 4 );

// Privacy requests (export and erase personal data)
add_action( 'wp_privacy_personal_data_export_file_created', function( $request_id ) {
    snn_log_user_activity( 'Privacy Data Export Created', "Request ID: {$request_id}", $request_id, 'privacy_request' );
});

add_action( 'wp_privacy_personal_data_erased', function( $request_id ) {
    snn_log_user_activity( 'Privacy Data Erased', "Request ID: {$request_id}", $request_id, 'privacy_erase' );
});

// Application passwords (creation and deletion)
add_action( 'wp_create_application_password', function( $user_id, $new_item ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        snn_log_user_activity( 'Application Password Created', $user->user_login, $user_id, 'application_password' );
    }
}, 10, 2 );

add_action( 'wp_delete_application_password', function( $user_id, $item ) {
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        snn_log_user_activity( 'Application Password Deleted', $user->user_login, $user_id, 'application_password' );
    }
}, 10, 2 );

/**
 * HTML for the activity log administration page.
 * Displays settings, a clear log button, and a table of recent activity.
 */
function snn_activity_log_page_html() {
    // Ensure only users with 'manage_options' capability can access this page.
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle clearing the log if the form is submitted and nonce is valid.
    if ( isset( $_POST['snn_clear_log_nonce'] ) && wp_verify_nonce( $_POST['snn_clear_log_nonce'], 'snn_clear_log_action' ) ) {
        global $wpdb;
        $post_type = 'snn_activity_log';
        // Delete all posts of our custom log type.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = %s", $post_type ) );
        echo '<div class="updated notice is-dismissible"><p>' . __( 'Activity log cleared.', 'snn-activity-log' ) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output security fields for the registered setting group.
            settings_fields( 'snn_activity_log_options' );
            // Output settings sections and fields for the page.
            do_settings_sections( 'snn-activity-log' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Enable Activity Log', 'snn-activity-log' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="snn_activity_log_enable" value="1" <?php checked( 1, get_option( 'snn_activity_log_enable' ), true ); ?> />
                            <?php _e( 'Enable the activity logging feature.', 'snn-activity-log' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Log Limit', 'snn-activity-log' ); ?></th>
                    <td>
                        <input type="number" name="snn_activity_log_limit" value="<?php echo esc_attr( get_option( 'snn_activity_log_limit', 1000 ) ); ?>" class="small-text" />
                        <p class="description"><?php _e( 'Maximum number of log entries to keep. Oldest entries will be deleted automatically.', 'snn-activity-log' ); ?></p>
                    </td>
                </tr>
            </table>


            <div class="snn-accordion">
                <div class="snn-accordion-item">
                    <h3 class="snn-accordion-header">
                        <button type="button" class="snn-accordion-button" aria-expanded="false">
                            <span class="snn-accordion-title"><?php _e( 'All Logging Options', 'snn-activity-log' ); ?></span>
                            <span class="snn-accordion-icon">▼</span>
                        </button>
                    </h3>
                    <div class="snn-accordion-content" style="display: none;">
                        <div style="padding: 10px 0; border-bottom: 1px solid #e1e4e8;">
                            <button type="button" id="snn-select-all-logs" class="button"><?php _e( 'Select All', 'snn-activity-log' ); ?></button>
                            <button type="button" id="snn-deselect-all-logs" class="button"><?php _e( 'Deselect All', 'snn-activity-log' ); ?></button>
                        </div>
                        <table class="form-table snn-logging-options">
                            <?php
                            $logging_options = snn_get_logging_options();
                            foreach ( $logging_options as $category => $options ) :
                                foreach ( $options as $key => $label ) : ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html( $label ); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="snn_log_<?php echo esc_attr( $key ); ?>" value="1" <?php checked( 1, get_option( 'snn_log_' . $key, true ), true ); ?> />
                                                <?php _e( 'Enable', 'snn-activity-log' ); ?>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <?php submit_button(); // WordPress standard submit button ?>
        </form>

        <form method="post">
            <?php wp_nonce_field( 'snn_clear_log_action', 'snn_clear_log_nonce' ); ?>
            <?php submit_button( __( 'Clear All Logs', 'snn-activity-log' ), 'delete', 'snn-clear-log' ); ?>
        </form>

        <hr>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1em;">
            <h2><?php _e( 'Recent Activity', 'snn-activity-log' ); ?></h2>
            <p class="search-box">
                <label class="screen-reader-text" for="snn-log-search-input"><?php _e('Search Logs:', 'snn-activity-log'); ?></label>
                <input type="search" id="snn-log-search-input" name="snn_log_search" placeholder="<?php _e('Filter logs...', 'snn-activity-log'); ?>">
            </p>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width:150px;"><?php _e( 'Date', 'snn-activity-log' ); ?></th>
                    <th scope="col"><?php _e( 'User', 'snn-activity-log' ); ?></th>
                    <th scope="col"><?php _e( 'Action', 'snn-activity-log' ); ?></th>
                    <th scope="col"><?php _e( 'Details', 'snn-activity-log' ); ?></th>
                </tr>
            </thead>
            <tbody id="snn-log-list">
                <?php
                // Query arguments to retrieve recent activity logs.
                $args = array(
                    'post_type'      => 'snn_activity_log',
                    'posts_per_page' => 1000, // Display up to 1000 recent logs on this page.
                    'orderby'        => 'date',
                    'order'          => 'DESC', // Order by newest first.
                );
                $logs = new WP_Query( $args );
                if ( $logs->have_posts() ) :
                    while ( $logs->have_posts() ) : $logs->the_post();
                        // Safely explode the title using the robust separator to get user info and action.
                        $title_parts = explode(' || ', get_the_title(), 2);
                        $user_info = isset($title_parts[0]) ? $title_parts[0] : 'N/A';
                        $action = isset($title_parts[1]) ? $title_parts[1] : 'Unknown Action';
                        ?>
                        <tr class="snn-log-entry">
                            <td><?php echo get_the_date( 'Y-m-d H:i:s' ); ?></td>
                            <td><?php echo esc_html( $user_info ); ?></td>
                            <td><?php echo esc_html( $action ); ?></td>
                            <td><code><?php echo esc_html( get_the_content() ); ?></code></td>
                        </tr>
                    <?php endwhile;
                    wp_reset_postdata(); // Restore original post data.
                else : ?>
                    <tr id="snn-no-logs-found">
                        <td colspan="4"><?php _e( 'No activity logged yet.', 'snn-activity-log' ); ?></td>
                    </tr>
                <?php endif; ?>
                       <tr id="snn-no-search-results" style="display: none;">
                        <td colspan="4"><?php _e( 'No matching logs found.', 'snn-activity-log' ); ?></td>
                    </tr>
            </tbody>
        </table>
    </div>

    <style>
        /* Styles for the accordion component */
        .snn-accordion {
            margin: 20px 0;
            border: 1px solid #ccd0d4;
            background: #fff;
        }

        .snn-accordion-item {
            border-bottom: 1px solid #ccd0d4;
        }

        .snn-accordion-item:last-child {
            border-bottom: none;
        }

        .snn-accordion-header {
            margin: 0;
            padding: 0;
        }

        .snn-accordion-button {
            width: 100%;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            text-align: left;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            color: #23282d;
            transition: background-color 0.2s;
        }

        .snn-accordion-button:hover {
            background: #f1f3f5;
        }

        .snn-accordion-button[aria-expanded="true"] {
            background: #e8eaed;
        }

        .snn-accordion-button[aria-expanded="true"] .snn-accordion-icon {
            transform: rotate(180deg);
        }

        .snn-accordion-icon {
            transition: transform 0.2s;
        }

        .snn-accordion-content {
            padding: 0 20px;
            background: #fafbfc;
        }

        /* Styles for the logging options table within the accordion */
        .snn-logging-options {
            margin: 0;
        }

        .snn-logging-options tr {
            border-bottom: 1px solid #e1e4e8;
        }

        .snn-logging-options tr:last-child {
            border-bottom: none;
        }

        .snn-logging-options th {
            padding: 10px 10px 10px 0;
            font-weight: normal;
        }

        .snn-logging-options td {
            padding: 10px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality for the activity log table
            const searchInput = document.getElementById('snn-log-search-input');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const logList = document.getElementById('snn-log-list');
                    const rows = logList.getElementsByClassName('snn-log-entry');
                    const noResultsRow = document.getElementById('snn-no-search-results');
                    let visibleCount = 0;

                    // Iterate through each log entry row
                    for (let i = 0; i < rows.length; i++) {
                        const rowText = rows[i].textContent.toLowerCase();
                        if (rowText.includes(filter)) {
                            rows[i].style.display = ''; // Show row if it matches the filter
                            visibleCount++;
                        } else {
                            rows[i].style.display = 'none'; // Hide row if it doesn't match
                        }
                    }

                    // Show/hide "No matching logs found" message
                    if (noResultsRow) {
                        // Only show "No matching logs" if there are actual logs to filter and none match.
                        if (visibleCount === 0 && rows.length > 0) {
                            noResultsRow.style.display = '';
                        } else {
                            noResultsRow.style.display = 'none';
                        }
                    }
                });
            }

            // Accordion functionality for the logging options section
            const accordionButtons = document.querySelectorAll('.snn-accordion-button');
            accordionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Toggle the 'aria-expanded' attribute
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    this.setAttribute('aria-expanded', !expanded);

                    // Find the corresponding content panel and toggle its display
                    const content = this.closest('.snn-accordion-item').querySelector('.snn-accordion-content');
                    content.style.display = expanded ? 'none' : 'block';
                });
            });

            // Select/Deselect All functionality
            const selectAllButton = document.getElementById('snn-select-all-logs');
            const deselectAllButton = document.getElementById('snn-deselect-all-logs');
            const logCheckboxes = document.querySelectorAll('.snn-logging-options input[type="checkbox"]');

            if (selectAllButton) {
                selectAllButton.addEventListener('click', function() {
                    logCheckboxes.forEach(checkbox => {
                        checkbox.checked = true;
                    });
                });
            }

            if (deselectAllButton) {
                deselectAllButton.addEventListener('click', function() {
                    logCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                });
            }
        });
    </script>
    <?php
}

