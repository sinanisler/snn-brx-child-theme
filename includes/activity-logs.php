<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register a custom post type for storing activity log entries.
 * This post type is set to be private and not queryable by the public.
 * Registered directly since this file is included early in the theme loading process.
 */
register_post_type( 'snn_activity_log', array(
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
) );

/**
 * Adds the "Activity Logs" submenu page under the 'snn-settings' parent menu.
 */
function snn_activity_log_page() {
    add_submenu_page(
        'snn-settings',
        __( 'Activity Logs', 'snn' ),
        __( 'Activity Logs', 'snn' ),
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
 * Handle CSV export early before any HTML output
 */
function snn_handle_activity_log_export() {
    // Only run on our activity log page
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'snn-activity-log' ) {
        return;
    }
    
    // Check if export was requested
    if ( ! isset( $_POST['snn_export_csv_nonce'] ) || ! wp_verify_nonce( $_POST['snn_export_csv_nonce'], 'snn_export_csv_action' ) ) {
        return;
    }
    
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Trigger the export
    snn_export_activity_log_csv();
}
add_action( 'admin_init', 'snn_handle_activity_log_export' );

/**
 * Returns the severity level and description for each log type.
 *
 * @return array Associative array mapping log type keys to severity info.
 */
function snn_get_log_severity_info() {
    return array(
        // RED - Critical Security & Compliance
        'failed_login'            => array( 'level' => 'critical', 'desc' => __( 'Critical: Essential for security monitoring and detecting potential attacks', 'snn' ) ),
        'user_deleted'            => array( 'level' => 'critical', 'desc' => __( 'Critical: Important for compliance and security auditing', 'snn' ) ),
        'user_role_change'        => array( 'level' => 'critical', 'desc' => __( 'Critical: Essential for tracking privilege escalation', 'snn' ) ),
        'user_capability_change'  => array( 'level' => 'critical', 'desc' => __( 'Critical: Essential for tracking permission changes', 'snn' ) ),
        'file_edited'             => array( 'level' => 'critical', 'desc' => __( 'Critical: Detects unauthorized code modifications', 'snn' ) ),
        'plugin_activated'        => array( 'level' => 'critical', 'desc' => __( 'Critical: New plugins can introduce security risks', 'snn' ) ),
        'plugin_deactivated'      => array( 'level' => 'critical', 'desc' => __( 'Critical: Track changes to site functionality', 'snn' ) ),
        'plugin_deleted'          => array( 'level' => 'critical', 'desc' => __( 'Critical: Permanent removal should be tracked', 'snn' ) ),
        'core_updated'            => array( 'level' => 'critical', 'desc' => __( 'Critical: Track WordPress version changes', 'snn' ) ),
        'application_password'    => array( 'level' => 'critical', 'desc' => __( 'Critical: Monitor API access credentials', 'snn' ) ),
        'privacy_erase'           => array( 'level' => 'critical', 'desc' => __( 'Critical: Required for GDPR compliance', 'snn' ) ),
        
        // YELLOW - Important Operational
        'user_login'              => array( 'level' => 'important', 'desc' => __( 'Important: Good for accountability and security', 'snn' ) ),
        'user_register'           => array( 'level' => 'important', 'desc' => __( 'Important: Track new user accounts', 'snn' ) ),
        'password_reset'          => array( 'level' => 'important', 'desc' => __( 'Important: Monitor password change requests', 'snn' ) ),
        'post_deleted'            => array( 'level' => 'important', 'desc' => __( 'Important: Permanent deletion should be tracked', 'snn' ) ),
        'post_trashed'            => array( 'level' => 'important', 'desc' => __( 'Important: Track content removal', 'snn' ) ),
        'attachment_deleted'      => array( 'level' => 'important', 'desc' => __( 'Important: Track media library changes', 'snn' ) ),
        'theme_switched'          => array( 'level' => 'important', 'desc' => __( 'Important: Track visual and functional changes', 'snn' ) ),
        'theme_deleted'           => array( 'level' => 'important', 'desc' => __( 'Important: Track theme removal', 'snn' ) ),
        'option_updated'          => array( 'level' => 'important', 'desc' => __( 'Important: Track configuration changes', 'snn' ) ),
        'comment_deleted'         => array( 'level' => 'important', 'desc' => __( 'Important: Track permanent comment removal', 'snn' ) ),
        'term_deleted'            => array( 'level' => 'important', 'desc' => __( 'Important: Track taxonomy changes', 'snn' ) ),
        'privacy_request'         => array( 'level' => 'important', 'desc' => __( 'Important: Required for GDPR compliance', 'snn' ) ),
        'export_performed'        => array( 'level' => 'important', 'desc' => __( 'Important: Track data exports for security', 'snn' ) ),
        
        // GRAY - Lower Priority
        'user_logout'             => array( 'level' => 'low', 'desc' => __( 'Low Priority: Creates high volume, mainly informational', 'snn' ) ),
        'user_profile_update'     => array( 'level' => 'low', 'desc' => __( 'Low Priority: Frequent updates, less critical', 'snn' ) ),
        'post_created'            => array( 'level' => 'low', 'desc' => __( 'Low Priority: High volume on active sites', 'snn' ) ),
        'post_updated'            => array( 'level' => 'low', 'desc' => __( 'Low Priority: Very high volume, can cause bloat', 'snn' ) ),
        'post_status_change'      => array( 'level' => 'low', 'desc' => __( 'Low Priority: Frequent on editorial sites', 'snn' ) ),
        'attachment_uploaded'     => array( 'level' => 'low', 'desc' => __( 'Low Priority: High volume, mainly informational', 'snn' ) ),
        'attachment_updated'      => array( 'level' => 'low', 'desc' => __( 'Low Priority: Frequent updates, less critical', 'snn' ) ),
        'comment_posted'          => array( 'level' => 'low', 'desc' => __( 'Low Priority: High volume on active sites', 'snn' ) ),
        'comment_approved'        => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine moderation action', 'snn' ) ),
        'comment_unapproved'      => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine moderation action', 'snn' ) ),
        'comment_trashed'         => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine moderation action', 'snn' ) ),
        'comment_spammed'         => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine spam filtering', 'snn' ) ),
        'theme_updated'           => array( 'level' => 'low', 'desc' => __( 'Low Priority: Regular maintenance activity', 'snn' ) ),
        'widget_updated'          => array( 'level' => 'low', 'desc' => __( 'Low Priority: Minor configuration changes', 'snn' ) ),
        'menu_updated'            => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine navigation changes', 'snn' ) ),
        'term_created'            => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine taxonomy management', 'snn' ) ),
        'term_edited'             => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine taxonomy updates', 'snn' ) ),
        'cron_executed'           => array( 'level' => 'low', 'desc' => __( 'Low Priority: Very high volume, mainly for debugging', 'snn' ) ),
        'db_query_error'          => array( 'level' => 'low', 'desc' => __( 'Low Priority: For debugging database issues', 'snn' ) ),
        'db_optimization'         => array( 'level' => 'low', 'desc' => __( 'Low Priority: Routine maintenance activity', 'snn' ) ),
    );
}

/**
 * Defines and returns an array of all available logging options, categorized for better organization.
 * Each option has a unique key and a human-readable label.
 *
 * @return array Associative array of logging options.
 */
function snn_get_logging_options() {
    return array(
        'user_activities' => array(
            'user_login'            => __( 'User Login', 'snn' ),
            'user_logout'           => __( 'User Logout', 'snn' ),
            'user_register'         => __( 'User Registration', 'snn' ),
            'user_profile_update'   => __( 'User Profile Updates', 'snn' ),
            'user_deleted'          => __( 'User Deletion', 'snn' ),
            'user_role_change'      => __( 'User Role Changes', 'snn' ),
            'password_reset'        => __( 'Password Reset Requests', 'snn' ),
            'failed_login'          => __( 'Failed Login Attempts', 'snn' ),
        ),
        'content_activities' => array(
            'post_created'          => __( 'Post/Page Creation', 'snn' ),
            'post_updated'          => __( 'Post/Page Updates', 'snn' ),
            'post_deleted'          => __( 'Post/Page Deletion', 'snn' ),
            'post_trashed'          => __( 'Post/Page Trashed', 'snn' ),
            'post_status_change'    => __( 'Post Status Changes', 'snn' ),
            'attachment_uploaded'   => __( 'Media Uploads', 'snn' ),
            'attachment_deleted'    => __( 'Media Deletion', 'snn' ),
            'attachment_updated'    => __( 'Media Updates', 'snn' ),
        ),
        'comment_activities' => array(
            'comment_posted'        => __( 'Comment Posted', 'snn' ),
            'comment_approved'      => __( 'Comment Approved', 'snn' ),
            'comment_unapproved'    => __( 'Comment Unapproved', 'snn' ),
            'comment_trashed'       => __( 'Comment Trashed', 'snn' ),
            'comment_spammed'       => __( 'Comment Marked as Spam', 'snn' ),
            'comment_deleted'       => __( 'Comment Deleted', 'snn' ),
        ),
        'system_activities' => array(
            'plugin_activated'      => __( 'Plugin Activation', 'snn' ),
            'plugin_deactivated'    => __( 'Plugin Deactivation', 'snn' ),
            'plugin_deleted'        => __( 'Plugin Deletion', 'snn' ),
            'theme_switched'        => __( 'Theme Switch', 'snn' ),
            'theme_updated'         => __( 'Theme Updates', 'snn' ),
            'theme_deleted'         => __( 'Theme Deletion', 'snn' ),
            'core_updated'          => __( 'WordPress Core Updates', 'snn' ),
            'widget_updated'        => __( 'Widget Changes', 'snn' ),
            'menu_updated'          => __( 'Menu Updates', 'snn' ),
            'option_updated'        => __( 'Settings Changes', 'snn' ),
            'export_performed'      => __( 'Data Exports', 'snn' ),
            'cron_executed'         => __( 'Scheduled Tasks (Cron)', 'snn' ),
        ),
        'security_activities' => array(
            'file_edited'           => __( 'Theme/Plugin File Edits', 'snn' ),
            'user_capability_change' => __( 'User Capability Changes', 'snn' ),
            'privacy_request'       => __( 'Privacy Data Requests', 'snn' ),
            'privacy_erase'         => __( 'Privacy Data Erasure', 'snn' ),
            'application_password'  => __( 'Application Password Events', 'snn' ),
        ),
        'taxonomy_activities' => array(
            'term_created' => __( 'Category/Tag Creation', 'snn' ),
            'term_edited'  => __( 'Category/Tag Updates', 'snn' ),
            'term_deleted' => __( 'Category/Tag Deletion', 'snn' ),
        ),
        'database_activities' => array(
            'db_query_error'  => __( 'Database Query Errors', 'snn' ),
            'db_optimization' => __( 'Database Optimization', 'snn' ),
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

    // Add user agent for security-related logs
    $security_log_types = array(
        'failed_login', 'user_login', 'user_logout', 'user_register', 
        'user_deleted', 'user_role_change', 'password_reset',
        'file_edited', 'user_capability_change', 'application_password',
        'plugin_activated', 'plugin_deactivated', 'plugin_deleted',
        'theme_switched', 'option_updated'
    );
    if ( in_array( $log_type, $security_log_types ) ) {
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
 * Custom search filter to search in both title and content for activity logs.
 * This allows searching across user names, actions, IP addresses, and details.
 *
 * @param string $search The search SQL.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified search SQL.
 */
function snn_activity_log_search_filter( $search, $query ) {
    global $wpdb;
    
    if ( ! $query->is_main_query() || empty( $query->query['s'] ) ) {
        return $search;
    }
    
    $search_term = $wpdb->esc_like( $query->query['s'] );
    $search_term = '%' . $search_term . '%';
    
    // Search in both post_title and post_content
    $search = " AND (({$wpdb->posts}.post_title LIKE %s) OR ({$wpdb->posts}.post_content LIKE %s))";
    $search = $wpdb->prepare( $search, $search_term, $search_term );
    
    return $search;
}

/**
 * Export activity logs to CSV file.
 * Exports all logs (respecting current filters) to a downloadable CSV.
 */
function snn_export_activity_log_csv() {
    // Query all logs (no pagination limit for export)
    $args = array(
        'post_type'      => 'snn_activity_log',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Apply same filters as the display
    if ( ! empty( $_GET['s'] ) ) {
        $search_term = sanitize_text_field( $_GET['s'] );
        $args['s'] = $search_term;
        add_filter( 'posts_search', 'snn_activity_log_search_filter', 10, 2 );
    }

    if ( ! empty( $_GET['date_from'] ) || ! empty( $_GET['date_to'] ) ) {
        $date_query = array();
        if ( ! empty( $_GET['date_from'] ) ) {
            $date_query['after'] = sanitize_text_field( $_GET['date_from'] );
        }
        if ( ! empty( $_GET['date_to'] ) ) {
            $date_query['before'] = sanitize_text_field( $_GET['date_to'] ) . ' 23:59:59';
        }
        $date_query['inclusive'] = true;
        $args['date_query'] = array( $date_query );
    }

    $logs = new WP_Query( $args );

    if ( ! empty( $_GET['s'] ) ) {
        remove_filter( 'posts_search', 'snn_activity_log_search_filter', 10 );
    }

    // Set headers for CSV download
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=activity-logs-' . date( 'Y-m-d-His' ) . '.csv' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    // Open output stream
    $output = fopen( 'php://output', 'w' );

    // Add BOM for UTF-8 Excel compatibility
    fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

    // Write CSV headers
    fputcsv( $output, array( 'Date', 'User', 'Action', 'Object', 'Object ID', 'IP Address', 'User Agent' ) );

    // Write data rows
    if ( $logs->have_posts() ) {
        while ( $logs->have_posts() ) {
            $logs->the_post();
            
            // Parse title to extract user and action
            $title_parts = explode( ' || ', get_the_title(), 2 );
            $user_info = isset( $title_parts[0] ) ? $title_parts[0] : 'N/A';
            $action = isset( $title_parts[1] ) ? $title_parts[1] : 'Unknown Action';
            
            // Parse content to extract details
            $content = get_the_content();
            $ip_address = 'N/A';
            $user_agent = 'N/A';
            $object = '';
            $object_id = '';
            
            // Extract IP address
            if ( preg_match( '/IP Address: ([^\n]+)/', $content, $matches ) ) {
                $ip_address = trim( $matches[1] );
            }
            
            // Extract User Agent
            if ( preg_match( '/User Agent: ([^\n]+)/', $content, $matches ) ) {
                $user_agent = trim( $matches[1] );
            }
            
            // Extract Object
            if ( preg_match( '/Object: ([^\n]+)/', $content, $matches ) ) {
                $object = trim( $matches[1] );
            }
            
            // Extract Object ID
            if ( preg_match( '/Object ID: ([^\n]+)/', $content, $matches ) ) {
                $object_id = trim( $matches[1] );
            }
            
            fputcsv( $output, array(
                get_the_date( 'Y-m-d H:i:s' ),
                $user_info,
                $action,
                $object,
                $object_id,
                $ip_address,
                $user_agent
            ) );
        }
        wp_reset_postdata();
    }

    fclose( $output );
    exit; // Stop execution to prevent HTML from being included in the CSV
}

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
        echo '<div class="updated notice is-dismissible"><p>' . __( 'Activity log cleared.', 'snn' ) . '</p></div>';
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
                    <th scope="row"><?php _e( 'Enable Activity Log', 'snn' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="snn_activity_log_enable" value="1" <?php checked( 1, get_option( 'snn_activity_log_enable' ), true ); ?> />
                            <?php _e( 'Enable the activity logging feature.', 'snn' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Log Limit', 'snn' ); ?></th>
                    <td>
                        <input type="number" name="snn_activity_log_limit" value="<?php echo esc_attr( get_option( 'snn_activity_log_limit', 1000 ) ); ?>" class="small-text" />
                        <p class="description"><?php _e( 'Maximum number of log entries to keep. Oldest entries will be deleted automatically.', 'snn' ); ?></p>
                    </td>
                </tr>
            </table>


            <div class="snn-accordion">
                <div class="snn-accordion-item">
                    <h3 class="snn-accordion-header">
                        <button type="button" class="snn-accordion-button" aria-expanded="false">
                            <span class="snn-accordion-title"><?php _e( 'All Logging Options', 'snn' ); ?></span>
                            <span class="snn-accordion-icon">▼</span>
                        </button>
                    </h3>
                    <div class="snn-accordion-content" style="display: none;">
                        <div style="padding: 10px 0; border-bottom: 1px solid #e1e4e8;">
                            <button type="button" id="snn-select-all-logs" class="button"><?php _e( 'Select All', 'snn' ); ?></button>
                            <button type="button" id="snn-deselect-all-logs" class="button"><?php _e( 'Deselect All', 'snn' ); ?></button>
                        </div>
                        <table class="form-table snn-logging-options">
                            <?php
                            $logging_options = snn_get_logging_options();
                            $severity_info = snn_get_log_severity_info();
                            foreach ( $logging_options as $category => $options ) :
                                foreach ( $options as $key => $label ) : 
                                    $severity = isset( $severity_info[$key] ) ? $severity_info[$key] : array( 'level' => 'low', 'desc' => '' );
                                    $severity_class = 'snn-severity-' . $severity['level'];
                                    ?>
                                    <tr>
                                        <th scope="row">
                                            <span class="snn-severity-indicator <?php echo esc_attr( $severity_class ); ?>" title="<?php echo esc_attr( $severity['desc'] ); ?>">
                                                <span class="snn-severity-dot"></span>
                                            </span>
                                            <?php echo esc_html( $label ); ?>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="snn_log_<?php echo esc_attr( $key ); ?>" value="1" <?php checked( 1, get_option( 'snn_log_' . $key, true ), true ); ?> />
                                                <?php _e( 'Enable', 'snn' ); ?>
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

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <form method="post" style="margin: 0;">
                <?php wp_nonce_field( 'snn_clear_log_action', 'snn_clear_log_nonce' ); ?>
                <?php submit_button( __( 'Clear All Logs', 'snn' ), 'delete', 'snn-clear-log', false ); ?>
            </form>
            <form method="post" style="margin: 0;">
                <?php wp_nonce_field( 'snn_export_csv_action', 'snn_export_csv_nonce' ); ?>
                <?php submit_button( __( 'Export to CSV', 'snn' ), 'secondary', 'snn-export-csv', false ); ?>
            </form>
        </div>

        <hr>

        <div style="margin-bottom: 1em;">
            <h2><?php _e( 'Recent Activity', 'snn' ); ?></h2>
            <form method="get" action="" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-top: 15px;">
                <input type="hidden" name="page" value="snn-activity-log" />
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="snn-log-search-input" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Search:', 'snn'); ?></label>
                        <input type="search" id="snn-log-search-input" name="s" value="<?php echo esc_attr( isset( $_GET['s'] ) ? $_GET['s'] : '' ); ?>" placeholder="<?php _e('Search logs...', 'snn'); ?>" style="width: 100%;" />
                    </div>
                    <div>
                        <label for="snn-log-date-from" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('From Date:', 'snn'); ?></label>
                        <input type="date" id="snn-log-date-from" name="date_from" value="<?php echo esc_attr( isset( $_GET['date_from'] ) ? $_GET['date_from'] : '' ); ?>" style="width: 155px;" />
                    </div>
                    <div>
                        <label for="snn-log-date-to" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('To Date:', 'snn'); ?></label>
                        <input type="date" id="snn-log-date-to" name="date_to" value="<?php echo esc_attr( isset( $_GET['date_to'] ) ? $_GET['date_to'] : '' ); ?>" style="width: 155px;" />
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <?php submit_button( __( 'Filter', 'snn' ), 'primary', 'filter', false ); ?>
                        <?php if ( ! empty( $_GET['s'] ) || ! empty( $_GET['date_from'] ) || ! empty( $_GET['date_to'] ) ) : ?>
                            <a href="<?php echo admin_url( 'admin.php?page=snn-activity-log' ); ?>" class="button"><?php _e( 'Reset', 'snn' ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width:180px;"><?php _e( 'Date', 'snn' ); ?></th>
                    <th scope="col" style="width:140px;"><?php _e( 'User', 'snn' ); ?></th>
                    <th scope="col" style="width:250px;"><?php _e( 'Action', 'snn' ); ?></th>
                    <th scope="col" style="width:140px;"><?php _e( 'IP Address', 'snn' ); ?></th>
                    <th scope="col"><?php _e( 'Details', 'snn' ); ?></th>
                </tr>
            </thead>
            <tbody id="snn-log-list">
                <?php
                // Get current page number for pagination
                $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
                
                // Query arguments to retrieve recent activity logs.
                $args = array(
                    'post_type'      => 'snn_activity_log',
                    'posts_per_page' => 50, // Display 50 logs per page for better performance
                    'orderby'        => 'date',
                    'order'          => 'DESC', // Order by newest first.
                    'paged'          => $paged,
                );

                // Add search query if present
                if ( ! empty( $_GET['s'] ) ) {
                    $search_term = sanitize_text_field( $_GET['s'] );
                    $args['s'] = $search_term;
                    // Also search in post content
                    add_filter( 'posts_search', 'snn_activity_log_search_filter', 10, 2 );
                }

                // Add date range filtering if present
                if ( ! empty( $_GET['date_from'] ) || ! empty( $_GET['date_to'] ) ) {
                    $date_query = array();
                    if ( ! empty( $_GET['date_from'] ) ) {
                        $date_query['after'] = sanitize_text_field( $_GET['date_from'] );
                    }
                    if ( ! empty( $_GET['date_to'] ) ) {
                        $date_query['before'] = sanitize_text_field( $_GET['date_to'] ) . ' 23:59:59';
                    }
                    $date_query['inclusive'] = true;
                    $args['date_query'] = array( $date_query );
                }

                $logs = new WP_Query( $args );
                
                // Remove search filter
                if ( ! empty( $_GET['s'] ) ) {
                    remove_filter( 'posts_search', 'snn_activity_log_search_filter', 10 );
                }
                if ( $logs->have_posts() ) :
                    while ( $logs->have_posts() ) : $logs->the_post();
                        // Safely explode the title using the robust separator to get user info and action.
                        $title_parts = explode(' || ', get_the_title(), 2);
                        $user_info = isset($title_parts[0]) ? $title_parts[0] : 'N/A';
                        $action = isset($title_parts[1]) ? $title_parts[1] : 'Unknown Action';
                        
                        // Extract IP address from content
                        $content = get_the_content();
                        $ip_address = 'N/A';
                        if ( preg_match( '/IP Address: ([0-9.]+|[0-9a-fA-F:]+)/', $content, $matches ) ) {
                            $ip_address = $matches[1];
                        }
                        
                        // Get human-readable time difference
                        $post_time = get_post_time( 'U' );
                        $human_time = human_time_diff( $post_time, current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'snn' );
                        ?>
                        <tr class="snn-log-entry">
                            <td>
                                <div style="font-size: 11px; color: #666; margin-bottom: 2px;"><?php echo esc_html( $human_time ); ?></div>
                                <div><?php echo get_the_date( 'd.M.Y' ); ?><br><?php echo get_the_date( 'H:i:s' ); ?></div>
                            </td>
                            <td><?php echo esc_html( $user_info ); ?></td>
                            <td><?php echo esc_html( $action ); ?></td>
                            <td>
                                <?php if ( $ip_address !== 'N/A' ) : ?>
                                    <a href="https://radar.cloudflare.com/ip/<?php echo esc_attr( $ip_address ); ?>" target="_blank" rel="noopener noreferrer" title="<?php _e( 'View IP details on Cloudflare Radar', 'snn' ); ?>">
                                        <?php echo esc_html( $ip_address ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $ip_address ); ?>
                                <?php endif; ?>
                            </td>
                            <td><pre><?php echo esc_html( $content ); ?></pre></td>
                        </tr>
                    <?php endwhile;
                    wp_reset_postdata(); // Restore original post data.
                else : ?>
                    <tr id="snn-no-logs-found">
                        <td colspan="5"><?php _e( 'No activity logged yet.', 'snn' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        // Display pagination if there are multiple pages
        if ( $logs->max_num_pages > 1 ) :
            $big = 999999999; // need an unlikely integer
            // Preserve search and filter parameters in pagination
            $add_args = array();
            if ( ! empty( $_GET['s'] ) ) {
                $add_args['s'] = sanitize_text_field( $_GET['s'] );
            }
            if ( ! empty( $_GET['date_from'] ) ) {
                $add_args['date_from'] = sanitize_text_field( $_GET['date_from'] );
            }
            if ( ! empty( $_GET['date_to'] ) ) {
                $add_args['date_to'] = sanitize_text_field( $_GET['date_to'] );
            }
            $pagination_args = array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $paged,
                'total'     => $logs->max_num_pages,
                'prev_text' => __( '&laquo; Previous', 'snn' ),
                'next_text' => __( 'Next &raquo;', 'snn' ),
                'type'      => 'plain',
                'add_args'  => $add_args,
            );
            ?>
            <div class="snn-pagination" style="margin-top: 20px;">
                <?php echo paginate_links( $pagination_args ); ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        /* Styles for severity indicators */
        .snn-severity-indicator {
            display: inline-block;
            position: relative;
            margin-right: 8px;
            cursor: help;
            vertical-align: middle;
        }

        .snn-severity-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .snn-severity-critical .snn-severity-dot {
            background-color: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
        }

        .snn-severity-important .snn-severity-dot {
            background-color: #ffc107;
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
        }

        .snn-severity-low .snn-severity-dot {
            background-color: #6c757d;
            box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.2);
        }

        /* Tooltip styles */
        .snn-severity-indicator::before {
            content: attr(title);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background: #2c3338;
            color: #fff;
            font-size: 12px;
            line-height: 1.4;
            border-radius: 4px;
            white-space: nowrap;
            max-width: 300px;
            white-space: normal;
            width: max-content;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 99999999;
            pointer-events: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .snn-severity-indicator::after {
            content: '';
            position: absolute;
            bottom: 115%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #2c3338;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 1000;
            pointer-events: none;
        }

        .snn-severity-indicator:hover::before,
        .snn-severity-indicator:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Adjust tooltip position for better visibility */
        .snn-logging-options tr:first-child .snn-severity-indicator::before {
            bottom: auto;
            top: 125%;
        }

        .snn-logging-options tr:first-child .snn-severity-indicator::after {
            bottom: auto;
            top: 115%;
            border-top-color: transparent;
            border-bottom-color: #2c3338;
        }

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

        /* Pagination styles */
        .snn-pagination {
            text-align: center;
            padding: 20px 0;
        }

        .snn-pagination .page-numbers {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            background: #fff;
            color: #0073aa;
            text-decoration: none;
            border-radius: 3px;
            transition: all 0.2s;
        }

        .snn-pagination .page-numbers:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }

        .snn-pagination .page-numbers.current {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
            font-weight: 600;
        }

        .snn-pagination .page-numbers.dots {
            border: none;
            background: transparent;
            color: #555;
            cursor: default;
        }

        .snn-pagination .page-numbers.dots:hover {
            background: transparent;
            color: #555;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

