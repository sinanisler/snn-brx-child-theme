<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

function snn_register_activity_log_post_type() {
    $args = array(
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => false,
        'show_in_menu'        => false,
        'query_var'           => false,
        'rewrite'             => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'supports'            => array( 'title', 'editor' ),
    );
    register_post_type( 'snn_activity_log', $args );
}
add_action( 'init', 'snn_register_activity_log_post_type' );

/**
 * Add the submenu page to the 'snn-settings' parent menu.
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
 * Register the settings for the activity log page.
 */
function snn_activity_log_register_settings() {
    register_setting( 'snn_activity_log_options', 'snn_activity_log_enable' );
    register_setting( 'snn_activity_log_options', 'snn_activity_log_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1000,
    ) );
}
add_action( 'admin_init', 'snn_activity_log_register_settings' );


/**
 * The main function to log user activity.
 *
 * @param string $action The action performed by the user.
 * @param string $object The object the action was performed on.
 * @param int    $object_id The ID of the object.
 */
function snn_log_user_activity( $action, $object = '', $object_id = 0 ) {
    // Stop if the feature is disabled or if the action is empty.
    if ( ! get_option( 'snn_activity_log_enable' ) || empty( trim( $action ) ) ) {
        return;
    }

    $user = wp_get_current_user();
    
    if ( $user->ID ) {
        $user_info = "{$user->user_login} (ID: {$user->ID})";
    } else {
        $user_info = 'system';
    }

    // Using a more robust separator to avoid parsing issues.
    $log_title = "{$user_info} || {$action}";
    $log_content = "Object: {$object}\nObject ID: {$object_id}\nIP Address: " . ( $_SERVER['REMOTE_ADDR'] ?? 'N/A' );

    $post_id = wp_insert_post( array(
        'post_type'    => 'snn_activity_log',
        'post_title'   => wp_strip_all_tags( $log_title ),
        'post_content' => $log_content,
        'post_status'  => 'publish',
    ) );

    if ($post_id) {
        snn_trim_activity_log();
    }
}

/**
 * Trim the activity log to the specified limit.
 */
function snn_trim_activity_log() {
    $limit = get_option( 'snn_activity_log_limit', 1000 );

    $args = array(
        'post_type'      => 'snn_activity_log',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'fields'         => 'ids',
    );
    $log_posts = new WP_Query( $args );

    if ( $log_posts->post_count > $limit ) {
        $posts_to_delete = array_slice( $log_posts->posts, 0, $log_posts->post_count - $limit );
        foreach ( $posts_to_delete as $post_id ) {
            wp_delete_post( $post_id, true );
        }
    }
}

/**
 * Hooks for various user activities.
 */
// User login
add_action( 'wp_login', function( $user_login, $user ) {
    snn_log_user_activity( 'User Logged In', $user_login, $user->ID );
}, 10, 2 );

// User logout
add_action( 'wp_logout', function( $user_id ) {
    $user = get_user_by( 'id', $user_id );
    if ($user) {
        snn_log_user_activity( 'User Logged Out', $user->user_login, $user_id );
    }
});

// User registration
add_action( 'user_register', function( $user_id ) {
    $user = get_user_by( 'id', $user_id );
     if ($user) {
        snn_log_user_activity( 'User Registered', $user->user_login, $user_id );
    }
});

// Post/Page updated
add_action( 'post_updated', function( $post_id, $post_after, $post_before ) {
    // Ignore revisions, log entries, and items being trashed (handled by wp_trash_post)
    if ( wp_is_post_revision( $post_id ) || $post_after->post_type === 'snn_activity_log' || $post_after->post_status === 'trash' ) {
        return;
    }
    $post_type = get_post_type_object( $post_after->post_type );
    $action = ( $post_before->post_status === 'auto-draft' || $post_before->post_status === 'new' ) ? 'Created' : 'Updated';
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';

    snn_log_user_activity( "{$action_label} {$action}", $post_after->post_title, $post_id );
}, 10, 3 );

