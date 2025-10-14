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
        __('Mail Logs', 'snn'),
        __('Mail Logs', 'snn'),
        'manage_options',
        'snn-mail-logs',
        'snn_render_mail_logs_page'
    );
}
add_action('admin_menu', 'snn_add_mail_logs_page');

// AJAX handler to fetch mail message content
function snn_get_mail_message_ajax() {
    check_ajax_referer('snn_mail_logs_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'snn')));
        return;
    }
    
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    
    if (!$log_id) {
        wp_send_json_error(array('message' => __('Invalid log ID', 'snn')));
        return;
    }
    
    $message = get_post_meta($log_id, 'message', true);
    $headers_data = maybe_unserialize(get_post_meta($log_id, 'headers', true));
    
    $headers_html = '';
    if (is_array($headers_data)) {
        $headers_html = esc_html(implode(', ', $headers_data));
    } else {
        $headers_html = esc_html($headers_data);
    }
    
    wp_send_json_success(array(
        'message' => $message,
        'headers' => $headers_html
    ));
}
add_action('wp_ajax_snn_get_mail_message', 'snn_get_mail_message_ajax');

function snn_handle_mail_logs_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle enabling/disabling mail logging.
    if (isset($_POST['snn_mail_logging_submit'])) {
        if (isset($_POST['snn_mail_logging_enabled'])) {
            update_option('snn_mail_logging_enabled', '1');
        } else {
            update_option('snn_mail_logging_enabled', '0');
        }
    }

    // Handle mail log size limit.
    if (isset($_POST['snn_mail_log_size_limit'])) {
        $size_limit = intval($_POST['snn_mail_log_size_limit']);
        if ($size_limit < 1) {
            $size_limit = 100;
        }
        update_option('snn_mail_log_size_limit', $size_limit);
    }

    // Handle clearing all mail logs.
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

    // Handle deleting a single mail log.
    if (isset($_POST['snn_delete_log']) && isset($_POST['snn_delete_log_id'])) {
        $log_id = intval($_POST['snn_delete_log_id']);
        wp_delete_post($log_id, true);
    }
}
add_action('admin_init', 'snn_handle_mail_logs_actions');

