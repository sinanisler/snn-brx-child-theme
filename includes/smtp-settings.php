<?php


add_action('admin_menu', 'custom_smtp_add_admin_menu');
function custom_smtp_add_admin_menu() {
    add_submenu_page(
        'snn-settings',
        __('Mail SMTP Settings', 'snn'),
        __('Mail SMTP Settings', 'snn'),
        'manage_options',
        'snn-smtp-settings',
        'custom_smtp_settings_page'
    );
}


add_action('admin_init', 'custom_smtp_settings_init');
function custom_smtp_settings_init() {
    register_setting('custom_smtp_settings_group', 'custom_smtp_settings', 'custom_smtp_settings_sanitize');

    add_settings_section(
        'custom_smtp_settings_section',
        __('SMTP Settings', 'snn'),
        'custom_smtp_settings_section_callback',
        'snn-smtp-settings'
    );

    add_settings_field(
        'enable_smtp',
        __('Enable SMTP', 'snn'),
        'custom_smtp_enable_smtp_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_host',
        __('SMTP Host', 'snn'),
        'custom_smtp_smtp_host_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_encryption',
        __('Encryption', 'snn'),
        'custom_smtp_smtp_encryption_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_port',
        __('SMTP Port', 'snn'),
        'custom_smtp_smtp_port_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_username',
        __('SMTP Username', 'snn'),
        'custom_smtp_smtp_username_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );

    add_settings_field(
        'smtp_password',
        __('SMTP Password', 'snn'),
        'custom_smtp_smtp_password_render',
        'snn-smtp-settings',
        'custom_smtp_settings_section'
    );
}


function custom_smtp_settings_sanitize($input) {
    $sanitized = array();

    $sanitized['enable_smtp']       = isset($input['enable_smtp']) ? boolval($input['enable_smtp']) : false;
    $sanitized['smtp_host']         = sanitize_text_field($input['smtp_host'] ?? '');
    $sanitized['smtp_encryption']   = sanitize_text_field($input['smtp_encryption'] ?? '');
    
    // Always save the port value provided by the user
    $sanitized['smtp_port'] = intval($input['smtp_port'] ?? 25);

    $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username'] ?? '');
    $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password'] ?? '');

    return $sanitized;
}


function custom_smtp_settings_section_callback() {
    echo '<p>' . __('Simple SMTP Settings for bypassing PHP mailler or eliminating falling to spam issues.', 'snn') . '</p>';
    
    // Check if admin email matches SMTP username
    $options = get_option('custom_smtp_settings', array());
    $admin_email = get_option('admin_email');
    $smtp_username = $options['smtp_username'] ?? '';
    
    if (!empty($smtp_username) && !empty($admin_email) && $smtp_username !== $admin_email) {
        echo '<div style="border: 2px solid #f0b849; background: #fff8e5; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '<p style="margin: 0; font-size: 13px; color: #856404;">';
        echo '<strong>⚠ ' . __('Warning:', 'snn') . '</strong> ';
        echo __('Your SMTP username differs from the WordPress Site Setting email. This may cause delivery issues if SPF/DKIM records don\'t match.', 'snn');
        echo '</p>';
        echo '</div>';
    }
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
        <option value='none' <?php selected($options['smtp_encryption'] ?? '', 'none'); ?>><?php _e('None', 'snn'); ?></option>
        <option value='ssl' <?php selected($options['smtp_encryption'] ?? '', 'ssl'); ?>><?php _e('SSL', 'snn'); ?></option>
        <option value='tls' <?php selected($options['smtp_encryption'] ?? '', 'tls'); ?>><?php _e('TLS', 'snn'); ?></option>
    </select>
    <script>
        function updateSMTPPort() {
            var encryption = document.getElementById('smtp_encryption').value;
            var portField = document.getElementsByName('custom_smtp_settings[smtp_port]')[0];
            
            // Only update if field is empty
            if (!portField.value) {
                if (encryption === 'ssl') {
                    portField.value = 465;
                } else if (encryption === 'tls') {
                    portField.value = 587;
                }
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
    ?>
    <input type='number' name='custom_smtp_settings[smtp_port]' 
        value='<?php echo esc_attr($options['smtp_port'] ?? ''); ?>' 
        size='10'>
    <p class="description"><?php _e('Default: 465 for SSL, 587 for TLS, 25 for None', 'snn'); ?></p>
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
    
    // Fix for WordPress 6.9 email sending issue
    // Set Sender to empty string to let the mail server handle envelope sender
    // This resolves the "Could not instantiate mail function" error
    $phpmailer->Sender = '';
}


add_action('admin_enqueue_scripts', 'custom_smtp_enqueue_scripts');
function custom_smtp_enqueue_scripts($hook) {
    if ($hook !== 'snn-settings_page_snn-smtp-settings') {
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
            __('Security check failed. Please refresh the page and try again.', 'snn'),
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
                    __('Could not connect to %s on port %d. It may be blocked by your hosting environment.', 'snn'),
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
    $subject = __('SMTP Test Email', 'snn');
    $message = __('This is a test email sent via your SMTP settings.', 'snn');
    $headers = array('Content-Type: text/html; charset=UTF-8');

    error_log("SMTP TEST: Calling wp_mail() now...");
    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_test_email_success',
            sprintf(
                __('Test email sent successfully to %s! Check the inbox.', 'snn'),
                esc_html($to)
            ),
            'updated'
        );
        error_log("SMTP TEST: wp_mail() succeeded.");
    } else {
        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_test_email_failed',
            __('Failed to send test email. Check your SMTP settings or logs for more information.', 'snn'),
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
        <h1><?php _e('Mail SMTP Settings', 'snn'); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('custom_smtp_settings_group');
            do_settings_sections('snn-smtp-settings');
            submit_button();
            ?>
        </form>

        <hr />

        <h2><?php _e('Send Test Email', 'snn'); ?></h2>
        <p><?php _e('Use the form below to send a test email to any email address, using the configured SMTP settings.', 'snn'); ?></p>
        <form method="post">
            <?php wp_nonce_field('custom_smtp_send_test_email_action', 'custom_smtp_send_test_email_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Recipient Email', 'snn'); ?></th>
                    <td>
                        <input 
                            type="email" 
                            name="test_email_address" 
                            value="" 
                            placeholder="<?php echo esc_attr__('you@example.com', 'snn'); ?>" 
                            size="40" 
                        />
                    </td>
                </tr>
            </table>
            <input type="hidden" name="custom_smtp_send_test_email" value="1" />
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Send Test Email', 'snn'); ?>" />
        </form>
    </div>
    <?php
}
