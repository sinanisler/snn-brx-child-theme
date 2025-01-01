<?php

function snn_register_mail_logs_post_type() {
    register_post_type('snn_mail_logs', array(
        'public'  => false,
        'show_ui' => false
    ));
}
add_action('init', 'snn_register_mail_logs_post_type');

function snn_add_mail_logs_page() {
    add_submenu_page(
        'snn-settings',
        'Mail Logs',
        'Mail Logs',
        'manage_options',
        'snn-mail-logs',
        'snn_render_mail_logs_page'
    );
}
add_action('admin_menu', 'snn_add_mail_logs_page');

function snn_handle_mail_logs_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['snn_mail_logging_submit'])) {
        if (isset($_POST['snn_mail_logging_enabled'])) {
            update_option('snn_mail_logging_enabled', '1');
        } else {
            update_option('snn_mail_logging_enabled', '0');
        }
    }

    if (isset($_POST['snn_mail_log_size_limit'])) {
        $size_limit = intval($_POST['snn_mail_log_size_limit']);
        if ($size_limit < 1) {
            $size_limit = 100;
        }
        update_option('snn_mail_log_size_limit', $size_limit);
    }

    if (isset($_POST['snn_clear_mail_logs'])) {
        $args = array(
            'post_type'      => 'snn_mail_logs',
            'posts_per_page' => -1,
            'post_status'    => 'any'
        );

        $logs = get_posts($args);
        foreach ($logs as $log) {
            wp_delete_post($log->ID, true);
        }
    }
}
add_action('admin_init', 'snn_handle_mail_logs_actions');

function snn_log_mail_event($to, $subject, $message, $headers, $attachments) {
    if (get_option('snn_mail_logging_enabled') !== '1') {
        return;
    }

    $size_limit = get_option('snn_mail_log_size_limit', 100);
    snn_cleanup_old_mail_logs($size_limit);

    $post_data = array(
        'post_type'   => 'snn_mail_logs',
        'post_status' => 'publish',
        'post_title'  => 'Mail Log - ' . date('Y-m-d H:i:s')
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        update_post_meta($post_id, 'date_time', date('Y-m-d H:i:s'));
        update_post_meta($post_id, 'to', $to);
        update_post_meta($post_id, 'subject', $subject);
        update_post_meta($post_id, 'message', $message);
        update_post_meta($post_id, 'headers', maybe_serialize($headers));
        update_post_meta($post_id, 'attachments', maybe_serialize($attachments));
    }
}
add_action('wp_mail', 'snn_log_mail_event', 10, 5);

function snn_cleanup_old_mail_logs($limit) {
    $args = array(
        'post_type'      => 'snn_mail_logs',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'post_status'    => 'any'
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

function snn_render_mail_logs_page() {
    $logging_enabled = get_option('snn_mail_logging_enabled') === '1';
    $log_size_limit  = get_option('snn_mail_log_size_limit', 100);
    ?>
    <div class="wrap">
        <h1>Mail Logs</h1>

        <form method="post" action="">
            <label>
                <input type="checkbox" name="snn_mail_logging_enabled" 
                       <?php checked($logging_enabled); ?>>
                Enable Mail Logging
            </label>
            <br><br>
            
            <label>
                Maximum number of logs to keep:
                <input type="number" name="snn_mail_log_size_limit" 
                       value="<?php echo esc_attr($log_size_limit); ?>" 
                       min="1" style="width: 100px;">
            </label>
            <br><br>
            
            <?php submit_button('Save Changes', 'primary', 'snn_mail_logging_submit', false); ?>
        </form>

        <?php if ($logging_enabled): ?>
            <div class="tablenav top">
                <form method="post" action="" style="float: left;">
                    <?php submit_button('Clear All Logs', 'delete', 'snn_clear_mail_logs', false); ?>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Headers</th>
                        <th>Attachments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $args = array(
                        'post_type'      => 'snn_mail_logs',
                        'posts_per_page' => 100,
                        'orderby'        => 'date',
                        'order'          => 'DESC'
                    );
                    $logs = get_posts($args);
                    foreach ($logs as $log) {
                        ?>
                        <tr>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'date_time', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'to', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'subject', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'message', true)); ?></td>
                            <td><?php echo esc_html(maybe_unserialize(get_post_meta($log->ID, 'headers', true))); ?></td>
                            <td><?php echo esc_html(maybe_unserialize(get_post_meta($log->ID, 'attachments', true))); ?></td>
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
