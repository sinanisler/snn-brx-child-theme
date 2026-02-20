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
    <p class="description"><?php _e('Your mail server address. e.g. smtp.gmail.com, mail.yourdomain.com', 'snn'); ?></p>
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
    <p class="description"><?php _e('Use SSL (port 465) or TLS (port 587). Most modern providers require TLS.', 'snn'); ?></p>
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
    <p class="description"><?php _e('Must be a full email address. e.g. you@yourdomain.com', 'snn'); ?></p>
    <?php
}


function custom_smtp_smtp_password_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='password' name='custom_smtp_settings[smtp_password]' 
        value='<?php echo esc_attr($options['smtp_password'] ?? ''); ?>' 
        size='50'>
    <p class="description"><?php _e('Your email account password. For Gmail/Google Workspace use an App Password, not your regular password.', 'snn'); ?></p>
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


add_action('wp_ajax_custom_smtp_ajax_test', 'custom_smtp_ajax_test_handler');
function custom_smtp_ajax_test_handler() {
    check_ajax_referer('custom_smtp_ajax_test_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json(array('success' => false, 'logs' => array(array('type' => 'error', 'message' => 'Unauthorized')), 'settings' => array(), 'email_sent' => false));
    }

    $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
    if (empty($to)) {
        $to = get_option('admin_email');
    }

    $logs    = array();
    $options = get_option('custom_smtp_settings', array());

    $smtp_enabled = !empty($options['enable_smtp']);
    $host         = $options['smtp_host'] ?? '';
    $port         = $options['smtp_port'] ?? 25;
    $encryption   = $options['smtp_encryption'] ?? 'none';
    $username     = $options['smtp_username'] ?? '';
    $password     = $options['smtp_password'] ?? '';

    $settings = array(
        'SMTP Enabled'  => $smtp_enabled ? 'Yes' : 'No',
        'Host'          => $host ?: '(not set)',
        'Port'          => (int) $port,
        'Encryption'    => strtoupper($encryption),
        'Username'      => $username ?: '(not set)',
        'Password'      => !empty($password) ? '(set)' : '(not set)',
        'Recipient'     => $to,
    );

    $logs[] = array('type' => 'info', 'message' => 'Test started — recipient: ' . $to);

    if ($smtp_enabled) {
        $logs[] = array('type' => 'info', 'message' => 'SMTP is enabled. Running connection checks...');

        // Validate host
        if (empty($host)) {
            $logs[] = array('type' => 'error', 'message' => 'SMTP host is empty. Please configure it.');
            wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
        }

        // DNS
        $logs[] = array('type' => 'info', 'message' => 'Resolving DNS for: ' . $host);
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            $logs[] = array('type' => 'error', 'message' => 'DNS resolution failed for "' . $host . '". Check hostname.');
            wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
        }
        $logs[] = array('type' => 'success', 'message' => 'DNS resolved: ' . $host . ' → ' . $ip);

        // Port connectivity
        $logs[] = array('type' => 'info', 'message' => 'Connecting to ' . $host . ':' . $port . ' (encryption: ' . strtoupper($encryption) . ')...');
        $conn_errno  = 0;
        $conn_errstr = '';
        $timeout     = 5;
        $context     = stream_context_create();

        if (strtolower($encryption) === 'ssl') {
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
            $connection = @stream_socket_client("ssl://{$host}:{$port}", $conn_errno, $conn_errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        } else {
            $connection = @fsockopen($host, $port, $conn_errno, $conn_errstr, $timeout);
        }

        if (!is_resource($connection)) {
            if ($conn_errno === 110 || $conn_errno === 60) {
                $logs[] = array('type' => 'error', 'message' => 'Connection timeout to ' . $host . ':' . $port . '. Port may be blocked by firewall.');
            } elseif ($conn_errno === 111 || $conn_errno === 61) {
                $logs[] = array('type' => 'error', 'message' => 'Connection refused by ' . $host . ':' . $port . '. Check port and SMTP service.');
            } else {
                $logs[] = array('type' => 'error', 'message' => 'Connection failed to ' . $host . ':' . $port . '. Error (' . $conn_errno . '): ' . $conn_errstr);
            }
            wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
        }
        $logs[] = array('type' => 'success', 'message' => 'Socket connection established to ' . $host . ':' . $port);

        // SMTP greeting
        $greeting = '';
        while ($line = fgets($connection, 512)) {
            $greeting .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if (!$greeting || substr($greeting, 0, 3) !== '220') {
            fclose($connection);
            $logs[] = array('type' => 'error', 'message' => 'Bad SMTP greeting. Response: ' . trim($greeting));
            wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
        }
        $logs[] = array('type' => 'info', 'message' => 'Server greeting: ' . trim($greeting));

        // EHLO
        fputs($connection, "EHLO localhost\r\n");
        $ehlo_response = '';
        while ($line = fgets($connection, 512)) {
            $ehlo_response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if (substr($ehlo_response, 0, 3) !== '250') {
            fclose($connection);
            $logs[] = array('type' => 'error', 'message' => 'EHLO rejected. Response: ' . trim($ehlo_response));
            wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
        }
        $logs[] = array('type' => 'success', 'message' => 'EHLO handshake OK.');

        // STARTTLS
        if (strtolower($encryption) === 'tls') {
            $logs[] = array('type' => 'info', 'message' => 'Initiating STARTTLS...');
            fputs($connection, "STARTTLS\r\n");
            $starttls_response = '';
            while ($line = fgets($connection, 512)) {
                $starttls_response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            if (substr($starttls_response, 0, 3) !== '220') {
                fclose($connection);
                $logs[] = array('type' => 'error', 'message' => 'STARTTLS failed. Response: ' . trim($starttls_response));
                wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
            }
            $crypto_result = stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto_result) {
                fclose($connection);
                $logs[] = array('type' => 'error', 'message' => 'TLS encryption failed. SSL certificate may be invalid or expired.');
                wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
            }
            $logs[] = array('type' => 'success', 'message' => 'TLS encryption established.');
            fputs($connection, "EHLO localhost\r\n");
            while ($line = fgets($connection, 512)) {
                if (substr($line, 3, 1) === ' ') break;
            }
        }

        // Auth
        if (!empty($username) && !empty($password)) {
            $logs[] = array('type' => 'info', 'message' => 'Authenticating as: ' . $username);
            fputs($connection, "AUTH LOGIN\r\n");
            $auth_response = fgets($connection, 512);
            if (substr($auth_response, 0, 3) !== '334') {
                fclose($connection);
                $logs[] = array('type' => 'error', 'message' => 'AUTH LOGIN not accepted. Response: ' . trim($auth_response));
                wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
            }
            fputs($connection, base64_encode($username) . "\r\n");
            $user_response = fgets($connection, 512);
            if (substr($user_response, 0, 3) !== '334') {
                fclose($connection);
                $logs[] = array('type' => 'error', 'message' => 'Username rejected. Response: ' . trim($user_response));
                wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
            }
            fputs($connection, base64_encode($password) . "\r\n");
            $pass_response = fgets($connection, 512);
            if (substr($pass_response, 0, 3) !== '235') {
                fclose($connection);
                if (substr($pass_response, 0, 3) === '535') {
                    $logs[] = array('type' => 'error', 'message' => 'Authentication failed: Invalid username or password.');
                } else {
                    $logs[] = array('type' => 'error', 'message' => 'Credentials rejected. Response: ' . trim($pass_response));
                }
                wp_send_json(array('success' => false, 'logs' => $logs, 'settings' => $settings, 'email_sent' => false));
            }
            $logs[] = array('type' => 'success', 'message' => 'SMTP authentication successful.');
        } else {
            $logs[] = array('type' => 'warning', 'message' => 'No credentials provided — skipping auth step.');
        }

        fputs($connection, "QUIT\r\n");
        fclose($connection);
        $logs[] = array('type' => 'success', 'message' => 'Connection test passed. Sending email via wp_mail()...');

    } else {
        $logs[] = array('type' => 'warning', 'message' => 'SMTP is disabled — using PHP mail() instead.');
        $logs[] = array('type' => 'info', 'message' => 'Sending email via wp_mail()...');
    }

    // Capture wp_mail errors
    $mail_error      = null;
    $mail_error_fn   = function ($wp_error) use (&$mail_error) { $mail_error = $wp_error; };
    add_action('wp_mail_failed', $mail_error_fn);

    $phpmailer_errors  = array();
    $phpmailer_error_fn = function ($phpmailer) use (&$phpmailer_errors) {
        if (!empty($phpmailer->ErrorInfo)) {
            $phpmailer_errors[] = $phpmailer->ErrorInfo;
        }
    };
    add_action('phpmailer_init', $phpmailer_error_fn, 999);

    $sent = wp_mail($to, __('SMTP Test Email', 'snn'), __('This is a test email sent via your SMTP settings.', 'snn'), array('Content-Type: text/html; charset=UTF-8'));

    remove_action('wp_mail_failed', $mail_error_fn);
    remove_action('phpmailer_init', $phpmailer_error_fn, 999);

    if ($sent) {
        $logs[] = array('type' => 'success', 'message' => 'Email sent successfully to ' . $to . '!');
    } else {
        if ($mail_error && is_wp_error($mail_error)) {
            $logs[] = array('type' => 'error', 'message' => 'WordPress mail error: ' . $mail_error->get_error_message());
            $error_data = $mail_error->get_error_data();
            if (!empty($error_data)) {
                if (is_array($error_data)) {
                    foreach ($error_data as $k => $v) {
                        if (is_string($v)) {
                            $logs[] = array('type' => 'error', 'message' => $k . ': ' . $v);
                        }
                    }
                } elseif (is_string($error_data)) {
                    $logs[] = array('type' => 'error', 'message' => $error_data);
                }
            }
        }
        foreach ($phpmailer_errors as $pe) {
            $logs[] = array('type' => 'error', 'message' => 'PHPMailer: ' . $pe);
        }
        if (empty($mail_error) && empty($phpmailer_errors)) {
            $logs[] = array('type' => 'error', 'message' => 'wp_mail() returned false but no specific error was captured. Check server logs.');
        }
    }

    wp_send_json(array(
        'success'    => $sent,
        'logs'       => $logs,
        'settings'   => $settings,
        'email_sent' => $sent,
        'recipient'  => $to,
    ));
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
    // Validate inputs first
    if (empty($host)) {
        return array(
            'success' => false,
            'message' => __('SMTP host is required. Please configure your SMTP settings.', 'snn')
        );
    }

    if (empty($port) || !is_numeric($port)) {
        return array(
            'success' => false,
            'message' => __('Valid SMTP port is required. Please configure your SMTP settings.', 'snn')
        );
    }

    // Set up error handler to capture PHP warnings and errors
    $php_errors = array();
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$php_errors) {
        $php_errors[] = "[$errno] $errstr (Line: $errline)";
        error_log("SMTP TEST: PHP Error captured - [$errno] $errstr in $errfile on line $errline");
        return true; // Prevent default PHP error handler
    });

    try {
        // Step 1: DNS Resolution Check
        error_log("SMTP TEST: Checking DNS resolution for $host");
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            restore_error_handler();
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
    } catch (Exception $e) {
        restore_error_handler();
        error_log("SMTP TEST: Exception caught during connection - " . $e->getMessage());

        return array(
            'success' => false,
            'message' => sprintf(
                __('Connection Exception: %s', 'snn'),
                esc_html($e->getMessage())
            )
        );
    }

    if (!is_resource($connection)) {
        restore_error_handler();
        error_log("SMTP TEST: Port connection failed. Error ($errno): $errstr");

        // Build error message with PHP errors if any
        $error_msg = '';

        // Provide specific error messages based on common error codes
        if ($errno === 110 || $errno === 60) {
            $error_msg = sprintf(
                __('Connection Timeout: Could not connect to %s on port %d. The port may be blocked by your hosting firewall or the server is not responding.', 'snn'),
                esc_html($host),
                esc_html($port)
            );
        } elseif ($errno === 111 || $errno === 61) {
            $error_msg = sprintf(
                __('Connection Refused: Server %s refused connection on port %d. Verify the port number is correct and the SMTP service is running.', 'snn'),
                esc_html($host),
                esc_html($port)
            );
        } else {
            $error_msg = sprintf(
                __('Connection Failed: Could not connect to %s on port %d. Error: %s', 'snn'),
                esc_html($host),
                esc_html($port),
                esc_html($errstr)
            );
        }

        // Append PHP errors if any were captured
        if (!empty($php_errors)) {
            $error_msg .= '<br/><br/><strong>' . __('Additional PHP Errors:', 'snn') . '</strong><br/>' . implode('<br/>', array_map('esc_html', $php_errors));
        }

        return array(
            'success' => false,
            'message' => $error_msg
        );
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
        restore_error_handler();
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
        restore_error_handler();
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
            restore_error_handler();
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
            restore_error_handler();
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
            restore_error_handler();
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
            restore_error_handler();
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
            restore_error_handler();

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

    // Restore error handler
    restore_error_handler();

    error_log("SMTP TEST: All checks passed successfully");

    // Build success message with any PHP warnings if captured
    $success_msg = __('Connection test successful! All checks passed.', 'snn');
    if (!empty($php_errors)) {
        $success_msg .= '<br/><br/><strong>' . __('Note - PHP Warnings encountered:', 'snn') . '</strong><br/>' . implode('<br/>', array_map('esc_html', $php_errors));
    }

    return array(
        'success' => true,
        'message' => $success_msg
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

    // Capture wp_mail errors using a temporary hook
    $mail_error = null;
    $mail_error_handler = function($wp_error) use (&$mail_error) {
        $mail_error = $wp_error;
        error_log("SMTP TEST: wp_mail_failed triggered - " . $wp_error->get_error_message());
    };
    add_action('wp_mail_failed', $mail_error_handler);

    // Capture PHPMailer exceptions and errors
    $phpmailer_errors = array();
    $phpmailer_error_handler = function($phpmailer) use (&$phpmailer_errors) {
        if (!empty($phpmailer->ErrorInfo)) {
            $phpmailer_errors[] = $phpmailer->ErrorInfo;
            error_log("SMTP TEST: PHPMailer ErrorInfo - " . $phpmailer->ErrorInfo);
        }
    };
    add_action('phpmailer_init', $phpmailer_error_handler, 999);

    // Attempt to send email using wp_mail()
    $subject = __('SMTP Test Email', 'snn');
    $message = __('This is a test email sent via your SMTP settings.', 'snn');
    $headers = array('Content-Type: text/html; charset=UTF-8');

    error_log("SMTP TEST: Calling wp_mail() now...");
    $sent = wp_mail($to, $subject, $message, $headers);

    // Remove temporary hooks
    remove_action('wp_mail_failed', $mail_error_handler);
    remove_action('phpmailer_init', $phpmailer_error_handler, 999);

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
        // Build detailed error message from all available error sources
        $error_details = array();

        // Get WP_Error details if available
        if ($mail_error && is_wp_error($mail_error)) {
            $error_details[] = '<strong>' . __('WordPress Error:', 'snn') . '</strong> ' . esc_html($mail_error->get_error_message());

            // Get all error data if available
            $error_data = $mail_error->get_error_data();
            if (!empty($error_data)) {
                if (is_array($error_data)) {
                    foreach ($error_data as $key => $value) {
                        if (is_string($value)) {
                            $error_details[] = esc_html($key) . ': ' . esc_html($value);
                        }
                    }
                } else if (is_string($error_data)) {
                    $error_details[] = esc_html($error_data);
                }
            }
        }

        // Get PHPMailer errors if available
        if (!empty($phpmailer_errors)) {
            foreach ($phpmailer_errors as $phpmailer_error) {
                $error_details[] = '<strong>' . __('PHPMailer Error:', 'snn') . '</strong> ' . esc_html($phpmailer_error);
            }
        }

        // If we have detailed errors, show them; otherwise show generic message
        if (!empty($error_details)) {
            $error_message = __('Failed to send test email. Details:', 'snn') . '<br/><br/>' . implode('<br/>', $error_details);
        } else {
            $error_message = __('Failed to send test email. No specific error details were captured. Check your SMTP settings or server error logs.', 'snn');
        }

        add_settings_error(
            'custom_smtp_test_email',
            'custom_smtp_test_email_failed',
            $error_message,
            'error'
        );
        error_log("SMTP TEST: wp_mail() FAILED.");
    }
}


function custom_smtp_settings_page() {
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
        <p><?php _e('Send a test email using the saved SMTP settings. The log below will show every step in real time.', 'snn'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="snn_test_email_addr"><?php _e('Recipient Email', 'snn'); ?></label></th>
                <td>
                    <input
                        type="email"
                        id="snn_test_email_addr"
                        value=""
                        placeholder="<?php echo esc_attr__('you@example.com', 'snn'); ?>"
                        size="40"
                    />
                </td>
            </tr>
        </table>
        <button id="snn_run_smtp_test" class="button button-primary"><?php _e('Send Test Email', 'snn'); ?></button>

        <div id="snn_smtp_log_wrap" style="display:none; margin-top:20px;">
            <div id="snn_smtp_log_box" style="
                position: relative;
                background: #1e1e1e;
                color: #d4d4d4;
                font-family: monospace;
                font-size: 12px;
                line-height: 1.7;
                padding: 14px 16px;
                border-radius: 6px;
                max-height: 400px;
                overflow-y: auto;
                border: 2px solid #444;
            ">
                <button id="snn_smtp_copy_btn" title="Copy log" style="
                    position: sticky;
                    float: right;
                    top: 0;
                    right: 0;
                    background: #3a3a3a;
                    color: #ccc;
                    border: 1px solid #555;
                    border-radius: 4px;
                    padding: 3px 10px;
                    font-size: 11px;
                    cursor: pointer;
                    z-index: 10;
                    margin-bottom: 8px;
                ">Copy</button>
                <div id="snn_smtp_log_entries"></div>
            </div>

            <div id="snn_smtp_settings_box" style="margin-top: 12px; padding: 12px 16px; border-radius: 6px; border: 2px solid #ccc; font-size: 13px;">
                <strong><?php _e('Settings used:', 'snn'); ?></strong>
                <table id="snn_smtp_settings_table" style="margin-top: 8px; border-collapse: collapse; width: auto;">
                </table>
            </div>
        </div>

        <script>
        var snnSmtpTest = {
            ajaxurl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
            nonce:   <?php echo wp_json_encode(wp_create_nonce('custom_smtp_ajax_test_nonce')); ?>
        };
        (function($) {
            var logData = [];

            function escHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function colorForType(type) {
                switch (type) {
                    case 'success': return '#4ec94e';
                    case 'error':   return '#f47878';
                    case 'warning': return '#f5c842';
                    default:        return '#8bbcf5';
                }
            }

            function prefixForType(type) {
                switch (type) {
                    case 'success': return '[OK]    ';
                    case 'error':   return '[ERROR] ';
                    case 'warning': return '[WARN]  ';
                    default:        return '[INFO]  ';
                }
            }

            function renderLog(logs) {
                logData = logs;
                var html = '';
                $.each(logs, function(i, entry) {
                    var color  = colorForType(entry.type);
                    var prefix = prefixForType(entry.type);
                    html += '<div style="color:' + color + '; white-space: pre-wrap; word-break: break-all;">'
                          + escHtml(prefix + entry.message)
                          + '</div>';
                });
                $('#snn_smtp_log_entries').html(html);
            }

            function renderSettings(settings, success) {
                var borderColor = success ? '#4ec94e' : '#f47878';
                var bgColor     = success ? '#f0fff0' : '#fff0f0';
                $('#snn_smtp_settings_box').css({
                    'border-color': borderColor,
                    'background':   bgColor
                });
                var rows = '';
                $.each(settings, function(key, val) {
                    rows += '<tr>'
                          + '<td style="padding: 2px 12px 2px 0; color: #555; font-weight: 600;">' + escHtml(key) + '</td>'
                          + '<td style="padding: 2px 0;">' + escHtml(val) + '</td>'
                          + '</tr>';
                });
                $('#snn_smtp_settings_table').html(rows);
            }

            function addLog(type, message) {
                var entry = {type: type, message: message};
                logData.push(entry);
                var color  = colorForType(type);
                var prefix = prefixForType(type);
                var div = $('<div>').css({
                    color: color,
                    'white-space': 'pre-wrap',
                    'word-break': 'break-all'
                }).text(prefix + message);
                $('#snn_smtp_log_entries').append(div);
                var box = document.getElementById('snn_smtp_log_box');
                box.scrollTop = box.scrollHeight;
            }

            $('#snn_run_smtp_test').on('click', function() {
                var $btn    = $(this);
                var toEmail = $('#snn_test_email_addr').val();

                $btn.prop('disabled', true).text('<?php echo esc_js(__('Sending...', 'snn')); ?>');
                logData = [];
                $('#snn_smtp_log_entries').empty();
                $('#snn_smtp_log_wrap').show();
                addLog('info', 'Initiating test...');

                $.post(snnSmtpTest.ajaxurl, {
                    action:     'custom_smtp_ajax_test',
                    nonce:      snnSmtpTest.nonce,
                    test_email: toEmail
                }, function(response) {
                    renderLog(response.logs);
                    renderSettings(response.settings, response.success);
                }).fail(function(xhr) {
                    addLog('error', 'AJAX request failed (' + xhr.status + ' ' + xhr.statusText + ')');
                    $('#snn_smtp_settings_box').css({'border-color': '#f47878', 'background': '#fff0f0'});
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Send Test Email', 'snn')); ?>');
                    var box = document.getElementById('snn_smtp_log_box');
                    if (box) box.scrollTop = box.scrollHeight;
                });
            });

            $('#snn_smtp_copy_btn').on('click', function() {
                var $btn = $(this);
                var text = logData.map(function(e) {
                    return prefixForType(e.type) + e.message;
                }).join('\n');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        $btn.text('Copied!');
                        setTimeout(function() { $btn.text('Copy'); }, 2000);
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity  = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    $btn.text('Copied!');
                    setTimeout(function() { $btn.text('Copy'); }, 2000);
                }
            });
        })(jQuery);
        </script>
    </div>
    <?php
}
