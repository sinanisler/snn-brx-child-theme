<?php

function snn_register_404_logs_post_type() {
    register_post_type('snn_404_logs', array(
        'public'  => false,
        'show_ui' => false
    ));
}
add_action('init', 'snn_register_404_logs_post_type');

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

function snn_handle_404_logs_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['snn_404_logging_submit'])) {
        if (isset($_POST['snn_404_logging_enabled'])) {
            update_option('snn_404_logging_enabled', '1');
        } else {
            update_option('snn_404_logging_enabled', '0');
        }

        // Handle disable bot/crawler logging option
        if (isset($_POST['snn_disable_bot_logging'])) {
            update_option('snn_disable_bot_logging', '1');
        } else {
            update_option('snn_disable_bot_logging', '0');
        }

        // Save bot blocklist from textarea
        if (isset($_POST['snn_bot_blocklist'])) {
            $bot_blocklist = sanitize_textarea_field($_POST['snn_bot_blocklist']);
            update_option('snn_bot_logging_blocklist', $bot_blocklist);
        }
    }

    if (isset($_POST['snn_404_log_size_limit'])) {
        $size_limit = intval($_POST['snn_404_log_size_limit']);
        if ($size_limit < 1) {
            $size_limit = 100;
        }
        update_option('snn_404_log_size_limit', $size_limit);
    }

    if (isset($_POST['snn_clear_404_logs'])) {
        $args = array(
            'post_type'      => 'snn_404_logs',
            'posts_per_page' => -1,
            'post_status'    => 'any'
        );

        $logs = get_posts($args);
        foreach ($logs as $log) {
            wp_delete_post($log->ID, true);
        }
    }
    
    // Delete logs based on IP or User Agent match
    if (isset($_POST['snn_delete_logs']) && !empty($_POST['snn_delete_logs_value'])) {
        $delete_value = sanitize_text_field($_POST['snn_delete_logs_value']);
        $args = array(
            'post_type'      => 'snn_404_logs',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'ip_address',
                    'value'   => $delete_value,
                    'compare' => '='
                ),
                array(
                    'key'     => 'user_agent',
                    'value'   => $delete_value,
                    'compare' => 'LIKE'
                )
            )
        );
        $logs = get_posts($args);
        foreach ($logs as $log) {
            wp_delete_post($log->ID, true);
        }
    }
}
add_action('admin_init', 'snn_handle_404_logs_actions');

function snn_404_normalize_path($url) {
    $url = preg_replace('/^https?:\/\/[^\/]+/i', '', $url);

    if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
    }

    if ($url !== '/' && substr($url, -1) === '/') {
        $url = rtrim($url, '/');
    }
    return strtolower($url);
}

function snn_has_301_redirect($request_uri) {
    $normalized_path = snn_404_normalize_path($request_uri);
    $path_without_query = strtok($normalized_path, '?');

    $redirects = get_posts(array(
        'post_type'      => 'snn_301_redirects',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => 'redirect_from',
                'value'   => $path_without_query,
                'compare' => '='
            )
        )
    ));

    return !empty($redirects);
}

