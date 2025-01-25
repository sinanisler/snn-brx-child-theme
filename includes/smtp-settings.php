<?php


add_action('admin_menu', 'custom_smtp_add_admin_menu');
function custom_smtp_add_admin_menu() {
    add_submenu_page(
        'snn-settings',
        'Mail SMTP Settings',
        'Mail SMTP Settings',
        'manage_options',
        'smtp-settings',
        'custom_smtp_settings_page'
    );
}


add_action('admin_init', 'custom_smtp_settings_init');
function custom_smtp_settings_init() {
    register_setting('custom_smtp_settings_group', 'custom_smtp_settings', 'custom_smtp_settings_sanitize');

    add_settings_section(
        'custom_smtp_settings_section',
        __('SMTP Settings', 'textdomain'),
        'custom_smtp_settings_section_callback',
        'smtp-settings'
    );

    add_settings_field(
        'enable_smtp',
        __('Enable SMTP', 'textdomain'),
        'custom_smtp_enable_smtp_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_host',
        __('SMTP Host', 'textdomain'),
        'custom_smtp_smtp_host_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_encryption',
        __('Encryption', 'textdomain'),
        'custom_smtp_smtp_encryption_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_port',
        __('SMTP Port', 'textdomain'),
        'custom_smtp_smtp_port_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_username',
        __('SMTP Username', 'textdomain'),
        'custom_smtp_smtp_username_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_password',
        __('SMTP Password', 'textdomain'),
        'custom_smtp_smtp_password_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );
}


function custom_smtp_settings_sanitize($input) {
    $sanitized = array();

    $sanitized['enable_smtp']       = isset($input['enable_smtp']) ? boolval($input['enable_smtp']) : false;
    $sanitized['smtp_host']         = sanitize_text_field($input['smtp_host'] ?? '');
    $sanitized['smtp_encryption']   = sanitize_text_field($input['smtp_encryption'] ?? '');

    if (!empty($sanitized['smtp_encryption'])) {
        switch (strtolower($sanitized['smtp_encryption'])) {
            case 'ssl':
                $sanitized['smtp_port'] = 465;
                break;
            case 'tls':
                $sanitized['smtp_port'] = 587;
                break;
            default:
                $sanitized['smtp_port'] = intval($input['smtp_port'] ?? 25);
        }
    } else {
        $sanitized['smtp_port'] = intval($input['smtp_port'] ?? 25);
    }

    $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username'] ?? '');
    $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password'] ?? '');

    return $sanitized;
}


function custom_smtp_settings_section_callback() {
    echo '<p>' . __('Simple SMTP Settings for bypassing PHP mailler or eliminating falling to spam issues.', 'textdomain') . '</p>';
}


function custom_smtp_enable_smtp_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='checkbox' name='custom_smtp_settings[enable_smtp]' <?php checked(isset($options['enable_smtp']) ? $options['enable_smtp'] : false, true); ?> value='1'>
    <?php
}


function custom_smtp_smtp_host_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='text' name='custom_smtp_settings[smtp_host]' value='<?php echo esc_attr($options['smtp_host'] ?? ''); ?>' size='50'>
    <?php
}


function custom_smtp_smtp_encryption_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <select name='custom_smtp_settings[smtp_encryption]' id='smtp_encryption' onchange="updateSMTPPort()">
        <option value='none' <?php selected($options['smtp_encryption'] ?? '', 'none'); ?>><?php _e('None', 'textdomain'); ?></option>
        <option value='ssl' <?php selected($options['smtp_encryption'] ?? '', 'ssl'); ?>><?php _e('SSL', 'textdomain'); ?></option>
        <option value='tls' <?php selected($options['smtp_encryption'] ?? '', 'tls'); ?>><?php _e('TLS', 'textdomain'); ?></option>
    </select>
    <script>
        function updateSMTPPort() {
            var encryption = document.getElementById('smtp_encryption').value;
            var portField = document.getElementsByName('custom_smtp_settings[smtp_port]')[0];
            if (encryption === 'ssl') {
                portField.value = 465;
                portField.readOnly = true;
            } else if (encryption === 'tls') {
                portField.value = 587;
                portField.readOnly = true;
            } else {
                portField.value = '';
                portField.readOnly = false;
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateSMTPPort();
        });
    </script>
    <?php
}


function custom_smtp_smtp_port_render() {
    $options = get_option('custom_smtp_settings', array());
    $encryption = strtolower($options['smtp_encryption'] ?? 'none');
    $is_readonly = in_array($encryption, ['ssl', 'tls']) ? 'readonly' : '';
    ?>
    <input type='number' name='custom_smtp_settings[smtp_port]' 
        value='<?php echo esc_attr($options['smtp_port'] ?? ''); ?>' 
        size='10' 
        <?php echo $is_readonly; ?>>
    <?php
}


function custom_smtp_smtp_username_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='text' name='custom_smtp_settings[smtp_username]' 
        value='<?php echo esc_attr($options['smtp_username'] ?? ''); ?>' 
        size='50'>
    <?php
}


function custom_smtp_smtp_password_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='password' name='custom_smtp_settings[smtp_password]' 
        value='<?php echo esc_attr($options['smtp_password'] ?? ''); ?>' 
        size='50'>
    <?php
}


