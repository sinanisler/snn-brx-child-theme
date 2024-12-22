<?php
// Ensure this code is added to your theme's functions.php file or a custom plugin.

// 1. Add SMTP Settings Submenu
add_action('admin_menu', 'custom_smtp_add_admin_menu');
function custom_smtp_add_admin_menu() {
    add_submenu_page(
        'snn-settings',              // Parent slug
        'SMTP Settings',             // Page title
        'SMTP Settings',             // Menu title
        'manage_options',            // Capability
        'smtp-settings',             // Menu slug
        'custom_smtp_settings_page'  // Callback function
    );
}

// 2. Register Settings
add_action('admin_init', 'custom_smtp_settings_init');
function custom_smtp_settings_init() {
    // Register a new setting for SMTP
    register_setting('custom_smtp_settings_group', 'custom_smtp_settings', 'custom_smtp_settings_sanitize');

    // Add a new section in the settings page
    add_settings_section(
        'custom_smtp_settings_section',
        __('SMTP Settings', 'textdomain'),
        'custom_smtp_settings_section_callback',
        'smtp-settings'
    );

    // Enable SMTP Checkbox
    add_settings_field(
        'enable_smtp',
        __('Enable SMTP', 'textdomain'),
        'custom_smtp_enable_smtp_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    // SMTP Host
    add_settings_field(
        'smtp_host',
        __('SMTP Host', 'textdomain'),
        'custom_smtp_smtp_host_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    // Encryption
    add_settings_field(
        'smtp_encryption',
        __('Encryption', 'textdomain'),
        'custom_smtp_smtp_encryption_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    // SMTP Port
    add_settings_field(
        'smtp_port',
        __('SMTP Port', 'textdomain'),
        'custom_smtp_smtp_port_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    // SMTP Username
    add_settings_field(
        'smtp_username',
        __('SMTP Username', 'textdomain'),
        'custom_smtp_smtp_username_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );

    // SMTP Password
    add_settings_field(
        'smtp_password',
        __('SMTP Password', 'textdomain'),
        'custom_smtp_smtp_password_render',
        'smtp-settings',
        'custom_smtp_settings_section'
    );


}

// 3. Sanitize and Validate Settings
function custom_smtp_settings_sanitize($input) {
    $sanitized = array();

    $sanitized['enable_smtp'] = isset($input['enable_smtp']) ? boolval($input['enable_smtp']) : false;
    $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host'] ?? '');
    $sanitized['smtp_encryption'] = sanitize_text_field($input['smtp_encryption'] ?? '');

    // Auto-set SMTP Port based on Encryption if not manually set
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

// 4. Section Callback
function custom_smtp_settings_section_callback() {
    echo '<p>' . __('After implementing, thoroughly test the SMTP settings to ensure that emails are sent correctly using the configured SMTP server ', 'textdomain') . '</p>';
    
}

// 5. Render Functions for Each Field

// Enable SMTP Checkbox
function custom_smtp_enable_smtp_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='checkbox' name='custom_smtp_settings[enable_smtp]' <?php checked(isset($options['enable_smtp']) ? $options['enable_smtp'] : false, true); ?> value='1'>
    <?php
}

// SMTP Host
function custom_smtp_smtp_host_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='text' name='custom_smtp_settings[smtp_host]' value='<?php echo esc_attr($options['smtp_host'] ?? ''); ?>' size='50'>
    <?php
}

// Encryption
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

// SMTP Port
function custom_smtp_smtp_port_render() {
    $options = get_option('custom_smtp_settings', array());
    $encryption = strtolower($options['smtp_encryption'] ?? 'none');
    $is_readonly = in_array($encryption, ['ssl', 'tls']) ? 'readonly' : '';
    ?>
    <input type='number' name='custom_smtp_settings[smtp_port]' value='<?php echo esc_attr($options['smtp_port'] ?? ''); ?>' size='10' <?php echo $is_readonly; ?>>
    <?php
}

// SMTP Username
function custom_smtp_smtp_username_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='text' name='custom_smtp_settings[smtp_username]' value='<?php echo esc_attr($options['smtp_username'] ?? ''); ?>' size='50'>
    <?php
}

// SMTP Password
function custom_smtp_smtp_password_render() {
    $options = get_option('custom_smtp_settings', array());
    ?>
    <input type='password' name='custom_smtp_settings[smtp_password]' value='<?php echo esc_attr($options['smtp_password'] ?? ''); ?>' size='50'>
    <?php
}



// 6. Handle AJAX Request to Remove Password
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

// 7. Settings Page HTML
function custom_smtp_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('SMTP Settings', 'textdomain'); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('custom_smtp_settings_group');
            do_settings_sections('smtp-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 8. Override wp_mail with SMTP Settings
add_action('phpmailer_init', 'custom_smtp_phpmailer_init');
function custom_smtp_phpmailer_init($phpmailer) {
    $options = get_option('custom_smtp_settings', array());

    if (isset($options['enable_smtp']) && $options['enable_smtp']) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $options['smtp_host'] ?? '';
        $phpmailer->SMTPAuth   = true; // Enable SMTP authentication
        $phpmailer->Port       = $options['smtp_port'] ?? 25;
        $phpmailer->Username   = $options['smtp_username'] ?? '';
        $phpmailer->Password   = $options['smtp_password'] ?? '';
        $phpmailer->SMTPSecure = (!empty($options['smtp_encryption']) && strtolower($options['smtp_encryption']) !== 'none') ? strtolower($options['smtp_encryption']) : '';
        $phpmailer->From       = $options['smtp_username'] ?? '';
        $phpmailer->FromName   = get_bloginfo('name');
    }
}

// 9. Enqueue jQuery for AJAX (if not already enqueued)
add_action('admin_enqueue_scripts', 'custom_smtp_enqueue_scripts');
function custom_smtp_enqueue_scripts($hook) {
    if ($hook !== 'snn-settings_page_smtp-settings') {
        return;
    }
    wp_enqueue_script('jquery');
}
