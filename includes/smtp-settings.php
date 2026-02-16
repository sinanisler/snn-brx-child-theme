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
        
        // Set timeout to prevent hanging (10 seconds for connection, 10 for sending)
        $phpmailer->Timeout = 10;
        
        // Enable debug output for troubleshooting (commented out by default)
        // $phpmailer->SMTPDebug = 2;
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
 * A comprehensive SMTP connection test that checks DNS, port, SSL/TLS, and authentication.
 *
 * @param string $host
 * @param int    $port
 * @param string $encryption ('ssl', 'tls', or 'none')
 * @param string $username
 * @param string $password
 * @return array Array with 'success' (bool) and 'message' (string) keys
 */
function custom_smtp_comprehensive_connection_test($host, $port, $encryption = 'none', $username = '', $password = '') {
    // Step 1: DNS Resolution Check
    error_log("SMTP TEST: Checking DNS resolution for $host");
    $ip = gethostbyname($host);
    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        error_log("SMTP TEST: DNS resolution failed for $host");
        return array(
            'success' => false,
            'message' => sprintf(
                __('DNS Resolution Failed: Could not resolve hostname "%s". Please verify the SMTP host is correct.', 'snn'),
                esc_html($host)
            )
        );
    }
    error_log("SMTP TEST: DNS resolved $host to $ip");

    // Step 2: Basic Port Connectivity
    error_log("SMTP TEST: Testing basic port connectivity to $host:$port");
    $errno = 0;
    $errstr = '';
    $timeout = 5;

    // Use SSL/TLS context if encryption is set
    $context = stream_context_create();
    if (strtolower($encryption) === 'ssl') {
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        $connection = @stream_socket_client("ssl://$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    } else {
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    if (!is_resource($connection)) {
        error_log("SMTP TEST: Port connection failed. Error ($errno): $errstr");

        // Provide specific error messages based on common error codes
        if ($errno === 110 || $errno === 60) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection Timeout: Could not connect to %s on port %d. The port may be blocked by your hosting firewall or the server is not responding.', 'snn'),
                    esc_html($host),
                    esc_html($port)
                )
            );
        } elseif ($errno === 111 || $errno === 61) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection Refused: Server %s refused connection on port %d. Verify the port number is correct and the SMTP service is running.', 'snn'),
                    esc_html($host),
                    esc_html($port)
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection Failed: Could not connect to %s on port %d. Error: %s', 'snn'),
                    esc_html($host),
                    esc_html($port),
                    esc_html($errstr)
                )
            );
        }
    }

    error_log("SMTP TEST: Port connection successful");

    // Step 3: SMTP Protocol Test with STARTTLS support
    error_log("SMTP TEST: Testing SMTP protocol handshake");

    // Read greeting (Handle multi-line greetings properly)
    $greeting = '';
    while ($line = fgets($connection, 512)) {
        $greeting .= $line;
        // If the 4th character is a space, it's the last line of the response
        if (substr($line, 3, 1) === ' ') {
            break;
        }
    }
    error_log("SMTP TEST: Server greeting: " . trim($greeting));

    // Check the very first 3 characters of the collected greeting
    if (!$greeting || substr($greeting, 0, 3) !== '220') {
        fclose($connection);
        return array(
            'success' => false,
            'message' => sprintf(
                __('SMTP Protocol Error: Server did not send proper greeting. Response: %s', 'snn'),
                esc_html(trim($greeting))
            )
        );
    }

    // Send EHLO command
    fputs($connection, "EHLO localhost\r\n");
    $ehlo_response = '';
    while ($line = fgets($connection, 512)) {
        $ehlo_response .= $line;
        if (substr($line, 3, 1) === ' ') break; // End of multi-line response
    }
    error_log("SMTP TEST: EHLO response: " . trim($ehlo_response));

    if (substr($ehlo_response, 0, 3) !== '250') {
        fclose($connection);
        return array(
            'success' => false,
            'message' => sprintf(
                __('SMTP Protocol Error: Server rejected EHLO command. Response: %s', 'snn'),
                esc_html(trim($ehlo_response))
            )
        );
    }

    // Step 4: STARTTLS Test (if encryption is TLS)
    if (strtolower($encryption) === 'tls') {
        error_log("SMTP TEST: Initiating STARTTLS");

        fputs($connection, "STARTTLS\r\n");
        // Read STARTTLS response (Handle multi-line responses properly)
        $starttls_response = '';
        while ($line = fgets($connection, 512)) {
            $starttls_response .= $line;
            // If the 4th character is a space, it's the last line of the response
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        error_log("SMTP TEST: STARTTLS response: " . trim($starttls_response));

        if (substr($starttls_response, 0, 3) !== '220') {
            fclose($connection);
            return array(
                'success' => false,
                'message' => sprintf(
                    __('TLS Error: Server does not support STARTTLS or rejected the request. Response: %s', 'snn'),
                    esc_html(trim($starttls_response))
                )
            );
        }

        // Enable TLS encryption
        $crypto_result = stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto_result) {
            fclose($connection);
            return array(
                'success' => false,
                'message' => __('TLS Encryption Failed: Could not establish secure TLS connection. The server\'s SSL certificate may be invalid or expired.', 'snn')
            );
        }
        error_log("SMTP TEST: TLS encryption established successfully");

        // Send EHLO again after STARTTLS
        fputs($connection, "EHLO localhost\r\n");
        while ($line = fgets($connection, 512)) {
            if (substr($line, 3, 1) === ' ') break;
        }
    }

    // Step 5: Authentication Test (if credentials provided)
    if (!empty($username) && !empty($password)) {
        error_log("SMTP TEST: Testing authentication with username: $username");

        fputs($connection, "AUTH LOGIN\r\n");
        $auth_response = fgets($connection, 512);
        error_log("SMTP TEST: AUTH LOGIN response: " . trim($auth_response));

        if (substr($auth_response, 0, 3) !== '334') {
            fclose($connection);
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Authentication Error: Server does not support LOGIN authentication method. Response: %s', 'snn'),
                    esc_html(trim($auth_response))
                )
            );
        }

        // Send username
        fputs($connection, base64_encode($username) . "\r\n");
        $user_response = fgets($connection, 512);
        error_log("SMTP TEST: Username response: " . trim($user_response));

        if (substr($user_response, 0, 3) !== '334') {
            fclose($connection);
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Authentication Error: Server rejected username. Response: %s', 'snn'),
                    esc_html(trim($user_response))
                )
            );
        }

        // Send password
        fputs($connection, base64_encode($password) . "\r\n");
        $pass_response = fgets($connection, 512);
        error_log("SMTP TEST: Password response: " . trim($pass_response));

        if (substr($pass_response, 0, 3) !== '235') {
            fclose($connection);

            // Check for specific authentication failure codes
            if (substr($pass_response, 0, 3) === '535') {
                return array(
                    'success' => false,
                    'message' => __('Authentication Failed: Invalid username or password. Please verify your SMTP credentials are correct.', 'snn')
                );
            } else {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('Authentication Error: Server rejected credentials. Response: %s', 'snn'),
                        esc_html(trim($pass_response))
                    )
                );
            }
        }

        error_log("SMTP TEST: Authentication successful");
    }

    // Clean up
    fputs($connection, "QUIT\r\n");
    fclose($connection);

    error_log("SMTP TEST: All checks passed successfully");
    return array(
        'success' => true,
        'message' => __('Connection test successful! All checks passed.', 'snn')
    );
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
        $encryption = $options['smtp_encryption'] ?? 'none';
        $username = $options['smtp_username'] ?? '';
        $password = $options['smtp_password'] ?? '';

        // Run comprehensive connection test before attempting to send
        $test_result = custom_smtp_comprehensive_connection_test($host, $port, $encryption, $username, $password);

        if (!$test_result['success']) {
            add_settings_error(
                'custom_smtp_test_email',
                'custom_smtp_connection_failed',
                $test_result['message'],
                'error'
            );
            error_log("SMTP TEST: Comprehensive connection test failed. Aborting send.");
            return; // Stop here; don't attempt sending the email
        }

        error_log("SMTP TEST: Comprehensive connection test passed.");
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