add_action('wp_ajax_remove_smtp_password', 'custom_smtp_remove_password_callback');
function custom_smtp_remove_password_callback() {
    check_ajax_referer('remove_smtp_password_nonce', 'nonce');

    $options = get_option('custom_smtp_settings', array());
    if (isset($options['smtp_password'])) {
        unset($options['smtp_password']);
        update_option('custom_smtp_settings', $options);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}


add_action('phpmailer_init', 'custom_smtp_phpmailer_init');
function custom_smtp_phpmailer_init($phpmailer) {
    $options = get_option('custom_smtp_settings', array());

    if (!empty($options['enable_smtp'])) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $options['smtp_host'] ?? '';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = $options['smtp_port'] ?? 25;
        $phpmailer->Username   = $options['smtp_username'] ?? '';
        $phpmailer->Password   = $options['smtp_password'] ?? '';
        $phpmailer->SMTPSecure = (!empty($options['smtp_encryption']) && strtolower($options['smtp_encryption']) !== 'none') 
            ? strtolower($options['smtp_encryption']) 
            : '';

        // Set From to the same as username (or change as you see fit)
        $phpmailer->From       = $options['smtp_username'] ?? '';
        $phpmailer->FromName   = get_bloginfo('name');
    }
}


add_action('admin_enqueue_scripts', 'custom_smtp_enqueue_scripts');
function custom_smtp_enqueue_scripts($hook) {
    if ($hook !== 'snn-settings_page_smtp-settings') {
        return;
    }
    wp_enqueue_script('jquery');
}

/**
 * A helper function to check if the server/port is reachable, with a short timeout.
 *
 * @param string $host
 * @param int    $port
 * @return bool
 */
function custom_smtp_check_port_availability($host, $port) {
    $errno    = 0;
    $errstr   = '';
    // Shorten the timeout to help avoid long stalls
    $timeout  = 3; // seconds
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }

    // Log debug info if something goes wrong
    error_log("SMTP CHECK: Could not connect to $host on port $port. Error ($errno): $errstr");
    return false;
}


function custom_smtp_handle_test_email_submission() {
    // Confirm we have a POST request to handle
    if (!isset($_POST['custom_smtp_send_test_email'])) {
        return; // Not a test email submission
    }

    // Check nonce first
    if (!check_admin_referer('custom_smtp_send_test_email_action', 'custom_smtp_send_test_email_nonce')) {
        // If the nonce fails, show error and stop
        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_nonce_failed',
            __('Security check failed. Please refresh the page and try again.', 'textdomain'),
            'error'
        );
        error_log("SMTP TEST: Nonce check failed.");
        return;
    }

    // Now proceed
    error_log("SMTP TEST: Test email submission received.");

    // Get the email address from the form
    $to = isset($_POST['test_email_address']) ? sanitize_email($_POST['test_email_address']) : '';

    // Fallback to the site admin email if the input field is empty or invalid
    if (empty($to)) {
        $to = get_option('admin_email');
    }

    error_log("SMTP TEST: Attempting to send test email to $to");

    // Check if SMTP is enabled
    $options = get_option('custom_smtp_settings', array());
    if (!empty($options['enable_smtp'])) {
        $host = $options['smtp_host'] ?? '';
        $port = $options['smtp_port'] ?? 25;

        // Check if the host/port are reachable before sending the email
        if (!custom_smtp_check_port_availability($host, $port)) {
            add_settings_error(
                'custom_smtp_test_email',
                'custom_smtp_port_blocked',
                sprintf(
                    __('Could not connect to %s on port %d. It may be blocked by your hosting environment.', 'textdomain'),
                    esc_html($host),
                    esc_html($port)
                ),
                'error'
            );
            error_log("SMTP TEST: Host/Port check failed for $host:$port. Aborting send.");
            return; // Stop here; don't attempt sending the email
        }
    }

    // Attempt to send email using wp_mail()
    $subject = __('SMTP Test Email', 'textdomain');
    $message = __('This is a test email sent via your SMTP settings.', 'textdomain');
    $headers = array('Content-Type: text/html; charset=UTF-8');

    error_log("SMTP TEST: Calling wp_mail() now...");
    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_test_email_success',
            sprintf(
                __('Test email sent successfully to %s! Check the inbox.', 'textdomain'),
                esc_html($to)
            ),
            'updated'
        );
        error_log("SMTP TEST: wp_mail() succeeded.");
    } else {
        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_test_email_failed',
            __('Failed to send test email. Check your SMTP settings or logs for more information.', 'textdomain'),
            'error'
        );
        error_log("SMTP TEST: wp_mail() FAILED.");
    }
}


function custom_smtp_settings_page() {
    // Process test email submission before rendering the page.
    custom_smtp_handle_test_email_submission();

    // Display any admin notices (including errors set above).
    settings_errors('custom_smtp_test_email');
    ?>
    <div class="wrap">
        <h1><?php _e('Mail SMTP Settings', 'textdomain'); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('custom_smtp_settings_group');
            do_settings_sections('smtp-settings');
            submit_button();
            ?>
        </form>

        <hr />

        <h2><?php _e('Send Test Email', 'textdomain'); ?></h2>
        <p><?php _e('Use the form below to send a test email to any email address, using the configured SMTP settings.', 'textdomain'); ?></p>
        <form method="post">
            <?php wp_nonce_field('custom_smtp_send_test_email_action', 'custom_smtp_send_test_email_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Recipient Email', 'textdomain'); ?></th>
                    <td>
                        <input 
                            type="email" 
                            name="test_email_address" 
                            value="" 
                            placeholder="<?php echo esc_attr__('you@example.com', 'textdomain'); ?>" 
                            size="40" 
                        />
                    </td>
                </tr>
            </table>
            <input type="hidden" name="custom_smtp_send_test_email" value="1" />
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Send Test Email', 'textdomain'); ?>" />
        </form>
    </div>
    <?php
}