// Post/Page trashed
add_action( 'wp_trash_post', function( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return;
    }
    $post_type = get_post_type_object( $post->post_type );
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';
    snn_log_user_activity( "{$action_label} Trashed", $post->post_title, $post_id );
});

// Post/Page deleted permanently
add_action( 'delete_post', function( $post_id ) {
    $post = get_post( $post_id );
    // Prevent logging deletion of logs themselves or if post object is not found
    if ( ! $post || $post->post_type === 'snn_activity_log' ) {
        return;
    }
    $post_type = get_post_type_object( $post->post_type );
    $action_label = $post_type ? $post_type->labels->singular_name : 'Item';
    snn_log_user_activity( "{$action_label} Deleted Permanently", $post->post_title, $post_id );
});

// Plugin activated
add_action( 'activated_plugin', function( $plugin ) {
    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
    snn_log_user_activity( 'Plugin Activated', $plugin_data['Name'] ?? 'Unknown Plugin' );
});

// Plugin deactivated
add_action( 'deactivated_plugin', function( $plugin ) {
    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
    snn_log_user_activity( 'Plugin Deactivated', $plugin_data['Name'] ?? 'Unknown Plugin' );
});

// Theme switched
add_action( 'switch_theme', function( $new_name ) {
    snn_log_user_activity( 'Theme Switched', $new_name );
});


/**
 * HTML for the activity log page.
 */
function snn_activity_log_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle clearing the log
    if ( isset( $_POST['snn_clear_log_nonce'] ) && wp_verify_nonce( $_POST['snn_clear_log_nonce'], 'snn_clear_log_action' ) ) {
        global $wpdb;
        $post_type = 'snn_activity_log';
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = %s", $post_type ) );
        echo '<div class="updated notice is-dismissible"><p>' . __( 'Activity log cleared.', 'snn-activity-log' ) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'snn_activity_log_options' );
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
            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php _e( 'Clear Log', 'snn-activity-log' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'snn_clear_log_action', 'snn_clear_log_nonce' ); ?>
            <p><?php _e( 'This will permanently delete all activity log entries.', 'snn-activity-log' ); ?></p>
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
                $args = array(
                    'post_type'      => 'snn_activity_log',
                    'posts_per_page' => 1000, // Show last 1000 logs on this page
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                );
                $logs = new WP_Query( $args );
                if ( $logs->have_posts() ) :
                    while ( $logs->have_posts() ) : $logs->the_post();
                        // Safely explode the title using the new robust separator
                        $title_parts = explode(' || ', get_the_title(), 2);
                        $user_info = isset($title_parts[0]) ? $title_parts[0] : 'N/A';
                        $action = isset($title_parts[1]) ? $title_parts[1] : 'Unknown Action';
                        ?>
                        <tr class="snn-log-entry">
                            <td><?php echo get_the_date( 'Y-m-d H:i:s' ); ?></td>
                            <td><?php echo esc_html( $user_info ); ?></td>
                            <td><?php echo esc_html( $action ); ?></td>
                            <td><pre><?php echo esc_html( get_the_content() ); ?></pre></td>
                        </tr>
                    <?php endwhile;
                    wp_reset_postdata();
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('snn-log-search-input');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const logList = document.getElementById('snn-log-list');
                    const rows = logList.getElementsByClassName('snn-log-entry');
                    const noResultsRow = document.getElementById('snn-no-search-results');
                    let visibleCount = 0;

                    for (let i = 0; i < rows.length; i++) {
                        const rowText = rows[i].textContent.toLowerCase();
                        if (rowText.includes(filter)) {
                            rows[i].style.display = '';
                            visibleCount++;
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }

                    if (noResultsRow) {
                        if (visibleCount === 0 && rows.length > 0) {
                            noResultsRow.style.display = '';
                        } else {
                            noResultsRow.style.display = 'none';
                        }
                    }
                });
            }
        });
    </script>
    <?php
}