function snn_log_mail_event($args) {
    // Only log if enabled.
    if (get_option('snn_mail_logging_enabled') !== '1') {
        return $args;
    }

    // Gather mail data.
    $to      = isset($args['to'])      ? $args['to']      : '';
    $subject = isset($args['subject']) ? $args['subject'] : '';
    $message = isset($args['message']) ? $args['message'] : '';
    $headers = isset($args['headers']) ? $args['headers'] : array();

    if (is_array($to)) {
        $to = implode(', ', $to);
    }

    // Determine "From" information.
    $default_from_email = apply_filters('wp_mail_from', get_option('admin_email'));
    $default_from_name  = apply_filters('wp_mail_from_name', get_bloginfo('name'));
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

    // Clean up old logs if needed.
    $size_limit = get_option('snn_mail_log_size_limit', 100);
    snn_cleanup_old_mail_logs($size_limit);

    // Insert the log as a custom post.
    $post_data = array(
        'post_type'   => 'snn_mail_logs',
        'post_status' => 'publish',
        'post_title'  => __('Mail Log', 'snn') . ' - ' . date('Y-m-d H:i:s')
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
    $log_size_limit  = get_option('snn_mail_log_size_limit', 100);

    echo '<div class="wrap">';
    echo '<h1>' . __('Mail Logs', 'snn') . '</h1>';

    // Settings form.
    echo '<form method="post" action="">';
    echo '<label>';
    echo '<input type="checkbox" name="snn_mail_logging_enabled" ' . checked($logging_enabled, true, false) . '>';
    _e('Enable Mail Logging', 'snn');
    echo '</label>';
    echo '<br><br>';
    echo '<label>';
    _e('Maximum number of logs to keep: ', 'snn');
    echo '<input type="number" name="snn_mail_log_size_limit" value="' . esc_attr($log_size_limit) . '" min="1" style="width: 100px;">';
    echo '</label>';
    echo '<br><br>';
    submit_button(__('Save Changes', 'snn'), 'primary', 'snn_mail_logging_submit', false);
    echo '</form>';

    // Display the logs table only if logging is enabled.
    if ($logging_enabled) {
        echo '<div class="tablenav top">';
        echo '<form method="post" action="" style="float: left;">';
        submit_button(__('Clear All Logs', 'snn'), 'delete', 'snn_clear_mail_logs', false);
        echo '</form>';
        echo '</div>';

        echo '<table class="wp-list-table wp-mail-log-list widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="actions">' . __('Actions', 'snn') . '</th>';
        echo '<th class="date">' . __('Date & Time', 'snn') . '</th>';
        echo '<th class="subject">' . __('Subject', 'snn') . '</th>';
        echo '<th class="from">' . __('From', 'snn') . '</th>';
        echo '<th class="to">' . __('To', 'snn') . '</th>';
        echo '<th class="delete">' . __('Delete', 'snn') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $args = array(
            'post_type'      => 'snn_mail_logs',
            'posts_per_page' => $log_size_limit,
            'orderby'        => 'date',
            'order'          => 'DESC'
        );
        $logs = get_posts($args);

        foreach ($logs as $log) {
            $date_time    = get_post_meta($log->ID, 'date_time', true);
            $from         = get_post_meta($log->ID, 'from', true);
            $to           = get_post_meta($log->ID, 'to', true);
            $subject      = get_post_meta($log->ID, 'subject', true);

            echo '<tr class="mail-log-row" data-log-id="' . esc_attr($log->ID) . '">';

            // View Message Button (first column)
            echo '<td>';
            echo '<button type="button" class="button button-secondary snn-view-message" data-log-id="' . esc_attr($log->ID) . '">' . __('View Message', 'snn') . '</button>';
            echo '</td>';

            echo '<td>' . esc_html($date_time) . '</td>';
            echo '<td>' . esc_html($subject) . '</td>';
            echo '<td>' . esc_html($from) . '</td>';
            echo '<td>' . esc_html($to) . '</td>';
            
            // Delete Button (last column)
            echo '<td>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="snn_delete_log_id" value="' . esc_attr($log->ID) . '">';
            submit_button(__('Delete', 'snn'), 'delete', 'snn_delete_log', false);
            echo '</form>';
            echo '</td>';

            echo '</tr>';
            
            // Hidden row for displaying message content
            echo '<tr class="mail-log-details" id="mail-log-details-' . esc_attr($log->ID) . '" style="display:none;">';
            echo '<td colspan="6">';
            echo '<div class="mail-log-content">';
            echo '<div class="mail-log-loading" style="display:none; padding: 20px; text-align: center;">';
            echo '<span class="spinner is-active" style="float:none;"></span> ' . __('Loading...', 'snn');
            echo '</div>';
            echo '<div class="mail-log-message-wrapper" style="display:none;">';
            echo '<h3>' . __('Message:', 'snn') . '</h3>';
            echo '<div class="mail-log-message-content"></div>';
            echo '<h3>' . __('Headers:', 'snn') . '</h3>';
            echo '<div class="mail-log-headers-content"></div>';
            echo '</div>';
            echo '<button type="button" class="button snn-close-message">' . __('Close', 'snn') . '</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    echo '</div>';
?>
<style>
.delete {
    width: 70px;
}
.date {
    width: 130px;
}
.actions {
    width: 120px;
}
#snn_clear_mail_logs {
    width: 100px;
}
.mail-log-details td {
    background: #f9f9f9;
    border-top: 1px solid #ddd;
}
.mail-log-content {
    padding: 0px;
}
.mail-log-content h3{
    padding: 0px;
    margin:0px;
}
.mail-log-message-content {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    max-height: 400px;
    overflow-y: auto;
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.mail-log-message-content iframe {
    width: 100%;
    min-height: 250px;
    border: none;
}
.mail-log-headers-content {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    word-break: break-all;
}
.snn-close-message {
    margin-top: 10px;
}
</style>
<script>
jQuery(document).ready(function($) {
    // View message button click
    $('.snn-view-message').on('click', function() {
        var logId = $(this).data('log-id');
        var detailsRow = $('#mail-log-details-' + logId);
        var loadingDiv = detailsRow.find('.mail-log-loading');
        var messageWrapper = detailsRow.find('.mail-log-message-wrapper');
        
        // Toggle visibility
        if (detailsRow.is(':visible')) {
            detailsRow.hide();
            return;
        }
        
        detailsRow.show();
        loadingDiv.show();
        messageWrapper.hide();
        
        // Fetch message via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'snn_get_mail_message',
                log_id: logId,
                nonce: '<?php echo wp_create_nonce('snn_mail_logs_nonce'); ?>'
            },
            success: function(response) {
                loadingDiv.hide();
                
                if (response.success) {
                    var message = response.data.message;
                    
                    // Check if message contains HTML tags
                    var isHTML = /<[a-z][\s\S]*>/i.test(message);
                    
                    if (isHTML) {
                        // Display HTML message in iframe for safe rendering
                        var messageHtml = '<iframe sandbox style="width:100%; min-height:250px; border:none;" srcdoc="' + 
                            '<html><head><style>body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; padding: 10px; }</style></head><body>' +
                            message.replace(/"/g, '&quot;') + '</body></html>"></iframe>';
                        detailsRow.find('.mail-log-message-content').html(messageHtml);
                    } else {
                        // Display plain text message with preserved formatting
                        detailsRow.find('.mail-log-message-content').html(
                            '<pre style="margin: 0; font-family: Arial, Helvetica, sans-serif; white-space: pre-wrap; word-wrap: break-word;">' + 
                            $('<div>').text(message).html() + 
                            '</pre>'
                        );
                    }
                    
                    detailsRow.find('.mail-log-headers-content').html(response.data.headers);
                    messageWrapper.show();
                } else {
                    detailsRow.find('.mail-log-message-content').html(
                        '<p style="color: red;">' + (response.data.message || '<?php _e('Error loading message', 'snn'); ?>') + '</p>'
                    );
                    messageWrapper.show();
                }
            },
            error: function() {
                loadingDiv.hide();
                detailsRow.find('.mail-log-message-content').html(
                    '<p style="color: red;"><?php _e('Error loading message', 'snn'); ?></p>'
                );
                messageWrapper.show();
            }
        });
    });
    
    // Close message button click
    $('.snn-close-message').on('click', function() {
        $(this).closest('.mail-log-details').hide();
    });
});
</script>
<?php
}
?>
