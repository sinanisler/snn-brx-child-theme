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

function snn_log_mail_event($args) {
    if (get_option('snn_mail_logging_enabled') !== '1') {
        return $args;
    }

    $to = isset($args['to']) ? $args['to'] : '';
    if (is_array($to)) {
        $to = implode(', ', $to);
    }

    $subject = isset($args['subject']) ? $args['subject'] : '';
    $message = isset($args['message']) ? $args['message'] : '';
    $headers = isset($args['headers']) ? $args['headers'] : array();

    $default_from_email = apply_filters('wp_mail_from', get_option('admin_email'));
    $default_from_name = apply_filters('wp_mail_from_name', get_bloginfo('name'));
    $from = $default_from_name . ' <' . $default_from_email . '>';

    if (is_array($headers)) {
        foreach ($headers as $header) {
            if (stripos($header, 'From:') === 0) {
                $from = trim(preg_replace('/From:\s*/i', '', $header));
                break;
            }
        }
    } else {
        if (stripos($headers, 'From:') === 0) {
            $from = trim(preg_replace('/From:\s*/i', '', $headers));
        }
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
        update_post_meta($post_id, 'from', $from);
        update_post_meta($post_id, 'to', $to);
        update_post_meta($post_id, 'subject', $subject);
        update_post_meta($post_id, 'message', $message);
        update_post_meta($post_id, 'headers', maybe_serialize($headers));
    }

    return $args;
}
add_filter('wp_mail', 'snn_log_mail_event', 10, 1);

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
    $log_size_limit = get_option('snn_mail_log_size_limit', 100);

    echo '<div class="wrap">';
    echo '<h1>Mail Logs</h1>';
    echo '<form method="post" action="">';
    echo '<label>';
    echo '<input type="checkbox" name="snn_mail_logging_enabled" ' . checked($logging_enabled, true, false) . '>';
    echo 'Enable Mail Logging';
    echo '</label>';
    echo '<br><br>';
    echo '<label>';
    echo 'Maximum number of logs to keep:';
    echo '<input type="number" name="snn_mail_log_size_limit" value="' . esc_attr($log_size_limit) . '" min="1" style="width: 100px;">';
    echo '</label>';
    echo '<br><br>';
    submit_button('Save Changes', 'primary', 'snn_mail_logging_submit', false);
    echo '</form>';

    if ($logging_enabled) {
        echo '<div class="tablenav top">';
        echo '<form method="post" action="" style="float: left;">';
        submit_button('Clear All Logs', 'delete', 'snn_clear_mail_logs', false);
        echo '</form>';
        echo '</div>';

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Date & Time</th>';
        echo '<th>From</th>';
        echo '<th>To</th>';
        echo '<th>Subject</th>';
        echo '<th>Message</th>';
        echo '<th>Headers</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $args = array(
            'post_type'      => 'snn_mail_logs',
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC'
        );
        $logs = get_posts($args);

        foreach ($logs as $log) {
            $date_time = get_post_meta($log->ID, 'date_time', true);
            $from = get_post_meta($log->ID, 'from', true);
            $to = get_post_meta($log->ID, 'to', true);
            $subject = get_post_meta($log->ID, 'subject', true);
            $message = get_post_meta($log->ID, 'message', true);
            $headers_data = maybe_unserialize(get_post_meta($log->ID, 'headers', true));

            echo '<tr>';
            echo '<td>' . esc_html($date_time) . '</td>';
            echo '<td>' . esc_html($from) . '</td>';
            echo '<td>' . esc_html($to) . '</td>';
            echo '<td>' . esc_html($subject) . '</td>';
            echo '<td class="log-message">' . esc_html($message) . '</td>';

            if (is_array($headers_data)) {
                echo '<td>' . esc_html(implode(', ', $headers_data)) . '</td>';
            } else {
                echo '<td>' . esc_html($headers_data) . '</td>';
            }

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    echo '</div>';
?>
<style>
.log-message{
    max-height:250px;
    overflow:auto;
}
</style>
<?php
}
