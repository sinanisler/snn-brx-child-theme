<?php
// Register the custom post type for 404 logs
function snn_register_404_logs_post_type() {
    register_post_type('snn_404_logs', array(
        'public' => false,
        'show_ui' => false
    ));
}
add_action('init', 'snn_register_404_logs_post_type');

// Add submenu page
function snn_add_404_logs_page() {
    add_submenu_page(
        'snn-settings',
        '404 Logs',
        '404 Logs',
        'manage_options',
        'snn-404-logs',
        'snn_render_404_logs_page'
    );
}
add_action('admin_menu', 'snn_add_404_logs_page');

// Handle form submissions
function snn_handle_404_logs_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['snn_404_logging_enabled'])) {
        update_option('snn_404_logging_enabled', '1');
    } else if (isset($_POST['snn_404_logging_submit'])) {
        update_option('snn_404_logging_enabled', '0');
    }

    // Handle log size limit setting
    if (isset($_POST['snn_404_log_size_limit'])) {
        $size_limit = intval($_POST['snn_404_log_size_limit']);
        if ($size_limit < 1) {
            $size_limit = 100; // Minimum size of 1
        }
        update_option('snn_404_log_size_limit', $size_limit);
    }

    if (isset($_POST['snn_clear_404_logs'])) {
        $args = array(
            'post_type' => 'snn_404_logs',
            'posts_per_page' => -1,
            'post_status' => 'any'
        );
        
        $logs = get_posts($args);
        foreach ($logs as $log) {
            wp_delete_post($log->ID, true);
        }
    }
}
add_action('admin_init', 'snn_handle_404_logs_actions');

// Function to cleanup old logs when limit is reached
function snn_cleanup_old_logs($limit) {
    $args = array(
        'post_type' => 'snn_404_logs',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC', // Get oldest first
        'post_status' => 'any'
    );
    
    $logs = get_posts($args);
    $total_logs = count($logs);
    
    if ($total_logs >= $limit) {
        $logs_to_delete = array_slice($logs, 0, $total_logs - $limit + 1);
        foreach ($logs_to_delete as $log) {
            wp_delete_post($log->ID, true);
        }
    }
}

// Log 404 errors
function snn_log_404_error() {
    if (is_404() && get_option('snn_404_logging_enabled') === '1') {
        // Get size limit (default 100)
        $size_limit = get_option('snn_404_log_size_limit', 100);
        
        // Cleanup old logs if necessary
        snn_cleanup_old_logs($size_limit);
        
        $post_data = array(
            'post_type' => 'snn_404_logs',
            'post_status' => 'publish',
            'post_title' => '404 Error - ' . date('Y-m-d H:i:s')
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            update_post_meta($post_id, 'url', $_SERVER['REQUEST_URI']);
            update_post_meta($post_id, 'date_time', date('Y-m-d H:i:s'));
            update_post_meta($post_id, 'referrer', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a');
            update_post_meta($post_id, 'ip_address', $_SERVER['REMOTE_ADDR']);
            update_post_meta($post_id, 'user_agent', $_SERVER['HTTP_USER_AGENT']);
        }
    }
}
add_action('template_redirect', 'snn_log_404_error');

// Render the admin page
function snn_render_404_logs_page() {
    $logging_enabled = get_option('snn_404_logging_enabled') === '1';
    $log_size_limit = get_option('snn_404_log_size_limit', 100);
    ?>
    <div class="wrap">
        <h1>404 Logs</h1>

        <form method="post" action="">
            <label>
                <input type="checkbox" name="snn_404_logging_enabled" <?php checked($logging_enabled); ?> 
                       onclick="this.form.submit()">
                Enable 404 Logging
            </label><br><br>
            
            <label>
                Maximum number of logs to keep:
                <input type="number" name="snn_404_log_size_limit" value="<?php echo esc_attr($log_size_limit); ?>" 
                       min="1" style="width: 100px;">
            </label><br><br>
            
            <?php submit_button('Save Changes', 'primary', 'snn_404_logging_submit', false); ?>
        </form>

        <?php if ($logging_enabled): ?>
            <div class="tablenav top">
                <form method="post" action="" style="float: left;">
                    <?php submit_button('Clear All Logs', 'delete', 'snn_clear_404_logs', false); ?>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>URL</th>
                        <th>Referrer</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $args = array(
                        'post_type' => 'snn_404_logs',
                        'posts_per_page' => 100,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    );

                    $logs = get_posts($args);
                    foreach ($logs as $log) {
                        ?>
                        <tr>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'date_time', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'url', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'referrer', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'ip_address', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'user_agent', true)); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}