function snn_cleanup_old_logs($limit) {
    $args = array(
        'post_type'      => 'snn_404_logs',
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

function snn_log_404_error() {
    if (is_404() && get_option('snn_404_logging_enabled') === '1') {

        if (snn_has_301_redirect($_SERVER['REQUEST_URI'])) {
            return;
        }

        if (get_option('snn_disable_bot_logging') === '1' && isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            
            // Retrieve bot blocklist from option or use default list if not set
            $bot_blocklist = get_option('snn_bot_logging_blocklist', '');
            if (empty($bot_blocklist)) {
                $bot_blocklist = "gptbot\ngooglebot\nyandexbot\nbytespider\nspider\nanthill\npetalbot\nsemrushbot\nahrefsbot\nbingbot\nimagesiftbot\nbarkrowler\nawariosmartbot\nsogou\ntimpibot\nseznambot\ntwitterbot\nxbot\ndataforseobot\nmeta-externalagent\nfacebook";
            }
            $bots = array_filter(array_map('trim', explode("\n", $bot_blocklist)));

            foreach ($bots as $bot) {
                if (!empty($bot) && strpos($user_agent, strtolower($bot)) !== false) {
                    return;
                }
            }
        }

        $size_limit = get_option('snn_404_log_size_limit', 100);
        snn_cleanup_old_logs($size_limit);

        $post_data = array(
            'post_type'   => 'snn_404_logs',
            'post_status' => 'publish',
            'post_title'  => '404 Error - ' . date('Y-m-d H:i:s')
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

function snn_render_404_logs_page() {
    $logging_enabled     = get_option('snn_404_logging_enabled') === '1';
    $log_size_limit      = get_option('snn_404_log_size_limit', 100);
    $disable_bot_logging = get_option('snn_disable_bot_logging') === '1';
    $bot_blocklist = get_option('snn_bot_logging_blocklist', '');
    if (empty($bot_blocklist)) {
        $bot_blocklist = "gptbot\ngooglebot\nyandexbot\nbytespider\nspider\nanthill\npetalbot\nsemrushbot\nahrefsbot\nbingbot\nimagesiftbot\nbarkrowler\nawariosmartbot\nsogou\ntimpibot\nseznambot\ntwitterbot\nxbot\ndataforseobot\nmeta-externalagent\nfacebook";
    }
    ?>
    <div class="wrap">
        <h1>404 Logs</h1>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div style="flex: 1; margin-right: 20px;">
                <!-- Settings Form -->
                <form method="post" action="">
                    <p>
                        <label>
                            <input type="checkbox" name="snn_404_logging_enabled" <?php checked($logging_enabled); ?>>
                            Enable 404 Logging
                        </label>
                    </p>
                    <p>
                        <label>
                            Maximum number of logs to keep:
                            <input type="number" name="snn_404_log_size_limit" value="<?php echo esc_attr($log_size_limit); ?>" min="1" style="width: 100px;">
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="snn_disable_bot_logging" <?php checked($disable_bot_logging); ?>>
                            Disable Bots/Robots Logging (Don't enable this if the website is new; collect some URLs first for SEO)
                        </label>
                    </p>
                    <p id="bot_blocklist_container" <?php if (!$disable_bot_logging) echo 'style="display:none;"'; ?>>
                        <label for="snn_bot_blocklist">Bot Logs Blocklist (one per line):</label><br>
                        <textarea name="snn_bot_blocklist" id="snn_bot_blocklist" rows="4" cols="50"><?php echo esc_textarea($bot_blocklist); ?></textarea>
                    </p>
                    <?php submit_button('Save Changes', 'primary', 'snn_404_logging_submit', false); ?>
                </form>
                <br>
                <form method="post" action="">
                    <?php submit_button('Clear All Logs', 'delete', 'snn_clear_404_logs', false); ?>
                </form>
            </div>
            <div style="flex: 1;">
                <!-- Delete Logs by IP or User Agent Form -->
                <h2>Delete Logs by IP or User Agent</h2>
                <p>Enter an IP address or a part of a user agent string (e.g. "bingbot") to remove all matching logs.</p>
                <form method="post" action="">
                    <p>
                        <label for="snn_delete_logs_value">IP or User Agent:</label>
                        <input type="text" name="snn_delete_logs_value" id="snn_delete_logs_value" placeholder="Enter value">
                    </p>
                    <?php submit_button('Delete Logs', 'delete', 'snn_delete_logs', false); ?>
                </form>
            </div>
        </div>
        <?php if ($logging_enabled): ?>
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
                        'post_type'      => 'snn_404_logs',
                        'posts_per_page' => $log_size_limit,
                        'orderby'        => 'date',
                        'order'          => 'DESC'
                    );
                    $logs = get_posts($args);
                    foreach ($logs as $log) {
                        ?>
                        <tr>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'date_time', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'url', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'referrer', true)); ?></td>
                            <td>
                                <a href="https://radar.cloudflare.com/ip/<?php echo esc_html(get_post_meta($log->ID, 'ip_address', true)); ?>" target="_blank">
                                    <?php echo esc_html(get_post_meta($log->ID, 'ip_address', true)); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(get_post_meta($log->ID, 'user_agent', true)); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var checkbox = document.querySelector('input[name="snn_disable_bot_logging"]');
        var botBlocklistContainer = document.getElementById('bot_blocklist_container');
        
        function toggleBotBlocklist() {
            if (checkbox.checked) {
                botBlocklistContainer.style.display = 'block';
            } else {
                botBlocklistContainer.style.display = 'none';
            }
        }
        
        if (checkbox && botBlocklistContainer) {
            checkbox.addEventListener('change', toggleBotBlocklist);
            toggleBotBlocklist();
        }
    });
    </script>
    <?php
}
?>
